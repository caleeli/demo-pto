# Custom task email notifications

This is the connector-backed email configuration stored on a human task at:

```text
pm:config.email_notifications.notifications[]
```

It is distinct from both:

- a standalone Send Email `serviceTask`; and
- core process `task_notifications` recipient/event metadata owned by [pm4-form-manual-task](../pm4-form-manual-task/SKILL.md).

## Entry shape

```json
{
  "sendAt": "task-start",
  "expression": "",
  "subject": "Request assigned: {{request_name}}",
  "type": "screen",
  "textBody": "Review {{request_name}}",
  "screenRef": 42,
  "toRecipients": [{ "type": "assignedUser", "value": null }],
  "ccRecipients": [],
  "bccRecipients": []
}
```

| Key | Values / behavior |
|-----|-------------------|
| `sendAt` | `task-start` or `task-end`; runtime ignores entries for the other event |
| `expression` | Optional condition evaluated with request/task data; false skips the email |
| `subject` | Required; Mustache is rendered at runtime |
| `type` | `text` or `screen` |
| `textBody` | Plain-text content, and notification body data available to a wrapper screen |
| `screenRef` | Published EMAIL screen id when `type: screen` |
| `toRecipients` | Required recipient list; task notifications may use `assignedUser` |
| `ccRecipients`, `bccRecipients` | Optional recipient lists |

Recipient types otherwise follow [recipients.md](recipients.md). `assignedUser` resolves from the active task token and is only appropriate for task notifications.

## Assigned-user same-user skip

When To contains only `assignedUser`, runtime normally skips delivery if the authenticated current user is also the task assignee. Instance config `app.notifications_send_to_same_user=true` disables that skip. A timer/system event with no authenticated user is not treated as the same user. Verify the target instance setting before declaring parity with PM3 next-assignee mail.

## EMAIL screen import/export

A referenced notification screen is a process dependent with:

```json
{
  "type": "notification_screen",
  "uuid": "<screen export uuid>",
  "meta": {
    "path": "/bpmn:definitions/bpmn:process/bpmn:task[2]",
    "index": 0
  }
}
```

- `meta.path` identifies the human task DOM path.
- `meta.index` identifies the entry in `notifications[]`.
- The importer rewrites that entry's `screenRef` to the imported screen id.
- Do not substitute standalone Send Email's dependent type `screens`; that shape has a path but no notification index.

## Verification

- [ ] `pm:config` parses as JSON and preserves unrelated task config
- [ ] `sendAt` matches the intended assignment/completion event
- [ ] expression uses data available at that event
- [ ] recipients resolve on the target instance
- [ ] every screen notification has one `notification_screen` dependent with matching path/index
- [ ] EMAIL screen is published and `screenRef` resolves after import
- [ ] outbound server and queue worker are running
- [ ] same-user behavior matches target instance configuration

## Form Task Actions by Email

The same Notifications panel hosts the Form Task **Actions by Email** toggle (`pm:isActionsByEmail` + `pm:configEmail`). Recipients come from the task assignment, not `toRecipients`. Contract: [pm4-action-by-email form-task-abe.md](../pm4-action-by-email/form-task-abe.md).
