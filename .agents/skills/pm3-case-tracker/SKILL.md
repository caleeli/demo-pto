---
name: pm3-case-tracker
description: >-
  ProcessMaker 3 case tracker. Use for public case status pages, CASE_TRACKER and
  CASE_TRACKER_OBJECT tables, or exposed dynaforms and documents on tracker.
---

# PM3 Case Tracker

**Case tracker** provides a public page where external parties view case status. One configuration per process (`PRO_UID` is primary key of `CASE_TRACKER`).

## Asset schema — CASE_TRACKER

| Field | Required | Notes |
|-------|----------|-------|
| `PRO_UID` | yes | Primary key — one tracker config per process |
| `CT_MAP_TYPE` | yes | `NONE`, `PROCESSMAP`, or `STAGES` |
| `CT_DERIVATION_HISTORY` | yes | `0` or `1` — show routing history |
| `CT_MESSAGE_HISTORY` | yes | `0` or `1` — show case messages |

API: `case-tracker`.

## Asset schema — CASE_TRACKER_OBJECT

| Field | Required | Notes |
|-------|----------|-------|
| `CTO_UID` | auto | Object id (primary key) |
| `PRO_UID` | yes | Owning process |
| `CTO_TYPE_OBJ` | yes | `DYNAFORM`, `INPUT_DOCUMENT`, `OUTPUT_DOCUMENT`, `MESSAGE`, `EXTERNAL` |
| `CTO_UID_OBJ` | yes | UID of the referenced asset |
| `CTO_CONDITION` | yes | Visibility condition |
| `CTO_POSITION` | yes | Display order |

API: `case-tracker-object`.

## Rules

- Tracker URL is workspace-specific; share only with intended audience
- Only expose dynaforms/documents appropriate for public viewing
- Case number + PIN (when configured) acts as access credential
- `CTO_CONDITION` can restrict object visibility per case state
- Tracker shows read-only view of selected objects

## Links to other PM3 assets

| Asset | Relationship |
|-------|--------------|
| `pm3-dynaform` | `CTO_TYPE_OBJ = DYNAFORM` |
| `pm3-input-document` | `CTO_TYPE_OBJ = INPUT_DOCUMENT` |
| `pm3-output-document` | `CTO_TYPE_OBJ = OUTPUT_DOCUMENT` |
| `pm3-process` | Parent `PRO_UID` configuration |

## Validation checklist

1. Only non-sensitive dynaforms/documents exposed
2. `CTO_UID_OBJ` references valid asset UID for `CTO_TYPE_OBJ`
3. `CTO_CONDITION` tested with cases in different states
4. Public URL tested with sample case

## Common mistakes

- Exposing internal-only fields on public tracker dynaform
- `CTO_UID_OBJ` references deleted dynaform
- Expecting multiple tracker configs per process (PK is `PRO_UID`)
- Using `CT_UID` field (does not exist — use `PRO_UID`)
