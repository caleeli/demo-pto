# Screen types

A screen's `type` is a `screen_types.name`. It decides which controls Screen Builder offers, which component renders the screen, and where a process may point at it.

| Type | Purpose | Seeded by | `is_interactive` | Referenced by | Rendered by |
|------|---------|-----------|------------------|---------------|-------------|
| `FORM` | Collect data; multi-page, validation, Submit | core | yes | Form Task, Web Entry, nested in a FORM | `vue-form-renderer` |
| `DISPLAY` | Show data, no input | core | no | Manual Task, End / Signal End / Terminate End summary, `interstitialScreenRef`, nested in a FORM or DISPLAY | `vue-form-renderer` |
| `CONVERSATIONAL` | Chat-style form, one question at a time | `package-conversational-forms` | yes | Form Task, Web Entry | `ConversationalForm` (package) |
| `EMAIL` | Email body | `connector-send-email`, `package-actions-by-email`, `package-savedsearch` (any one) | no | Send Email, task email notifications, Actions by Email, Saved Search reports | Node `EmailRenderer` on the server (connector-send-email) |

Exact BPMN fields are in [SKILL.md](SKILL.md#links-to-other-assets).

## Checking the type exists

- No API endpoint lists screen types. Designer reads `screen_types` directly when it opens `/designer/screens`.
- **`POST /screens` only validates `type` as `required`.** It accepts any string, including a type the instance doesn't have, and returns 201. Check first:
  - `GET /api/1.0/screens?type=CONVERSATIONAL&per_page=1` shows whether screens of that type exist.
  - Ask whether the package is installed; [asset-map.md](asset-map.md) lists the packages per type.
- `GET /api/1.0/screens?interactive=true` returns screens of every `is_interactive` type (FORM and CONVERSATIONAL). The Form Task and Web Entry screen pickers use `type=FORM,CONVERSATIONAL`.
- `screen_types` can hold duplicate FORM / DISPLAY rows (the core seeder uses `create`). That is harmless.

## How a type gets its controls

Every page that renders a screen fires `ScreenBuilderStarting($manager, $type)`: Screen Builder, the task page, Web Entry, Cases. Listeners add scripts:

| Type | Script | Added when |
|------|--------|------------|
| FORM | core `typeForm.js` | `$type === 'FORM'` |
| DISPLAY | core `typeDisplay.js` | `$type === 'DISPLAY'` |
| CONVERSATIONAL | `package-conversational-forms` `typeConversational.js` | always; it acts only when `screen.type === 'CONVERSATIONAL'` |
| EMAIL | `connector-send-email` `typeEmail.js` | `$type === 'EMAIL'` |

Core also loads each package's `screen-builder-{type}-components.js` for the type (package controls, [controls.md](controls.md)).

Controls per type in Screen Builder:

- **FORM**: all core controls except `FormListTable`, `FormAnalyticsChart`, `FormAvatar`, `LinkButton`, `FormCollectionViewControl`, `CaseProgressBar`.
- **DISPLAY**: Rich Text, Table (`FormMultiColumn`), Record List (read-only), Image, Avatar, Loop, Nested Screen, File Download, List Table, Analytics Chart, Collection View, Case Progress Bar. No inputs, no Submit.
- **CONVERSATIONAL**: Line Input, Select List, Rich Text, File Upload, Date Picker, plus the package's Inline Action (`FormInlineAction`), Break (`FormBreak`), and Jump (`FormJump`).
- **EMAIL**: Rich Text, Record List (read-only), Link Button, and a Submit-style button. See [EMAIL](#email) for what actually renders.

Details per control: [controls.md](controls.md).

## FORM

- A FORM used to complete a task needs a Submit button. Collect inputs only when required by the user's workflow; an acknowledgment-only form need not add an input ([screen-config.md](screen-config.md#completion-by-screen-type)).
- It can have several pages. A `FormButton` with `event: "pageNavigate"` moves to the page whose **array index** is in `eventData` ([config-schema.md](config-schema.md#pages)).

## Nesting by type

The Nested Screen picker (screen-builder `screen-selector.vue`) offers:

| Parent | Child types offered |
|--------|---------------------|
| FORM | FORM, DISPLAY |
| DISPLAY | DISPLAY |

The picker always excludes the screen itself. The API doesn't enforce these rules, so a hand-set `config.screen` can break them. Keep to the table.

## DISPLAY

- Read-only. Nothing in it writes request data.
- A DISPLAY screen used by a Manual Task contains instructions/context, not inputs or a Submit control. The task renderer supplies **Complete Task** outside the screen ([screen-config.md](screen-config.md#completion-by-screen-type)).
- Interstitial screens are DISPLAY. The one with `key = "interstitial"` is the system default ([architecture.md](architecture.md#export-and-import)).

## CONVERSATIONAL

- **Needs `package-conversational-forms` wherever it is edited or run.** Without it:
  - Screen Builder opens with no controls, because core loads controls only for FORM and DISPLAY.
  - The task page cannot render the screen, because `ConversationalForm` is never registered.
- Task and Web Entry switch to `ConversationalForm` when `screen.type === 'CONVERSATIONAL'` (screen-builder `task.vue`).
- Start from [examples/conversational-minimal.json](examples/conversational-minimal.json) (needs `package-conversational-forms`). Item rules are in [config-schema.md](config-schema.md#conversational).

## EMAIL

- The Send Email connector loads the screen with `Screen::find()` (the published row) and renders it on the server in Node (`renderer/dist/email-renderer.js`). The browser renderer is not used.
- **Only the first page is rendered.** Other pages are dropped.
- **Only these components render:** `FormHtmlViewer` / `FormHtmlEditor` / `FormHtmlEditorStatic` (Rich Text), `FormRecordList`, `LinkButton`, and `FormButton`.
  - Any other component (a nested screen, an input, a loop) is replaced by the literal text `undefined` in the email.
- A `FormButton` becomes a link to `{{abe_uri}}/tasks/update_variable/{{token_abe}}?varName=…&varValue=…`. Those two values come from Actions by Email. Without that package, the link is broken.
- `computed` runs. `watchers` never run (the renderer passes `[]`).
- `custom_css` is inlined into a `<style>` block. `[selector='x']` becomes `.x`, and each `selector="x"` attribute becomes `class="x"`.
- Everything is Mustache-rendered with the request data, so `{{var}}` works in Rich Text, Link Button URLs, and labels.
- `screenRef` on Send Email is itself Mustache-rendered, so it can be `{{emailScreenId}}`.
- Start from [examples/email-minimal.json](examples/email-minimal.json) (needs `connector-send-email` to send it). The Link Button image caveat is in [config-schema.md](config-schema.md#email).
