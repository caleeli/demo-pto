---
name: pm4-process
description: >-
  Create or edit ProcessMaker 4 BPMN process graphs: nodes, flows, events
  (including Start Event interstitial and Display Next Assigned Task),
  exclusive, inclusive, and parallel gateways and their default flows, Script
  Tasks, embedded subprocesses, call activities, pools and lanes, FlowGenie, Send Email, Actions by Email, publish,
  import, and export. Not task/screen assignment
  (pm4-form-manual-task), screen config, asset-specific configuration, or signals.
---

# PM4 Process

## When to use

- Process graph: Start, tasks, gateways, End, embedded subprocess phases, or Call Activity for reusable children.
- Publish, import, or export a process.
- Do not use for one Form or Manual Task's screen, assignment, or due date (use [pm4-form-manual-task](../pm4-form-manual-task/SKILL.md)), subprocess shape choice / child contract (use [pm4-sub-process](../pm4-sub-process/SKILL.md)), screen config (use [pm4-screen](../pm4-screen/SKILL.md)), or signal events (use [pm4-signal](../pm4-signal/SKILL.md)). External API calls (Data Connectors) are not covered here.

## Readable diagrams (required when designing)

Large flat graphs are hard to understand. Prefer a short top-level **phase story** and put detail inside embedded subprocesses:

1. Outline phases (Capture → Validate → Review → …) before placing every task.
2. Default grouping shape: `bpmn:subProcess` (same request). See [pm4-sub-process/design.md](../pm4-sub-process/design.md).
3. Use `bpmn:callActivity` only for reusable published processes, PM Blocks, or a separate request lifecycle.
4. Do not invent a child process merely to hide nodes — embed them.

Heuristics and examples: [pm4-sub-process](../pm4-sub-process/SKILL.md).

## Reference map

| Need | Read |
|---|---|
| Choose/configure a gateway, sequence flow, split/join, or event race | [gateways.md](gateways.md) |
| Event inventory, configuration, restrictions, dependencies, troubleshooting | [events.md](events.md) |
| Routing expression syntax and root/global/instance data scope | [pm4-expressions](../pm4-expressions/SKILL.md) |
| Element types, asset links, and the Service Task connector routing table | [bpmn-elements.md](bpmn-elements.md) |
| BPMN document rules: skeleton, required structure, asset references, portable refs, order of work | [bpmn-document.md](bpmn-document.md) |
| Pools, lanes, and message flows between participants | [pools.md](pools.md) |
| Complete BPMN examples, input cases, placeholders, expected outcomes | [examples.md](examples.md) |
| Resolve real instance asset IDs | [lookup.md](lookup.md) |
| Create/edit in Designer or API | [create-edit.md](create-edit.md) |
| Static verification and authorized runtime checks | [verify.md](verify.md) |
| Official package import/export | [import-export.md](import-export.md) |

## Before creating

Ask only if missing:

1. **Name** (alphanumeric for API).
2. **Description** — short summary of what the process does. Always set on create; never omit or leave blank.
3. **Category** — default **Uncategorized** unless the user names another. Look up the id ([lookup.md](lookup.md)); do not invent it.

## Asset schema

| Field | Required | Notes |
|-------|----------|-------|
| name | yes | Unique. Letters, numbers, spaces, and `- _ . '` (`alpha_spaces`) |
| process_category_id | yes | Existing category id |
| bpmn | yes | BPMN XML; `bpmn:process` is executable |
| status | yes | `ACTIVE`, `INACTIVE`, or `ARCHIVED` |
| description | yes | Short summary of what the process does. Required on create and on every PUT; core returns 422 without it |

## Element rules

- New Form Task is `bpmn:task`; Manual Task is `bpmn:manualTask`, both with `pm:screenRef`. Typed screen and assignment: [pm4-form-manual-task](../pm4-form-manual-task/SKILL.md).
- Script Task is `bpmn:scriptTask` with `pm:scriptRef`. No Inbox task.
- FlowGenie is `bpmn:serviceTask` with `implementation="package-ai/processmaker-ai-task"`. Genie id and output variable: pm4-flow-genie.
- Embedded phase grouping uses `bpmn:subProcess` (also `adHocSubProcess` / `transaction` when those semantics apply). Inner flows stay inside the container: [pm4-sub-process](../pm4-sub-process/SKILL.md).
- Call Activity `calledElement` is `{ownerProcessId}-{id}` after Modeler save. Use only for reuse / separate request. Child process, start event, and data: [pm4-sub-process](../pm4-sub-process/SKILL.md).
- Start has no incoming flow; End has no outgoing flow; Intermediate events have both. Boundary events use `attachedToRef` and an outgoing alternate path, not an incoming flow. Event types, configuration, and restrictions: [events.md](events.md).
- Exclusive/Inclusive exits use conditions and an intentional default fallback; Parallel/Event-Based exits use neither. Selection, split/join rules, and XML fields: [gateways.md](gateways.md).
- Create screens, scripts, and (when Call Activity) child processes before wiring their ids.
- Import only an official process export. Do not invent a `process_package` JSON.
- **PM3 converter `process_package` (v2):** BPMN may ship with empty `pm:screenRef` before import; the v2 importer sets refs from screen dependents. After import, confirm refs in Modeler and check sequence-flow expressions against the target evaluator/input shape with [pm4-expressions](../pm4-expressions/SKILL.md). See [pm3-pm4-migration](../pm3-pm4-migration/import-package-rules.md).

## Links to other assets

- `pm:screenRef` on a Form Task is a FORM or supported CONVERSATIONAL screen id; on a Manual Task it is a DISPLAY screen id.
- `pm:scriptRef` on a Script Task is a script id.
- `calledElement` on a Call Activity is `{ownerProcessId}-{child process id}`. Configure it with [pm4-sub-process](../pm4-sub-process/SKILL.md).
- `process_category_id` is a process category id. Default new processes to **Uncategorized** (looked-up id) unless another category is named.
- Start interstitial and End redirect use screen ids when set.

## Asset links

| BPMN element | Asset skill | Link by |
|--------------|-------------|---------|
| Form/Manual task | `pm4-screen` | Screen id; node configuration: [pm4-form-manual-task](../pm4-form-manual-task/SKILL.md) |
| Script task | `pm4-script` | Script id |
| Business rule | `pm4-decision-table` | Decision table id |
| Embedded / ad-hoc / transaction subprocess | `pm4-sub-process` | Nested BPMN inside the container |
| Call activity (child process) | `pm4-sub-process` | `calledElement` / `pm:config` |
| Call activity (PM Block) | `pm4-pm-block` | `processKey` / `process_package_key` |
| Signal throw/catch/start/end/boundary | `pm4-signal` | Signal ID (`signalRef`) and declaration; non-signal events use [events.md](events.md) |
| Genie task | `pm4-flow-genie` | Flow Genie id |
| PDF / Slack / UiPath / IDP service task | `pm4-pdf-generator`, `pm4-slack`, `pm4-uipath`, `pm4-idp` | Screen id, Slack channel object, UiPath objects, IDP document type and folder ([bpmn-elements.md](bpmn-elements.md#service-task-connectors)) |
| DocuSign call activity | `pm4-docusign` | DocuSign template id |

## Validation

- Each configured Start has an intended route to an End; no orphan nodes. Boundary routes are reachable through their attachment, not an incoming flow.
- Category, screen, script, and process ids exist on this instance.
- `name` is unique and uses only letters, numbers, spaces, and `- _ . '`. A 422 on the name means a duplicate or another character. The error text can say "alphanumeric".
- `description` is set on create (and on every PUT). Category defaults to looked-up **Uncategorized** unless another was requested; screen, script, and process ids exist on this instance.
- `status` is `ACTIVE`, `INACTIVE`, or `ARCHIVED`. There is no `PUBLISHED` status.
- After `POST /api/1.0/processes`, PUT the same `bpmn` when any task uses `user_group`, so `process_task_assignments` rows exist.
- A process created with `status` `ACTIVE` is live. Later Designer edits need Save and Publish.
- Run [static verification](verify.md) before saving/publishing. Do not start requests, complete tasks, deliver events, or exercise schedules unless asked; publishing an automatic-start process can enable request creation too.

## Edit an existing process (API)

1. Look up the id: `GET /api/1.0/processes?filter=<name>`. `GET /api/1.0/processes/{id}` omits `bpmn`.
2. Read the XML: `GET /api/1.0/processes/{id}/bpmn` returns the BPMN as raw XML.
3. Change the XML, then `PUT /api/1.0/processes/{id}` with `description` and the whole `bpmn` string. Without `description` core returns 422.

## Minimal example

`POST /api/1.0/processes` body. Replace `CATEGORY_ID` (looked-up **Uncategorized** by default). Always send a real `description`.

```json
{
  "name": "ApproveInvoice",
  "description": "Collect invoice details and route for approval",
  "status": "ACTIVE",
  "process_category_id": "CATEGORY_ID",
  "bpmn": "<full BPMN XML string>"
}
```

Copy the complete BPMN from [examples/start-form-end.bpmn.xml](examples/start-form-end.bpmn.xml) (replace `SCREEN_ID`). Do not invent a logic-only or shapes-only fragment — DI rules in [bpmn-document.md](bpmn-document.md); pools/lanes in [pools.md](pools.md).
