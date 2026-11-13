---
name: pm3-input-document
description: >-
  ProcessMaker 3 input documents (request documents). Use for required file uploads,
  virtual/real documents, versioning, file type rules, or attaching uploads to
  task steps.
---

# PM3 Input Document

**Input documents** define files users must upload during a case. Attached as **steps** on user tasks.

## Asset schema

| Field | Required | Notes |
|-------|----------|-------|
| `INP_DOC_UID` | auto | Unique id |
| `INP_DOC_ID` | auto | Numeric id |
| `PRO_UID` | yes | Owning process |
| `INP_DOC_TITLE` | yes | Display name |
| `INP_DOC_DESCRIPTION` | no | Description |
| `INP_DOC_FORM_NEEDED` | yes | `VIRTUAL`, `REAL`, or `VREAL` |
| `INP_DOC_ORIGINAL` | yes | `COPY`, `ORIGINAL`, `COPYLEGAL`, or `FINAL` |
| `INP_DOC_PUBLISHED` | yes | `PUBLIC` or `PRIVATE` |
| `INP_DOC_VERSIONING` | yes | `0` or `1` — enable version tracking |
| `INP_DOC_DESTINATION_PATH` | no | Custom storage path |
| `INP_DOC_TAGS` | no | Document tags |
| `INP_DOC_TYPE_FILE` | no | Allowed extensions |
| `INP_DOC_MAX_FILESIZE` | yes | Max size numeric value |
| `INP_DOC_MAX_FILESIZE_UNIT` | yes | `KB` or `MB` (default `KB`) |

Exported as `INPUTDOCUMENTS` in `.pmx`. API: `input-document`.

## Rules

- Must be attached as a **step** on a task (like dynaforms)
- `REAL` stores physical files; `VIRTUAL` tracks metadata only; `VREAL` combines both
- `INP_DOC_ORIGINAL` controls copy/original/legal/final document handling
- `INP_DOC_PUBLISHED` controls document access visibility
- Versioning keeps history when user re-uploads
- File type/size restrictions enforced at upload time

## Links to other PM3 assets

| Asset | Relationship |
|-------|--------------|
| `pm3-process` | Task step attachment |
| `pm3-dynaform` | Alternative/complement for file capture |
| `pm3-variables` | File variable may reference uploaded doc |
| `pm3-case-tracker` | May expose input docs on public tracker |

## Validation checklist

1. Input document attached as step on correct task
2. File type restrictions match business requirements
3. Versioning enabled when audit trail required

## Common mistakes

- Input document created but not added as task step
- Conflicting file upload in dynaform and input document for same data
- Max file size too small for expected documents
