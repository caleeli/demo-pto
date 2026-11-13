# Screen `config` that Screen Builder can open

`POST /api/1.0/screens` accepts almost any `config` array and returns **201**. That is not proof the screen works. Hand-authored items without the expected builder metadata can produce a screen the **Screen Builder cannot open**: `/designer/screen-builder/{id}/edit` renders the page chrome and no canvas, no page tab, no fields.

Do not judge a screen by the 201. Open the builder.

A plain `PUT /api/1.0/screens/{id}` usually drops a new `config` and still returns **204**. Core replaces the submitted `config` with the latest saved version (the draft if there is one) whenever the title, description, category, or `projects` differ from the stored row. Omitting `projects` always counts as a difference. To change `config`, send it to `PUT /screens/{id}/draft`, then publish with `PUT /screens/{id}` sending only `title`, `description`, and `type`. That copies the draft into the screen. The exact rules are in [architecture.md](architecture.md#updating-config).

## Why hand-authored items fail

Every control that Screen Builder saves carries an `inspector` array — the full property-panel definition for that control (Variable Name, Label, Data Type, Validation Rules, colors, …). A `FormInput` has ~18 inspector entries.

The builder reads `inspector` from the stored item. It does **not** rebuild it from the component registry. An item without `inspector` breaks the load.

Measured on a 4.x instance, all screens created with `POST /api/1.0/screens`:

| `config` | Builder canvas |
|----------|----------------|
| Items with `uuid`, `order`, `dataFormat`, `validation: []`, no `inspector` | fails — 0 page tabs |
| Items missing `uuid` and page `order` too | fails — 0 page tabs |
| `[]` (no page at all) | toolbar loads, no page |
| `[{"name": "Page 1", "order": 0, "items": []}]` | **opens** |
| Items deep-copied from a Designer screen, `inspector` intact | **opens** |

Adding `uuid`, `order`, `dataFormat`, or an array `validation` does not help on its own. `inspector` is the deciding field.

A screen with `config: []` is also wrong: the builder toolbar loads but there is no page to drop controls on. Always ship at least one page.

## Use one of these three

### A. Shell via API, fields in Screen Builder (default)

POST one empty page, then add the requested controls in the builder. The [FORM shell example](examples/form-shell.json) uses this shape; it is builder-loadable, not a finished task screen:

```json
{
  "title": "FormScreenShell",
  "description": "Empty FORM shell; add the requested controls and a Submit button before using it to complete a task.",
  "type": "FORM",
  "status": "ACTIVE",
  "screen_category_id": "SCREEN_CATEGORY_ID",
  "computed": [],
  "custom_css": null,
  "watchers": [],
  "config": [{ "name": "Page 1", "order": 1, "items": [] }]
}
```

Replace `SCREEN_CATEGORY_ID` with an existing category id from [asset-map.md](asset-map.md#lookups-never-invent-ids); never assume `1`. Then open `/designer/screen-builder/{id}/edit`, add the requested controls, and Save/Publish as appropriate for the installed version.

A FORM screen used to complete a Form Task needs:

| Control | Where | Notes |
|---------|--------|-------|
| Requested inputs, if any (e.g. Line Input) | Input Fields | Variable Name is the request data key; an acknowledgment-only form need not collect an input |
| Submit Button | Navigation | Without it the assignee cannot complete the task |

Use the fields the user asked for; ask only when their requested data-entry requirements are unclear. Do not invent an input just to make a form valid. The Submit button is still needed for FORM task completion, even when the screen only displays information for acknowledgment. A Regular Button with `event: "script"` does not submit the task.

Completion requirements depend on the screen type; FORM rules must not be copied to the other types. See [Completion by screen type](#completion-by-screen-type).

### B. Clone an existing Designer screen

When the fields must be set programmatically:

```
GET /api/1.0/screens/{sourceId}
```

Deep-copy its `config`. Change only `items[].config` values the user asked for (`name`, `label`, `placeholder`) and give every item a fresh `uuid`. Keep `inspector`, `component`, `editor-component`, `editor-control` untouched. POST the result.

Never retype `inspector` by hand and never share a `uuid` between two items.

Clone **only** control types that already exist on a Designer screen on **this** instance. File Upload, Select, and similar cannot be invented. If no source item has that `component`, open the builder, add the requested control (e.g. Files → File Upload), and Save/Publish. Do not substitute an unrelated Line Input or hand-author File Upload JSON.

### C. Import a screen export

```
POST /api/1.0/screens/import
```

Takes a file ProcessMaker produced via `POST /api/1.0/screens/{id}/export`. Use this to move a screen between instances.

## Item shape (read-only reference)

What Screen Builder actually stores per page:

```json
{ "name": "Page 1", "order": 0, "items": [ /* controls */ ] }
```

Per control:

```json
{
  "uuid": "aa62f1eb-bd63-4436-85aa-580dd2f8dbdd",
  "label": "Line Input",
  "component": "FormInput",
  "editor-component": "FormInput",
  "editor-control": "FormInput",
  "config": {
    "icon": "far fa-square",
    "name": "name",
    "type": "text",
    "label": "Name",
    "helper": null,
    "readonly": false,
    "dataFormat": "string",
    "validation": [],
    "placeholder": "INV-1001"
  },
  "inspector": [ /* ~18 property definitions */ ]
}
```

Notes that trip up hand-written JSON:

- `validation` is an **array**, not `"required"`.
- Screen Builder saves the Submit button as `component: FormButton`, `editor-component: FormButton`, `editor-control: FormSubmit`, `event: "submit"`, `defaultSubmit: true`, `fieldValue: null`. `event: "script"` makes it a Regular Button that does not submit ([controls.md](controls.md#buttons)).
- Page needs `order`.

## Completion by screen type

| Screen type | Completion contract |
|-------------|---------------------|
| `FORM` | A screen used to complete a Form Task has a Submit button with `event: "submit"`. Inputs are needed only when the user wants to collect data. |
| `DISPLAY` | Read-only instructions/context. No input fields or screen-level Submit control. When used by a Manual Task, the task renderer supplies **Complete Task** outside the screen. |
| `CONVERSATIONAL` | Requires `package-conversational-forms`. Do not add a FORM Submit control; the package submits answers and completes the form after its last item ([config-schema.md](config-schema.md#conversational)). |
| `EMAIL` | Email body, not an interactive task screen. Its Submit-style controls render as Actions by Email links when that capability is available ([screen-types.md](screen-types.md#email)). |

Screen types and available controls: [screen-types.md](screen-types.md). Screen checks: [verify.md](verify.md). Do not start a request or complete a task just to verify screen configuration unless the user asked.

## Verify the screen before wiring the task

```
- [ ] The screen exists with the intended type and any required package is installed; 201 alone is not proof it works
- [ ] /designer/screen-builder/{id}/edit shows a page tab
- [ ] Requested controls render in preview; FORM task screens have a Submit action, DISPLAY has no inputs/Submit, CONVERSATIONAL uses package completion
- [ ] Changes are published, not only saved as a draft
- [ ] Only then wire the real screen id into the intended task or other asset
```

A screen that fails to open in the builder can still be referenced by a process and render badly or empty at runtime. Catch it here; screen creation does not authorize creating a process or running a request.
