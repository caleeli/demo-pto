---
name: pm3-pm4-web-entry
description: >-
  After pm-analyze: PM3 WEB_ENTRY/webEntryEvent → PM4 Web Entry (package-webentry).
  Not pm3-web-entry-only or convert auto.
---

# PM3 → PM4 web entry

PM3 **web entries** start cases from outside the inbox: **Setup → Web Entry** (`WEB_ENTRY` rows) and/or a **BPMN web entry start** (`webEntryEvent` linked to a start `EVN_UID` and target task). PM4 uses the **Web Entry** capability (`package-webentry`): `web_entry` JSON on a **Start Event** or **Form Task** `pm:config`, public FORM screen (`screen_id`), and optional thank-you screen (`completed_screen_id`).

**`convert` emits Start `web_entry`** using the same JSON shape as `package-webentry` (`screen_id`, `completed_action`, `enable_query_params`, … — not a `mode` field). PM3 **ANONYMOUS** / Guest maps to Start `pm:assignment="user"` plus a process **`user_assignment`** dependent that resolves **`_pm4_anon_user`** on import (`ProcessExporter::importAssignments`) so Web Entry mode shows **Anonymous** in Modeler; `web_entry.require_valid_session` stays **false**. **LOGIN_REQUIRED** (and PM3 **AUTHENTICATED**) → `pm:assignment="requester"` and `require_valid_session: true`. Setup/WS/HITL and completed screens still manual ([ROADMAP](../../tools/pm-migrate/docs/ROADMAP.md)).

**Prerequisite:** [pm-analyze](../pm-analyze/SKILL.md) **`report-web-entry`** (or **`report`** — Web entry row counts `WEB_ENTRY` + `webEntryEvent`). **Source (PM3):** [pm3-web-entry](../pm3-web-entry/SKILL.md). **PM4 Web Entry fields:** [pm4-web-entry](../pm4-web-entry/SKILL.md). **PM4 wiring:** [pm4-process](../pm4-process/SKILL.md) (start permissions, interstitial off for web entry), [pm4-form-manual-task](../pm4-form-manual-task/SKILL.md) (Form Task + anonymous web entry disables assignment), [pm4-screen](../pm4-screen/SKILL.md) (public form + Captcha). **Parent:** [pm3-pm4-migration](../pm3-pm4-migration/SKILL.md).

```bash
PM=<skills-checkout>/skills/pm-analyze/pm-analyze
"$PM" report-web-entry --pmx2 <export.pmx2> --out web-entry-plan.json
```

## Report and inventory gaps

| Signal | Where to look |
|--------|----------------|
| Legacy note: empty `webEntry` table only | Run **`report-web-entry`** — canvas-only entries live in **`webEntryEvent`** |
| `parse.unmodeled_tables.webentryevent` | Rebuild pm-analyze; table is modeled in current tool |
| `WEB_ENTRY` rows in Setup export | `we_method`, `we_authentication`, `dyn_uid`, `tas_uid`, `we_type` |

Ask if unclear: *“Plan only (inventory + URL/auth strategy), or configure Web Entry on the imported PM4 process?”*

Worked rows: [examples.md](examples.md).

## Classify first

One plan row per **entry path** (Setup `WEB_ENTRY` and/or each `webEntryEvent` record).

| PM3 pattern | PM4 approach |
|-------------|--------------|
| **HTML** + `DYN_UID` on Setup entry | Public FORM screen from converted dynaform (or new screen); Web Entry `screen_id`; map fields to request `data` |
| **HTML** web entry event, **`dyn_uid` empty**, form on **first task** | Web Entry on **Start** or **Form Task** pointing at task **`pm:screenRef`**; turn off interstitial if start should go straight to public form ([pm3-pm4-migration](../pm3-pm4-migration/SKILL.md) checklist) |
| **ANONYMOUS** | PM4 Start `pm:assignment="user"` + **`user_assignment`** → workspace user **`_pm4_anon_user`** (PM3 “Guest” is not imported by UID; same role as anonymous web entry). Web Entry mode **Anonymous** when `assignedUsers` matches that id after import. Confirm guest start permissions ([pm4-process](../pm4-process/SKILL.md)). |
| **LOGIN_REQUIRED** | PM4 login before form; not anonymous guest |
| **WS** (`WE_METHOD`) | No HTML public screen — **HITL**: REST/API case start redesign (not a 1:1 `.php` endpoint port) |
| **`WE_TYPE` MULTIPLE** | Allow multiple submissions per link/session rules — confirm product behavior on PM4 |
| **`WE_CALLBACK` CUSTOM / CUSTOM_CLEAR** | Redirect after submit — map to `completed_screen_id` or custom URL; **HITL** if hard-coded PM3 PHP callback |
| Start is **message / timer / signal**, not empty | Configure **that** start in BPMN first ([pm3-message-type](../pm3-message-type/SKILL.md), [pm4-process](../pm4-process/events.md)); web entry is **additional** public path, not a substitute for message catch |
| `workflow-files/public/*.php` info pages | Thank-you / error UX → PM4 **completed** DISPLAY/FORM screen; do not copy PHP into PM4 |

Do **not** hand-edit `process_package` JSON to invent `web_entry` blobs. Use Modeler Web Entry inspector after lookup on the instance.

## PM3 → PM4 mapping (typical HTML)

| PM3 | PM4 |
|-----|-----|
| `WE_TITLE` / `wee_title` | Web Entry title (admin + link label) |
| `DYN_UID` / task dynaform step | `screen_id` → published FORM screen uuid |
| `TAS_UID` / `act_uid` | Process opens on that task’s screen (Form Task `pm:screenRef` or start → first task) |
| `WE_AUTHENTICATION` ANONYMOUS | Anonymous Web Entry + process start permission for guest |
| `WE_HIDE_INFORMATION_BAR`, skin/language/domain | Partial — confirm what must match brand; many are **HITL** |
| `wee_we_url` / `*.php` link | **New** PM4 web entry URL on target host — never reuse PM3 workspace path |
| Success message PHP (`workflow-files/public`) | `completed_screen_id` or generic thank-you screen |

## Build order

1. **Import/publish** converted process ([pm4-process](../pm4-process/SKILL.md)); fix screens Preview ([pm4-screen](../pm4-screen/SKILL.md)).
2. Identify **which BPMN start** or **Form Task** owns the public form; disable **interstitial** on that start if the public form replaces it.
3. Enable/configure **Web Entry** on Start or Form Task (instance must have `package-webentry`).
4. Set **screen** and **completed screen**; test anonymous submission in a private window.
5. If **message/timer start** also exists, test both entry paths separately.

## Verify

- [ ] `report-web-entry` (or plan JSON) lists every `WEB_ENTRY` row and every `webEntryEvent` row
- [ ] Public FORM screen Preview works; field names bind to request `data`
- [ ] Web Entry URL opens without login when PM3 was ANONYMOUS
- [ ] Case lands on expected task; assignment matches anonymous Form Task rules
- [ ] Thank-you / error UX after submit (completed screen)
- [ ] Message/timer/signal starts still work if present
- [ ] WS-only or CUSTOM callback entries documented as HITL, not silently dropped

## HITL — stop and ask

WS endpoints, CUSTOM callback URLs, ADVANCED link domains, captcha/legal attestation, input-document access via web entry (`WE_INPUT_DOCUMENT_ACCESS`), and compliance around anonymous PII. Ask what PM3 did in production before configuring PM4.

## Wrong skill?

| Need | Skill |
|------|--------|
| Create/edit PM3 Web Entry in Setup or Designer only | [pm3-web-entry](../pm3-web-entry/SKILL.md) |
| Public form screen controls with no migration context | [pm4-screen](../pm4-screen/SKILL.md) |
| BPMN import, start event permissions, interstitial | [pm4-process](../pm4-process/SKILL.md) |
| Assignment on non-anonymous tasks | [pm4-form-manual-task](../pm4-form-manual-task/SKILL.md) |
| report / convert CLI | [pm-analyze](../pm-analyze/SKILL.md) |
| Migration gaps / checklist | [pm3-pm4-migration](../pm3-pm4-migration/SKILL.md) |
