---
name: pm4-action-by-email
description: >-
  Configure PM4 Actions by Email: the call activity that emails action
  buttons, waits for the reply, and stores the chosen value in keyName; also the
  Form Task Actions by Email toggle. Use for options, keyName, recipients,
  reply handling. Adding the node to the graph uses pm4-process. Not one-way
  Send Email.
---

# Actions by Email

An **Actions by Email** node is a `bpmn:callActivity` that runs the seeded sub-process `package-actions-by-email/sub-process`. Config lives in **`pm:config`** (JSON) on the call activity. Package: `processmaker/package-actions-by-email` (requires `connector-send-email`).

The node **sends** an email with action buttons, **waits** for the recipient to reply, then writes the chosen option's `value` into the parent request variable named **`keyName`**.

This skill wires the call activity config. It does not configure Admin → Settings → Email (SMTP) or IMAP. Both must work before debugging a node ([verify.md](verify.md)).

References: [config-schema.md](config-schema.md), [form-task-abe.md](form-task-abe.md) (Form Task toggle: `pm:isActionsByEmail` / `pm:configEmail`), [verify.md](verify.md). Examples: [examples/](examples/).

User docs: [Actions by Email connector](https://docs.processmaker.com/docs/actions-by-email-connector). Where they disagree with these files (config keys, reply flow), trust these files: checked against `package-actions-by-email` source.

## Not this skill

| User wants | Use instead |
|------------|-------------|
| Form Task that emails its assignee an Actions by Email link (not a Call Activity) | [form-task-abe.md](form-task-abe.md) |
| One-way Send Email (no wait for reply) | [pm4-send-email](../pm4-send-email/SKILL.md) |
| EMAIL screen design (Rich Text, controls) | [pm4-screen](../pm4-screen/SKILL.md) |
| Call Activity graph wiring, publish | [pm4-process](../pm4-process/SKILL.md) |
| Mustache `{{var}}` only | [pm4-expressions](../pm4-expressions/SKILL.md) |
| `abe_stop_handler` / handler process signals | [pm4-signal](../pm4-signal/SKILL.md) |
| SMTP or IMAP server setup, queue worker | [setup-processmaker](../setup-processmaker/SKILL.md) |
| REST external API | [pm4-data-connector](../pm4-data-connector/SKILL.md) |

## Before changing anything

Ask only if missing:

1. **Process** — existing name or id.
2. **Recipient** — email address or `{{variable}}`.
3. **Subject** and **body** — plain text (`bodyType: "text"`) or EMAIL screen (`bodyType: "screen"` + `screenRef`).
4. **Options** — button label (`content`) and stored value (`value`) per action (e.g. Approve / `approved`).
5. **keyName** — request variable that receives the selected `value`.
6. **Email server** — default (`""`) or index from Settings.

Look up EMAIL screen ids and email server indices on this instance. **Never invent ids.**

## Required (every Actions by Email node)

1. **Element**: `bpmn:callActivity` with `calledElement` pointing at the ABE sub-process (`ProcessId-{id}` after Modeler save). Modeler sets this via `GET /api/1.0/actions-by-email/get-sub-process`; in an API-built BPMN, call that endpoint and use the returned id (`ProcessId-{id}`). Palette node id: `processmaker-send-actions-by-email`.
2. **`pm:config`**: must include **`options`** (non-empty) and **`keyName`** — these identify an ABE node at export/runtime.
3. **`email`**, **`subject`**, body (`body` or `screenRef`), and resolvable **`emailServer`**.
4. Every **`{{var}}`** in email, subject, and body resolves from request data when the node runs.
5. **Inbound IMAP** configured and the `handle-replies` handler process running ([verify.md](verify.md)).

## Workflow

```
- [ ] 1. Confirm package-actions-by-email installed (php artisan package-actions-by-email:install)
- [ ] 2. Outbound SMTP and inbound IMAP both pass their test in Settings
- [ ] 3. Look up EMAIL screen id (if bodyType screen) and email server index
- [ ] 4. Set pm:config: email, subject, bodyType, body/screenRef, options, keyName, emailServer
- [ ] 5. Confirm calledElement is set on the call activity
- [ ] 6. Save + Publish parent process
- [ ] 7. Static check (verify.md). Start a request and reply to the email only if the user asked
```

## Done when (static)

```
- [ ] callActivity with pm:config containing options[] and keyName
- [ ] calledElement references the ABE sub-process on this instance
- [ ] options has at least one entry with content and value
- [ ] keyName is a valid request variable name downstream steps will read
- [ ] subject and body set; screenRef is EMAIL type when bodyType is screen
- [ ] emailServer empty (default) or exists on this instance
```

After import on another instance, re-run install or verify sub-process id and screen refs.
