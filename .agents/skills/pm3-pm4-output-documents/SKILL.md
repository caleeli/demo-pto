---
name: pm3-pm4-output-documents
description: >-
  After pm-analyze: inventory PM3 OUTPUTDOCUMENTS (email_body + step generate_pdf
  in convert when eligible; trigger/JRXML manual). Not pm3-output-document-only
  or manage-requirements.
---

# PM3 → PM4 output documents

PM4 has **no Output Document asset**. `convert` does **not** paste the template onto the task FORM. For HTML + `OUTPUT_DOCUMENT` step + dynaform (`destination: email_body`) it emits:

1. An **EMAIL** screen (template body, `@@` → `{{var}}`)
2. A BPMN **Send Email** `serviceTask` (`connector-send-email/processmaker-communication-email-send`) after that Form Task

For HTML **`generate_pdf`** with an **`OUTPUT_DOCUMENT` step** on a task **without** a dynaform on that task, `convert` also emits:

1. A **Script** asset (PHP + Dompdf) from the HTML template (`@@` → `$data` in the generated script)
2. A BPMN **scriptTask** after that task, wired with `pm:scriptRef` and `meta.path` on import

Trigger-only **`generate_pdf`** (no step): `convert` exports the Dompdf **Script** only; wire **`scriptTask`** via [pm3-pm4-triggers](../pm3-pm4-triggers/SKILL.md) / `attach-scripts`. JRXML / ACROFORM / ITEXT are not emitted (**hitl**). Dynaform + output step on the same task → **`email_body` only** (no auto Dompdf).

**Prerequisite:** [pm-analyze](../pm-analyze/SKILL.md) **report** on the same export. **Source:** [pm3-output-document](../pm3-output-document/SKILL.md). **Parent:** [pm3-pm4-migration](../pm3-pm4-migration/SKILL.md). **Send Email node:** [pm4-send-email](../pm4-send-email/SKILL.md).

```bash
"$PM" report-output-documents --pmx2 <export.pmx> --out output-doc-plan.json
```

Review `destination` (`email_body` / `display` / `generate_pdf` / `exclude` / `hitl`) before rebuilding. Example: [output-doc-plan.json](../../tools/pm-migrate/testdata/output-doc-plan.json). `PM` is `skills/pm-analyze/pm-analyze` (use `go run ./cmd/pm-analyze` from `tools/pm-migrate` until the prebuilt bin is rebuilt).

Do **not** hand-edit `process_package` JSON to invent a Documents/PDF package or FileDownload. Re-run `convert` for eligible `email_body` and step **`generate_pdf`** rows. Do **not** treat FORM Rich Text as a migrated output document.

Ask if unclear: *“Does a generated PDF/DOC file have to exist, or is sending the HTML as email enough?”* — especially for **`email_body`** with `OUT_DOC_GENERATE=PDF` or heavy **`hitl[]`**.

## Classify first

Start from `output-doc-plan.json` (or read `OUTPUTDOCUMENTS` + `STEPS` + `TRIGGERS` if the CLI cannot run). One row per definition, or per step when attached. Confirm each **`destination`** with the customer when behavior is unclear — the CLI is a hint, not a sign-off. **`hitl[]`** lists leftover PM3 fields; it does **not** change **`destination`**.

| If in PM3… | Destination / `convert` |
|------------|-------------------------|
| HTML + `OUTPUT_DOCUMENT` step on a task that already has a dynaform | **email_body** — EMAIL screen + Send Email after that Form Task |
| HTML and the body is only emailed (no file) | **email_body** — same emit |
| HTML step with **no** dynaform on that task | **generate_pdf** — Dompdf script + `scriptTask` after the task |
| `PMFGenerateOutputDocument` only (no step) | **generate_pdf** — Script export in `convert`; **`scriptTask`** via triggers / `attach-scripts` |
| Read the letter on the form (no send) | **display** — agent override; convert does not emit |
| `JRXML` / `ACROFORM` / `ITEXT` | **hitl** — no core PM4 template engine |
| No `OUTPUT_DOCUMENT` step and no `PMFGenerateOutputDocument` | **exclude** — document |

**Classify order** (`pkg/outputdocs/plan.go`): non-HTML → **`hitl`**; HTML + step + dynaform → **`email_body`**; HTML + step without dynaform → **`generate_pdf`**; HTML + trigger-only generate → **`generate_pdf`**; else **`exclude`**. Dynaform on the task blocks step-based PDF in `convert`.

`BEFORE_OUTPUT_DOCUMENT` / `AFTER_OUTPUT_DOCUMENT` and `PMFGenerateOutputDocument` are peel-offs → [pm3-pm4-triggers](../pm3-pm4-triggers/SKILL.md). `@@VAR` in the template → Mustache / request keys ([pm4-expressions](../pm4-expressions/SKILL.md)). Grid tokens `@>` / `@#` are not Mustache — list them.

Worked rows: [examples.md](examples.md).

## What maps (`email_body`)

| PM3 | PM4 |
|-----|-----|
| `OUT_DOC_TITLE` | EMAIL screen title + Send Email node name |
| `OUT_DOC_TEMPLATE` `@@VAR` | Rich Text on the EMAIL screen: `{{variable}}` |
| `OUT_DOC_DESCRIPTION` | EMAIL screen description |
| Task with `OUTPUT_DOCUMENT` step (dynaform on same task) | Send Email **after** that Form Task |
| First `*email*` process variable | `toRecipients` `{{that_var}}` (else `{{email}}`) |

## What maps (step `generate_pdf`, no dynaform on task)

| PM3 | PM4 |
|-----|-----|
| `OUT_DOC_TITLE` | Script title (with version suffix and `[out_doc_uid]` slug) + scriptTask name |
| `OUT_DOC_TEMPLATE` `@@VAR` | Generated PHP: `str_replace` / `$data` + Dompdf |
| `OUT_DOC_LANDSCAPE` | `setPaper(..., portrait\|landscape)` when `1` |
| Task with step, no dynaform on task | `scriptTask` after that BPMN task |
| Generated file | Request file id merged into `data` under `outDocPdf_<uid>` (use FileDownload or screens as needed) |

Target instance needs a Dompdf-capable script executor (e.g. `php-w.dompdf`). Set `run_as_user_id` / executor on import like other scripts.

`OUT_DOC_GENERATE` (`DOC` / `BOTH`), TCPDF margins, destination path, tags, versioning, and PDF passwords have **no** PM4 equivalent in `convert`. Record them in **`hitl[]`**. For step-based PDF, `convert` maps **`OUT_DOC_LANDSCAPE`** and **`@@` in `out_doc_filename`** in the generated script; those may still appear in **`hitl[]`** on **`email_body`** rows. `FileDownload` only reads a file already on the request — it does not generate one.

After import: confirm `connector-send-email` is installed; in Modeler re-select the EMAIL screen if `screenRef` did not remap to a numeric id; confirm To and the outbound email server ([pm4-send-email](../pm4-send-email/SKILL.md)).

## HITL — leftover fields, not a destination

Stop and ask before dropping: versioning; destination path; tags; `@@` filename; PDF security; `DOC`/`BOTH`; grid placeholders. Case-tracker / Documents list access is a separate redesign. **`hitl[]`** may still list landscape/filename even when step **`generate_pdf`** maps them in the Dompdf script.

## Verify

- [ ] `report-output-documents` items cover every `OUTPUTDOCUMENTS` row (and every attached step)
- [ ] Every item is email_body, display, generate_pdf, exclude, or hitl
- [ ] `hitl[]` listed, not used as `destination` unless the template type is JRXML/ACROFORM/ITEXT
- [ ] `email_body` items: EMAIL screen exists; FORM screens do **not** contain the template; Modeler shows Send Email after the Form Task
- [ ] Step **`generate_pdf`**: script + `scriptTask`; Dompdf executor on instance
- [ ] Trigger-only **`generate_pdf`**: Script in package; `scriptTask` via triggers / `attach-scripts`
- [ ] Asked whether a generated file must exist beyond Send Email (`hitl[]` / `OUT_DOC_GENERATE=PDF`)
- [ ] No invented Documents package or FileDownload in the process_package
- [ ] Output-document triggers handed to [pm3-pm4-triggers](../pm3-pm4-triggers/SKILL.md)

## Wrong skill?

| Need | Skill |
|------|--------|
| Create/edit a PM3 output document only | [pm3-output-document](../pm3-output-document/SKILL.md) |
| Input-document uploads → FileUpload | [pm3-pm4-input-documents](../pm3-pm4-input-documents/SKILL.md) |
| EMAIL screen / Send Email with no PM3 output doc | [pm4-screen](../pm4-screen/SKILL.md) / [pm4-send-email](../pm4-send-email/SKILL.md) |
| report / convert CLI | [pm-analyze](../pm-analyze/SKILL.md) |
| Migration gaps / checklist | [pm3-pm4-migration](../pm3-pm4-migration/SKILL.md) |
