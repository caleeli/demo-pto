# `pm:config` schema (Send Email node)

Stored as a JSON string on `pm:config`. Runtime: `EmailSendImplementation` → `SendEmail` job.

## Core keys

| Key | Required | Notes |
|-----|----------|-------|
| `emailServer` | no | `""` or `0` = default mailer; integer index = `Email Server N` in Settings. Mustache allowed |
| `type` | yes | `"text"` (plain) or `"screen"` (EMAIL screen body) |
| `subject` | yes | Mustache `{{var}}` supported |
| `textBody` | when `type` is `text` | Plain body; Mustache |
| `screenRef` | when `type` is `screen` | EMAIL screen id (integer) or Mustache e.g. `{{emailScreenId}}`. Rendered server-side in Node |
| `toRecipients` | yes (new format) | Array of recipient objects — [recipients.md](recipients.md) |
| `ccRecipients` | no | Same shape as To |
| `bccRecipients` | no | Same shape as To |
| `attachments` | no | Array of `{ source, value, variableInArray? }` — see below |

## Legacy keys (still supported)

Prefer `toRecipients` for new work.

| Key | Notes |
|-----|-------|
| `users` | User id list |
| `groups` | Group id list |
| `addEmails` | Comma-separated addresses (Mustache) |

## Attachments

Each item:

| Field | Values |
|-------|--------|
| `source` | `url`, `variable`, `array` |
| `value` | URL string, request variable name, or array variable name |
| `variableInArray` | When `source` is `array`: property name inside each array object; blank if array is a list of file ids |

File variables must come from File Upload controls linked in an upstream Form Task.

## Body rendering

| `type` | Behavior |
|--------|----------|
| `text` | `textBody` after Mustache |
| `screen` | Loads published EMAIL screen; **first page only**; allowed controls: Rich Text, Record List, Link Button, Submit → link. See [pm4-screen screen-types#email](../pm4-screen/screen-types.md#email) |

## Email server index

- Modeler loads servers from `GET /api/1.0/plugins/email/servers`.
- Stored value is the server **index** (0 = default).
- On process import, missing server index is cleared to default ([ProcessExporterExtension](asset-map.md)).

## Minimal configs

Plain text: [examples/pm-config-text.json](examples/pm-config-text.json)

EMAIL screen: [examples/pm-config-screen.json](examples/pm-config-screen.json)
