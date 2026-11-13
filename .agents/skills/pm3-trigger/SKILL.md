---
name: pm3-trigger
description: >-
  ProcessMaker 3 triggers and PHP webbot code. Use for TRI_WEBBOT, PMF functions,
  @@variable syntax, before/after step hooks, before-assignment triggers, derivation, or
  script tasks on BPMN canvas.
---

# PM3 Trigger

A **trigger** is reusable PHP code (`TRI_WEBBOT`) attached to process events. Triggers are the primary automation mechanism in PM3.

## When to use

| Hook | PMScript constant | When it runs |
|------|-------------------|--------------|
| Before dynaform step | `BEFORE_DYNAFORM` | Before dynaform step executes |
| After dynaform step | `AFTER_DYNAFORM` | After dynaform submit |
| Before/after input document | `BEFORE_INPUT_DOCUMENT` / `AFTER_INPUT_DOCUMENT` | Around input doc step |
| Before/after output document | `BEFORE_OUTPUT_DOCUMENT` / `AFTER_OUTPUT_DOCUMENT` | Around output doc step |
| Before assignment | `BEFORE_ASSIGNMENT` | Before task assignment |
| Before/after routing | `BEFORE_ROUTING` / `AFTER_ROUTING` | Around case derivation |
| Condition | `CONDITION` | Classic map / routing condition |
| Script task | `SCRIPT_TASK` | BPMN script task execution |

Use triggers for calculations, external API calls, report table population, and variable transforms.

## Asset schema

| Field | Required | Notes |
|-------|----------|-------|
| `TRI_UID` | auto | Unique id |
| `PRO_UID` | yes | Owning process |
| `TRI_TITLE` | yes | Display name |
| `TRI_DESCRIPTION` | no | Documentation |
| `TRI_WEBBOT` | yes | PHP source code (stored in `TRIGGERS` table) |

Exported as `TRIGGERS` in `.pmx`.

## Variable syntax in trigger code

Triggers use `@@VARIABLE` syntax. The `PMScript` engine translates `@@` references to `$this->aFields[...]` at runtime.

```php
// Read and assign case variables
@@TOTAL = floatval(@@AMOUNT) * 1.15;
@@TAX = @@TOTAL - floatval(@@AMOUNT);

// Persist changes to the current case
PMFSaveCurrentData();
```

Type modifiers in expressions:

| Prefix | Meaning |
|--------|---------|
| `@@` | String (default) |
| `@%` | Integer |
| `@#` | Float |
| `@?` | URL-encoded |
| `@$` | SQL-escaped |

## PMF functions

PM3 exposes global functions (not class methods). Common ones:

| Function | Purpose |
|----------|---------|
| `PMFSaveCurrentData()` | Save `$oPMScript->aFields` to current case |
| `PMFSendVariables($caseId, $variables)` | Send variables to a specific case |
| `executeQuery($sql, $dbConnectionUID, $parameters)` | Run SQL; `$dbConnectionUID` is `DBS_UID` or `'workflow'` |
| `PMFGenerateOutputDocument($outputID, ...)` | Generate output document |
| `PMFDerivateCase($caseId, $delIndex, ...)` | Route case to next task |
| `PMFNewCase($processId, $userId, $taskId, $variables)` | Start new case |

Full list: `workflow/engine/classes/class.pmFunctions.php`.

## Script tasks and step triggers

- BPMN **script tasks** bind to a trigger (`ScriptTask` API, `ACT_UID` link)
- **Step-level triggers** (`Activity/Step/Trigger` API) attach to specific dynaform/input/output steps

## Links to other PM3 assets

| Asset | Relationship |
|-------|--------------|
| `pm3-variables` | `@@` names match process variables |
| `pm3-expressions` | `@@` syntax and type modifiers |
| `pm3-dynaform` | Before/after step on dynaform submit |
| `pm3-process` | Routing/assignment hook attachment |
| `pm3-db-connection` | `executeQuery` uses `DBS_UID` |
| `pm3-report-table` | Triggers INSERT/UPDATE report table rows |
| `pm3-output-document` | `PMFGenerateOutputDocument()` |

## Validation checklist

1. Call `PMFSaveCurrentData()` after modifying `@@` variables in the current case
2. Use `@$` prefix or `pmSqlEscape()` for SQL values from case variables
3. `executeQuery` second parameter is `DBS_UID` (from `DB_SOURCE` table), not a display name
4. Trigger attached to correct hook point (before vs after step)
5. Code passes `CodeScanner` security review on import

## Common mistakes

- Assigning `@@VAR` without calling `PMFSaveCurrentData()`
- Using `$aVars` directly in hand-written code (use `@@` syntax instead)
- Before-step trigger expecting data from current dynaform (use after-step)
- Passing connection display name to `executeQuery` instead of `DBS_UID`
- Using `PMF::method()` OOP syntax (PMF functions are global functions)

Example: [examples/trigger-minimal.webbot](examples/trigger-minimal.webbot)
