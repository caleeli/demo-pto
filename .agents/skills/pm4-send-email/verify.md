# Check a Send Email node

Static checklist. Start a request only if the user asked.

## Prerequisites

```
- [ ] connector-send-email installed (Send Email in Modeler Object panel)
- [ ] Outbound email server configured and Test Email succeeds
- [ ] Queue worker running if mail is queued (local: see setup-processmaker)
```

## Node checklist

See [bpmn-attrs.md](bpmn-attrs.md), [config-schema.md](config-schema.md).

```
- [ ] bpmn:serviceTask with implementation="connector-send-email/processmaker-communication-email-send" (exact)
- [ ] Name is action-oriented, not the default "Send Email"
- [ ] toRecipients has at least one recipient that resolves at runtime
- [ ] subject non-empty
- [ ] type=text → textBody non-empty; type=screen → screenRef set
- [ ] screenRef points to published EMAIL screen when type is screen
- [ ] emailServer is "" / 0 (default) or an index that exists (GET plugins/email/servers)
- [ ] Every {{var}} in subject, body, recipients, screenRef, attachments exists upstream
- [ ] Attachments: file variables populated before this node (File Upload on prior Form Task)
```

Designer: Save, then Publish. Drafts are not live.

## Failures

| Symptom | Likely cause |
|---------|--------------|
| No Send Email in Modeler | `connector-send-email` not installed |
| Token fails immediately | No recipients resolve to an address |
| Email never arrives | SMTP misconfigured; queue worker stopped; check Laravel logs |
| Empty body | `textBody` blank; or `screenRef` missing/wrong type |
| `Screen::findOrFail` error at send time | `screenRef` id missing or not EMAIL — fails at **send**, not save |
| Wrong recipient | Typo in `{{var}}`; user/group id from wrong environment |
| Broken link button in EMAIL body | Actions by Email vars missing (`abe_uri`, `token_abe`) — see pm4-screen |
| Attachment missing | Request variable empty; wrong `source` / `variableInArray` |
| Works in test email, fails in process | Different server index; Mustache vars empty at node run time |
| After process import | Screen id or email server index invalid on target instance |

Optional runtime check (only if user asked): start request, confirm email received.

Form Task **email notifications** use the same recipient model inside `pm:config.email_notifications` on `bpmn:task` — see [pm4-form-manual-task](../pm4-form-manual-task/SKILL.md); debugging steps are similar.
