---
name: pm3-db-connection
description: >-
  ProcessMaker 3 database connections. Use for per-process external DB config in
  DB_SOURCE table, executeQuery with DBS_UID, SQL suggest in dynaforms, or report
  table external queries.
---

# PM3 DB Connection

**DB connections** define external database access. Stored in `DB_SOURCE` table, scoped per process (`PRO_UID`).

## Asset schema

| Field | Required | Notes |
|-------|----------|-------|
| `DBS_UID` | auto | Connection unique id — used in `executeQuery()` |
| `PRO_UID` | yes | Owning process |
| `DBS_TYPE` | yes | `mysql`, `mssql`, `oracle`, `postgres`, etc. |
| `DBS_SERVER` | yes | Host |
| `DBS_DATABASE_NAME` | yes | Database name |
| `DBS_USERNAME` | yes | Credentials |
| `DBS_PASSWORD` | yes | Encrypted storage |
| `DBS_PORT` | no | Port number |
| `DBS_ENCODE` | no | Character encoding |
| `DBS_CONNECTION_TYPE` | no | Connection type |

Exported as `DBCONNECTION` in `.pmx`. API alias: `data-base-connections` (`api.ini`); REST paths use `database-connection` / `database-connections`.

Display name shown in UI is built from server/database info, not a separate alias column.

## Usage

| Consumer | How |
|----------|-----|
| Trigger | `executeQuery($sql, $dbsUid)` — second param is `DBS_UID` or `'workflow'` |
| Dynaform SQL suggest | Control references `DBS_UID` |
| PM Table | `DBS_UID` column in `ADDITIONAL_TABLES` (connection short name or external UID) |
| Report table | `REP_TAB_CONNECTION` in `REPORT_TABLE` (default `'report'`, not `DBS_UID`) |
| Process variable | `VAR_DBCONNECTION` field |

`DbConnections::loadAdditionalConnections()` registers each `DBS_UID` as a Laravel database connection at runtime.

## Rules

- `DBS_UID` is the connection identifier in `executeQuery`, not a human-readable alias
- Report tables default to `'report'` connection — do not assume `DBS_UID` unless explicitly configured
- Credentials stored encrypted — re-enter on import to new environment
- Default connection `'workflow'` refers to the PM3 workspace database
- Sanitize all dynamic SQL — use `@$VAR` or `pmSqlEscape()` for case variable values
- SQL blacklist validation runs on all `executeQuery` statements

## Links to other PM3 assets

| Asset | Relationship |
|-------|--------------|
| `pm3-trigger` | Primary consumer via `executeQuery()` |
| `pm3-dynaform` | SQL suggest controls |
| `pm3-variables` | `VAR_DBCONNECTION` + `VAR_SQL` |
| `pm3-report-table` | `REP_TAB_CONNECTION` may reference `DBS_UID` |
| `pm3-pm-table` | `DBS_UID` column on `ADDITIONAL_TABLES` |

## Validation checklist

1. `DBS_UID` in trigger/dynaform matches a defined connection
2. Credentials valid in target environment after import
3. Firewall/network allows PM server → DB host
4. SQL queries use `@$` escaping for case variable interpolation

## Common mistakes

- Passing display name instead of `DBS_UID` to `executeQuery`
- Using `DBS_UID` for report table when connection is `'report'`
- Importing `.pmx` without updating `DBS_PASSWORD`
- SQL injection via unescaped `@@` variables in dynamic queries
- Using process connection UID for workspace-level PM Table
