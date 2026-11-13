# Form Task Actions by Email (toggle)

Separate from the **Actions by Email call activity** in [SKILL.md](SKILL.md). Here the **Form Task itself** emails its assignee a link to complete the task from the email. No sub-process, no `keyName`, no `options[]`.

Package: `processmaker/package-actions-by-email` (the inspector panel is contributed through `connector-send-email`, **Notifications** section of the Form Task).

## BPMN attributes

On the `bpmn:task` (Form Task):

| Attribute | Value |
|-----------|-------|
| `pm:isActionsByEmail` | `"true"` (string). Core compares with `=== 'true'`; any other value disables it |
| `pm:configEmail` | JSON string, see below |

```json
{
  "emailServer": "",
  "subject": "RE: Review request",
  "screenEmailRef": "",
  "requireLogin": true,
  "screenCompleteRef": ""
}
```

| Key | Notes |
|-----|-------|
| `emailServer` | `""` = default outbound server; otherwise an email server index (see [pm4-send-email lookup](../pm4-send-email/lookup.md)) |
| `subject` | Mustache allowed. Designer default: `RE: <task name>` |
| `screenEmailRef` | EMAIL screen id for the message body. Empty uses the default template |
| `requireLogin` | Designer default `true`. Stored on the ABE token as `require_login` |
| `screenCompleteRef` | Optional screen id shown after the task is completed from the email (`completed_screen_id`) |

Example node:

```xml
<bpmn:task id="node_2" name="Review request" pm:screenRef="12"
  pm:isActionsByEmail="true"
  pm:configEmail="{&#34;emailServer&#34;:&#34;&#34;,&#34;subject&#34;:&#34;RE: Review request&#34;,&#34;screenEmailRef&#34;:&#34;&#34;,&#34;requireLogin&#34;:true,&#34;screenCompleteRef&#34;:&#34;&#34;}" />
```

Keep the Form Task's own attributes ([pm4-form-manual-task](../pm4-form-manual-task/SKILL.md)); this skill only adds these two.

## Recipient

There is no `toRecipients`. At activation the email goes to the **task assignee's email**. For a self-service task with no single assignee, Core collects the emails of the users and groups in `self_service_groups`. If no address resolves, nothing is sent and no error surfaces to the user (failures are only logged).

## Rules

1. Do not confuse with the **notification** settings (`process.task_notifications`, `pm:config.email_notifications`): those are plain messages. See [pm4-form-manual-task](../pm4-form-manual-task/SKILL.md) and [pm4-send-email notifications](../pm4-send-email/notifications.md).
2. The Designer exposes this toggle on Form Tasks (`bpmn:task`) only. Do not add it to other node types.
3. Never synthesize the toggle on an instance without `package-actions-by-email`: the attribute is inert there.
4. Do not invent `screenEmailRef` / `screenCompleteRef` ids; look them up ([pm4-screen](../pm4-screen/SKILL.md)).
5. If the user wants the process to **branch on the reply** (Approve / Reject), use the call activity in [SKILL.md](SKILL.md), not this toggle.

## Check

```
- [ ] bpmn:task with pm:isActionsByEmail="true" and valid JSON in pm:configEmail
- [ ] The task has an assignee (user, group, or self-service) with a resolvable email
- [ ] Outbound email server works (Test Email); queue worker running
- [ ] screenEmailRef / screenCompleteRef exist on this instance, or are empty
```
