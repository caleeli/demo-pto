# Hook → destination

Use this after the classify-first table in [SKILL.md](SKILL.md). Constants: [pm3-trigger](../pm3-trigger/SKILL.md).

## Screen / dynaform hooks (may become Script Tasks)

| PM3 (Designer / `steptriggers`) | PMScript | Bucket | Plan slot |
|---------------------------------|----------|--------|-----------|
| Before Screen (`step_uid=-1`, BEFORE) or dynaform BEFORE | `BEFORE_DYNAFORM` | **pre-form** → one Script Task **before** the Form Task | `before` |
| Dynaform AFTER | `AFTER_DYNAFORM` | **post-form** → one Script Task **after** the Form Task | `after` |
| Before routing (`step_uid=-2`, BEFORE) | `BEFORE_ROUTING` (after submit, before derive) | **post-form** | `after` |

Merge hooks in the same bucket in `ST_POSITION` order. In merged code use `// Before Screen Trigger: {title}` / `// After Screen Trigger: {title}`.

Target: `… → PreScript → FormTask → PostScript → …` on the **same** sequence (not a parallel branch or extra swimlane).

## Not in `trigger-plan.json`

Route these with the peel-off table or [pm3-trigger](../pm3-trigger/SKILL.md) plus the target `pm4-*` skill:

- Input / output document step hooks
- `BEFORE_ASSIGNMENT`
- `BEFORE_ROUTING` on non-form steps
- PM3 BPMN **script tasks** (already activities)
- Classic-map-only hooks

## Peel-offs (not `attach-scripts`)

| Purpose | Use |
|---------|-----|
| Assignment / groups | [pm4-form-manual-task](../pm4-form-manual-task/SKILL.md) |
| Field change while editing | [pm4-screen](../pm4-screen/SKILL.md) watcher / computed |
| Email | [pm3-pm4-email-notifications](../pm3-pm4-email-notifications/SKILL.md), then [pm4-send-email](../pm4-send-email/SKILL.md) for the selected PM4 mechanism |
| Redirect / interstitial | [pm4-form-manual-task](../pm4-form-manual-task/SKILL.md) BPMN attrs |
| Integration / SQL | [pm4-script](../pm4-script/SKILL.md), [pm4-data-connector](../pm4-data-connector/SKILL.md) |
| Empty webbot | Exclude — document |

**Signals in the webbot:** `PMFSendMessage`, `G::sendMail`, `sendNotification`, or `sendNextUserNotification` → email migration plan; `header(` / redirect URL → form task BPMN; group/user assignment APIs → form task; `executeQuery` / REST → script or connector; no-op body → exclude.

A row can be both a screen hook **and** a peel-off (for example After Screen that only sends mail). Prefer the peel-off. Do not also emit a Script Task that only wraps the same email.
