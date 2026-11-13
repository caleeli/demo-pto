---
name: pm3-consolidated
description: >-
  ProcessMaker 3 consolidated cases. Use for enterprise cross-process case
  aggregation, CASE_CONSOLIDATED table, or Consolidated API.
---

# PM3 Consolidated Cases

**Consolidated cases** is an enterprise feature that aggregates cases across multiple processes into a unified inbox view.

## When to use

- Enterprise license with consolidated cases feature enabled
- Task-level consolidation configured in `CASE_CONSOLIDATED`
- Reporting across related cases from different processes

API: `[alias: consolidated]` → `ProcessMaker\Services\Api\Consolidated`.

## Asset schema — CASE_CONSOLIDATED

| Field | Required | Notes |
|-------|----------|-------|
| `TAS_UID` | yes | Primary key — consolidated task |
| `DYN_UID` | yes | Dynaform for consolidated view |
| `REP_TAB_UID` | yes | Report table for consolidated data |
| `CON_STATUS` | yes | `ACTIVE` or inactive |

One row per consolidated task. `ConsolidatedCases::buildReportVariables()` builds report variables from the linked report table.

## License gate

The Process Map editor injects `consolidated: "1"` into `processmap.js` options only when the enterprise license feature is active (`processes_Map.php`). This is a license/UI flag, not a per-process database field.

## Rules

- Enterprise-only feature — verify license before designing
- Consolidation is configured per **task** (`TAS_UID`), not per process
- Requires linked dynaform and report table on the consolidated task
- `CON_STATUS = ACTIVE` required for consolidation to apply
- Report variables may differ from standard process variables

## Links to other PM3 assets

| Asset | Relationship |
|-------|--------------|
| `pm3-classic-process` | Consolidated tasks configured in map |
| `pm3-process` | Parent process and task |
| `pm3-dynaform` | `DYN_UID` for consolidated view |
| `pm3-report-table` | `REP_TAB_UID` data source |
| `pm3-variables` | Report variable mapping |

## Validation checklist

1. Enterprise license active
2. `CASE_CONSOLIDATED` row exists for target `TAS_UID`
3. `DYN_UID` and `REP_TAB_UID` reference valid assets
4. Report variables tested in consolidated inbox view

## Common mistakes

- Treating `consolidated` JS flag as a per-process setting (it reflects license)
- Enabling consolidation without `REP_TAB_UID` report table
- Expecting consolidated view on non-enterprise install
- Missing report variable definitions for consolidated display
