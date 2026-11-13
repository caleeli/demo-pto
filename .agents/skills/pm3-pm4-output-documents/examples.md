# Worked classifications

Inventory one `OUTPUTDOCUMENT`, then classify per [SKILL.md](SKILL.md). Do not paste the PM3 HTML template onto a FORM screen.

## Exclude — defined, never generated

| Field | Value |
|-------|--------|
| Title | Email Itinerary |
| Type / generate | `HTML` / `PDF` |
| Steps | none |
| `PMFGenerateOutputDocument` | not called |
| Destination | **Exclude** |

Travel-style leftover when the document is unused. Document and move on unless the user still needs the generated file.

## Email body — HTML step on a form task

| Field | Value |
|-------|--------|
| Task | Invoice consolidation — dynaform Finance **plus** `OUTPUT_DOCUMENT` “Email Itinerary” |
| Type | `HTML` |
| Destination | **email_body** — `convert` adds EMAIL screen “Email Itinerary” + Send Email after Invoice consolidation |

`@@Traveller_Name` → `{{Traveller_Name}}` on the EMAIL screen, not on Travel Req - Finance. To is `{{Traveller_Email}}` when that variable exists. The PDF file is still HITL (`hitl[]`). Grid tokens `@>` stay as text.

## Generate PDF — step without a dynaform

| Field | Value |
|-------|--------|
| Task | Issue Offer — **only** an `OUTPUT_DOCUMENT` step |
| Type | `HTML` |
| Destination | **generate_pdf** — `convert` adds Dompdf script + `scriptTask` after Issue Offer |

Requires Dompdf executor on the PM4 instance. Task has **only** the output-document step (no dynaform on that task). Residual **`hitl[]`** (path, tags, versioning) may still need manual follow-up.

## Generate PDF — trigger only

| Field | Value |
|-------|--------|
| Steps | none |
| Webbot | `PMFGenerateOutputDocument("…uid…")` |
| Destination | **generate_pdf** (plan label only) |

Hand the webbot to [pm3-pm4-triggers](../pm3-pm4-triggers/SKILL.md). `generated_by_trigger` is true; `convert` exports the Dompdf script; BPMN placement is manual.

## HITL — JRXML / ACROFORM / ITEXT

| Field | Value |
|-------|--------|
| Type | `JRXML` |
| Destination | **hitl** |

No core PM4 Jasper/Acroform engine. External report or a later package. Path/tags/versioning on an HTML row stay in `hitl[]`; destination can still be `email_body` or `exclude`.
