---
name: pm3-actions-by-email
description: >-
  ProcessMaker 3 Actions by Email. Use for enterprise email approve/reject tasks,
  token-based routing, or actionsByEmail plugin configuration.
---

# PM3 Actions by Email

**Actions by Email** is an enterprise plugin allowing users to approve or reject tasks by replying to email.

## When to use

- Task completion via email link (approve/reject)
- External approvers without PM accounts
- Email-driven routing decisions

Requires enterprise license and `actionsByEmail` plugin enabled.

## Asset schema — ABE_CONFIGURATION

| Field | Required | Notes |
|-------|----------|-------|
| `ABE_UID` | auto | Configuration id (primary key) |
| `PRO_UID` | yes | Owning process |
| `TAS_UID` | yes | Target task |
| `ABE_TYPE` | yes | `LINK`, `RESPONSE`, `FIELD`, or `CUSTOM` |
| `ABE_TEMPLATE` | yes | Email template name |
| `ABE_DYN_TYPE` | yes | Dynaform type for action form |
| `DYN_UID` | yes | Dynaform for action capture (`FIELD`/`LINK` types) |
| `ABE_EMAIL_FIELD` | yes | Case variable with recipient email |
| `ABE_ACTION_FIELD` | no | Field storing selected action |
| `ABE_ACTION_BODY_FIELD` | no | Field storing action body/comment |
| `ABE_SUBJECT_FIELD` | no | Email subject field |
| `ABE_CASE_NOTE_IN_RESPONSE` | no | Create case note on response |
| `ABE_FORCE_LOGIN` | no | Require login before action |
| `ABE_EMAIL_SERVER_UID` | no | Outbound mail server |
| `ABE_EMAIL_SERVER_RECEIVER_UID` | no | Inbound mail server (`RESPONSE` type) |

Plugin classes: `ActionsByEmailCoreClass`, `ProcessMaker\BusinessModel\ActionsByEmail`.

## Token flow

1. Task assigned → email sent with unique action links
2. User clicks link → lightweight form or one-click action
3. Token validated → case routes per action configuration
4. Invalid/expired token → error page (`actionsByEmailErrorReply.html`)

## Rules

- Email server must be configured (`pm3-email-template` prerequisites)
- Each action link is single-use or time-limited per configuration
- Dynaform fields can be embedded in email for partial data capture
- Log available in Setup → Actions by Email Log
- Distinct from standard task email notifications

## Links to other PM3 assets

| Asset | Relationship |
|-------|--------------|
| `pm3-email-template` | Email content and server config |
| `pm3-process` | Per-task ABE configuration |
| `pm3-dynaform` | Fields in email action form |
| `pm3-trigger` | May fire on action completion |

## Validation checklist

1. Plugin enabled and licensed
2. Email server delivers action links correctly
3. Token expiration matches business SLA
4. All action outcomes have defined routing

## Common mistakes

- Confusing with standard task notification email
- Action links blocked by email security scanners (pre-fetch)
- Missing routing for reject path
- Expired tokens without resend mechanism
