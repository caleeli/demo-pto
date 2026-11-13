# Check an Actions by Email node

Static checklist. Reply to a test email only if the user asked.

## Prerequisites

```
- [ ] package-actions-by-email installed (package-actions-by-email:install)
- [ ] connector-send-email installed (composer dependency)
- [ ] Outbound SMTP: Admin → Settings → Email → Test succeeds
- [ ] Inbound IMAP: Admin → Settings → Actions by Email → Test succeeds
- [ ] handle-replies process running (queue worker / scheduler on this instance)
```

## Node checklist

```
- [ ] bpmn:callActivity with pm:config containing options and keyName
- [ ] calledElement points to ABE sub-process on this instance
- [ ] options has at least one { content, value, buttonVariant }
- [ ] keyName set; downstream gateway/script reads that variable
- [ ] email and subject non-empty; body or screenRef per bodyType
- [ ] screenRef is published EMAIL screen when bodyType is screen
- [ ] emailServer "" or valid index (GET /api/1.0/plugins/email/servers)
- [ ] Every {{var}} exists in request data before this node
```

Designer: Save + Publish parent process.

## Failures

| Symptom | Likely cause |
|---------|--------------|
| No Actions by Email in Modeler | Package not installed |
| Email sent, process never continues | IMAP misconfigured; handle-replies not running; recipient did not use action buttons |
| `keyName` empty after reply | Reply stripped PM-ABE markers; wrong mailbox; reply from different address |
| Wrong value in `keyName` | Option `value` typo; recipient edited reply body |
| Screen body error at send | `screenRef` missing or not EMAIL — fails at send time |
| Works outbound, fails inbound | IMAP auth (Google/Office365 OAuth) or wrong inbox |
| After process import | Sub-process id or screenRef invalid; re-import or run install |

Optional runtime (user asked): start request → receive email → click action button → reply → confirm `keyName` in request data → token advances.
