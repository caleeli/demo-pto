# Worked classifications

Inventory one row, classify, then either peel off or merge into [trigger-plan.md](trigger-plan.md). Rewrite intent — never paste the PM3 webbot.

## Assignment → peel-off (not in the plan)

| Field | Value |
|-------|--------|
| Hook | `BEFORE_ASSIGNMENT` |
| Signal | group/user assignment API |
| Destination | [pm4-form-manual-task](../pm4-form-manual-task/SKILL.md) |
| Plan JSON | none |

```php
<?php
@@NEXT_GROUP = 'managers';
```

Configure `pm:assignment` / groups on the Form Task after import. Do not emit a Script Task that only sets the assignee.

## Email → peel-off (not in the plan)

| Field | Value |
|-------|--------|
| Hook | After Screen (`AFTER_DYNAFORM`) |
| Signal | `PMFSendMessage` / mail |
| Destination | [pm4-send-email](../pm4-send-email/SKILL.md) |
| Plan JSON | none |

```php
<?php
PMFSendMessage(@@APPLICATION, 'admin', 'admin', '', '', 'Case update', 'Body');
```

Prefer a Send Email connector. Do not wrap the same send in a post-form Script Task.

## Calculation → pre-form script

| Field | Value |
|-------|--------|
| Hook | Before Screen (`step_uid=-1`, `BEFORE_DYNAFORM`) |
| Signal | case-variable math, no mail/assign/redirect |
| Destination | **pre-form** (`slot: before`) |

PM3 intent: `@@TOTAL = floatval(@@AMOUNT) * 1.15`. Rewritten PHP script (not a paste):

```php
<?php
// Before Screen Trigger: Compute total
$amount = (float) ($data['AMOUNT'] ?? 0);
return ['TOTAL' => $amount * 1.15];
```

## Empty → exclude

| Field | Value |
|-------|--------|
| Hook | any |
| Signal | no-op / empty body |
| Destination | exclude — document the title and hook |
| Plan JSON | none |

```php
<?php
```
