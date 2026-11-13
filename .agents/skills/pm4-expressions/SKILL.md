---
name: pm4-expressions
description: >-
  ProcessMaker 4 expressions and data binding. Use for Mustache in BPMN,
  FEEL in gateways, data.* paths, env variables, conditional visibility,
  assignment rules, or flow conditions.
---

# PM4 expressions

Load alongside any other `pm4-*` skill. Expressions connect request data to BPMN, screens, and assignments.

## Choose the field's evaluator and data scope

The request's JSON payload and an expression's variable context are different things. An API response may expose the payload under `data`; that does **not** add a `data` wrapper to every evaluator.

- **Gateway conditions:** the reviewed PHP engine passes request-store keys directly. For payload `{"amount":1500,"status":"approved"}`, use `amount > 1000` and `status == "approved"`.
- **Nested values:** use `applicant.email` and `items[0].amount` in this evaluator. Use `data.status` only when the actual input contains a top-level `data` object.
- **Conditional events:** Start evaluates global-store data; Intermediate/Boundary evaluates instance-store data. A Start condition cannot assume a request already exists.
- **Screens, scripts, assignment, notifications, and connectors:** use the field's owning skill to establish its context. Do not replace their `data.*` paths based on gateway examples.

Initialize every referenced root variable and nested structure before evaluation, with deliberate value types. Missing values are not universally converted to false.

## Gateway expressions (PM's FEEL-labelled evaluator)

In reviewed core `2026.16.1` with PHP Nayra `1.12.4`, `FormalExpression` uses Symfony ExpressionLanguage behind the `FEEL` label. Do not substitute generic DMN FEEL syntax or assume every engine/driver has the same contract. See [expression-contract.md](expression-contract.md) for evidence, supported examples, and edge cases.

```text
amount > 1000
status == "approved"
approved === true
amount >= 1000 and (status in ["approved", "review"]) and not blocked
items[0].amount > 1000
```

- `==` compares values; `===` also checks type. Single `=` is invalid here.
- Prefer a Boolean result. The string `"false"` is truthy; it is not Boolean `false`.
- Conditions belong on outgoing sequence flows of **Exclusive/Inclusive** Gateways. Their fallback is the gateway's `default` flow reference, not an always-true condition.
- Parallel/Event-Based Gateways do not use these conditions/defaults. Configure their graph with [pm4-process](../pm4-process/SKILL.md).

## Mustache: rendering versus expression evaluation

A template-only field returns rendered text. A `FormalExpression` field instead performs **Mustache rendering → expression evaluation**; these are not two interchangeable selectable languages.

With root input `{"amount":1500}`, `{{amount}} > 1000` evaluates to true, but prefer `amount > 1000` for a gateway. Direct references preserve types and avoid interpolation/quoting mistakes. A template field may use `{{data.amount}}` only if that field supplies such a wrapper.

**Never use a bare `{{approved}}` as a Boolean gateway condition.** Boolean false or a missing Mustache key can render an empty body, and the reviewed formal-expression evaluator returns **true** for empty/whitespace-only bodies. Use initialized input and `approved === true`. Empty conditional-event bodies are handled separately as false; do not generalize that exception to gateways.

## Environment variables

Reference secrets and environment-specific values through the owning asset's supported mechanism, rather than hardcoding them. Template-enabled fields that expose `env` may use:

```
{{env.API_KEY}}
{{env.WEBHOOK_URL}}
```

Define variables in Designer (skill `pm4-environment-variable` planned). Scripts use their runtime environment API (`getenv()` / `process.env` / `os.getenv()`). Core's formal-expression evaluator registers `env("NAME")`; it performs a platform lookup and is not the same as an automatically available `env` template object. Confirm field support and access before using either form; do not print secret values to verify configuration.

## Where each type applies

| Context | Expression type |
|---------|-----------------|
| Exclusive/Inclusive sequence flow condition | Request-root values; Mustache rendering then PM's FEEL-labelled evaluator; prefer direct references |
| Conditional Start Event | Global-store values; condition expression with event-specific edge detection |
| Intermediate/Boundary Conditional Event | Instance-store values; condition expression with event-specific edge detection |
| Timer expression | Timer-specific parsing after Mustache; not a Boolean gateway condition |
| Task assignment (expression) | Field-specific context and result contract; see [pm4-form-manual-task](../pm4-form-manual-task/SKILL.md) |
| Screen conditional visibility | JavaScript-like rules on `data` in screen config |
| Script task | Full language in script; reads/writes `data` object |
| Computed properties | JavaScript in screen `computed` array |

## Validation checklist

1. Identify the evaluator, engine/driver, and exact input shape; distinguish root payload keys from wrapper objects.
2. Initialize referenced variables, nested objects, and array positions before use; test missing/null/false/empty-string inputs separately.
3. For Exclusive/Inclusive decisions, define conditions and an intentional fallback. Check Exclusive overlaps and Inclusive multiple-match behavior.
4. Check positive, negative, and type-mismatched inputs using the target evaluator; parsing alone does not prove workflow routing.
5. Keep template-only text, timer expressions, screen JavaScript, and gateway conditions distinct. Use the asset's supported environment-variable mechanism.

## Common mistakes

- Copying `data.status = "approved"` from generic FEEL guidance into a root-context PM gateway.
- Assuming missing direct references silently become empty: they can raise evaluation errors.
- Trying to guard an undeclared root with `and`: parsing can reject the variable before short-circuit evaluation.
- Treating `"false"`, false, null, and `""` as equivalent, or using bare Mustache for a Boolean branch.
- Assuming a blank condition disables a sequence flow, or reading an array position before it exists.
