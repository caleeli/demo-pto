# `pm:config` schema (Actions by Email)

JSON string on `bpmn:callActivity` **`pm:config`**. Runtime reads parent config from subprocess data `_parent.config`. Export detects ABE nodes when both **`options`** and **`keyName`** are present.

## Core keys

| Key | Required | Notes |
|-----|----------|-------|
| `email` | yes | Recipient address; Mustache `{{var}}` |
| `subject` | yes | Mustache |
| `bodyType` | yes | `text` or `screen` |
| `template` | no | Legacy key (`"welcome"`) that Designer-created nodes may carry. Ignore it; do not add it to new nodes |
| `body` | when `bodyType` is `text` | Plain text; Mustache. Wrapped in default ABE email template when no custom screen |
| `screenRef` | when `bodyType` is `screen` | EMAIL screen id (integer). Mustache on `screenRef` allowed |
| `emailServer` | no | `""` = default outbound server; integer index otherwise. Same as [pm4-send-email](../pm4-send-email/config-schema.md) |
| **`keyName`** | yes | Request variable that receives the selected option **`value`** |
| **`options`** | yes | Array of action buttons (see below) |
| `selectedLanguage` | no | `en`, `de`, `es`, `fr`, or `variable` (default `en`) |
| `langVariable` | when language is `variable` | Request variable holding locale code |
| `startEvent` | no | Sub-process start id; default `subprocess_start` |

## `options[]` entry

| Field | Notes |
|-------|-------|
| `content` | Button label shown in the email |
| `value` | Stored in `keyName` when the recipient picks this button |
| `buttonVariant` | Bootstrap style. The Designer writes `btn-primary`, `btn-secondary`, … ; the sender strips the `btn-` prefix and maps the name to the instance color palette, so `primary` and `btn-primary` both resolve. Names: `primary`, `secondary`, `success`, `danger`, `warning`, `info`, `light`, `dark` |

Buttons render as `mailto:` links to the IMAP inbox. Reply body includes `PM-ABE-REF|{requestId}|{nodeId}|` and `PM-ABE-VALUE|{value}|`. Do not remove those markers from replies.

## EMAIL screen body

Same rendering rules as Send Email — see [pm4-screen screen-types#email](../pm4-screen/screen-types.md#email). Action buttons are **appended** after the screen HTML.

## `calledElement`

Not inside `pm:config`. Attribute on the call activity: `ProcessId-{subProcessDbId}` for package key `package-actions-by-email/sub-process`. Modeler loads id from `GET /api/1.0/actions-by-email/get-sub-process`.

## Minimal example

[examples/pm-config.json](examples/pm-config.json)
