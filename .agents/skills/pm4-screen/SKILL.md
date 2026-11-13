---
name: pm4-screen
description: >-
  Create, edit, publish, import/export, and debug PM4 FORM, DISPLAY,
  CONVERSATIONAL, and EMAIL screens: controls, config, watchers, computed values,
  custom CSS, and templates; new FORM/DISPLAY screens are built as a Vue
  Component Editor mount. Not task wiring (pm4-form-manual-task), process
  graphs (pm4-process), AI generation, or request execution.
---

# PM4 Screen

A **screen** is a row in `screens`: metadata (`title`, `description`, `type`, category) plus three JSON columns the Screen Builder edits: `config` (pages and controls), `watchers`, and `computed`, and a `custom_css` string. Processes and packages point at a screen by **numeric id**.

This skill works on an **existing** instance. Do not create a demo process, start a request, or complete a task unless the user asked.

References: [vue-component-editor.md](vue-component-editor.md) (**read first for any new FORM or DISPLAY screen**), [refactor-parity.md](refactor-parity.md) (rewriting a native screen as Vue), [architecture.md](architecture.md) (storage, versions, drafts, rendering), [screen-types.md](screen-types.md), [screen-config.md](screen-config.md) (builder-loadable config and completion requirements), [config-schema.md](config-schema.md), [controls.md](controls.md), [watchers.md](watchers.md), [templates.md](templates.md), [asset-map.md](asset-map.md) (API, files), [verify.md](verify.md) (checks, failures). Examples: [examples/](examples/).

User docs: [Screens](https://docs.processmaker.com/docs/screens). Where they disagree with these files, trust these files: they were checked against the code and a running instance. Known differences:

- Screen names: the docs allow letters, digits, apostrophes, and spaces. The `alpha_spaces` rule also accepts `-`, `_`, and `.` (see Asset schema).
- Submit Button: the docs say it is only on FORM screens. EMAIL screens offer it too (`connector-send-email` `typeEmail.js`), and it renders as an Actions by Email link ([screen-types.md](screen-types.md#email)).
- "Do Not Submit, Set Value" (Regular Button) doesn't just set the value. Each click toggles it between the value and `null` ([controls.md](controls.md#buttons)).

## When to use

- Create, edit, duplicate, publish, or delete a screen of any type.
- Add or change controls, nested screens, watchers, calculated properties, or custom CSS.
- Save a screen as a template, create a screen from a template, or apply a template.
- Export or import screens, or check what travels with them.
- A screen that won't open in Screen Builder, loses its changes on save, or renders empty.

## Vue Component Editor rule

**New FORM and DISPLAY screens, and rewrites of existing ones, must render their main UI through the Vue component Editor control** (`VueComponentRenderTemplate`) with a thin `contentVue` mount of a registered Vue component. Do not build the main layout from native controls. Rules, control shape, component registration, and domain fields (uploads, catalog selects, signatures): [vue-component-editor.md](vue-component-editor.md). A rewrite must keep the original's fields, behavior, calcs, watchers, nested screens, and help content: [refactor-parity.md](refactor-parity.md).

Small edits to an existing screen keep its current structure: add a native control to a native screen, or edit the component of a Vue screen. EMAIL and CONVERSATIONAL screens are out of scope. The rest of this file and the native-control references ([controls.md](controls.md), [config-schema.md](config-schema.md)) apply to those edits and to EMAIL, CONVERSATIONAL, and Submit/helper controls.

## Not this skill

| User wants | Use instead |
|------------|-------------|
| Wire a screen into a Form/Manual Task: `pm:screenRef`, assignment, due date | [pm4-form-manual-task](../pm4-form-manual-task/SKILL.md) |
| Process graph, BPMN, publish a process | [pm4-process](../pm4-process/SKILL.md) |
| Generate a screen with the AI Assistant | not covered |
| The script a watcher runs | [pm4-script](../pm4-script/SKILL.md) |
| A Data Connector a watcher calls | [pm4-data-connector](../pm4-data-connector/SKILL.md) |
| Signal throw/catch in BPMN | [pm4-signal](../pm4-signal/SKILL.md) |
| Collection CRUD | [pm4-collection](../pm4-collection/SKILL.md) |
| Editing Screen Builder or renderer source | [vue2-pm4-patterns](../vue2-pm4-patterns/SKILL.md) |
| 422 / PMQL / pagination on other endpoints | [pm-api-contracts](../pm-api-contracts/SKILL.md) |
| Local instance down | [setup-processmaker](../setup-processmaker/SKILL.md) |

## Before changing anything

Ask only if missing:

1. **Screen**: existing title or id, or the title for a new one.
2. **Type**: `FORM` (collects data), `DISPLAY` (shows data), `CONVERSATIONAL` (chat-style form), or `EMAIL` (email body). See [screen-types.md](screen-types.md).
3. **Fields**: the request variables to read or write. Do not invent a field list.
4. **Category**: existing screen category, or `Uncategorized`.
5. **Where it is used**: Form Task, Web Entry, Send Email, notification, nested in another screen. Wiring is done by the skill that owns that node.

Then look up real ids ([asset-map.md](asset-map.md#lookups-never-invent-ids)). **Never invent screen, category, script, or data source ids.**

## Asset schema

| Field | Required | Notes |
|-------|----------|-------|
| `title` | yes | Unique across screens. `alpha_spaces`: Unicode letters, digits, whitespace, `-` `_` `.` `'`; no `()`, `,`, `:`, `/` |
| `description` | yes | Plain text |
| `type` | yes | `FORM`, `DISPLAY`, `CONVERSATIONAL`, `EMAIL`; the last two need their package |
| `screen_category_id` | no | Category id, or a comma-separated list. Stored as a string |
| `config` | no | Array of pages: `[{"name", "order", "items": [...]}]`. Ship at least one page |
| `computed` | no | Calculated properties; `[]` when empty |
| `watchers` | no | Watchers; `[]` when empty |
| `custom_css` | no | CSS string or `null` |
| `projects` | no | JSON array string, e.g. `"[]"`; matters on update (see Rules) |

`title`, `description`, and `type` are required on **every** `PUT`, not only on create.

## Rules

- **Create** with `POST /api/1.0/screens`. A 201 only proves the row exists, not that Screen Builder can open it. Follow [screen-config.md](screen-config.md) for the `config` itself.
- **Change `config` through a draft**, the way Designer does:
  1. `PUT /api/1.0/screens/{id}/draft` with `title`, `description`, `type`, and the full `config` / `computed` / `watchers` / `custom_css`.
  2. `PUT /api/1.0/screens/{id}` with `title`, `description`, `type` to publish the draft.
  3. `POST /api/1.0/screens/{id}/close` discards an unwanted draft.
- **A direct `PUT /screens/{id}` silently drops `config`** (still 204) unless `projects` is sent as a JSON array string (`"[]"`), the category is omitted or sent as the stored string, and title and description are unchanged. Prefer the draft flow. Details: [architecture.md](architecture.md).
- **FORM screens need a Submit button** if a task must be completed with them; inputs are needed only when the requested workflow collects data. DISPLAY and CONVERSATIONAL have different [completion requirements](screen-config.md#completion-by-screen-type).
- **Vue mount first for new FORM/DISPLAY screens** ([vue-component-editor.md](vue-component-editor.md)); the clone rule below applies to native controls you still need (small edits, a submit button, EMAIL, CONVERSATIONAL).
- **Clone controls; don't hand-type them.** Copy items from a screen built in Designer on this instance, give each a new `uuid`, and keep `inspector`, `component`, `editor-component`, `editor-control` as they are.
- **Package types and controls need their package on the target instance.** CONVERSATIONAL needs `package-conversational-forms`; EMAIL needs `connector-send-email` (or `package-savedsearch` / `package-actions-by-email`). See [screen-types.md](screen-types.md) and [controls.md](controls.md).
- **Nested screens are referenced by id** in `config.screen` of a `FormNestedScreen` item. A screen must not nest itself or a parent: export fails after 10 levels of nesting.
- **Move screens with the official export**, never a hand-made package. See [asset-map.md](asset-map.md).

## PM3 migration (converter packages)

When fixing or finishing screens produced from a PM3 export (`pm-analyze` / `tools/pm-migrate`):

- **`FormMultiColumn`**: PM3 **`colSpan`** rows (two+ fields in one row, all with `colSpan`). Match column widths in `config.options` to the PM3 spans (bootstrap widths sum to 12). See [config-schema.md](config-schema.md#containers).
- **`FormRecordList`**: PM3 **`type: grid`** control. `config.form` must point at the add/edit **page index** after import (often a second page on the same screen); package export may ship a numeric placeholder — fix so the row form opens. See [controls.md](controls.md).
- **`FormLoop`**: not the default output of the current grid mapping; if you introduce it for repeating in-place rows, follow [config-schema.md](config-schema.md#containers) (`container: true`, valid `items` and `settings`) — see [PM3 migration FormLoop container](../../knowledge/findings/pm3-migration-formloop-missing-container.md).
- **Dependent dropdowns** and other PM3-only behaviors: rebuild with visibility rules, data sources, or watchers as needed ([watchers.md](watchers.md)).

Package import quirks (v2 manifest): see [import-package-rules.md](../pm3-pm4-migration/import-package-rules.md): `config` may ship as a JSON **string** (DB stores an array after import), `validation` arrays, `renderAs` dropdown/checkbox only (Screen Builder inspector), FormLoop / inner `FormMultiColumn` with `container: true`, non-empty `description` (`Process::rules` / `Screen::rules`). **Several screens** with blank Preview after import: start with [pm3-pm4-migration](../pm3-pm4-migration/SKILL.md) checklist; **one named screen** fix stays here. Always verify **Preview**, not only the Design canvas ([findings](../../references/findings-index.md) area `pm3-pm4-migration`).

See also [pm3-dynaform](../pm3-dynaform/SKILL.md) and [pm3-pm4-migration](../pm3-pm4-migration/SKILL.md).

## Links to other assets

| From | Field | Points to |
|------|-------|-----------|
| Form Task (`bpmn:task`) | `pm:screenRef` | FORM or CONVERSATIONAL screen id |
| Manual Task | `pm:screenRef` | DISPLAY screen id |
| End, Signal End, Terminate End Event (Summary Screen) | `pm:screenRef` | DISPLAY screen id |
| Start Event, Form Task (Display Next Assigned Task) | `pm:interstitialScreenRef` | DISPLAY screen id |
| Send Email connector | `pm:config.screenRef` | EMAIL screen id (Mustache-rendered, so it can be `{{var}}`) |
| Task email notifications | `pm:config.email_notifications.notifications[n].screenRef` | EMAIL screen id |
| Actions by Email (`bpmn:callActivity`) | `pm:config.screenRef` | EMAIL screen id when `bodyType` is `screen` — [pm4-action-by-email](../pm4-action-by-email/SKILL.md) |
| Screen `config` | `FormNestedScreen` → `config.screen` | Child screen id |
| Screen `watchers` | `script_id`, `script.id` | Script id, or a data source (`data_source-…`) |
| Screen | `screen_category_id` | Screen category id(s) |
| Collection | `create_screen_id`, `read_screen_id`, `update_screen_id` | FORM screens defining field schema (`pm4-collection`) |

Web Entry, Collections, and Saved Search store their own screen ids; see [asset-map.md](asset-map.md).

## Workflow

```
- [ ] 1. Confirm the type and that its package is installed (screen-types.md)
- [ ] 2. Look up ids: screen, category, child screens, scripts, data sources. Stop if any is missing
- [ ] 3. Create: POST /screens with one page, or create from a template (templates.md)
- [ ] 4. New FORM/DISPLAY screen or rewrite: build or reuse the Vue component and mount it (vue-component-editor.md). Small edit: clone controls from a Designer screen. Either way PUT /draft, then PUT to publish
- [ ] 5. Watchers and calculated properties only if asked (watchers.md, config-schema.md)
- [ ] 6. Open /designer/screen-builder/{id}/edit: page tab shows, controls load, preview renders
- [ ] 7. Static check (verify.md). Task screen/assignment config belongs to pm4-form-manual-task; process graph construction belongs to pm4-process
```

## Validation

```
- [ ] title unique and alpha_spaces; description present; type is one of the four
- [ ] config has at least one page with name and order
- [ ] Every item was cloned from a Designer screen (inspector present, unique uuid)
- [ ] FORM used to complete a task has a Submit button; input fields match the user's data-entry requirements, if any
- [ ] Every FormNestedScreen config.screen, watcher script, and data source exists on this instance
- [ ] Package for the type and for each package control is installed
- [ ] After the last PUT, GET /screens/{id} returns the config you sent (drafts are not live)
- [ ] Screen Builder opens the screen and preview renders
```

## Minimal example

Smallest screen Screen Builder opens: a FORM shell with one empty page ([examples/form-shell.json](examples/form-shell.json)). Replace `SCREEN_CATEGORY_ID`, then add the requested controls and a Submit action in Screen Builder if it will complete a task, or clone controls as in the Rules.

`POST /api/1.0/screens`

```json
{
  "title": "Approve Invoice Form",
  "description": "Approver reviews and submits the invoice",
  "type": "FORM",
  "screen_category_id": "SCREEN_CATEGORY_ID",
  "config": [{ "name": "Page 1", "order": 1, "items": [] }],
  "computed": [],
  "watchers": [],
  "custom_css": null
}
```

More examples, each a `POST /api/1.0/screens` body with items cloned from screens saved by Screen Builder:

| File | Shows |
|------|-------|
| [examples/vue-component-form.json](examples/vue-component-form.json) | FORM: one Vue component Editor mount (the default for new FORM/DISPLAY screens). Replace the category, collection id, and component key |
| [examples/form-minimal.json](examples/form-minimal.json) | FORM: one required Line Input and a Submit button |
| [examples/display-minimal.json](examples/display-minimal.json) | DISPLAY: Rich Text with a `{{variable}}` |
| [examples/nested-screen.json](examples/nested-screen.json) | FORM embedding a DISPLAY screen, plus an input and Submit |
| [examples/watcher-script.json](examples/watcher-script.json) | FORM with a script watcher writing `invoiceLookup`, shown in Rich Text |
| [examples/conversational-minimal.json](examples/conversational-minimal.json) | CONVERSATIONAL: intro, one required question, closing message. Needs `package-conversational-forms` |
| [examples/email-minimal.json](examples/email-minimal.json) | EMAIL: two Rich Text items and a Link Button. Sent by `connector-send-email` |

Replace the placeholders before posting:

- `SCREEN_CATEGORY_ID`: a screen category id, as a string.
- `CHILD_SCREEN_ID`: the child screen id, as a **number**.
- `SCRIPT_ID` (in `script_id` and `script.id`) and `SCRIPT_TITLE`: the watcher's script.

Then give every item a new `uuid` if you post an example more than once.
