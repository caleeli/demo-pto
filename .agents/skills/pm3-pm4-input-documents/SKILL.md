---
name: pm3-pm4-input-documents
description: >-
  After pm-analyze: PM3 INPUT_DOCUMENT steps → FileUpload on FORM screens (merge in
  convert). Not pm3-input-document-only or manage-requirements.
---

# PM3 → PM4 input documents

PM4 has **no Input Document asset**. The destination is a **FileUpload** control on a FORM screen ([pm4-screen](../pm4-screen/SKILL.md)). A PM3 upload is a **task step**; a PM4 upload is a **control**. `convert` emits FileUpload when the same task already has a dynaform (`destination: merge`), including `VIRTUAL` uploads that are still file steps. Tasks that are upload-only (`new_screen`) stay manual.

**Prerequisite:** [pm-analyze](../pm-analyze/SKILL.md) **report** on the same export. **Source:** [pm3-input-document](../pm3-input-document/SKILL.md). **Parent:** [pm3-pm4-migration](../pm3-pm4-migration/SKILL.md).

Ask if unclear: *“Plan only, or add the FileUploads on the imported screens?”*

```bash
"$PM" report-input-documents --pmx2 <export.pmx> --out input-doc-plan.json
```

Review `destination` (`merge` / `new_screen` / `skip` / `exclude` / `hitl`) before adding controls. Example: [input-doc-plan.json](../../tools/pm-migrate/testdata/input-doc-plan.json). `PM` is `skills/pm-analyze/pm-analyze`.

Do **not** hand-edit `process_package` JSON to invent FileUpload items. Re-run `convert` for merge cases. Add `new_screen` uploads in Screen Builder after import (clone a FileUpload — [pm4-screen](../pm4-screen/SKILL.md)).

## Classify first

Start from `input-doc-plan.json` (or read `INPUTDOCUMENTS` + `STEPS` if the CLI cannot run). One row per step. Confirm each `destination` — the CLI is a hint, not HITL.

| If in PM3… | Destination |
|------------|-------------|
| Same task already has a DYNAFORM step (including VIRTUAL file steps) | **Merge** — `convert` adds FileUpload on that screen |
| Task has **only** an INPUT_DOCUMENT step | **New screen** — FileUpload + Submit; wire `pm:screenRef` ([pm4-form-manual-task](../pm4-form-manual-task/SKILL.md)) |
| Dynaform `file` field already converted for the same file | **Skip** — do not add a second upload |
| Empty / unused definition (no step) | **Exclude** — document |

`BEFORE_INPUT_DOCUMENT` / `AFTER_INPUT_DOCUMENT` triggers are peel-offs → [pm3-pm4-triggers](../pm3-pm4-triggers/SKILL.md). File variable `INP_DOC_UID` → `data.<FileUpload name>` ([pm4-expressions](../pm4-expressions/SKILL.md)).

Worked rows: [examples.md](examples.md).

## What maps

| PM3 | FileUpload |
|-----|------------|
| `INP_DOC_TITLE` | `label` |
| `INP_DOC_TYPE_FILE` | `accept` |
| Required step | `validation` `required` |
| One file (typical) | `multipleUpload: false` unless the process clearly allowed several |
| Linked file variable / title slug | `name` (valid Screen Builder name) |

Do **not** treat `INP_DOC_MAX_FILESIZE` as screen `max:n` (that rule is not file size). Record the PM3 limit; enforce with instance upload limits if needed.

## HITL — stop and ask

No core PM4 match for: `VIRTUAL` / `VREAL` vs `REAL`; `INP_DOC_ORIGINAL`; `PUBLIC` / `PRIVATE`; versioning; destination path; tags. Ask what must survive go-live. Case-tracker / web-entry access to the doc is a separate redesign.

## Verify

- [ ] `report-input-documents` items cover every `INPUTDOCUMENTS` row (and every attached step)
- [ ] Every item is merge, new_screen, skip, hitl, or exclude
- [ ] No duplicate FileUpload for a dynaform `file` already converted
- [ ] HITL fields listed, not silently dropped
- [ ] Screen Preview shows the upload; Form Task has `pm:screenRef` if a new screen was added
- [ ] Input-document triggers handed to [pm3-pm4-triggers](../pm3-pm4-triggers/SKILL.md)

## Wrong skill?

| Need | Skill |
|------|--------|
| Create/edit a PM3 input document only | [pm3-input-document](../pm3-input-document/SKILL.md) |
| FileUpload with no PM3 source | [pm4-screen](../pm4-screen/SKILL.md) |
| Output documents (PDF/DOC generation) | [pm3-pm4-output-documents](../pm3-pm4-output-documents/SKILL.md); PM3-only → [pm3-output-document](../pm3-output-document/SKILL.md) |
| report / convert CLI | [pm-analyze](../pm-analyze/SKILL.md) |
| Migration gaps / checklist | [pm3-pm4-migration](../pm3-pm4-migration/SKILL.md) |
