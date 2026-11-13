---
name: pm4-overview
description: >-
  Start here when building ProcessMaker 4 solutions. Use for new workflows,
  solution design, asset map, build order, or choosing which pm4-* skill to load.
---

# PM4 solution overview

ProcessMaker 4 solutions are built from **assets** linked together. Load this skill first, then the asset-specific skill.

## Asset map

| Asset | Skill | Status | Role |
|-------|-------|--------|------|
| Process | `pm4-process` | Implemented | BPMN graph, gateways, flows, non-signal events, pools and lanes; [document rules](../pm4-process/bpmn-document.md) / [gateways](../pm4-process/gateways.md) / [events](../pm4-process/events.md) / [pools](../pm4-process/pools.md) / [connector routing](../pm4-process/bpmn-elements.md#service-task-connectors) |
| Sub Process | `pm4-sub-process` | Implemented | Embedded/ad-hoc/transaction for phase grouping; Call Activity for reusable child request |
| Form Task / Manual Task | `pm4-form-manual-task` | Implemented | Typed `pm:screenRef`, assignment, due date on `bpmn:task` / `bpmn:manualTask` |
| Screen | `pm4-screen` | Implemented | FORM, DISPLAY, CONVERSATIONAL*, EMAIL* screens |
| Script | `pm4-script` | Implemented | Logic in script tasks and watchers |
| Decision table | `pm4-decision-table` | Implemented | Tabular business rules (Decision Task) |
| Signal | `pm4-signal` | Implemented | Cross-process throw/catch events |
| Expressions | `pm4-expressions` | Implemented | Field-specific scope, Mustache, PM's FEEL-labelled evaluator (pair with others) |
| Data connector | `pm4-data-connector` | Implemented | REST Data Connector, auth, node mapping |
| PM Block | `pm4-pm-block` | Implemented | Reusable subprocess package |
| Flow Genie | `pm4-flow-genie` | Implemented | AI agent step in a process |
| Send Email | `pm4-send-email` | Implemented | Send Email connector, recipients, EMAIL screen body |
| Actions by Email | `pm4-action-by-email` | Implemented | Call activity, response options, email reply, Form Task toggle |
| PDF Generator | `pm4-pdf-generator` | Implemented | Render a screen to a PDF file |
| Slack Notification | `pm4-slack` | Implemented | Post a message to a Slack channel |
| UiPath | `pm4-uipath` | Implemented | RPA bot run, input and response mapping |
| DocuSign | `pm4-docusign` | Implemented | Send an envelope from a template and wait for signing |
| IDP | `pm4-idp` | Implemented | Document extraction: file, document type, destination folder |
| Web Entry | `pm4-web-entry` | Implemented | Public or login-gated URL on a Start Event or Form Task |
| Collection | `pm4-collection` | Implemented | Persistent reusable data |
| Process health review | `pm4-process-health` | Implemented | Audit existing process; optimization report |
| Environment variable | `pm4-environment-variable` | Planned | Secrets and config |
| PM3 → PM4 migration | `pm3-pm4-migration` | Implemented | Scope, checklist, PM3 asset mapping (cross-cutting) |
| PM3 → PM4 triggers | `pm3-pm4-triggers` | Implemented | After pm-analyze: map webbots/hooks to script/watcher/assignment/email |
| PM3 → PM4 input documents | `pm3-pm4-input-documents` | Implemented | After pm-analyze: map INPUT_DOCUMENT steps to FileUpload |
| PM3 → PM4 output documents | `pm3-pm4-output-documents` | Implemented | After pm-analyze: HTML step → EMAIL screen + Send Email BPMN |

\* CONVERSATIONAL and EMAIL require their package on the target instance.

## Plan before building

When a request is vague ("build me a leave process"), do not start with BPMN. Interview the user first, then build. Do not invent assignees or ids.

| Ask | Why |
|-----|-----|
| Process name (unique: letters, numbers, spaces, `- _ . '`) | Required on create |
| Business outcome in one or two sentences | Becomes the description and guides screen and task wording |
| Each step in order: name, type (form task, manual task, script, service, none), and the data it collects or decides | Maps to BPMN tasks and screens |
| Each decision: condition field, and what happens on each branch. "None" if linear | Maps to gateways and sequence-flow conditions |
| Who works each human task | Sets `pm:assignment` and users or groups |
| Process manager, when any task uses `process_manager` | Process-level manager list |
| For each form, manual, or summary step: reuse an existing screen or create one (type FORM or DISPLAY, key fields) | Screens must exist before `pm:screenRef` |
| How the process starts (requester, Web Entry anonymous or authenticated, timer, signal), and whether an interstitial follows | Start Event, permissions, Web Entry ([pm4-web-entry](../pm4-web-entry/SKILL.md)) |

For assignees, look users and groups up on the instance, show a short list (id and name) and let the user pick. Do not hard-code ids the user has not seen. Do not create the process until the open questions are answered.

| Assignment | `pm:assignment` | How to choose |
|------------|-----------------|---------------|
| Requester | `requester` | No lookup |
| Process manager | `process_manager` | Look up the user and set the process manager list |
| Specific users | `user_group` with `pm:assignedUsers="1,2"` | Look up users |
| Groups | `user_group` with `pm:assignedGroups="3"` | Look up groups |

Details: [pm4-form-manual-task](../pm4-form-manual-task/SKILL.md).

## Build order

1. Plan with the user (above), then load `pm4-expressions` — data paths used everywhere
2. Define the request JSON keys and the field-specific paths screens, scripts, and BPMN evaluators will use
3. Create **screens** (`pm4-screen`) for user-facing steps
4. Create **collections** when you need persistent master data, and for every select, radio, or checkbox group with more than two options ([catalogs](../pm4-collection/catalogs.md)) — each needs create/read/update schema screens (separate from task screens)
5. Wire **Form/Manual Tasks** (`pm4-form-manual-task`) and create **scripts** or **decision tables** as needed
6. Assemble the **process** BPMN (`pm4-process`): short top-level phases via embedded subprocesses (`pm4-sub-process`); Call Activity / PM Blocks only for reuse
7. Add **signals**, **PM Blocks**, **Flow Genies**, or email connectors only when needed
8. Configure **environment variables** for secrets (skill planned; use Designer until `pm4-environment-variable` exists)

## Request data

Linked assets exchange a request payload, but their evaluation contexts can differ:

- Screens and scripts use their own field/runtime binding contracts; consult the asset skill rather than inventing a second data model.
- An API response may wrap the payload under `data`; that wrapper is not automatically passed into every expression.
- The reviewed PHP gateway evaluator receives root request keys directly: payload `{"approved":true}` uses `approved === true`, not `data.approved` unless an actual nested `data` object exists.
- Conditional Starts evaluate global-store data, while Intermediate/Boundary conditionals use existing instance data. Embedded subprocesses share the same request data. Cross-process Call Activities / messages have explicit data-transfer contracts, not automatically shared state.

Use [pm4-expressions](../pm4-expressions/SKILL.md) to establish the evaluator and input shape before writing conditions.

## What does not get its own skill

| Item | How to handle |
|------|---------------|
| Users, groups | Reference by id/username in process assignment |
| Categories | Organize assets; no runtime logic |
| Requests, tasks | Runtime; created when a process runs |

## Migrating from PM3

When the source is a PM3 `.pmx` or a converter `process_package`, import with [pm4-process](../pm4-process/SKILL.md), then [pm3-pm4-migration](../pm3-pm4-migration/SKILL.md) for gaps and checklist, then per-asset `pm4-*` skills (see order in that skill).

## Engineering vs solution skills

This repo also has skills for **editing ProcessMaker source code** (`laravel-pm4-patterns`, `vue2-pm4-patterns`, etc.). Use those when changing the platform; use `pm4-*` skills when **designing a customer solution**.

## Common mistakes

- Building BPMN before screens/scripts exist (orphan references)
- Configuring Form/Manual Task screen/assignment in `pm4-process` instead of `pm4-form-manual-task`
- Flattening large processes instead of grouping phases with embedded `bpmn:subProcess` ([pm4-sub-process/design.md](../pm4-sub-process/design.md))
- Using Call Activity only to hide nodes that belong in an embedded subprocess
- Configuring a Call Activity child, start event, or data in `pm4-process` instead of `pm4-sub-process`
- Putting integration logic in screen watchers when a script task is clearer
- Hardcoding secrets instead of environment variables
- Using `DISPLAY` screens for data entry
