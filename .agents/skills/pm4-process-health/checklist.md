# Health check checklist

For each item that fails, record: **asset** (process, screen id, script name, …), **category**, **severity**, **evidence**, **recommendation**.

Cross-check rules in the asset skill when unsure.

## BPMN flow

| Check | Category | Typical severity |
|-------|----------|------------------|
| Every path from Start reaches an End; no orphan nodes | Technical debt | High |
| Gateway exits have distinct conditions or an explicit default | Maintainability | Medium |
| No duplicate parallel branches doing the same work | Simplification | Medium |
| Script / connector / decision tasks are not duplicated serially when one step suffices | Simplification | Medium |
| Call Activity `calledElement` and `pm:config` match a real child ([pm4-sub-process](../pm4-sub-process/SKILL.md)) | Technical debt | Critical |
| Signal throw has a matching catch (or documented external consumer) | Technical debt | High |
| Task names are action-oriented, not defaults (`Form Task`, `Script Task`) | UX | Low |
| FlowGenie nodes use linked genie id, not legacy inline prompt config | Technical debt | High |
| PM Block call activities have inbound/outbound mapping documented | Maintainability | Medium |

## Screens

| Check | Category | Typical severity |
|-------|----------|------------------|
| Form tasks use `FORM` screens with Submit and required inputs | UX | High |
| No `DISPLAY` screen used for data entry | UX | High |
| Controls bind to `data.*` keys used downstream | Maintainability | Medium |
| Validations on required business fields | UX | Medium |
| Watchers are not doing heavy integration (prefer script task) | Performance | Medium |
| Nested screens and templates reduce duplication | Simplification | Low |
| Conditional visibility expressions reference existing `data.*` paths | Technical debt | Medium |
| Collection schema screens exist and match collection field usage | Maintainability | Medium |

## Scripts

| Check | Category | Typical severity |
|-------|----------|------------------|
| No hardcoded API keys, passwords, or tokens | Security | Critical |
| Secrets use `getenv()` / `{{env.*}}` | Security | High |
| Output merges into `data` with stable key names | Maintainability | Medium |
| Timeout and executor appropriate for work (avoid docker PHP for trivial JS) | Performance | Medium |
| No blocking external calls in screen watchers when async script task fits | Performance | High |
| Error handling returns meaningful failure (not silent empty merge) | Maintainability | Medium |
| Language matches the selected executor (`php` + PHP Executor; retain `php-nayra` only for an intentional specialized Nayra runner) | Technical debt | High |

## Data Connectors

| Check | Category | Typical severity |
|-------|----------|------------------|
| Auth uses instance config, not inline secrets | Security | Critical |
| Outbound mapping only sends required fields | Security | Medium |
| Response mapping targets existing `data.*` keys | Maintainability | Medium |
| Connector reused across tasks instead of duplicate resources | Simplification | Low |
| Retry / error paths defined for external dependency failures | Maintainability | Medium |

## Decision Tables

| Check | Category | Typical severity |
|-------|----------|------------------|
| Hit policy matches business rules ([pm4-decision-table](../pm4-decision-table/SKILL.md)) | Maintainability | High |
| Input/output mappings align with `data.*` model | Technical debt | Medium |
| Tabular rules not reimplemented in scripts elsewhere | Simplification | Medium |
| Rules readable — not overly wide single rows without comments | Maintainability | Low |

## PM Blocks

| Check | Category | Typical severity |
|-------|----------|------------------|
| Block version pinned intentionally; parent not on stale block | Technical debt | Medium |
| `inboundConfig` / `dataMapping` maps parent `data` clearly ([pm4-pm-block](../pm4-pm-block/SKILL.md)) | Maintainability | Medium |
| Block env vars (`PMB_*`) defined before use | Technical debt | High |
| Block scope is truly reusable — not one-off logic forced into a block | Simplification | Low |

## Variables (request data model)

| Check | Category | Typical severity |
|-------|----------|------------------|
| Single consistent naming convention (`camelCase` or `snake_case`) | Maintainability | Low |
| No orphan `data.*` keys (written never read, or read never set) | Technical debt | Medium |
| Mustache / FEEL paths exist before the step that uses them | Technical debt | High |
| Sensitive fields not logged in script `Log::` or email bodies | Security | High |
| PII fields marked or obfuscated where collections support it | Security | Medium |

## Process configuration

| Check | Category | Typical severity |
|-------|----------|------------------|
| `status` matches intent (`ACTIVE` only when ready for production) | Technical debt | High |
| Start event permissions match who may launch | Security | High |
| `manager_id` set when using process manager assignment | Technical debt | Medium |
| Form tasks with `user_group` have matching `process_task_assignments` after create | Technical debt | High |
| Notifications do not embed secrets or full PII | Security | Medium |
| Description and category support operators finding the process | Maintainability | Info |
| Unpublished Designer draft while production cases rely on the last published version | Technical debt | Medium |

## PM3 migration follow-up

Use when the process was imported from PM3 or an accelerated converter ([pm3-pm4-migration](../pm3-pm4-migration/SKILL.md)).

| Check | Category | Typical severity |
|-------|----------|------------------|
| Every FORM screen passes Preview (invalid `name` / subform flattening) | UX | Critical |
| Human tasks have real assignment, not default `user` / `requester` only | Technical debt | High |
| Sequence flow conditions tested against `data.*` | Maintainability | High |
| PM3 triggers accounted for (script tasks or watchers planned) | Technical debt | High |
| Migrated PHP Script assets use `php` + **PHP Executor**, never `php-nayra` | Technical debt | High |
| Email, web entry, PM Tables, SQL dropdowns listed as manual gaps | Maintainability | Medium |
