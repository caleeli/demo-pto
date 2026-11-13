---
name: pm3-overview
description: >-
  Start here when working with ProcessMaker 3 solutions only (no PM4 target).
  Use for PM3 asset map, classic vs BPMN editors, build order, .pmx export or
  import within PM3, or choosing which pm3-* skill to load. For PM3→PM4
  migration phases, converter limits, process_package gaps, or post-import
  checklist, use pm3-pm4-migration instead.
---

# PM3 solution overview

ProcessMaker 3 solutions are built from **assets** linked inside a process. Load this skill first, then the asset-specific `pm3-*` skill.

**Platform scope:** ProcessMaker 3 (`release/3.9.11` and compatible 3.x).

## Asset map

| Asset | Skill | Role |
|-------|-------|------|
| Expressions | `pm3-expressions` | `@@var` in conditions, routing, templates |
| Variables | `pm3-variables` | Typed process/case variables |
| Dynaform | `pm3-dynaform` | Forms (xmlform/grid), controls, steps |
| Trigger | `pm3-trigger` | PHP webbot code, PMF functions, hook points |
| Process (BPMN) | `pm3-process` | BPMN workflow, gateways, events, tasks, steps, assignment |
| Process (Classic) | `pm3-classic-process` | Sequential/conditional routing in classic map |
| Input document | `pm3-input-document` | Required file uploads on task steps |
| Output document | `pm3-output-document` | PDF/DOC generation from case variables |
| Report table | `pm3-report-table` | Process-scoped `REPORT_TABLE` + `ADDITIONAL_TABLES` mirror |
| DB connection | `pm3-db-connection` | Per-process external database |
| Case scheduler | `pm3-case-scheduler` | Scheduled case creation per process |
| Email template | `pm3-email-template` | Mail templates and task notifications |
| Web entry | `pm3-web-entry` | Public/anonymous case start |
| Message type | `pm3-message-type` | BPMN message definitions |
| Case tracker | `pm3-case-tracker` | Public case status page |
| PM table | `pm3-pm-table` | Workspace-level `ADDITIONAL_TABLES` (`PRO_UID` null) |
| Actions by email | `pm3-actions-by-email` | Enterprise email approve/reject |
| Consolidated cases | `pm3-consolidated` | Enterprise cross-process case aggregation |
| Custom cases list | `pm3-custom-cases-list` | Admin inbox column/filter definitions |
| PM3 → PM4 migration | `pm3-pm4-migration` | Scope, `.pmx` / package import gaps, asset mapping (not a PM3 runtime asset) |

## Two process editors

| Editor | URL pattern | Routing model |
|--------|-------------|---------------|
| **BPMN Designer** | `designer?prj_uid={PRO_UID}` | BPMN 2.0 tasks, gateways, events |
| **Classic Process Map** | `processes_Map?PRO_UID={PRO_UID}` | Sequential tasks, explicit conditions on transitions |

A process uses one primary editor. Classic map processes may still reference BPMN assets in enterprise builds — confirm in Designer before changing routing.

## Designer building blocks (BPMN submenu)

Per-process menu (`processes.php`):

1. Dynaforms
2. Input Documents (Request Documents)
3. Output Documents
4. Triggers
5. Report Tables *(if `ReportTables::tableExist()`)*
6. DB Connections
7. Case Scheduler

Classic Process Map also exposes **Messages** in building-block panels (commented out in BPMN submenu on 3.9.11).

## Case data model

All solution assets share case variables:

- Process variables use `@@VARIABLE_NAME` syntax
- Dynaform fields write to case variables with the same names
- Triggers read/write `@@` variables in PHP (`PMScript` + `PMFSaveCurrentData()`)
- Output documents and email templates embed `@@` placeholders

Do not invent parallel variable namespaces. See `pm3-variables` and `pm3-expressions`.

## Build order

1. Load `pm3-expressions` — variable syntax used everywhere
2. Define `pm3-variables` (case data model)
3. Create `pm3-dynaform` and `pm3-trigger` assets
4. Assemble `pm3-process` (+ `pm3-classic-process` when source is classic map)
5. Add `pm3-input-document`, `pm3-output-document`, `pm3-report-table`, `pm3-db-connection` as needed
6. Add `pm3-email-template`, `pm3-case-scheduler`, `pm3-web-entry`, `pm3-message-type`, `pm3-case-tracker` when present
7. Review workspace assets: `pm3-pm-table`, `pm3-actions-by-email`, `pm3-consolidated`, `pm3-custom-cases-list`

## Export / import

Processes export as `.pmx` or `.pmx2` (XML `ProcessMaker-Project`, via `XmlExporter` / granular export). Granular export objects (`ExportObjects.php`):

| Key | Label |
|-----|-------|
| `PROCESSDEFINITION` | Process Definition |
| `ASSIGNMENTRULES` | Assignment Rules |
| `VARIABLES` | Variables |
| `DYNAFORMS` | Dynaforms |
| `INPUTDOCUMENTS` | Input Documents |
| `OUTPUTDOCUMENTS` | Output Documents |
| `TRIGGERS` | Triggers |
| `REPORTTABLES` | Report Tables |
| `TEMPLATES` | Templates |
| `FILES` | Files |
| `DBCONNECTION` | DB Connection |
| `PERMISSIONS` | Permissions |
| `SUPERVISORS` | Supervisors |
| `SUPERVISORSOBJECTS` | Supervisors Objects |

Plus plugin migrable objects when plugins register `bIsMigrable`.

## What does not get its own skill

| Item | How to handle |
|------|---------------|
| Process category | Organizational; reference in `pm3-process` |
| Assignment rules, permissions, supervisors | Fold into `pm3-process` |
| Subprocess, gateways, timer/email/message events | Fold into `pm3-process` ([gateways.md](../pm3-process/gateways.md), [events.md](../pm3-process/events.md)) |
| Script task, step triggers | Fold into `pm3-trigger` |
| Users, groups | Reference by UID in assignment |
| Calendar, dashboards | Platform config in Setup |
| Cases, tasks | Runtime artifacts |

## Migrating to PM4

Load [pm3-pm4-migration](../pm3-pm4-migration/SKILL.md) before treating a `.pmx` export as PM4-ready. BPMN Designer exports are the usual converter input; classic map processes need [pm3-classic-process](../pm3-classic-process/SKILL.md) and expect BPMN redesign on PM4. Triggers, PM Tables, web entry, and case data are not fully covered by Phase 1 accelerators — use [asset-mapping.md](../pm3-pm4-migration/asset-mapping.md) for per-asset follow-up.

## Common mistakes

- Editing BPMN routing on a classic-only process (or vice versa)
- Referencing `@@VAR` before the dynaform step that creates it
- Attaching a dynaform to a task without defining it as a **step**
- Exporting partial `.pmx` without triggers that populate report tables
- Confusing **PM Tables** (workspace) with **Report Tables** (process-scoped)
