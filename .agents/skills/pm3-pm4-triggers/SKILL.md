---
name: pm3-pm4-triggers
description: >-
  After pm-analyze, map non-email PM3 steptriggers/webbots and screen/assignment
  hooks to PM4 scripts, watchers, assignments, or redirects; build
  trigger-plan.json and run attach-scripts. Mail peel-offs use
  pm3-pm4-email-notifications. Not BPMN editing, convert-only, or catalogs.
---

# PM3 → PM4 triggers

`convert` imports structure and screens; **PHP webbots do not run**. Rewrite intent (never paste PHP) per [pm4-script](../pm4-script/SKILL.md). Classify each hook first — most are not Script Tasks.

**Prerequisite:** [pm-analyze](../pm-analyze/SKILL.md) **report** on the same export (counts and rewrite hints — not per-step attachments). **Parent:** [pm3-pm4-migration](../pm3-pm4-migration/SKILL.md).

Ask if unclear: *“Plan only, or implement with attach-scripts?”*

## Classify first

For every `STEPTRIGGERS` row pick **one** destination. Do not write `trigger-plan.json` until every row has one.

| If the webbot… | Destination | Next |
|----------------|-------------|------|
| Changes assignment / groups / users | assignment | [pm4-form-manual-task](../pm4-form-manual-task/SKILL.md) |
| Reacts to a field while the user edits | watcher / computed | [pm4-screen](../pm4-screen/SKILL.md) |
| Sends mail (`PMFSendMessage`, plugin mail helpers) | email peel-off | [pm3-pm4-email-notifications](../pm3-pm4-email-notifications/SKILL.md) |
| Redirects (`header(`) | redirect | [pm4-form-manual-task](../pm4-form-manual-task/SKILL.md) |
| Empty / no-op | exclude | Document only |
| Before Screen / dynaform BEFORE | **pre-form** | Plan JSON `slot: before` |
| Dynaform AFTER or before routing (`step_uid=-2`) | **post-form** | Plan JSON `slot: after` |
| SQL / REST on a screen hook | still **pre-form** / **post-form** unless the lookup belongs on the screen | [pm4-script](../pm4-script/SKILL.md), [pm4-data-connector](../pm4-data-connector/SKILL.md) |

Hook constants and what stays out of the plan: [decision-tree.md](decision-tree.md). Worked rows: [examples.md](examples.md). Schema: [trigger-plan.md](trigger-plan.md).

## Workflow

1. `report` → `convert` → `process.json` (BPMN ids for the plan).
2. Inventory `STEPTRIGGERS` + `TRIGGERS` + `STEPS` in the `.pmx`/`.pmx2` XML — `pm-analyze` does not parse `steptriggers` yet. One row per attachment.
3. Map `tas_uid` → PM4 Form Task id. Classify each row with the table above.
4. Merge **pre-form** / **post-form** buckets in `ST_POSITION` order → `trigger-plan.json`. Peel-offs stay out of the plan. Run `report-email-notifications` for mail peel-offs before deciding task notification vs Send Email vs HITL.
5. **Implement** only if asked: `attach-scripts` → import **once** → peel-offs in Modeler → [pm4-process](../pm4-process/SKILL.md) → Publish.

Target: `… → PreScript → FormTask → PostScript → …` on the **same** sequence (no parallel branch, no extra swimlane).

Optional: import `convert` output only to smoke-test structure; re-import `process-with-scripts.json` before UAT. Do not keep two packages.

## Verify each phase

- [ ] **0** — export path and plan vs implement stated
- [ ] **1** — one inventory row per `STEPTRIGGERS` attachment
- [ ] **2** — every row is `pre-form`, `post-form`, peel-off, or exclude
- [ ] **3** — one merged script per bucket; peel-offs listed, not in JSON; migrated PHP uses `php`, never `php-nayra`
- [ ] **4** (implement) — no hand-edit of package BPMN / DI / dependents
- [ ] **5** — `bpmn:scriptTask` count = merge buckets (rework: same `script.uuid` → one task)

## Implement

**Banned:** hand-editing `process_package` BPMN/DI/dependents. **Banned:** automatic PHP translation.

```bash
PM="<skills-checkout>/skills/pm-analyze/pm-analyze"

"$PM" attach-scripts --package process.json --plan trigger-plan.json --out process-with-scripts.json
```

Default migrated PHP `language`: `php`, bound through the **PHP Executor** dependent. **Never use `php-nayra`** for PM3→PM4 Script Tasks; it selects a different runner and breaks the migration contract. Use `javascript` or `python` only intentionally and only if that executor exists on the target instance. Fallback: Modeler splice only if the CLI cannot run. Commands and executor mismatches: [trigger-plan.md](trigger-plan.md).

**Not done:** convert-only package (zero `scriptTask`); markdown-only folder; parallel gateway “for triggers”.

## Wrong skill?

| Need | Skill |
|------|--------|
| report / convert / attach-scripts CLI | [pm-analyze](../pm-analyze/SKILL.md) |
| Import rules, checklist | [pm3-pm4-migration](../pm3-pm4-migration/SKILL.md) |
| PM3 hook reference | [pm3-trigger](../pm3-trigger/SKILL.md) |
| Trigger/native email migration | [pm3-pm4-email-notifications](../pm3-pm4-email-notifications/SKILL.md) |
| FR/NFR catalogs | [manage-requirements](../../.agents/skills/manage-requirements/SKILL.md) |

## HITL — stop and ask

Do **not** invent a rewrite when: the PHP is opaque; the webbot has credentials or secrets; assignment rules a Form Task cannot express. Ask what the trigger is supposed to do, then classify again.
