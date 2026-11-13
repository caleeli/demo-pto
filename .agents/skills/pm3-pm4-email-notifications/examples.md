# Worked classifications

These rows are sanitized from a deal-closing migration pattern. Names, UIDs, recipients, and content are synthetic. The private source export is never a repository fixture.

## Next assignee only

| Source | Value |
|--------|-------|
| Trigger hook | `BEFORE_ROUTING` on **Prepare Request** |
| Helper | `sendNextUserNotification` |
| Destination task | **Review Request** |
| PM4 action | `task_notification`, `sendAt: task-start`, To `assignedUser` |

The notification belongs on the destination task. Verify same-user skip behavior on the target instance.

## Event email on completion

| Source | Value |
|--------|-------|
| Trigger hook | `BEFORE_ROUTING` on **Review Request** |
| Event | `REVIEW_COMPLETE` |
| Recipient | PM3 user UID variable `@@REVIEWER_UID` |
| PM4 action | Source task `task-end`; `userId: {{REVIEWER_UID}}` |
| Content | `external_template_table` until subject/body rows are supplied |

The HTML wrapper becomes one shared EMAIL screen; the per-event body remains `textBody` data rendered through the wrapper.

## Event plus next assignee

One PM3 switch case may produce two actions:

1. Event email on **Approve Request** → task-end. A hard-coded PM3 group is `unresolved_group`, so this action stays HITL.
2. Next-assignee email on **Conditional Review** → task-start assigned user. This action is safe and can still be generated.

Do not collapse a mixed item to all-or-nothing when action-level classifications differ.

## Conditional mail with unsafe behavior

Condition: `@@SEND_CONDITIONAL == 'YES'`. The same case reads a static address table, de-duplicates Cc recipients, and attaches a case document. Keep the condition in the plan, but classify the send HITL with:

```text
external_template_table
static_address_table
dedup_cc_list
attachment_from_case_document
```

Rebuild only after the address source, de-duplication rule, and document contract are known.

## Native task setting

`TAS_SEND_LAST_EMAIL=TRUE`, default subject/body, and `notificationEnabled=true` map to a task-start `assignedUser` notification. Convert `@@REQUEST_NAME` and simple `@#REQUEST_SUMMARY` tokens to Mustache, then verify rendering.

## Email Event

A safe native Email Event becomes a Send Email service task at the event position. A From override or custom PM3 email server makes it HITL until target-instance behavior is selected.

## Actions by Email

A non-empty ABE row with a template and response field is classified `abe`, not `task_notification`. Empty ABE rows are counted but omitted from plan items; system templates are excluded. Use [pm4-action-by-email](../pm4-action-by-email/SKILL.md).

## Full-pattern local check

The private source pattern contained 38 trigger attachments and 37 switch cases: 16 next-assignee only, 8 combined, 12 event only, and 1 empty. It also contained two mail Script Tasks, one real ABE row among many empty rows, and shared wrappers. Those counts are verified only through the opt-in local golden test; no customer export or content is committed.
