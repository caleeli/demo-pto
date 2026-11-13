---
name: pm3-expressions
description: >-
  ProcessMaker 3 expressions and variable references. Use for @@var syntax,
  classic map conditions, routing expressions, output document placeholders,
  email template variables, or trigger variable scope.
---

# PM3 expressions

Load alongside any other `pm3-*` skill. Expressions connect case variables to routing, templates, and triggers.

## Variable reference syntax

- Case/process variables use `@@` prefix by default: `@@APPLICANT_NAME`, `@@TOTAL`
- In PHP triggers: use `@@VAR` syntax; `PMScript` translates to `$this->aFields[...]` at runtime
- In output documents and email templates: embedded as `@@VARIABLE_NAME`
- In classic map and BPMN gateway conditions: compared with operators on typed prefixes
- Type prefixes (same as [pm3-trigger](../pm3-trigger/SKILL.md)): `@@` string, `@%` integer, `@#` float, `@?` URL-encoded, `@$` SQL-escaped

## Where expressions apply

| Context | Syntax | Notes |
|---------|--------|-------|
| Classic map transition condition | `@@VAR = 'value'` | PHP-like comparisons |
| BPMN gateway / flow condition | `@@VAR` / `@#VAR` expressions | Gateway Properties routing rules; see [gateways.md](../pm3-process/gateways.md) |
| Output document template | `@@FIELD` | Replaced at generation time |
| Email template / notification | `@@FIELD` | Same placeholder rules |
| Trigger (before save) | `@@FIELD` assignment and reads | PHP execution context via `PMScript` |
| Dynaform default value | `@@OTHER_VAR` | Must exist before step runs |
| SQL suggest / external query | `@@VAR` in query string | Escaping matters |

## Type coercion

PM3 coerces types loosely in conditions:

- Empty string `''` may differ from unset variable
- `'0'` and `0` behave differently in strict comparisons
- Boolean-like values often arrive as strings from dynaforms

Always test edge cases: null, empty, numeric zero, unchecked checkbox.

## Classic map conditions

Classic processes define conditions on transitions between tasks:

```
@@APPROVED == 'YES'
@@AMOUNT > 1000
@@STATUS != 'rejected'
```

Ensure every outgoing path from a decision point has a condition or a default route.

## BPMN conditions

BPMN Designer uses case variables in gateway **Properties** routing rules (Exclusive/Inclusive). Variable names match process variables without inventing new prefixes. Numeric compares often use `@#amount`; strings use `@@VAR == 'value'`. Rule order, default flow, and Parallel (no conditions): [pm3-process gateways](../pm3-process/gateways.md).

## Validation checklist

1. Every `@@VAR` referenced is defined in process variables or written by an earlier step
2. Classic conditions cover all branches
3. Output/email placeholders match exact variable names (case-sensitive)
4. Trigger expressions do not assume variables from future steps

## Links to other PM3 assets

| Asset | Relationship |
|-------|--------------|
| `pm3-variables` | Defines typed variables behind `@@` names |
| `pm3-dynaform` | Field names become case variables |
| `pm3-trigger` | Reads/writes variables in PHP |
| `pm3-output-document` | Embeds `@@` in templates |
| `pm3-email-template` | Embeds `@@` in mail bodies |
| `pm3-process` / `pm3-classic-process` | Attaches conditions to routing |

## Common mistakes

- Typo in variable name → silent empty replacement
- Using `@@var` in a trigger before the dynaform step assigns it
- Comparing checkbox value without knowing stored format (`'1'` vs `'on'`)
- SQL injection via unescaped `@@` in dynamic queries
- Mixing classic condition syntax with BPMN gateway config
