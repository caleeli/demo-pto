---
name: pm4-script
description: >-
  Choose between a PM4 Script Task, screen calculated property, and decision
  table. Also use for Script Task/watcher code, language, I/O, executors, and
  timeouts. Writing only Mustache/FEEL uses pm4-expressions.
---

# PM4 Script

Scripts are reusable code assets (`ProcessMaker\Models\Script`) invoked from script tasks, watchers, and some connectors.

## When to use

| Use script | Use something else |
|------------|-------------------|
| Multi-step logic, API orchestration | Simple field calc → screen `computed` |
| Transform data between tasks | Tabular rules → `pm4-decision-table` |
| Reusable logic across processes | One-off UI → screen watcher only if trivial |
| BPMN script task step | Routing only → gateway + expressions |

## Asset schema

| Field | Required | Notes |
|-------|----------|-------|
| `title` | yes | Script name |
| `description` | yes | Short description |
| `language` | yes* | Runner language |
| `script_executor_id` | yes* | Executor record id |
| `code` | no | Source (required in practice) |
| `run_as_user_id` | yes | Sets `API_TOKEN` permissions at runtime |
| `timeout` | no | Max seconds (default 60) |
| `retry_attempts` | no | Retries on failure (default 0) |
| `retry_wait_time` | no | Seconds between retries |
| `script_category_id` | no | Designer category |

\* Provide `language` **or** `script_executor_id`. If only `language` is set, PM4 assigns the default executor on save.

For PM3→PM4 migration, PHP Script assets must use `language: php` with the **PHP Executor**. Do not use `php-nayra`; it selects the separate PHP (µService) Nayra runner and violates the migrated package contract.

BPMN script task `errorHandling` overrides `timeout` and retry settings on the script asset.

## Script Task node

BPMN type `bpmn:scriptTask` (modeler id `processmaker-modeler-script-task`). The node runs a Script asset. It creates no Inbox task. Graph placement is [pm4-process](../pm4-process/SKILL.md).

| Inspector | Attribute | Required | Notes |
|-----------|-----------|----------|-------|
| Name | `name` | Yes | A label only. It is **not** the script lookup key |
| Script | `pm:scriptRef` | **Yes** | Looked-up script id. A named ref `${script:exact-title}` is for `package-plg` deploy only ([bpmn-document.md](../pm4-process/bpmn-document.md#portable-references-package-plg-deploy-only)) |
| Script Configuration | `pm:config` JSON | No | Passed to the script as `$config` |
| Error Handling | `pm:errorHandling` JSON | No | Overrides the script asset's timeout and retries |
| Loop | `loopCharacteristics` | No | Multi-instance |

`pm:errorHandling` keys use snake case:

| Key | Notes |
|-----|-------|
| `timeout` | Seconds |
| `retry_attempts` | Retries after a failure |
| `retry_wait_time` | Seconds between tries |
| `inapp_notification` | `true` notifies the process managers in the app after a failure |
| `email_notification` | `true` emails the process managers after a failure |

```xml
<bpmn:scriptTask id="node_30" name="Calculate Credits"
  pm:scriptRef="SCRIPT_ID"
  pm:config="{&#34;mode&#34;:&#34;strict&#34;}"
  pm:errorHandling="{&#34;timeout&#34;:60,&#34;retry_attempts&#34;:0,&#34;retry_wait_time&#34;:5}">
  <bpmn:incoming>flow_1</bpmn:incoming>
  <bpmn:outgoing>flow_2</bpmn:outgoing>
</bpmn:scriptTask>
```

Replace `SCRIPT_ID` with the looked-up script id.

Data: the script receives the full request data (and system variables) as `$data`, and the decoded `pm:config` as `$config`. It must return an **associative array**. PM4 merges it into request data. A non-array return breaks the merge.

Node mistakes:

- A missing `pm:scriptRef` means the task does not run.
- Invalid JSON in `pm:config` or `pm:errorHandling`, or JSON that is not XML-escaped.
- The Script asset's language or executor does not match what the code needs.
- A long-running script with no `timeout` in `pm:errorHandling` or on the asset.
- Deploying the process before the script exists. Create or publish the Script first.

## Languages

Map to installed `ScriptExecutor` records (`config/script-runners.php` defines docker runner packages).

| Language | Notes |
|----------|-------|
| `php` | Standard PHP Executor; `$api` SDK + `getenv()`. Required for migrated PM3 PHP Script Tasks |
| `php-nayra` | Specialized PHP (µService) Nayra runner. Valid for intentional non-migration use; **never use for PM3→PM4 Script Tasks** |
| `javascript` | Node executor; `process.env` |
| `python` | Python executor; `os.getenv()` |

Deprecated: `lua`, `r`.

## Runtime

- Script code runs in an **isolated executor** (Docker/microservice), not in the PM4 web worker — **same source under PHP-FPM and Octane**.
- Script tasks and preview run on the **`bpmn` queue** (async). Watchers use `/scripts/execute` then poll `/scripts/execution/{key}`.
- Write **stateless, idempotent** code — retries re-run the script. Uncaught exceptions mark the task **FAILING**.

## Input / output

| | Contract |
|-|----------|
| Input | `$data` / `data`; optional `$config` / `config` from BPMN task, watcher, or API |
| Output | Return an array — PM4 merges **top-level keys** into request `data` |

### Magic variables in `$data`

| Key | Use |
|-----|-----|
| `_request.id` | Current request id — API calls, logging |
| `_request.case_number` | Case number |
| `_request.case_title` | Case title (when present — use `?? ''`) |
| `_user` | Task assignee (absent on some self-service tasks) — **not** the same as `run_as_user_id` |
| `_parent` | Parent loop data in multi-instance tasks |

Use `??` defaults — magic variables may be missing in preview or watchers.

### Runtime variables (not Script asset fields)

| Name | PHP access | Notes |
|------|------------|-------|
| Request data | `$data` | Business keys from screens and prior tasks |
| Task config | `$config` | From BPMN script task, watcher, or execute/preview body |
| PM API SDK | `$api` | PHP **docker** executor only — guard with `isset($api)` in preview |
| `API_TOKEN` | `getenv('API_TOKEN')` | Scoped to `run_as_user_id` |
| `API_HOST` | `getenv('API_HOST')` | PM4 API base URL |
| `APP_URL` / `HOST_URL` | `getenv(...)` | App URLs |
| Custom secrets | `getenv('VAR_NAME')` | From Environment Variable assets |

`{{env.VAR}}` is for BPMN, screens, and connectors only — see `pm4-expressions`.

### Reserved output keys

Do not return these — PM4 ignores them on merge: `_user`, `_request`, `_parent`, `loopCounter`, `numberOfInstances`, `numberOfActiveInstances`, `numberOfCompletedInstances`, `numberOfTerminatedInstances`.

### Examples

| File | When to copy |
|------|--------------|
| `examples/transform-minimal.php` | Simple calc from `$data` fields |
| `examples/api-call-minimal.php` | `_request`, `getenv()`, optional `$api` SDK (docker PHP) |

Copy as-is into the script `code` field (include `<?php`).

## Links to other assets

| Link | How |
|------|-----|
| Script task | BPMN `scriptRef` → script id |
| Screen watcher | Watcher → script id |
| Data connector | Some patterns delegate to scripts |
| Collection | `$api->collections()` SDK for record CRUD (`pm4-collection`; docker PHP executor) |

Published processes use the script **version** bound to that process (`versionFor`).

## API

```
GET/POST  /api/1.0/scripts
GET/PUT   /api/1.0/scripts/{id}
POST      /api/1.0/scripts/{script}/preview
POST      /api/1.0/scripts/execute/{script_id}
GET       /api/1.0/scripts/execution/{key}
```

Permissions: `view-scripts`, `create-scripts`, `edit-scripts`, `delete-scripts`.

## Validation checklist

1. Returned keys match downstream `data.*` references
2. `_request` / business keys use `??` defaults
3. `run_as_user_id` has permissions for every `$api` call
4. `timeout` fits external calls; idempotent when retries apply
5. Secrets via `getenv()`, never hardcoded
6. Language matches the selected executor; migrated PHP uses `php` + **PHP Executor**, never `php-nayra`

## Common mistakes

- Ignoring `_request` and assuming only screen fields exist in `$data`
- Returning reserved keys or partial nested objects that wipe nested fields
- `{{env.*}}`, `now()`, or Eloquent inside script source
- Confusing `_user` in `$data` with `run_as_user_id` on the script (`API_TOKEN` uses `run_as_user_id`)
- Sync long-running `/scripts/execute?sync=true` blocking the web worker
- `run_as_user_id` without permissions for the API call → 403 from `$api` or empty SDK response
- Non-idempotent side effects with `retry_attempts` > 0
- Choosing `php-nayra` for a PM3→PM4 Script Task — use `php` so Script Builder and the **PHP Executor** dependent agree
