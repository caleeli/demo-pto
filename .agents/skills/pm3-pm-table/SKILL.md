---
name: pm3-pm-table
description: >-
  ProcessMaker 3 PM Tables (Additional Tables). Use for workspace-level tables,
  manual schema and data, ADD_TAB_TYPE table, or Setup PM Tables configuration.
---

# PM3 PM Table

**PM Tables** are workspace-level rows in `ADDITIONAL_TABLES` where `PRO_UID` is null. Distinct from process-scoped **report tables** (`REPORT_TABLE` + `ADDITIONAL_TABLES` with `PRO_UID` set).

## Asset schema — ADDITIONAL_TABLES

| Field | Required | Notes |
|-------|----------|-------|
| `ADD_TAB_UID` | auto | Table unique id |
| `PRO_UID` | no | Null for workspace PM Tables |
| `ADD_TAB_NAME` | yes | Physical table name |
| `ADD_TAB_CLASS_NAME` | yes | PHP class name derived from table name |
| `ADD_TAB_DESCRIPTION` | no | Description |
| `ADD_TAB_TYPE` | no | `NORMAL` or `GRID` (UI convention, no DB validator) |
| `ADD_TAB_GRID` | no | Grid reference when type is GRID |
| `ADD_TAB_TAG` | no | Display label |
| `DBS_UID` | no | Connection short name (`'workflow'` default) or external `DBS_UID` |
| `ADD_TAB_PLG_UID` | no | Plugin association |
| `ADD_TAB_OFFLINE` | yes | Offline sync flag |

Managed from Setup → PM Tables (`../pmTables`). Can also import/export `.pmt` files.

## Rules

- Workspace PM Tables are shared across processes in the workspace
- Manual schema definition — add columns in PM Tables editor
- Data editable in grid UI or via triggers/`executeQuery()`
- Used as suggest/autocomplete data source in dynaforms
- Process-scoped tables with `PRO_UID` set are **report tables** — see `pm3-report-table` (`REPORT_TABLE` is the primary metadata table)

## Common operations

| Operation | Where |
|-----------|-------|
| Create table | Setup → PM Tables → New PM Table |
| Edit schema | PM Tables editor |
| Import data | PM Tables → Import |
| Export | PM Tables → Export `.pmt` |
| Query in trigger | `executeQuery($sql, 'workflow')` against table name |

## Links to other PM3 assets

| Asset | Relationship |
|-------|--------------|
| `pm3-dynaform` | Suggest control data source |
| `pm3-trigger` | Read/write table data |
| `pm3-report-table` | Process-scoped sibling (uses `REPORT_TABLE`) |
| `pm3-db-connection` | External `DBS_UID` for table connection |

## Validation checklist

1. `PRO_UID` is null (workspace scope, not process-scoped)
2. Column types appropriate for data
3. Triggers/dynaforms reference correct table name
4. Not confused with process report table

## Common mistakes

- Creating report table when workspace lookup table needed
- Confusing `ADDITIONAL_TABLES` alone with report table metadata (`REPORT_TABLE` required)
- Table name collision across workspace
- Missing indexes on frequently queried columns
- Direct manual data edit conflicting with trigger population
