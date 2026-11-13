---
name: pm3-email-template
description: >-
  ProcessMaker 3 email templates and messages. Use for mailTemplates files,
  task notifications, BPMN email events, or @@ variable placeholders in email
  bodies.
---

# PM3 Email Template

Email templates define notification content for tasks, trigger-driven mail, and BPMN email events. Inventory the sender as well as the template: a file in `mailTemplates/` does not prove that PM3 sends it through native task settings.

## Asset types

| Type | Storage | Usage |
|------|---------|-------|
| Process templates | `mailTemplates/` folder in process files | Task notifications |
| Messages (classic) | Process messages building block | Classic map notifications |
| BPMN email event | `EmailEvent` API | Event-driven email on canvas |

Exported as `TEMPLATES` in `.pmx` (file-based templates).

Related export data can also appear in `TASKS`, `TASKEXTRAPROPERTIES`, `EMAILEVENT`, `ABECONFIGURATION`, `FILESMANAGER`, `TRIGGERS`, and `STEPTRIGGERS`. Do not count the two Actions by Email system defaults (`actionsByEmail.html`, `actionsByEmailErrorReply.html`) as user notification templates.

## Template content

Bodies use `@@` placeholders for case variables:

```html
<p>Hello @@USR_USERNAME,</p>
<p>Case #@@APP_NUMBER</p>
<p>Process: @@PROCESS</p>
<p>Task: @@TASK</p>
```

PM3 type prefixes can also appear in subjects, bodies, and wrapper templates:

| Prefix | PM3 meaning | Migration note |
|--------|-------------|----------------|
| `@@` | String | Convert a simple variable token to PM4 Mustache `{{VAR}}` |
| `@%` | Integer | Type distinction is not preserved by Mustache; verify formatting |
| `@#` | Float; commonly used as an unquoted/raw wrapper token | `pm-migrate` converts a simple token to `{{VAR}}`; verify HTML and escaping |
| `@?` | URL-encoded | Rebuild encoding explicitly when PM4 output must remain URL-safe |
| `@$` | SQL-escaped | Never carry SQL escaping into an email template; redesign the data source |

See [pm3-expressions](../pm3-expressions/SKILL.md) and [pm3-trigger](../pm3-trigger/SKILL.md). A token inside PHP, a grid token, or a compound expression is not a safe text substitution.

System variables available in templates:

| Variable | Content |
|----------|---------|
| `@@APPLICATION` | Case UID |
| `@@APP_NUMBER` | Case number |
| `@@PROCESS` | Process UID |
| `@@TASK` | Task UID |
| `@@INDEX` | Delegation index |
| `@@USER_LOGGED` | Current user UID |
| `@@USR_USERNAME` | Current username |
| `@@PIN` | Case PIN (when set) |
| `@@SYS_LANG`, `@@SYS_SKIN`, `@@SYS_SYS` | Workspace/locale constants |

## Task notifications

Configured per task in process:

- On assignment
- On overdue
- Reminder intervals

Each references a template from `mailTemplates/` or inline content.

Native task notification fields found in exports include:

| PM3 field | Meaning |
|-----------|---------|
| `TAS_SEND_LAST_EMAIL` | Enable the native assignment/next-task notification path |
| `TAS_DEF_SUBJECT_MESSAGE` | Default notification subject |
| `TAS_DEF_MESSAGE` | Default inline body |
| `TAS_EMAIL_SERVER_UID` | Optional PM3 email server override |
| `TASKEXTRAPROPERTIES.CFG_VALUE` | JSON such as `notificationEnabled` and the selected server |

Read the actual values; many processes leave these disabled and send all mail from triggers instead.

## Trigger-driven sending

Mail may be sent by `PMFSendMessage`, `G::sendMail`, plugin methods such as `sendNotification` / `sendNextUserNotification`, or a BPMN Script Task. Join `TRIGGERS` to every `STEPTRIGGERS` attachment and preserve its condition and hook:

| `STEP_UID` / type | Hook | Timing |
|-------------------|------|--------|
| `-1` / `BEFORE` | `BEFORE_ASSIGNMENT` | Before assignment |
| `-2` / `BEFORE` | `BEFORE_ROUTING` | After task submit, before derivation |
| `-3` / `AFTER` | `AFTER_ROUTING` | After derivation |

Static analysis may identify recipients, event names, and template files, but must not execute or broadly translate PHP. For migration use [pm3-pm4-email-notifications](../pm3-pm4-email-notifications/SKILL.md); Actions by Email stays with [pm3-actions-by-email](../pm3-actions-by-email/SKILL.md).

## Rules

- Email server must be configured in Setup → Email Server
- Templates support HTML and plain text depending on configuration
- **Messages** panel available in classic map (`processmap.js`); commented out in BPMN Designer menu on 3.9.11
- BPMN `EmailEvent` sends on workflow event, not task assignment

## Links to other PM3 assets

| Asset | Relationship |
|-------|--------------|
| `pm3-expressions` | `@@` placeholder syntax |
| `pm3-variables` | Custom placeholders |
| `pm3-process` | Task notifications; canvas email events: [events.md](../pm3-process/events.md) |
| `pm3-actions-by-email` | Enterprise reply-to-approve flows |

## Validation checklist

1. Email server configured and tested
2. All `@@` placeholders resolve in test case
3. HTML renders correctly in target email clients
4. Notification triggers on correct task event
5. Trigger attachments, conditions, and special hook ids are inventoried
6. Native settings, Email Events, trigger mail, and Actions by Email are not conflated

## Common mistakes

- Missing email server configuration
- Placeholder references variable not yet set at notification time
- HTML template without proper encoding
- Confusing task notification with Actions by Email plugin
- Assuming `TAS_SEND_LAST_EMAIL=FALSE` means the process sends no email
- Migrating a trigger template without its recipient, condition, hook, or external content source
