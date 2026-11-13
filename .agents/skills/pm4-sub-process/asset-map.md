# Where things live

Paths are relative to each repo: `processmaker` (core) and `modeler`. There is no Sub Process API. The node is BPMN on the parent process.

## Lookups (never invent ids)

```
GET /api/1.0/processes?filter=Background%20Check&include=events,category&filter_without_assignments=true&per_page=20
GET /api/1.0/processes/{id}?include=events,category&filter_without_assignments=true
GET /api/1.0/processes/{id}/bpmn
GET /api/1.0/tasks?process_request_id={parentRequestId}&all_tasks=true
GET /api/1.0/requests/{parentRequestId}/tokens?element_id={callActivityId}
GET /api/1.0/requests/{id}?include=data
```

- First call: find the **child** by name. Use `id` as `pm:config.processId` and as the suffix of `calledElement`.
- `include=events` returns each start event with `id`, `name`, `ownerProcessId`, `eventDefinitions`, and `config`. Pick a start event with an empty `eventDefinitions` and no `web_entry` in `config`. That `id` is `pm:config.startEvent`. `calledElement` is `{ownerProcessId}-{process.id}`.
- The modeler process picker uses the same query (`ProcessFormSelect.vue`): it hides the process currently open in the modeler, archived processes, and processes with no valid Start Event.
- Second call: confirm one child by id when you already have it.
- Third call: read the **parent** BPMN. `GET /api/1.0/processes/{id}` often omits `bpmn`.
- After a request exists: `all_tasks=true` includes the Call Activity token (`element_type` `callActivity`, `subprocess_request_id` = child). Default Inbox `/tasks` does not. `include=data` strips keys that start with `_` (`_parent` will not appear). The child row has `parent_request_id`.

**Never invent** a process id or a start-event id. Guessing `ProcessId-1` or copying ids from another instance breaks the node.

## Edit and save

The node lives in the parent BPMN. Save it the same way as any other process edit ([pm4-process/create-edit.md](../pm4-process/create-edit.md#editing-an-existing-process)):

```
GET /api/1.0/processes/{id}/bpmn
PUT /api/1.0/processes/{id}
```

```json
{ "description": "<existing description>", "bpmn": "<whole updated bpmn string>" }
```

`description` is required on every PUT. Without it core returns 422. Designer: Save + Publish; drafts are not live.

## Export / import

`ProcessExporter::getSubprocesses()` walks `callActivity` elements. It exports the child when `calledElement` has two parts, the suffix is numeric, the process exists, and `package_key` is null (not a Data Connector). The child process travels as a dependent of the parent. Import still rewrites ids; look them up again on the target instance.

## Core (`processmaker`)

| Path | What |
|------|------|
| `ProcessMaker/Models/CallActivity.php` | Activate, copy data, merge-back, errors, `getCalledElement`, published version, `isServiceSubProcess` |
| `ProcessMaker/Repositories/TokenRepository.php` `persistCallActivityActivated` | Sets child `parent_request_id` and parent token `subprocess_request_id` |
| `ProcessMaker/Repositories/TokenRepository.php` `removeParentFromData` | Drops `_parent` from the parent data store when the Call Activity token completes |
| `ProcessMaker/Managers/DataManager.php` | Reads parent data into the child; `reservedVariables` includes `_parent`, so merge-back skips it |
| `ProcessMaker/Http/Resources/ProcessRequests.php` `filterMagicVariables` | `GET /requests/{id}?include=data` drops keys that start with `_` |
| `ProcessMaker/Listeners/HandleEndEventRedirect.php` | Skips redirect when `parent_request_id` is set |
| `ProcessMaker/ImportExport/Exporters/ProcessExporter.php` `getSubprocesses()` | Child processes as export dependents |
| `ProcessMaker/Http/Controllers/Api/ProcessController.php` | Process list, show, store, update; `start_events` |
| `ProcessMaker/Http/Resources/Process.php` | `include=events` → `getStartEvents()` |

## Modeler (`modeler`)

| Path | What |
|------|------|
| `src/components/nodes/subProcess/index.js` | Node `processmaker-modeler-call-activity`, `bpmn:CallActivity`, inspector handler copies `config.calledElement` onto `calledElement` |
| `src/components/nodes/subProcess/subProcess.vue` | Shape, subprocess marker, loop markers |
| `src/components/nodes/subProcess/elementIdParser.js` | Splits `calledElement` into `ownerProcessId` and `processId` |
| `src/components/inspectors/ProcessFormSelect.vue` | Process and Start Event pickers; writes `calledElement`, `processId`, `startEvent`, `name` into `config` |
| `src/setup/addLoopCharacteristics.js` | Injects Loop Activity for `bpmn:CallActivity` |
| `src/components/inspectors/LoopCharacteristics.js` | Loop / Multi-instance inspector |

## Tests

| Path | Covers |
|------|--------|
| `processmaker/tests/Feature/Api/CallActivityTest.php` | Child start, files, in-progress update, Web Entry start, missing start, loop, child error |
| `processmaker/tests/Feature/Api/CallActivityMultilevelTest.php` | Nested Call Activities |
| `processmaker/tests/unit/ProcessMaker/Models/CallActivityMergeTest.php` | Merge-back of updated, new, and changed keys |
| `modeler/tests/e2e/specs/SubProcess.cy.js` | Sub Process inspector |
| `modeler/tests/unit/elementIdParser.spec.js` | `calledElement` split |
