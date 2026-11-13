---
name: pm4-send-email
description: >-
  Configure an existing PM4 Send Email task: recipients, subject, text/EMAIL
  screen body, attachments, email server, and pm:config. Use for connector
  settings or send/render failures. Adding, placing, or connecting the node in
  the process graph uses pm4-process. Not Actions by Email, SMTP admin, or FORM
  screen design.
---

# Send Email connector

A **Send Email** node is a `bpmn:serviceTask` with `implementation="connector-send-email/processmaker-communication-email-send"`. Config lives in **`pm:config`** (JSON). Package: `processmaker/connector-send-email`.

This skill wires the connector and validates config. It does not configure Admin → Settings → Email (SMTP servers). An outbound server must already exist and pass **Test Email** before debugging a process node.

References: [config-schema.md](config-schema.md) (`pm:config` keys), [notifications.md](notifications.md) (custom task email notifications), [bpmn-attrs.md](bpmn-attrs.md) (BPMN element), [recipients.md](recipients.md) (To/Cc/Bcc types), [lookup.md](lookup.md) (email servers, screens, users), [asset-map.md](asset-map.md) (code paths), [verify.md](verify.md) (checks, failures). Examples: [examples/](examples/).

User docs: [Send Email connector](https://docs.processmaker.com/docs/send-email-connector). Where they disagree with these files (exact config keys, recipient shape), trust these files: they were checked against `connector-send-email` source.

## Not this skill

| User wants | Use instead |
|------------|-------------|
| EMAIL screen design (Rich Text, controls) | [pm4-screen](../pm4-screen/SKILL.md) |
| Core Form/Manual Task event notifications (`process.task_notifications`) | [pm4-form-manual-task](../pm4-form-manual-task/SKILL.md) |
| Actions by Email (wait for email reply) or the Form Task Actions by Email toggle | [pm4-action-by-email](../pm4-action-by-email/SKILL.md) |
| REST / external API call | [pm4-data-connector](../pm4-data-connector/SKILL.md) |
| Process graph (Start, gateways, publish) | [pm4-process](../pm4-process/SKILL.md) |
| Admin → Settings → Email (create SMTP server) | Platform admin |
| Mustache / `{{var}}` syntax only | [pm4-expressions](../pm4-expressions/SKILL.md) |
| Queue worker down, mail never dispatches | [setup-processmaker](../setup-processmaker/SKILL.md) |

## Before changing anything

Ask only if missing:

1. **Process** — existing name or id.
2. **Recipients** — To (required), Cc/Bcc if needed; fixed email, user/group, or request variable.
3. **Body** — plain text (`type: "text"`) or EMAIL screen (`type: "screen"` + `screenRef`).
4. **Subject** — static or Mustache `{{var}}`.
5. **Email server** — default (empty/`0`) or a named server index from Settings.
6. **Attachments** — file upload request variable, URL, or array of file ids (optional).

Then look up ids ([lookup.md](lookup.md)). **Never invent screen, user, group, or email-server indices.**

## Required (every Send Email node)

1. **Element**: `bpmn:serviceTask`, `implementation="connector-send-email/processmaker-communication-email-send"` — exact string.
2. **`pm:config`**: at least one **To** recipient, **subject**, **body** (`textBody` or `screenRef`), and resolvable **email server**.
3. **Every `{{var}}`** in subject, body, recipients, `screenRef`, and attachments must exist in request data when the node runs.
4. **`type: "screen"`**: `screenRef` must be a published **EMAIL** screen id on this instance ([pm4-screen](../pm4-screen/SKILL.md)).

Legacy `pm:config` keys (`users`, `groups`, `addEmails`) still run but prefer `toRecipients` / `ccRecipients` / `bccRecipients`. See [recipients.md](recipients.md).

Custom task emails stored under `pm:config.email_notifications.notifications` reuse much of this connector's recipient/body contract but are not standalone service tasks and are not the core `process.task_notifications` map. See [notifications.md](notifications.md). For PM3 migration classification use [pm3-pm4-email-notifications](../pm3-pm4-email-notifications/SKILL.md).

## Workflow

```
- [ ] 1. Confirm connector-send-email is installed (Send Email object in Modeler)
- [ ] 2. Confirm outbound email server works (Admin test email)
- [ ] 3. Look up email server index, screen id, user/group ids if needed
- [ ] 4. Create or pick EMAIL screen when body type is screen
- [ ] 5. Set pm:config: recipients, subject, type, textBody or screenRef, emailServer, attachments
- [ ] 6. Save + Publish process (drafts are not live)
- [ ] 7. Static check (verify.md). Start a request only if the user asked
```

## Done when (static)

```
- [ ] serviceTask with implementation="connector-send-email/processmaker-communication-email-send"
- [ ] Name is action-oriented, not the default "Send Email"
- [ ] toRecipients has at least one valid recipient
- [ ] subject non-empty; textBody or screenRef set per type
- [ ] screenRef is a real EMAIL screen when type is screen
- [ ] emailServer empty/0 (default) or a server that exists on this instance
- [ ] Attachments reference existing request variables or URLs
```

Process export includes referenced EMAIL screens. Standalone Send Email screen dependents use type `screens`; custom task-email screens use `notification_screen` with `meta.path` and `meta.index`. After import on another instance, verify screen ids and email server indices.

Wire steps: [create-edit.md](create-edit.md).
