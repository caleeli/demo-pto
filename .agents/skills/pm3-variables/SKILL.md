---
name: pm3-variables
description: >-
  ProcessMaker 3 process and case variables. Use for @@ prefix, variable types,
  naming, scope, export in .pmx, or mapping fields from dynaforms and triggers.
---

# PM3 variables

Process variables define the case data model. Stored in `PROCESS_VARIABLES` table. Referenced everywhere as `@@VARIABLE_NAME`.

## When to use

- Define or audit the data model for a process
- Add variables before dynaforms, triggers, or output documents reference them
- Export/import variable definitions in `.pmx` (`VARIABLES` export key)
- Debug missing or empty `@@` values in cases

## Asset schema

| Field | Required | Notes |
|-------|----------|-------|
| `VAR_UID` | auto | Unique id |
| `PRJ_UID` | yes | BPMN project UID (not `PRO_UID`) |
| `PRO_ID` | auto | Numeric process id |
| `VAR_NAME` | yes | Name used in `@@VAR_NAME` |
| `VAR_FIELD_TYPE` | yes | `string`, `integer`, `float`, `boolean`, `datetime`, `grid`, `array`, `file`, `multiplefile`, `object` |
| `VAR_FIELD_TYPE_ID` | no | Internal type id |
| `VAR_FIELD_SIZE` | no | Max length for strings |
| `VAR_LABEL` | no | Display label |
| `VAR_DBCONNECTION` | no | DB connection for SQL-based variable |
| `VAR_SQL` | no | SQL for external/suggest data |
| `VAR_NULL` | no | Allow null |
| `VAR_DEFAULT` | no | Default value |
| `VAR_ACCEPTED_VALUES` | no | Allowed values constraint |
| `INP_DOC_UID` | no | Linked input document (file-type variables) |

API: `process-variable` (`Variable.php`).

## Rules

- Variable names must be unique within a process
- Dynaform field element names should align with `VAR_NAME` when data must persist
- File-type variables may link to `INP_DOC_UID`
- Variables exist at **case** scope — all tasks in a case share the same values
- BPMN processes use `PRJ_UID`; classic dynaforms/triggers use `PRO_UID` on related assets

## Variable lifecycle

1. **Defined** in process Variables (Designer or import)
2. **Written** by dynaform submit, trigger (`@@VAR = ...` + `PMFSaveCurrentData()`), or web service
3. **Read** by conditions, output docs, email templates, subsequent triggers
4. **Persisted** in case record until case completes or is deleted

## Links to other PM3 assets

| Asset | Relationship |
|-------|--------------|
| `pm3-dynaform` | Field element name maps to case variable |
| `pm3-trigger` | `@@VAR` read/write; `PMFSaveCurrentData()` |
| `pm3-expressions` | `@@VAR` reference syntax |
| `pm3-output-document` | `@@VAR` placeholders in templates |
| `pm3-report-table` | Columns populated from case variables via triggers |
| `pm3-input-document` | `INP_DOC_UID` link for file variables |

## Validation checklist

1. Every `@@` reference in the process has a matching `VAR_NAME` definition
2. Types match usage (numeric comparisons need integer/float, not string)
3. `PRJ_UID` matches the BPMN project when using BPMN Designer
4. No duplicate `VAR_NAME` within the process

## Common mistakes

- Dynaform field name differs from `VAR_NAME` → data not where triggers expect
- Using `PRO_UID` instead of `PRJ_UID` when working with BPMN process variables
- Assuming variables reset between tasks (they persist for the case)
- Modifying variables in trigger without `PMFSaveCurrentData()`
