# `trigger-plan.json`

One **merged script** per pre-form / post-form **bucket**. Extra `placements` rows only when several `sequenceFlow`s need the same bucket (rework).

`pm-analyze` does not generate this file. Planned CLI: `report-step-triggers`. Phase 3 is manual.

## Shape

```json
{
  "placements": [
    {
      "form_task_id": "node_5",
      "slot": "before",
      "anchor_flow_id": "flow_1_5",
      "script": {
        "title": "Task — pre form v1.0",
        "language": "php",
        "code": "<?php\n// Before Screen Trigger: …\nreturn [];\n"
      }
    }
  ]
}
```

| Field | Rule |
|-------|------|
| `form_task_id` | `bpmn:task` id from **convert** `attributes.bpmn` |
| `slot` | `before` (pre-form) or `after` (post-form) |
| `anchor_flow_id` | `bpmn:sequenceFlow` id (e.g. `flow_*`), not a node id. Required when several flows reach the same task |
| `script.language` | Omit or use `php` for migrated PHP; it must resolve to **PHP Executor**. `php-nayra` is banned. Use `javascript` or `python` only intentionally and when that executor exists on the target instance |
| `script.code` | Rewritten intent. Never pasted PM3 PHP. `return [];` at the end of a PHP script |
| `script.uuid` | Set when two placements must share one Script Task (rework). Omit on a single linear placement |

Linear example: [attach-scripts-plan.json](../../tools/pm-migrate/testdata/attach-scripts-plan.json).

## Rework / second incoming path

Two `before` placements, **same** `script.uuid` and `code`. First splices; second **joins** the rework flow — one Script Task, one Script asset. Reuse the first title; skip a duplicate `(rework)` box.

Example: [attach-scripts-merge-before-plan.json](../../tools/pm-migrate/testdata/attach-scripts-merge-before-plan.json).

Phase 5: several placements with the same `script.uuid` still count as **one** `bpmn:scriptTask`.

## Commands

```bash
PM="<skills-checkout>/skills/pm-analyze/pm-analyze"   # make -C <repo> pm-analyze

"$PM" convert --pmx2 <export.pmx> --out process.json --version 1.0
"$PM" attach-scripts --package process.json --plan trigger-plan.json --out process-with-scripts.json
```

Import `process-with-scripts.json` → [pm4-process](../pm4-process/SKILL.md). Fix peel-offs in Modeler. **Fallback:** Modeler splice only if the CLI cannot run.

**Banned:** hand-editing `process_package` BPMN/DI/dependents (breaks import and canvas).

## Executor mismatches

Every migrated PHP Script export must have `attributes.language: "php"` and an `executor` dependent with `fallbackMatches.title: "PHP Executor"`. `php-nayra` is the separate PHP (µService) Nayra runner and must not appear in a PM3→PM4 trigger plan.

If Builder crashes on `scriptExecutor.language`: language/executor mismatch — re-run `attach-scripts` with `php` or fix the script in Designer.

Already imported JS scripts on a PHP-only instance: set Language **PHP**, save, or delete and re-import the package.
