# Worked classifications

Inventory with **`report-web-entry`** (or **`report`** Web entry row). Configure on PM4 after import — do not paste PM3 `*.php` links into BPMN.

## Canvas web entry event (empty `webEntry` table)

| Field | Value |
|-------|--------|
| Export | `webEntry` table empty; one `webEntryEvent` row |
| Start | Message catch (`evn_message`, e.g. `LEAD`) — **not** an empty start |
| `act_uid` | First human task (e.g. Task 1) |
| `dyn_uid` | Empty — form comes from task **DYNAFORM** step (`primero`) |
| `we_authentication` | `ANONYMOUS` |
| `we_type` | `MULTIPLE` |
| Public file | `workflow-files/public/{evn_uid}Info.php` success/error page |

**Plan:** (1) Map message start → PM4 message catch ([pm3-message-type](../pm3-message-type/SKILL.md)). (2) After import, Web Entry on **Form Task** (or start + no interstitial) with `screen_id` = converted task FORM. (3) Replace PHP info page with **completed** screen. (4) Test anonymous URL and message-started case separately.

## Setup HTML web entry with dynaform

| Field | Value |
|-------|--------|
| `WEB_ENTRY` row | `WE_METHOD=HTML`, `DYN_UID` set, `TAS_UID` target task |
| `WE_AUTHENTICATION` | `ANONYMOUS` |
| Convert | Dynaform → FORM screen exists |

**Plan:** Web Entry `screen_id` = that screen (or clone for public-only layout). Guest permissions on process start. Assignment on task: expect anonymous Form Task behavior.

## WS web entry

| Field | Value |
|-------|--------|
| `WE_METHOD` | `WS` |
| `WE_CALLBACK` | `CUSTOM` with URL |

**Disposition:** **HITL** — design PM4 API/integration case start; no automatic HTML Web Entry mapping.
