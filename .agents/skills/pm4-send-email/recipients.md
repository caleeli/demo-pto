# Recipients (To / Cc / Bcc)

Current format: arrays on `pm:config` — `toRecipients`, `ccRecipients`, `bccRecipients`. At least one **To** entry is required.

Each recipient is an object:

```json
{ "type": "<type>", "value": "<depends on type>" }
```

Types without `value`: `requester`, `participants`, `manager`. Only one of `requester`, `participants`, `assignedUser`, `manager` per list (inspector enforces).

| `type` | `value` | Resolves to |
|--------|---------|-------------|
| `email` | Address or `{{var}}` (comma-separated ok) | Literal or Mustache-rendered emails |
| `userId` | User id or `{{var}}` | That user's email |
| `usersAndGroups` | `{ "users": [{ "id": N }], "groups": [{ "id": N }] }` | User email or all group members' emails |
| `requester` | (omit) | Request starter email |
| `participants` | (omit) | All participant emails on the request |
| `assignedUser` | (omit) | Current task assignee — **Form Task notifications only**; not offered on Send Email nodes |
| `manager` | (omit) | Process manager (`process_version.properties.manager_id`) |

## Examples

Fixed To address:

```json
"toRecipients": [{ "type": "email", "value": "alerts@example.com" }]
```

Mustache To from request data:

```json
"toRecipients": [{ "type": "email", "value": "{{customer_email}}" }]
```

Request starter:

```json
"toRecipients": [{ "type": "requester" }]
```

User and group:

```json
"toRecipients": [{
  "type": "usersAndGroups",
  "value": { "users": [{ "id": 12, "fullname": "Jane Doe" }], "groups": [] }
}]
```

## Legacy format

Older nodes may use:

```json
{
  "users": [12],
  "groups": [3],
  "addEmails": "ops@example.com,{{backup_email}}"
}
```

Runtime falls back to `loadMails()` when `toRecipients` is absent. Migrate to `toRecipients` when editing.

## Cc and Bcc

Same object shape in `ccRecipients` and `bccRecipients`. Empty arrays are fine.
