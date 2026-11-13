---
name: pm4-collection
description: >-
  ProcessMaker 4 Collections — persistent reusable data stores. Use when defining
  collection schemas, CRUD records, collection relationships, PMQL search, screen
  bindings, script SDK access, or collection signals. Also use for select-list
  catalogs (a select, radio, or checkbox group with more than two options) and
  for seeding sample or demo records.
---

# PM4 Collection

A Collection is a persistent data store scoped outside a single process request. Each collection owns a dedicated records table (`collection_{id}`) and is backed by screens that define its field schema. Requires package `processmaker/package-collections`.

Model: `ProcessMaker\Plugins\Collections\Models\Collection` (`collections` table).

## When to use

| Use Collection | Use something else |
|----------------|-------------------|
| Master data shared across many requests (students, institutions, catalogs) | Per-case workflow state → request `data.*` |
| CRUD UI for tabular data outside a process | One-off form fields → `pm4-screen` on a task |
| Lookup tables queried from scripts or watchers | External API only → `pm4-data-connector` |
| Parent/child linked records across collections | Simple in-request arrays → `data` on the request |
| React to record changes in BPMN | Direct signal throw → `pm4-signal` (pair with collection signals) |
| Options for a select, radio, or checkbox group with more than two choices | Yes/No or True/False only → hard-code in the screen. See [catalogs.md](catalogs.md) |
| Sample or demo rows for a collection | See [test-data.md](test-data.md) |

## Asset schema

| Field | Required | Notes |
|-------|----------|-------|
| `name` | yes | Unique; `alpha_spaces` (letters, numbers, spaces, `-`, `_`, `.`, `'`) |
| `description` | yes | Short description |
| `create_screen_id` | yes | Screen defining create form and field schema |
| `read_screen_id` | yes | Screen for viewing a record |
| `update_screen_id` | yes | Screen for editing a record |
| `custom_title` | no | Mustache for record title; default `{{ id }}`; use `{{ data.fieldName }}` for form fields |
| `obfuscate` | no | When true, record URLs/API keys use `uuid` instead of numeric `id` |
| `signal_create` | no | Emit signal on record create (default false) |
| `signal_update` | no | Emit signal on record update |
| `signal_delete` | no | Emit signal on record delete |
| `include_records_on_export` | no | Include records in collection export |
| `type` | no | `RAG` for document/RAG collections; omit for standard |
| `columns` | no | JSON column configuration for listing UI |
| `asset_type` | auto | Set by import/bundling (`PM_BLOCK`, etc.) |

On create, PM4 provisions `collection_{id}` and `collection_{id}_versions` tables and a hidden signal process (`package_key = package-collections/collection-{id}`).

## Field schema

Collection fields are **not** declared separately. They come from the collection screens:

- Each form control with a `config.name` becomes a key in `record.data`
- `/record-fields` derives fields from the **create** screen; listing **columns** merge create + update screens
- Allowed controls: `FormInput`, `FormTextArea`, `FormSelectList`, `FormCheckbox`, `FormDatePicker`, `FormHtmlEditor`, `FileUpload`, `FileDownload`, `FormMaskedInput`, `FormText`, `FormButton`, nested screens
- `GET /api/1.0/collections/{id}/record-fields` returns the derived field list (requires `can:create` on the collection)
- Listing columns: `GET/PUT /api/1.0/collections/{id}/columns` (`current`, `default`, `available`, `data`, `foreign`; `PUT` requires `can:view`)

Create three **collection schema screens** with `pm4-screen` before creating the collection (`FORM` for create/update, `DISPLAY` for read). These are separate from process task screens. Reuse existing screens when they already fit: field names on the create screen become the record `data` keys.

Order of work, select-list catalogs, and finding the auto-created Data Connector: [catalogs.md](catalogs.md).

## Record schema

| Field | Notes |
|-------|-------|
| `id` | Auto-increment per collection table; default `uri_key` when `obfuscate` is false |
| `uuid` | Always generated; used as `uri_key` when `obfuscate` is true |
| `data` | JSON object — user-defined fields from screens |
| `created_by_id`, `updated_by_id` | Audit |
| `created_at`, `updated_at` | Timestamps |
| `status` | `COMPLETED` by default; RAG sets `PENDING` on POST create and PUT update (not on PATCH) |

Records are **not** request-scoped. Scripts use the Collections API/SDK; screens use collection UIs or the auto-created data source.

## Relationships

Link a parent collection field to child records via `collection_relationships`:

| Field | Notes |
|-------|-------|
| `name` | Relationship label |
| `parent_collection_key` | Column `field` from parent (e.g. `data.customerId` or `id`) |
| `child_collection_id` | Target collection id |
| `child_collection_key` | Column `field` in child matched against parent value (e.g. `data.customerId`) |

`GET /api/1.0/collections/{parent}/records/{record}/relationships/{rel}/records` filters child records with PMQL. Parent `{record}` is **numeric id only** — uuid/obfuscate does not apply on this route.

## Signals

When `signal_create|update|delete` is true, record CRUD throws:

| Operation | Signal id |
|-----------|-----------|
| Create | `collection_{id}_create` |
| Update | `collection_{id}_update` |
| Delete | `collection_{id}_delete` |

Catch with a **Signal Start Event** whose `signalRef` matches the table above (see [`pm4-signal`](../pm4-signal/SKILL.md)). Payload is `record.data` only — keys land as top-level request `data` on the new case. Put lookup ids inside `data` if needed. Setup steps: `reference.md` § Catching collection signals; BPMN fixture: `examples/signal-catch-minimal.bpmn.xml`.

## Permissions

Per-collection record permissions (`collection_permissions`): `create`, `read`, `update`, `delete`, `raw`.

- Creator gets all permissions by default
- Assign to users/groups via `user_permissions` / `group_permissions` on update
- API record routes use gate `can:view|create|update|delete,collection` (`view` checks the `read` permission on the collection)
- `raw` is a per-collection permission used in the web UI; no dedicated REST route

Global permissions: `view-collections`, `create-collections`, `edit-collections`, `delete-collections`, `list-collections`, `export-collections`, `import-collections`, `truncate-collections`.

## Read/write patterns

### From scripts (PHP docker executor)

Use `$api->collections()` SDK. See `examples/script-record-crud.php` and `reference.md`.

PMQL example: `data.email = "user@example.com"` — prefix field paths with `data.`.

### From screens

- Collection listing/detail UIs use the collection's read/create/update screens
- When the Data Sources package is installed, each collection gets an auto-created data source pointing to `/collections/{id}/records` — use it from watchers and select lists

### From request data

Request `data.*` and collection `record.data` are separate namespaces. Copy explicitly in scripts — see `examples/script-record-crud.php`.

## Links to other assets

| Asset | How |
|-------|-----|
| Screen | `create_screen_id`, `read_screen_id`, `update_screen_id` define schema and UI |
| Script | Collections SDK — CRUD, PMQL search |
| Data source | Auto-created per collection (Data Sources package) |
| Signal | Optional CRUD signals → catch in `pm4-signal` |
| PM Block | Collections can be bundled with `asset_type = PM_BLOCK` |
| Process | Not required; use signals or scripts to react to record changes |

Pair with `pm4-screen`, `pm4-script`, `pm4-expressions`, `pm4-signal`.

## Minimal example

See `examples/collection-minimal.json`, `examples/record-minimal.json`, `examples/script-record-crud.php`, and `examples/signal-catch-minimal.bpmn.xml`.

## API

```
GET/POST  /api/1.0/collections
GET/PUT   /api/1.0/collections/{id}
DELETE    /api/1.0/collections/{id}
DELETE    /api/1.0/collections/{id}/truncate
POST      /api/1.0/collections/{id}/export
POST      /api/1.0/collections/import
GET       /api/1.0/collections/{id}/columns
PUT       /api/1.0/collections/{id}/columns
GET       /api/1.0/collections/{id}/record-fields
GET/POST  /api/1.0/collections/{id}/records
GET/PUT   /api/1.0/collections/{id}/records/{recordId}  (numeric id or uuid when obfuscate)
PATCH     /api/1.0/collections/{id}/records/{recordId}
DELETE    /api/1.0/collections/{id}/records/{recordId}
POST      /api/1.0/collections/{id}/import-csv
GET/POST  /api/1.0/collections/{id}/relationships
GET       /api/1.0/collections/{id}/records/{record}/relationships/{rel}/records  (parent {record}: numeric id only)
```

Record CRUD `{recordId}` accepts numeric id or uuid when `obfuscate` is true. Relationship child listing does not.

## Validation checklist

1. `package-collections` installed
2. Create, read, and update screens exist and share consistent field `name` keys
3. Collection `name` is unique
4. Scripts reference collection id and use `data.*` in PMQL
5. Relationship keys exist in both parent and child field schemas
6. Signal catch processes exist when `signal_*` flags are enabled
7. Record permissions grant access to intended users/groups

## Common mistakes

- Treating collection records as request variables — they persist across cases
- Changing screen field names after records exist — breaks PMQL and relationships
- Using `PUT` on a record expecting merge — it replaces entire `data`; use `PATCH` to merge
- Enabling collection signals without a catch process — events are lost
- Assuming truncate resets auto-increment ids — it does not
- Querying collection fields in PMQL without `data.` prefix
- Using `{{ fieldName }}` in `custom_title` — Mustache context is `{ id, data }`; use `{{ data.fieldName }}`
- Creating a collection before its screens — validation requires all three screen ids
- Assuming signal payload includes record `id` — only `data` is thrown; store ids in `data` if needed downstream
- Using uuid in relationship records URL when `obfuscate` is on — that route accepts numeric parent `id` only
- Leaving a multi-option select as a hard-coded list instead of a Collection — see [catalogs.md](catalogs.md)
- Wiring a select to a collection name or an invented id — use the numeric id from the list or create response
- Creating a collection and never seeding it — selects stay empty ([test-data.md](test-data.md))
- Sending record values outside `data` — each record body is `{ "data": { ... } }`
