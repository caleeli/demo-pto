# Worked classifications

Inventory one `INPUT_DOCUMENT` step, then merge, add a screen, skip, or HITL. Add FileUpload in Screen Builder — do not paste PM3 step XML into the package.

## Merge onto the converted screen

| Field | Value |
|-------|--------|
| Task | Review — has a DYNAFORM step (already a FORM screen) plus `INPUT_DOCUMENT` “Signed contract” |
| `INP_DOC_FORM_NEEDED` | `REAL` |
| `INP_DOC_TYPE_FILE` | `pdf` |
| Destination | **Merge** — FileUpload on the Review screen |

`label`: Signed contract. `name`: `signed_contract` (or the linked file variable). `accept`: `.pdf`. `validation`: `required`. `multipleUpload`: false.

## New screen (upload-only task)

| Field | Value |
|-------|--------|
| Task | Upload ID — **only** an `INPUT_DOCUMENT` step |
| Destination | **New screen** — FileUpload + Submit |

Create a FORM screen, add FileUpload, set `pm:screenRef` on that Form Task ([pm4-form-manual-task](../pm4-form-manual-task/SKILL.md)).

## Skip — dynaform file already converted

| Field | Value |
|-------|--------|
| Same task | Dynaform field `file` / `contract` **and** input document “Contract” |
| `convert` | Already emitted `FileUpload` `name: contract` |
| Destination | **Skip** — keep the converted control; do not add a second upload |

## HITL leftover after merge

| Field | Value |
|-------|--------|
| `INP_DOC_FORM_NEEDED` | `VIRTUAL` on a task that already has a dynaform |
| Destination | **Merge** — `convert` still emits FileUpload |
| Still HITL | folder path, PRIVATE, ORIGINAL, versioning — not a PM4 FileUpload property |

Ask whether those extras must survive go-live. Do not invent folder paths or `package-files` unless the user asks.
