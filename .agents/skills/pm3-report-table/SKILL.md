---
name: pm3-report-table
description: >-
  ProcessMaker 3 report tables. Use for process-scoped REPORT_TABLE metadata,
  denormalized case data, schema definition, or trigger-based population.
---

# PM3 Report Table

**Report tables** denormalize case data for reporting. Metadata lives in `REPORT_TABLE`; column schema is mirrored in `ADDITIONAL_TABLES` (same UID). Distinguished from workspace PM Tables (`PRO_UID` is null in `ADDITIONAL_TABLES`).

## Asset schema — REPORT_TABLE (primary)

| Field | Required | Notes |
|-------|----------|-------|
| `REP_TAB_UID` | auto | Primary key — same value as `ADD_TAB_UID` |
| `PRO_UID` | yes | Owning process |
| `REP_TAB_TITLE` | yes | Display title |
| `REP_TAB_NAME` | yes | Physical table name (prefixed `PMT_` on create) |
| `REP_TAB_TYPE` | yes | `NORMAL` (case-level) or `GRID` (grid rows) |
| `REP_TAB_CONNECTION` | yes | DB connection short name — default `'report'`; also `'workflow'` or external `DBS_UID` |
| `REP_TAB_STATUS` | yes | `ACTIVE` or `INACTIVE` |
| `REP_TAB_GRID` | no | Grid dynaform reference when `REP_TAB_TYPE = GRID` |

Exported as `REPORTTABLES` in `.pmx`. API alias: `report-Table` (case-sensitive in `api.ini`).

## Asset schema — ADDITIONAL_TABLES (schema mirror)

Same `REP_TAB_UID` stored as `ADD_TAB_UID`. Holds column definitions and physical DDL metadata:

| Field | Notes |
|-------|-------|
| `ADD_TAB_NAME` | Mirrors `REP_TAB_NAME` |
| `ADD_TAB_CLASS_NAME` | PHP class name for the table |
| `ADD_TAB_TYPE` | Mirrors `REP_TAB_TYPE` (`NORMAL` or `GRID`) |
| `ADD_TAB_GRID` | Mirrors `REP_TAB_GRID` |
| `DBS_UID` | Stores `REP_TAB_CONNECTION` value (connection short name, not always a `DBS_UID`) |
| `PRO_UID` | Set for process-scoped report tables |

## Rules

- Created from Designer → Report Tables (when `ReportTables::tableExist()`)
- Also creatable from classic map building blocks panel
- Default columns auto-added: `APP_UID`, `APP_NUMBER`, `APP_STATUS` (+ `ROW` for GRID type)
- Schema changes require migration care on existing data
- Data populated via **triggers** (`INSERT`/`UPDATE`) or report table regeneration
- Distinct from workspace **PM Tables** (`pm3-pm-table`) where `PRO_UID` is null

## Population pattern

Typical trigger after case step or routing:

```php
executeQuery(
    "INSERT INTO PMT_MYPROCESS (APP_UID, AMOUNT, STATUS)
     VALUES ('" . pmSqlEscape(@@APPLICATION) . "',
             '" . pmSqlEscape(@@AMOUNT) . "',
             '" . pmSqlEscape(@@STATUS) . "')",
    'report'
);
```

Use connection `'report'` (default report DB), `'workflow'`, or external `DBS_UID`. Use `@$VAR` prefix or `pmSqlEscape()` for case variable values in SQL.

## Links to other PM3 assets

| Asset | Relationship |
|-------|--------------|
| `pm3-trigger` | Populates rows |
| `pm3-variables` | Source data for columns |
| `pm3-pm-table` | Workspace tables (different scope) |
| `pm3-db-connection` | External `DBS_UID` as `REP_TAB_CONNECTION` |
| `pm3-consolidated` | `REP_TAB_UID` linked from `CASE_CONSOLIDATED` |

## Validation checklist

1. `REP_TAB_STATUS = ACTIVE` before relying on table
2. Table schema matches trigger INSERT columns
3. Primary key / `APP_UID` included for case correlation
4. `REP_TAB_CONNECTION` matches target database
5. Not confused with workspace PM Table

## Common mistakes

- Documenting only `ADDITIONAL_TABLES` and missing `REPORT_TABLE` metadata
- Passing `DBS_UID` to `executeQuery` when table uses `'report'` connection
- Report table without trigger to populate it
- Column type mismatch on INSERT
- Using report table for transactional case data (use case variables instead)
