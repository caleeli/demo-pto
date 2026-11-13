---
name: pm4-pm-block
description: >-
  Publishes and configures ProcessMaker 4 PM Block packages. Use when publishing
  a subprocess as a block, configuring inbound_configs, panels, global settings,
  call-activity inboundConfig or dataMapping, or block import/export.
  Inserting or connecting an existing PM Block as a call activity between process
  events or tasks is process graph editing: use pm4-process, not this skill.
---

# PM4 PM Block

A PM Block is a reusable subprocess packaged with its dependent assets (screens, scripts, connectors, etc.). Requires package `processmaker/package-pm-blocks`.

Model: `ProcessMaker\Package\PackagePmBlocks\Models\PmBlock` (`pm_blocks` table).

Each block owns an internal process (`editing_process_id`) with `asset_type = PM_BLOCK` and a unique `process_package_key` (generated as `pm-block-{uniqid}` on publish).

## When to use

| Use PM Block | Use something else |
|--------------|-------------------|
| Reusable subprocess with bundled screens/scripts | One-off subprocess in same process → inline call activity |
| Distributable/importable solution fragment | Simple logic → `pm4-script` |
| Configurable insertion (panels + inbound mapping) | Tabular rules → `pm4-decision-table` |
| Same flow in many parent processes | Full standalone process → `pm4-process` only |

## Asset schema

| Field | Required | Notes |
|-------|----------|-------|
| `name` | yes | Unique block name (max 255) |
| `description` | yes | Short description (max 255) |
| `status` | auto | `ACTIVE`, `INACTIVE`, `ARCHIVED` (set `ACTIVE` on publish) |
| `process_package_key` | auto | Unique key; links subprocess and call activity |
| `editing_process_id` | auto | Internal BPMN process id |
| `editing_process_uuid` | auto | Internal process uuid |
| `pm_block_category_id` | yes | Category id (DB not null) |
| `panels` | no | JSON — configuration fields in call activity inspector |
| `global_settings` | no | JSON — block settings backed by environment variables |
| `inbound_configs` | no | JSON object — catalog of inbound property names for mapping UI |
| `meta` | no | `icon`, `defaultStartEvent`, `setupStartEvent`, `version`, `author`, `isImported`, `isLocked` |
| `user_id` | auto | Creator |

On publish, bundled assets (process, screens, scripts, data connectors, collections) are copied with `asset_type = PM_BLOCK`.

## Lifecycle

1. **Build** the source process in the modeler (screens, scripts, BPMN).
2. **Publish**: `POST /api/1.0/pm-blocks` with `asset_id` (source process id) and `pm_block_category_id`.
3. **Configure** at `/designer/pm-blocks/{id}/edit/`: panels, `inbound_configs`, global settings, subprocess BPMN, `meta.defaultStartEvent`.
4. **Insert** PM Block call activity node into parent process and map inbound/response data per insertion.
5. **Import/Export** for distribution across instances.

## Call activity `pm:config`

PM Blocks register as `bpmn:CallActivity` nodes. Per-insertion config keys:

| Key | Purpose |
|-----|---------|
| `processKey` | `processmaker-{process_package_key}-call-activity` |
| `processId` | Resolved subprocess id; `calledElement` = `ProcessId-{id}` |
| `startEvent` | Start event node id inside the block subprocess |
| `inboundConfig` | Per-insertion inbound mapping (see below) |
| `dataMapping` | Per-insertion response mapping (see below) |
| `{panelField}` | Dynamic keys from `panels` (e.g. `name`) |

The modeler resolves `processId` and `startEvent` via `GET /api/1.0/pm-blocks/get-process/{processKey}`. `meta.defaultStartEvent` overrides the first start event when set.

See `examples/call-activity-config-minimal.json` (config only) and `examples/call-activity-minimal.bpmn.xml` (illustrative fragment — not importable to Modeler as-is; IDs are placeholders).

## Data mapping

### Inbound catalog vs per-insertion mapping

| Level | Field | Where |
|-------|-------|-------|
| Block asset | `inbound_configs` | PM Block edit form — declares property names for the mapping dropdown |
| Call activity | `inboundConfig` | `pm:config` — maps each property to a parent request variable |

See `examples/inbound-configs-schema.json`.

### Inbound (parent → subprocess)

`CallActivity::callSubprocess()` passes parent token `data` into the subprocess and sets `data._parent.config`. On subprocess request create, `ProcessRequestObserver` applies `inboundConfig`:

| Field | Meaning |
|-------|---------|
| `key` | Block property name (from `inbound_configs`) |
| `value` | Parent request variable (top-level `data` key already in subprocess data) |

`{ "key": "customerId", "value": "applicantId" }` → `data.customerId = data.applicantId`.

### Response (subprocess → parent)

| Field | Meaning |
|-------|---------|
| `key` | Parent request variable to set |
| `value` | Top-level subprocess `data` key; empty string = entire subprocess `data` |
| `format` | Stored by UI only — `PmBlockCompleted` does not resolve dot notation |

Three code paths touch response data (all use the same `dataMapping` array shape):

1. **Subprocess request create** (`ProcessRequestObserver::created`): applies `dataMapping` to subprocess `data` if source keys already exist in initial data.
2. **Call activity complete** (`PmBlockCompleted`): writes only mapped keys to the parent token (`DataManager::updateData`).
3. **Subprocess request complete** (`ProcessRequestObserver::saved`): merges child `data` into parent request `data` (parent wins on key collision).

## Panels

`panels` JSON defines controls in the call activity inspector (`config.name` → key in `pm:config`). Publish seeds a default `name` input (`PmBlockController::DEFAULT_PANEL`). See `examples/panels-minimal.json`.

## Global settings

Adding a field via `POST /api/1.0/pm-blocks/{id}/settings` (`mode: add`) creates an `EnvironmentVariable` and appends a row to `global_settings`:

- Name: `PMB_{variable}_{process_package_key}` (uppercase; package key normalized: spaces/hyphens → underscores).
- Use in block assets: `{{env.PMB_...}}` or `getenv()` in scripts.
- Updates via `mode: edit` or `mode: editValue` persist with `Crypt::encrypt()`; `protected` masks display in UI only.

`GET /api/1.0/pm-blocks/{id}/settings` returns current rows.

## Subprocess rules

- At least one empty start event (no message, timer, or signal).
- Start event must not be a web entry.
- Every executable path reaches an end event.

`Process::validateBpmnDefinition()` → `validateCallActivity()` when the call activity `pm:config` includes `startEvent` (PM Block nodes set this via `get-process`).

## Import / export

- `POST /api/1.0/pm-blocks/export/download/{id}`
- `POST /api/1.0/pm-blocks/import/do-import`
- Parent process export pulls block dependents via `ProcessExporterExtension`.
- Import sets `meta.isImported = true`; UI disables edit when imported. Process edit blocked when `isImported` + `isLocked`.
- Duplicate import may increment `name` and `process_package_key`.
- `processKey`, `calledElement`, and `processId` rewritten on import.

See `reference.md` for publish, export, and import flow detail.

## Links to other assets

| Asset | How |
|-------|-----|
| Parent process | PM Block call activity with `processKey` from `process_package_key` |
| Screens / scripts / connectors | Bundled in subprocess (`asset_type = PM_BLOCK`) |
| Environment variables | From `global_settings` |

Pair with `pm4-process`, `pm4-screen`, `pm4-script`, `pm4-expressions`.

## API

```
GET/POST  /api/1.0/pm-blocks
GET/PUT   /api/1.0/pm-blocks/{id}
DELETE    /api/1.0/pm-blocks/{id}              → archives
PUT       /api/1.0/pm-blocks/{id}/restore
GET/POST  /api/1.0/pm-blocks/{id}/settings
GET       /api/1.0/pm-blocks/get-process/{processKey}
POST      /api/1.0/pm-blocks/export/download/{id}
POST      /api/1.0/pm-blocks/import/do-import
GET       /api/1.0/pm-blocks-categories
```

Permissions: `view-pm-blocks`, `create-pm-blocks`, `edit-pm-blocks`, `archive-pm-blocks`, `import-pm-blocks`, `export-pm-blocks`.

## Validation checklist

1. `package-pm-blocks` installed
2. Block subprocess has valid empty start event
3. `inbound_configs` keys cover every `inboundConfig` property
4. Parent variables in `inboundConfig` exist upstream
5. `dataMapping` uses top-level subprocess keys only
6. Panel field names avoid reserved keys (`processKey`, `processId`, `startEvent`, `inboundConfig`, `dataMapping`)
7. Global settings env vars exist before block assets reference them

## Common mistakes

- Generic call activity instead of PM Block node (missing `processKey`)
- Confusing `inbound_configs` (catalog) with `inboundConfig` (per-insertion mapping)
- Hardcoding `processId` or assuming `process_package_key` never changes on import
- Expecting `dataMapping` alone to control all parent data — `saved` merges child request data; `created` may also pre-apply mapping to subprocess data
- Editing imported block UI (`meta.isImported`) or locked process (`isImported` + `isLocked`)
