---
name: pm3-pm4-migration
description: >-
  Orchestrate PM3→PM4 on .pmx/.pmx2: analyze, catalogs/exclusions, approved
  convert, gap lists, post-import checklist (Preview blank, assignment).
  Catalog-only is manage-requirements. After analyze: pm3-pm4-triggers,
  pm3-pm4-email-notifications, pm3-pm4-subprocess, pm3-pm4-input-documents,
  pm3-pm4-output-documents, pm3-pm4-web-entry. Not pm4-process import,
  process-health, or overview.
---

# PM3 → PM4 migration

Cross-cutting guide for **PM3 → PM4** (not database/server “migration”). Load when the user names **PM3**, **PM4**, **`.pmx`**, **converter**, or **gaps after a PM3 import**. Then load the **target** `pm4-*` (or `pm3-*` for source analysis) skill per asset.

**Diagnostics vs fixes:** migration scoping, gap lists, and post-import checklist → this skill (see [Preview blank (triage)](#preview-blank-triage)). **Import/publish/export in PM4** → [pm4-process](../pm4-process/SKILL.md). **One named screen** (control, layout, Preview on that screen) → [pm4-screen](../pm4-screen/SKILL.md). **BPMN node or flow on an existing PM4 process** → [pm4-process](../pm4-process/SKILL.md).

References: [asset-mapping.md](asset-mapping.md) (PM3 asset → PM4 skill), [import-package-rules.md](import-package-rules.md) (package JSON and BPMN quirks after automated conversion).

**In-repo converter:** [pm-analyze](../pm-analyze/SKILL.md) (`tools/pm-migrate`) runs `report` / `convert` on `.pmx` / `.pmx2`. Treat converter output as a **starting point**, not production-ready parity. Other third-party converters may still appear; same rule.

## Starting a migration

For a request to migrate a named local `.pmx` or `.pmx2` export, start with
analysis—not conversion. The user's direct request authorizes the read-only
report stage; do not ask a redundant confirmation before running it.

1. Resolve the export path. If no path is supplied or it is ambiguous, ask for
   a path and run nothing. Archives and non-XML inputs are unsupported: explain
   the limitation and run nothing. For a named local `.pmx`/`.pmx2` path that
   does not exist, still run the read-only report so its actionable file error
   is shown; then stop.
2. Load [pm-analyze](../pm-analyze/SKILL.md), then run `report --json` for the
   named export.
3. Render the complete user-facing report through pm-analyze's
   [Successful report response](../pm-analyze/SKILL.md#successful-report-response)
   or [Report failure](../pm-analyze/SKILL.md#report-failure) contract.
4. Stop after the report. Do not run `convert`, `attach-scripts`, import,
   publish, or implement a trigger/asset in this turn.
5. Present conversion as later work and wait for a separate explicit approval
   that names the output path and version.

A favorable report is analysis evidence, not conversion approval, package
output, import/publish evidence, runtime proof, or migration completion.

If `pm-analyze` is not an available skill or its `skills/pm-analyze/pm-analyze`
wrapper is missing, stop and state that the installed migration bundle is
incomplete. Do not continue with a prose-only migration plan in place of the
required report. Install or update the complete PM3 → PM4 migration skill group,
then start a new agent session and retry.

### Migration entry-point boundaries

| User request | First skill |
| --- | --- |
| Migrate a named PM3 `.pmx`/`.pmx2` to PM4 | This skill; start the report-first workflow above |
| Run `pm-analyze report` or `convert` on a named export | [pm-analyze](../pm-analyze/SKILL.md) |
| Author or validate existing migration requirement catalogs only | Internal [manage-requirements](../../.agents/skills/manage-requirements/SKILL.md) |
| Import or publish a generated package in PM4 | [pm4-process](../pm4-process/SKILL.md) |
| Map the reported PM3 triggers or webbots | [pm3-pm4-triggers](../pm3-pm4-triggers/SKILL.md) |

Do not send a full migration request directly to catalog authoring, package
import, or trigger implementation. Those stages begin only after the report
and their own requested scope or authorization.

**Triggers (required after pm-analyze):** load [pm3-pm4-triggers](../pm3-pm4-triggers/SKILL.md) immediately after `report` (and usually `convert`) to map each webbot/hook to script task, watcher, assignment, redirect, or email. Do not skip this when the report lists triggers or the export has `steptriggers`.

**Subprocesses (required when report lists them):** load [pm3-pm4-subprocess](../pm3-pm4-subprocess/SKILL.md) and **tell the user** the child process must still be **imported and published**, and the **Sub Process on the parent** must be **configured in Modeler** on the main process — convert does not complete that on the instance.

**Input documents:** load [pm3-pm4-input-documents](../pm3-pm4-input-documents/SKILL.md) when the export has `INPUTDOCUMENTS` or `INPUT_DOCUMENT` steps. `convert` emits FileUpload on the task screen (`merge`). Upload-only tasks stay manual.

**Output documents:** load [pm3-pm4-output-documents](../pm3-pm4-output-documents/SKILL.md) when the export has `OUTPUTDOCUMENTS` or `OUTPUT_DOCUMENT` steps. `convert` emits `email_body` and step `generate_pdf` when eligible; trigger-only PDF and JRXML stay manual. Do not paste the template onto the FORM.

**Web entry:** load [pm3-pm4-web-entry](../pm3-pm4-web-entry/SKILL.md) when the export has `WEB_ENTRY` / `webEntryEvent` rows, anonymous public start, or `workflow-files/public` entry PHP. Run **`report-web-entry`**; **`convert`** emits `package-webentry`-shaped Start config when canvas + task dynaform (requires `package-webentry` on PM4).

## When to use

- Scope a migration (what converts automatically vs manual rework).
- Validate or patch **converter-generated** `process_package` JSON (fix known import/Preview issues — do not fabricate a package from scratch).
- Build a gap list after import (triggers, assignment, email, collections, web entry).
- Scope manual follow-up before a formal audit; for structured health/security review use [pm4-process-health](../pm4-process-health/SKILL.md).
- Choose which `pm3-*` skills to read for the source and which `pm4-*` skills for the target.

## Not this skill

| User wants | Use instead |
|------------|-------------|
| Edit PM3 dynaform, trigger, or classic map only | Matching `pm3-*` skill |
| Import, publish, or export the process in PM4 | [pm4-process](../pm4-process/SKILL.md) |
| Fix **one named** screen (one control, validation, layout) | [pm4-screen](../pm4-screen/SKILL.md) |
| PM3-only asset map / build order with **no** PM4 migration | [pm3-overview](../pm3-overview/SKILL.md) |
| Fix one script, connector, or collection | That `pm4-*` skill |
| Edit BPMN (gateway, flow, task node) on an existing process | [pm4-process](../pm4-process/SKILL.md) |
| Configure assignment / screenRef on a task | [pm4-form-manual-task](../pm4-form-manual-task/SKILL.md) |
| Full health / optimization audit | [pm4-process-health](../pm4-process-health/SKILL.md) |
| Brand-new PM4 solution with no PM3 source | [pm4-overview](../pm4-overview/SKILL.md) |
| Register a solved import bug in the findings library | [register-finding](../register-finding/SKILL.md) |
| Run or patch converter Python source | Out of repo; document gaps here |

## Phases (typical)

| Phase | Usually automated | Usually manual |
|-------|-------------------|----------------|
| **1 — Structure** | BPMN map, lanes, basic sequence flows; dynaform → FORM screens (field mapping) | Gateway conditions rewritten to **FEEL**; layout edge cases |
| **2 — Behavior** | — | Triggers → [pm3-pm4-triggers](../pm3-pm4-triggers/SKILL.md) (scripts/watchers/assignment/redirect/email); notifications → Send Email; assignment rules |
| **3 — Data & integrations** | — | PM Tables → collections; DB/API dropdowns → connectors; input documents → [pm3-pm4-input-documents](../pm3-pm4-input-documents/SKILL.md); output documents → [pm3-pm4-output-documents](../pm3-pm4-output-documents/SKILL.md); case data cutover |

**Phase 1 converters** (external PoCs) often cover BPMN + screens only. Do not assume triggers, email, PM Tables, web entry, or case history migrate.

## Source constraints

- **BPMN PM3** `.pmx` (Designer) is the usual converter input. **Classic Process Map** (`processes_Map`) is a different model — see [pm3-classic-process](../pm3-classic-process/SKILL.md); expect redesign or manual BPMN rebuild, not a faithful auto-map.
- **One dynaform step per task** is a common PoC limit: multiple DYNAFORM steps on one task may collapse to a single screen.
- **Nested PM3 subforms** (`type: form` inside a row) are often **flattened** into the parent PM4 screen (no embedded dynaform component).
- **Grid control** (`type: grid` in v2 `dyn_content`, variable type `grid`): converters often emit **`FormRecordList`** and an extra page for row fields; column layout inside the grid may not reuse **`FormMultiColumn`** until the converter is extended. **`pm-analyze report`** can still label the grid control **`partial`** in the migration ledger while aggregate **`report.rows`** counts grids as auto — compare ledger vs convert output for parity.
- **Case data, in-flight cases, and workspace-only assets** are out of scope for package import; plan a separate data/project.

## Preview blank (triage)

| Situation | First skill |
|-----------|-------------|
| **Several screens** after a PM3 package import: Design shows controls, Preview is empty, user asks what to check first | This skill (post-import checklist + findings) |
| **One screen** named in the request: rename or fix a variable, control, or layout so Preview works | [pm4-screen](../pm4-screen/SKILL.md) |

## Post-import checklist

Run after import on the target instance:

1. Process and every screen open in Designer without **"Description is required"** errors.
2. Each FORM screen: **Preview** tab (not only Design canvas) — blank Preview often means invalid control `name` / binding.
3. Scan control variable names against Screen Builder rules ([pm4-screen](../pm4-screen/SKILL.md) asset schema); invalid names (often from flattened PM3 subform titles) break Preview.
4. FormLoop / grid screens: `FormLoop` and inner `FormMultiColumn` have `container: true` and valid `items` (see findings).
5. Sequence flow conditions: BPMN stores `conditionExpression` with language **FEEL** (`FormalExpression` in PM4). Re-test every branch against request `data` ([pm4-expressions](../pm4-expressions/SKILL.md)); mechanical `@@` removal from PM3 is not a guarantee of valid FEEL.
6. Every human task: **assignment** (`pm:assignment`, `pm:assignedUsers`, `pm:assignedGroups`, `user_group`, etc.) and screen type — converter BPMN often leaves `requester` or `user` without real users/groups; configure in [pm4-form-manual-task](../pm4-form-manual-task/SKILL.md).
7. In Modeler, each Form/Manual Task has a non-empty **`pm:screenRef`** after v2 import (wired from package screen dependents); if empty, the export paths or dependents are wrong.
8. **Triggers:** run [pm3-pm4-triggers](../pm3-pm4-triggers/SKILL.md) (after pm-analyze) — plan merges → `trigger-plan.json`, then `skills/pm-analyze/pm-analyze attach-scripts` or Modeler; peel-offs via hand-off skills in that skill.
9. List PM3 **email templates**, **web entry** → [pm3-pm4-web-entry](../pm3-pm4-web-entry/SKILL.md), **Case Scheduler**, **PM Tables**, **input documents** → [pm3-pm4-input-documents](../pm3-pm4-input-documents/SKILL.md), **output documents** → [pm3-pm4-output-documents](../pm3-pm4-output-documents/SKILL.md) and matching `pm4-*` / `pm3-*` skills in [asset-mapping.md](asset-mapping.md).
10. **Subprocesses:** child imported and **published**; on the **parent** process, each Sub Process node bound to that child + start event ([pm3-pm4-subprocess](../pm3-pm4-subprocess/SKILL.md), [pm4-sub-process](../pm4-sub-process/SKILL.md)).
11. Catalog **Start** on a manual start opens the first task through the interstitial. The converter sets `pm:allowInterstitial="true"` and leaves `pm:interstitialScreenRef` empty (system `interstitial` screen). Leave that as-is. Turn it off only for a timer, message, signal, or web-entry start. Task Destination stays the default (`taskSource`).

## Go / no-go (decision)

**Reasonable accelerator use:** BPMN PM3, acceptable manual Phase 2/3, team can fix assignment and scripts, success = import + Preview + documented gaps.

**Poor fit alone:** classic-only processes, trigger-heavy PHP, strict assignment parity day one, SQL-driven dynaforms, unattended bulk migration, or case/data cutover without a separate plan.

## Findings library

Search [references/findings-index.md](../../references/findings-index.md) for **pm3-pm4-migration** area: import package shape, Preview blank, FormLoop, hidden/radio controls.

## Order of work (converter output → production)

1. [pm-analyze](../pm-analyze/SKILL.md) — `report` / `convert` `.pmx`/`.pmx2` → coverage + `process_package`.
2. [pm3-pm4-triggers](../pm3-pm4-triggers/SKILL.md) — when triggers exist: plan → `attach-scripts` → `process-with-scripts.json` (do not wait until the end of the checklist).
2b. [pm3-pm4-subprocess](../pm3-pm4-subprocess/SKILL.md) — when subprocesses exist: child import/publish, then configure Sub Process on the parent in Modeler.
3. [pm4-process](../pm4-process/SKILL.md) — import and publish **`process-with-scripts.json`** (or `convert` output only for a quick structure smoke test, then re-import with scripts before UAT).
4. This skill — gap list and [post-import checklist](#post-import-checklist).
5. [pm4-screen](../pm4-screen/SKILL.md) — Preview/import fixes on screens. [pm3-pm4-input-documents](../pm3-pm4-input-documents/SKILL.md) when `INPUTDOCUMENTS` exist. [pm3-pm4-output-documents](../pm3-pm4-output-documents/SKILL.md) when `OUTPUTDOCUMENTS` exist.
6. [pm4-form-manual-task](../pm4-form-manual-task/SKILL.md) — assignment per human task (including assignment changes from the trigger plan).
7. [pm4-process](../pm4-process/SKILL.md) — gateway conditions (FEEL), peel-off BPMN from testing.
8. [pm4-script](../pm4-script/SKILL.md), [pm4-send-email](../pm4-send-email/SKILL.md), [pm4-collection](../pm4-collection/SKILL.md), etc. per [asset-mapping.md](asset-mapping.md) and the trigger plan.
