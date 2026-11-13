# Worked example: synchronous compliance subprocess

Generic parent/child names often used in migration fixtures.

## Processes

| Role | Typical PM3 name | Flow |
|------|------------------|------|
| Parent | Purchase Approval with Compliance | Start → enter request → **Run compliance check** (subprocess) → gateway or end |
| Child | Procurement Compliance Check | Sub-process-only; task **Review against policy** |

## Subprocess configuration (PM3)

| Setting | Example value |
|---------|----------------|
| Child process | Procurement Compliance Check |
| Starting activity | Review against policy |
| Type | Synchronous (`SP_SYNCHRONOUS=1`) |
| Variables Out | `requesterName` → `requesterName`; `supplierName` → `supplierUnderReview`; `purchaseAmount` → `amountUnderReview` |
| Variables In | `complianceApproved`, `complianceNotes` (same names on parent) |

PM3 API / export shape (`spr_variables_out` / `spr_variables_in` keys use `@@` / `@#` prefixes).

## PM4 migration

1. **Convert/import child** first → compliance FORM screen field names match **Out** targets on the child (`supplierUnderReview`, not parent `supplierName` unless you add a script).
2. **Convert/import parent** → Sub Process on **Run compliance check** → published child + plain Start Event for the review task ([pm4-sub-process](../pm4-sub-process/SKILL.md)).
3. **Test** — parent case: submit request → child shows context → complete review → parent has `complianceApproved` / `complianceNotes` via merge-back.
4. **Gateway** — FEEL on `complianceApproved` after subprocess ([pm4-process](../pm4-process/SKILL.md), [pm4-expressions](../pm4-expressions/SKILL.md)).

`convert` emits `bpmn:callActivity` when the child export is in the package; confirm Sub Process binding in Modeler if import did not link the child.
