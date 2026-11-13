---
name: pm3-case-scheduler
description: >-
  ProcessMaker 3 case scheduler. Use for per-process scheduled case creation,
  recurring actions, CASE_SCHEDULER table, or cron-like rules in Designer.
---

# PM3 Case Scheduler

**Case scheduler** defines per-process rules to create cases on a schedule. Stored in `CASE_SCHEDULER` table.

## Asset schema

| Field | Required | Notes |
|-------|----------|-------|
| `SCH_UID` | auto | Scheduler entry id (primary key) |
| `PRO_UID` | yes | Target process |
| `TAS_UID` | yes | Target start task |
| `SCH_NAME` | yes | Rule name |
| `SCH_DEL_USER_UID` | yes | Delegate user UID |
| `SCH_DEL_USER_NAME` | yes | Delegate username |
| `SCH_DEL_USER_PASS` | yes | Delegate password (encrypted) |
| `USR_UID` | yes | Owner user UID |
| `SCH_STATE` | yes | `ACTIVE`, `INACTIVE`, or `PROCESSED` (default `ACTIVE`) |
| `SCH_LAST_STATE` | no | Previous state before toggle |
| `SCH_START_DATE` | yes | Start date (timestamp) |
| `SCH_START_TIME` | yes | Start time (timestamp) |
| `SCH_END_DATE` | no | End date |
| `SCH_TIME_NEXT_RUN` | yes | Next execution timestamp |
| `SCH_LAST_RUN_TIME` | no | Last run timestamp |
| `SCH_DAYS_PERFORM_TASK` | yes | Days to perform (char 5) |
| `SCH_WEEK_DAYS` | yes | Week days bitmask (char 14) |
| `SCH_START_DAY` | no | Start day of month |
| `SCH_MONTHS` | yes | Months bitmask (char 27) |
| `SCH_EVERY_DAYS` | no | Repeat every N days |
| `SCH_REPEAT_EVERY` | yes | Repeat interval string |
| `SCH_REPEAT_UNTIL` | yes | Repeat until string |
| `SCH_REPEAT_STOP_IF_RUNNING` | no | Stop repeat if previous run still active |
| `SCH_OPTION` | yes | Schedule option type |
| `CASE_SH_PLUGIN_UID` | no | Plugin scheduler extension |

API: `case-scheduler`. Designer submenu: Case Scheduler.

## Rules

- Requires system cron (`cron.php`) running on schedule
- `SCH_DEL_USER_UID` must have permission to start the process at `TAS_UID`
- Distinct from workspace **Task Scheduler** (Setup → scheduler categories) which is platform cron admin
- No `SCH_VARIABLES` column — initial case data set via scheduler plugin or post-creation trigger

## Links to other PM3 assets

| Asset | Relationship |
|-------|--------------|
| `pm3-process` | Target `PRO_UID` and `TAS_UID` |
| `pm3-trigger` | May set variables after scheduled case creation |

## Validation checklist

1. Cron job active on server
2. Delegate user has start permission on target task
3. `SCH_TIME_NEXT_RUN` calculated correctly for timezone
4. `TAS_UID` points to valid start task

## Common mistakes

- Scheduler defined but workspace cron not running
- Delegate user lacks process start permission
- Wrong `TAS_UID` (not a start task)
- Confusing per-process Case Scheduler with Setup → Task Scheduler
