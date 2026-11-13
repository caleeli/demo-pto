---
name: pm3-dynaform
description: >-
  ProcessMaker 3 dynaforms and form controls. Use for xmlform, grid dynaforms,
  suggest, SQL suggest, file upload fields, dynaform steps on tasks, or v1 vs v2
  dynaform editor.
---

# PM3 Dynaform

A **dynaform** is a form asset attached to task **steps**. Stored as xmlform definitions (XML or grid type) under the process.

## When to use

- Create or edit a form for a user task step
- Add controls: text, dropdown, suggest, SQL suggest, grid, file, checkbox, etc.
- Configure validation, visibility, or default values with `@@` variables
- Attach a dynaform to a task step sequence

## Asset schema

| Field | Required | Notes |
|-------|----------|-------|
| `DYN_UID` | auto | Unique id |
| `PRO_UID` | yes | Owning process |
| `DYN_TITLE` | yes | Display name |
| `DYN_DESCRIPTION` | no | Description |
| `DYN_TYPE` | yes | `xmlform` (standard) or `grid` (record list) |
| `DYN_FILENAME` | yes | Storage filename under process dynaforms folder |
| `DYN_CONTENT` | no | XML body (API field; also stored on disk as `DYN_FILENAME`) |
| `DYN_LABEL` | no | Localized label |
| `DYN_VERSION` | no | `1` (legacy) or `2` (responsive editor) |

Controls are XML nodes inside `DYN_CONTENT` / the dynaform file. The XML element name is the case variable name; each field has a `type` attribute and localized label.

## Control types (common)

| Type | Purpose |
|------|---------|
| `text` | Single-line text |
| `textarea` | Multi-line text |
| `dropdown` | Fixed option list |
| `suggest` | Autocomplete from options or PM Table |
| `sql` / SQL suggest | Options from DB connection query |
| `checkbox` | Boolean (`'1'` / `'0'` typical) |
| `date` / `datetime` | Date picker |
| `file` | File upload (may pair with input document) |
| `grid` | Nested repeating rows |
| `hidden` | Non-visible persisted field |
| `link` | External URL |
| `subtitle` / `title` | Layout only |

## Responsive editor (v2 JSON in `dyn_content`)

PM3 stores the canvas as JSON (`items` under a root `type: form` wrapper). These patterns matter for migration tools and PM4 layout:

| PM3 pattern | Meaning | PM4 direction (converter or manual) |
|-------------|---------|-------------------------------------|
| Same row, two+ controls, each with **`colSpan`** | Multicolumn row | Often maps to **`FormMultiColumn`** when every field in that row has `colSpan` |
| **`type: form`** nested in `items` | Subform / embedded block | Today often **flattened** into the parent screen; not a separate nested screen by default |
| **`type: grid`** with **`columns`** | Repeating row control bound to a **`grid`** process variable | Often maps to **`FormRecordList`** plus a second page for add/edit row fields |
| **`mode: dependent`** / **`formula`** on a dropdown (e.g. region depends on country) | Dependent options | **Not** reliably emitted as Screen Builder visibility rules; rewire in PM4 ([pm4-screen](../pm4-screen/SKILL.md)) |

Do not confuse **grid control** (above, inside an xmlform) with **`DYN_TYPE = grid`** on the dynaform asset (whole dynaform is a record list — see Rules).

## Rules

- A dynaform must be added as a **step** on a task to appear during case execution
- Field element name should match a process variable when data must persist
- **Grid dynaforms** (`DYN_TYPE = grid`) behave as record lists — different from grid control inside xmlform
- SQL suggest controls require a valid `pm3-db-connection` and safe query (escape `@@` inputs)
- v2 dynaforms use responsive layout; v1 uses classic xmlform editor
- Required fields block step submission when empty

## Step attachment

Tasks have ordered steps:

| Step type | Asset |
|-----------|-------|
| `DYNAFORM` | `DYN_UID` |
| `INPUT_DOCUMENT` | `INP_DOC_UID` |
| `OUTPUT_DOCUMENT` | `OUT_DOC_UID` |
| `MESSAGE` | Message template |
| `EXTERNAL` | Plugin-registered step |

Step order determines when the user sees each asset.

## Links to other PM3 assets

| Asset | Relationship |
|-------|--------------|
| `pm3-variables` | Field names → case variables |
| `pm3-expressions` | Default values and visibility with `@@` |
| `pm3-process` | Task step sequence |
| `pm3-input-document` | File upload step alternative/complement |
| `pm3-db-connection` | SQL suggest data source |
| `pm3-trigger` | Before/after step triggers on dynaform submit |

## Validation checklist

1. `DYN_TITLE` unique within process
2. Every persisted field has matching process variable (if triggers/output docs depend on it)
3. Dynaform attached as step on intended task
4. SQL suggest queries tested with sample `@@` values
5. Required fields marked for mandatory business data

## Common mistakes

- Creating dynaform but not adding it as a task step
- Field name typo vs `@@` variable in trigger
- Grid control confused with grid-type dynaform
- Checkbox compared as boolean in PHP when stored as string
- SQL suggest without DB connection permissions

Example: [examples/dynaform-minimal.xml](examples/dynaform-minimal.xml)
