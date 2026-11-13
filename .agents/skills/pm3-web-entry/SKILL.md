---
name: pm3-web-entry
description: >-
  ProcessMaker 3 web entries. Use for an anonymous HTML web entry, a public
  dynaform that starts a case, WE_METHOD, LOGIN_REQUIRED, WS method, or the
  WEB_ENTRY table. Not PM4 web_entry JSON.
---

# PM3 Web Entry

**Web entries** allow external users to start cases. Stored in `WEB_ENTRY` table with localized title/description in `CONTENT`.

## Asset schema — WEB_ENTRY (database)

| Field | Required | Notes |
|-------|----------|-------|
| `WE_UID` | auto | Web entry id |
| `PRO_UID` | yes | Target process |
| `TAS_UID` | yes | Target task to start |
| `WE_METHOD` | no* | `WS` (web service) or `HTML` (public form) — required by API |
| `DYN_UID` | no | Dynaform for HTML entry |
| `USR_UID` | no | User context for case creation |
| `WE_INPUT_DOCUMENT_ACCESS` | no | `0` or `1` — allow input document access |
| `WE_TYPE` | yes | `SINGLE` or `MULTIPLE` |
| `WE_AUTHENTICATION` | yes | `ANONYMOUS` or `LOGIN_REQUIRED` |
| `WE_CALLBACK` | yes | `PROCESSMAKER`, `CUSTOM`, or `CUSTOM_CLEAR` |
| `WE_LINK_GENERATION` | yes | `DEFAULT` or `ADVANCED` |
| `WE_CUSTOM_TITLE` | no | Custom title stored in DB |

\* `WE_METHOD` is nullable in DB but required by REST API (`WebEntry.php`).

## Asset schema — API / CONTENT (localized)

| Field | Storage | Notes |
|-------|---------|-------|
| `WE_TITLE` | `CONTENT` table | Display name (API field, not a DB column) |
| `WE_DESCRIPTION` | `CONTENT` table | Description (API field, not a DB column) |

APIs: `web-entry`, `web-entry-event` (BPMN start via web entry, links `ACT_UID`).

## Entry types

| Method | Behavior |
|--------|----------|
| `HTML` | Public dynaform (`DYN_UID`) renders for anonymous/guest user |
| `WS` | SOAP/REST endpoint starts case programmatically |

## Rules

- `TAS_UID` must point to a valid task in `PRO_UID`
- HTML entry requires `DYN_UID` configured for external display
- `WE_AUTHENTICATION = ANONYMOUS` requires process start permissions for guest
- `WE_LINK_GENERATION = ADVANCED` enables custom link skin/language/domain options
- Web entry URL is process-specific and workspace-specific
- BPMN **web entry event** on start event links canvas `ACT_UID` to web entry config

## Links to other PM3 assets

| Asset | Relationship |
|-------|--------------|
| `pm3-process` | Target `PRO_UID` and `TAS_UID` |
| `pm3-dynaform` | `DYN_UID` for HTML entry form |
| `pm3-variables` | Initial case data from form submission |

## Validation checklist

1. `TAS_UID` valid for target process
2. Authentication mode matches security requirements
3. `DYN_UID` dynaform fields map to process variables
4. `WE_CALLBACK` and `WE_LINK_GENERATION` set for WS entries
5. Process start permissions allow guest/external start when anonymous

## Common mistakes

- Confusing `WE_TITLE` (API/Content) with `WE_CUSTOM_TITLE` (DB column)
- Missing `TAS_UID` (required field)
- Web entry without guest start permission on process
- Dynaform not configured for web entry display
- Hardcoded workspace URL after environment move
