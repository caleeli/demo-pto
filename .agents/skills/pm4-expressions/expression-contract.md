# PM4 expression contract and edge cases

Use with [SKILL.md](SKILL.md). Scope: reviewed core declaring `2026.16.1`, PHP Nayra `1.12.4`, Symfony ExpressionLanguage `7.4.8`, PHP `8.5.11`. Other releases, broker engines, package-provided functions, and field-specific contexts need their own verification. This is not a DMN FEEL reference.

## Evaluation sequence and variable shape

1. The caller provides an array: gateway transitions use the instance data store directly, without adding a `data` wrapper.
2. `MustacheExpressionEvaluator` renders the expression against that array.
3. `FormalExpression` returns Boolean true when the rendered body is blank; otherwise it evaluates the body using its FEEL-labelled Symfony evaluator.
4. Before evaluation, core JSON-encodes/decodes the input and casts the outer value back to an array. Nested JSON objects are accessed with dots; indexed arrays use brackets.

Given request payload:

```json
{
  "amount": 1500,
  "status": "approved",
  "approved": true,
  "blocked": false,
  "applicant": { "email": "a@example.test" },
  "items": [{ "amount": 1500 }]
}
```

These expressions evaluate to true:

```text
amount > 1000
status == "approved"
approved === true
applicant.email == "a@example.test"
items[0].amount > 1000
amount >= 1000 and (status in ["approved", "review"]) and not blocked
```

`data.status == "approved"` requires a different input shape: `{"data":{"status":"approved"}}`. Do not add a nested payload object merely to satisfy a copied expression.

## Operators and types

Existing core tests cover `+`, `-`, `*`, `/`, comparisons `==`, `!=`, `<`, `>`, `<=`, `>=`, logical `and`, `or`, `not`, membership `in` / `not in`, ternary `condition ? a : b`, string concatenation `~`, and regex `matches`. Strict equality `===` was additionally checked directly against the installed evaluator. Parenthesize combined conditions to make precedence explicit.

| Expression | Input | Observed result |
|---|---|---|
| `approved === true` | `approved: true` | true |
| `approved === true` | `approved: "true"` | false |
| `approved ? true : false` | `approved: "false"` | true: a nonempty string is truthy |
| `approved === null` | `approved: null` | true |
| `approved === ""` | `approved: ""` | true |
| `amount == 1000` | `amount: "1000"` | true: value comparison coerces the numeric string |
| `amount === 1000` | `amount: "1000"` | false: string is not integer |
| `status = "approved"` | `status: "approved"` | error: single `=` is not equality syntax |

The input column uses shorthand for JSON properties. Preserve actual types in the request. A gateway condition should deliberately return a Boolean rather than relying on coercion of arbitrary strings or numbers.

## Missing, empty, and invalid inputs

| Case | Observed behavior / instruction |
|---|---|
| `missing == true` with no `missing` key | Error. Initialize the variable before routing. |
| `not empty("approved") and approved === true` with no `approved` key | Error: parser validation of the undeclared name occurs before the short-circuit guard can help. |
| `empty("approved")` with `approved: false` | true. PM's helper uses PHP `empty`; it is not a missing-key-only check. |
| `data.status == "approved"` with only root `status` | Error. The wrapper was not supplied. |
| Blank formal-expression body | true; do not use blank conditions to disable a branch. |
| Bare `{{missing}}` with no key | Renders blank, then evaluates true. |
| Bare `{{approved}}` with Boolean false | Renders blank, then evaluates true. |
| `amount >` | Error. A default flow is not a substitute for repairing invalid expressions. |

For nested fields and array positions, establish their presence and type upstream rather than assuming a guard works in every parser version. Missing, null, empty string, empty array, and Boolean false are different test cases.

## Mustache and field-specific behavior

`{{amount}} > 1000` with `amount: 1500` renders `1500 > 1000` and evaluates true. Direct `amount > 1000` is preferable. Interpolated strings introduce escaping/quoting behavior; substituting arbitrary text into an expression is not equivalent to passing a typed variable.

Mustache-only fields return rendered text and do not necessarily execute `FormalExpression`. Screen visibility/computed properties use their screen-specific evaluator. Assignment fields have their own result contract. Use the owning skill for each field.

Timers use `TimerExpression`, which renders Mustache and then parses timer values. Conditional event definitions explicitly treat an empty body as false and use false/unknown-to-true edge detection. Conditional Starts evaluate global-store data; Intermediate/Boundary conditionals evaluate existing instance-store data. None of these exceptions changes the gateway's blank-expression behavior.

Core registers additional functions such as `empty("name")`, `count(items)`, and `env("NAME")`. Do not assume all functions are pure: environment/model lookups and task-reassignment helpers can use platform services or modify state. The verification below invoked only in-memory comparisons and `empty`, without bootstrapping Laravel.

## Evidence and verification limits

Source pointers relative to the reviewed core checkout:

- `ProcessMaker/Models/FormalExpression.php`: `registerPMFunctions`, `feelEncode`, `__invoke`, `evaluate`.
- `ProcessMaker/Models/MustacheExpressionEvaluator.php`: `render`.
- `vendor/processmaker/nayra/src/ProcessMaker/Nayra/Bpmn/ConditionedExclusiveTransition.php`: `assertCondition` passes store data to the expression.
- `vendor/processmaker/nayra/src/ProcessMaker/Nayra/Bpmn/Models/ConditionalEventDefinition.php`: event-specific blank/edge handling.
- `tests/Model/FormalExpressionTest.php`, `tests/Feature/FEELTest.php`, `tests/Feature/MustacheFEELTest.php`: existing syntax/data assertions inspected, not run as suites here.
- [Symfony 7.4 expression syntax](https://symfony.com/doc/7.4/reference/formats/expression_language.html): upstream syntax reference; PM's wrapper and input conversion still apply.

On **2026-10-19**, direct in-memory probes exercised the installed `ProcessMaker\Models\FormalExpression` using Composer autoload in the local core container. They checked root/nested/array/wrapped input, equality/types, missing inputs, Boolean operators, blank/Mustache behavior, and invalid syntax. These checks establish evaluator behavior for those inputs only: they do not verify Modeler serialization, gateway token routing, database-backed functions, alternate engine drivers, or complete process execution.
