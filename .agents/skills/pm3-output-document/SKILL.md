---
name: pm3-output-document
description: >-
  ProcessMaker 3 output documents. Use for PDF/DOC generation, HTML/iText/JRXML/
  ACROFORM templates, field mapping from @@ case variables, or document generation
  triggers.
---

# PM3 Output Document

**Output documents** generate files (DOC/PDF) from case variables using templates.

## Asset schema

| Field | Required | Notes |
|-------|----------|-------|
| `OUT_DOC_UID` | auto | Unique id |
| `PRO_UID` | yes | Owning process |
| `OUT_DOC_TITLE` | yes | Display name |
| `OUT_DOC_GENERATE` | yes | `BOTH`, `DOC`, or `PDF` |
| `OUT_DOC_TEMPLATE` | no | Template content with `@@` placeholders |
| `OUT_DOC_FIELD_MAPPING` | no | Field mapping for JRXML/ACROFORM |
| `OUT_DOC_TYPE` | yes | `HTML`, `ITEXT`, `JRXML`, or `ACROFORM` |

Exported as `OUTPUTDOCUMENTS` in `.pmx`. API: list `output-documents`; create/update/delete `output-document`.

## Template placeholders

Embed case variables in template body:

```
Applicant: @@APPLICANT_NAME
Amount: @@AMOUNT
Date: @@APPLICATION_DATE
```

Generation replaces `@@VAR` with current case values at runtime.

## Generation

- Attached as **step** on task (user downloads/generated doc)
- Triggered by **trigger** calling output document generation functions
- `OUT_DOC_TYPE = HTML` uses HTML template; `ITEXT` uses iText; `JRXML` uses JasperReports; `ACROFORM` uses PDF forms
- `OUT_DOC_GENERATE` controls generated file: `DOC`, `PDF`, or `BOTH`

## Links to other PM3 assets

| Asset | Relationship |
|-------|--------------|
| `pm3-variables` | `@@` placeholder source |
| `pm3-expressions` | Placeholder syntax |
| `pm3-trigger` | Programmatic generation |
| `pm3-process` | Task step attachment |
| `pm3-case-tracker` | May expose generated docs |
| `pm3-pm4-output-documents` | PM3 → PM4 inventory; `email_body` when task has dynaform; step PDF when output step on task without dynaform |

## Validation checklist

1. Every `@@` placeholder has matching case variable
2. Template tested with sample case data
3. `OUT_DOC_GENERATE` matches business requirement (`DOC`, `PDF`, or `BOTH`)

## Common mistakes

- Placeholder name mismatch (case-sensitive)
- Generating before variables are populated
- JRXML template without JasperReports dependency
- Large images in template causing PDF memory errors
