---
name: pm3-pm4-subprocess
description: >-
  After pm-analyze on .pmx/.pmx2: PM3 SUB_PROCESS inventory, Variables Out/In,
  child import, Sub Process on parent. Not pm4-sub-process-only PM4 wiring or
  manage-requirements.
---

# PM3 → PM4 subprocess

PM3 stores a subprocess as a **SUBPROCESS task** plus a **`SUB_PROCESS` table row** (child process, child start task, sync/async, Variables Out/In). PM4 uses a **Sub Process** node (`bpmn:callActivity` + `pm:config`) on the parent; the child is a **separate published process**. There is **no** per-field mapping UI on the node — the engine copies the parent request `data` into the child and merges new or changed keys back when the child completes.

**`convert`** emits `bpmn:callActivity` when the child export is in the package ([ROADMAP](../../tools/pm-migrate/docs/ROADMAP.md): **Partial**). Place `{child_pro_uid}.pmx2` next to the parent export or pass `--subprocess-pmx2 <path>` (repeatable). Without the child file, convert still uses Call Activity but sets `warnings` on the process — import will not link until the child is included.

**Prerequisite:** [pm-analyze](../pm-analyze/SKILL.md) **report** on the export (subprocess row is **Partial** — count + BPMN `SUB_PROCESS` activities). **Source:** [pm3-process](../pm3-process/SKILL.md) (subprocess on BPMN map). **PM4 wiring:** [pm4-sub-process](../pm4-sub-process/SKILL.md). **Parent:** [pm3-pm4-migration](../pm3-pm4-migration/SKILL.md).

## Tell the user (required after report or convert)

Whenever the export has subprocesses, **say this plainly** — do not imply `convert` alone finishes migration:

1. **Import and publish the child process** on PM4 (convert only bundles the child if `{child_pro_uid}.pmx2` was beside the parent or passed with `--subprocess-pmx2`; otherwise export/convert the child separately first).
2. **Import the parent process**, then **configure the Sub Process** on the main process in Modeler: select the **published** child process and the correct **Start Event** ([pm4-sub-process](../pm4-sub-process/SKILL.md)). Import may show a Call Activity shape but **`processId` / `calledElement` often still need binding** on the instance.
3. **Publish the parent** after Sub Process is saved; then run one case to validate Variables Out/In (merge-back).

**Order:** child import → child publish → parent import → Sub Process on parent → parent publish → test case.

Ask if unclear: *“Plan only (inventory + data strategy), or wire Sub Process on the imported parent?”*

Worked mapping rows: [examples.md](examples.md).

## Classify first

One row per **parent subprocess task** (`TAS_TYPE=SUBPROCESS` / BPMN `ACT_TYPE=SUB_PROCESS`). Read `subprocess` records in the `.pmx2` and the task row in `workflow`.

| If in PM3… | Migration handling |
|------------|-------------------|
| **Synchronous** subprocess (`SP_SYNCHRONOUS=1`) | **Wire** Call Activity on parent; parent token waits (same intent as PM3) — [pm4-sub-process](../pm4-sub-process/SKILL.md) |
| **Asynchronous** subprocess | **HITL** — not covered by this skill’s default workflow; PM4 Sub Process still waits on the call-activity token; redesign or keep sequential |
| Variables Out only (rename parent → child keys) | **Rename strategy** — align PM4 screen/control `name` / request keys on child, or [pm4-script](../pm4-script/SKILL.md) before child task |
| Variables In only (child → parent) | **Merge-back** — keys must exist on child `data` with the names the parent expects after merge |
| Out + In with 1:1 names | **Auto-friendly** after convert — verify in Preview / one case |
| Out + In with renames on both sides | **HITL** — document mapping table; test end-to-end |
| Multiple subprocess tasks on one parent | One plan row each; **child import order** by dependency |
| Subprocess-only child process | Convert/import **child first**, publish, then parent |

Do **not** hand-edit `process_package` JSON to invent `callActivity` / `pm:config`. Configure in Modeler (or BPMN PUT per [pm4-sub-process](../pm4-sub-process/SKILL.md)) after lookup on the target instance.

## Build order (always)

1. **Child process** — `convert` → import → [pm4-screen](../pm4-screen/SKILL.md) / assignment fixes → **Publish**.
2. **Parent process** — `convert` → import → place or fix sequence → **Sub Process** node → lookup child id + start event id → **Publish**.
3. **Gateway / routing after subprocess** — FEEL on exclusive flows ([pm4-expressions](../pm4-expressions/SKILL.md), [pm4-process](../pm4-process/SKILL.md)).

PM3 export: include **both** processes (granular `.pmx2` with subprocess definitions). If the report counts subprocesses but the parent package has no child process asset, export again or convert the child separately.

## Data: PM3 mapping vs PM4 behavior

| PM3 | PM4 |
|-----|-----|
| Variables Out (parent → child at invoke) | Full parent `data` copy; ensure child screens bind to the **keys that will exist** (rename fields or scripts if PM3 used different names) |
| Variables In (child → parent on complete) | Merge-back of new/changed keys; no `SUB_PROCESS` table |
| `@@` / `@#` in mapping UI | Request `data` keys without prefixes in PM4 |

When PM3 renamed on Out (e.g. `supplierName` → `supplierUnderReview`), either use the **child key names** on PM4 child screens or copy/rename in a **Script Task** on the child before the form. When In mapped `@@complianceApproved` → `@@complianceApproved`, confirm the child form writes `complianceApproved` and the parent reads it after merge.

## Workflow

1. `report` on parent export (and child if separate) — note **Subprocesses** count and manual details.
2. Inventory each subprocess task (table per row): parent task, child process name/`PRO_UID`, child start `TAS_UID`, sync flag, Out/In map, disposition (`wire` / `rename` / `hitl` / `exclude`). Read `subprocess` records in the `.pmx2` and `SUB_PROCESS` / `TASK` rows in workflow.
3. `convert` **child** → import → publish.
4. `convert` **parent** → import → **configure Sub Process on the main process** (child + start event in Modeler) → publish.
5. After import, confirm the diagram is **flat** (no participant pool); if a pool appears, re-import from a package built with current `pm-analyze convert`.
6. Run one parent case: data visible on child task; after child completes, parent has merged fields and routing works.

## Verify

- [ ] Report or XML lists every `subprocess` row and matching parent `SUBPROCESS` task
- [ ] Plan row per subprocess; async or unsupported patterns marked HITL
- [ ] Child process **published** before parent Sub Process is configured
- [ ] `calledElement` / `pm:config` match looked-up child id and plain Start Event ([verify.md](../pm4-sub-process/verify.md))
- [ ] Child case shows context fields (Out / rename strategy validated)
- [ ] Parent case after child shows In fields (merge-back validated)
- [ ] Parent does not advance past subprocess until child completes (sync)
- [ ] Post-subprocess gateway conditions use valid **FEEL** on merged keys
- [ ] Requirements catalogs: subprocess FRs with `manual` / `hitl` disposition addressed in gap notes

## HITL — stop and ask

Do **not** guess when: asynchronous subprocess must stay fire-and-forget; grid In (`SP_GRID_IN`); multiple children from one call; credentials or external callbacks inside the child; parent must receive **subset** of child data with strict compliance rules. Ask what PM3 actually did, then update the plan.

## Wrong skill?

| Need | Skill |
|------|--------|
| Configure Call Activity on an existing PM4 parent (no PM3 source) | [pm4-sub-process](../pm4-sub-process/SKILL.md) |
| Parent BPMN only (gateways, flows, import) | [pm4-process](../pm4-process/SKILL.md) |
| Create/edit PM3 subprocess in Designer | [pm3-process](../pm3-process/SKILL.md) |
| report / convert CLI | [pm-analyze](../pm-analyze/SKILL.md) |
| FR/NFR catalogs, dispositions | [manage-requirements](../../.agents/skills/manage-requirements/SKILL.md) |
| Triggers on subprocess tasks | [pm3-pm4-triggers](../pm3-pm4-triggers/SKILL.md) |
