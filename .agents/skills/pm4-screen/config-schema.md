# Screen config schema

What `config`, `computed`, and `custom_css` hold, per screen type. For a `config` that Screen Builder can open (`inspector`, `uuid`, `editor-control`, the Submit button), follow [screen-config.md](screen-config.md). This file does not repeat it.

Shapes below come from screens saved by Screen Builder on core `2026.15.3` / screen-builder `3.8.43`, and from the renderer source.

## Pages

`config` is an array of pages:

```json
[
  { "name": "Page 1", "order": 1, "items": [] },
  { "name": "Review", "order": 2, "items": [] }
]
```

- Screen Builder gives a new page `order = max(order) + 1`.
- **Navigation uses the array index, not `order`.** A `FormButton` with `event: "pageNavigate"` and `eventData: 1` opens `config[1]`. Keep the array sorted by `order`.
- Each page needs `name`, `order`, and `items`. Designer numbers pages from 1. On load, Screen Builder replaces a missing or `0` order with the page's position + 1 (`page.order || index + 1`), so `0` still opens.

| Type | Pages used at run time |
|------|------------------------|
| FORM | all |
| DISPLAY | all |
| CONVERSATIONAL | `config[0]` only |
| EMAIL | `config[0]` only |

## Items

Each item is a control:

```json
{ "uuid": "…", "label": "…", "component": "…", "editor-component": "…", "editor-control": "…", "config": {}, "inspector": [] }
```

The full control shape and its traps are in [screen-config.md](screen-config.md#item-shape-read-only-reference). The list of controls is in [controls.md](controls.md).

### Containers

| Component | Extra top-level keys | `items` shape | Config |
|-----------|----------------------|---------------|--------|
| `FormMultiColumn` (Table) | `container: true` | **array of columns**, each an array of items: `[[…], […]]` | `options: [{"value": 1, "content": "6"}, {"value": 2, "content": "6"}]`; `content` is the Bootstrap width; widths must add up to 12 |
| `FormLoop` | `container: true` | flat array of items | `name` (array variable); `settings: {"type": "existing"\|"new", "varname", "times", "add"}` |
| `FormNestedScreen` | none | none | `screen`: child screen id (number). See [screen-types.md](screen-types.md#nesting-by-type) |

Walk containers recursively when searching for a field, cloning, or renaming variables. A flat scan of `page.items` misses controls inside tables and loops.

### Properties every control has

Screen Builder appends these inspector fields to every control (screen-builder `global-properties.js`). Their values live in `item.config`:

| `config` key | Inspector label | Value |
|--------------|-----------------|-------|
| `conditionalHide` | Visibility Rule | Expression. The control is **shown when it is true** (despite the name). Empty means always shown |
| `deviceVisibility` | Device Visibility | `{"showForDesktop": true, "showForMobile": true}` |
| `customFormatter` | Custom Format String | Mask, e.g. `###-##-####` |
| `customCssSelector` | CSS Selector Name | Name used in `custom_css` as `[selector='name']` |
| `ariaLabel` | Aria Label | Text |
| `tabindex` | Tab Order | Digits |
| `encryptedConfig` | Encrypted | Field encryption settings |

Expressions in `conditionalHide` use [expr-eval](https://github.com/silentmatt/expr-eval) syntax: `and`, `or`, `==`, `!=`. `+` is numeric only; join strings with `||`.

## Per type

### FORM and DISPLAY

Same page and item shape. DISPLAY simply has no input controls ([screen-types.md](screen-types.md)).

### CONVERSATIONAL

`ConversationalForm` (package-conversational-forms) reads `config[0].items` as a **flat list** and shows one item at a time as a chat:

- `FormHtmlViewer`, `FormBreak`, and `FormJump` are bot messages. Inputs (Line Input, Select List, Date Picker, File Upload) are questions.
- **Don't add a Submit control.** Each question shows its own Submit button that sends the answer, and after the last item the form submits itself.
- Items whose `conditionalHide` is false are skipped.
- `FormJump` has `config.jump_configuration.config: [{ "condition": "…", "destination": "Label" }]`. The first true condition jumps to the item after the `FormBreak` whose `config.label` equals `destination`. **Rename a Break and every Jump that targets it breaks.**
- Containers (tables, loops) and nested screens are not walked. Keep everything top-level.

[examples/conversational-minimal.json](examples/conversational-minimal.json) is a working starting point: an intro message, one required question, and a closing message that uses the answer. The palette offers Line Input, Select List, Rich Text, File Upload, Date Picker, Action, Break, and Jump. Their `inspector` arrays match the FORM ones, so items cloned from a FORM screen work.

### EMAIL

One page of `FormHtmlViewer` (`editor-control: "FormHtmlEditor"`), `FormRecordList`, `LinkButton`, and `FormButton` items. Any other component prints the literal text `undefined` in the email ([screen-types.md](screen-types.md#email)).

A Rich Text item saved by Screen Builder on an EMAIL screen:

```json
{
  "label": "Rich Text",
  "component": "FormHtmlViewer",
  "editor-component": "FormHtmlEditor",
  "editor-control": "FormHtmlEditor",
  "config": { "icon": "fas fa-pencil-ruler", "label": null, "content": "<p>Hello {{_user.firstname}}</p>", "interactive": true, "renderVarHtml": false },
  "uuid": "…",
  "inspector": []
}
```

- `content` is Mustache-rendered with the request data. Values are HTML-escaped unless `renderVarHtml` is true.
- `LinkButton.config.linkUrl` is Mustache-rendered too.
- A `LinkButton` with `variantStyle: "button"` adds `<img src="{{_app_url_arrow_img}}">` before its label. Only task notification emails set `_app_url_arrow_img` (`{app.url}/img/arrow-top-right.png`). In a Send Email task the image is empty unless you add that variable to the request data. Use `variantStyle: "link"` for a plain link with no image.

[examples/email-minimal.json](examples/email-minimal.json) has two Rich Text items and a Link Button cloned from "Default Email Task Notification".

`inspector` is shortened to `[]` here only for reading. Clone real items to get it ([screen-config.md](screen-config.md)).

## Calculated properties (`computed`)

An array of objects, one per calculated property:

```json
[
  {
    "id": 1,
    "name": "Transcript count",
    "type": "javascript",
    "order": 1,
    "byPass": false,
    "formula": "return Array.isArray(this.transcripts) ? this.transcripts.length : 0;",
    "property": "transcriptCount"
  },
  {
    "id": 2,
    "name": "Full name",
    "type": "expression",
    "order": 2,
    "byPass": false,
    "formula": "firstName || \" \" || lastName",
    "property": "fullName"
  }
]
```

| Key | Notes |
|-----|-------|
| `id` | Unique within the screen; Screen Builder numbers them 1, 2, … |
| `name` | Label shown in Screen Builder |
| `property` | Data key the result is written to. Controls and other properties read it like any variable |
| `type` | `expression` (expr-eval; read-only, so assigning throws) or `javascript` (function body; `this` is the screen data; must `return`) |
| `formula` | The expression or function body |
| `byPass` | `true` disables the property without deleting it |
| `order` | Order in the Screen Builder list |

- They re-run when the data they read changes. Errors are logged to the browser console as `Calc` and the value is not set.
- EMAIL screens evaluate `computed` on the server before rendering, using the same two types.
- Keep formulas free of DOM code. A `javascript` formula can run jQuery, but it also runs in the email renderer (Node), where `$` does not exist.
- Data-changing logic that calls a script or a Data Connector belongs in a watcher, not here ([watchers.md](watchers.md)).

## Custom CSS (`custom_css`)

A CSS string, edited in Screen Builder under **Custom CSS**:

```css
[selector='main-container'] { padding: 15px; }
[selector='main-container'] .form-group { margin-bottom: 0; }
```

- Target controls with `[selector='name']`, where `name` is the control's `customCssSelector`.
- **Browser renderer** (FORM, DISPLAY, CONVERSATIONAL):
  - Every selector is prefixed with `.custom-css-scope`, so rules cannot leak outside the screen.
  - `@media (min-width / max-width)` blocks are matched against the **screen container's width**, not the window.
  - More than about 5000 CSS nodes throws "CSS is too long".
  - A CSS syntax error is reported in Screen Builder's Custom CSS editor.
- **Email renderer** (EMAIL): the CSS goes into a `<style>` block. `[selector='x']` becomes `.x`, and each control's `selector="x"` attribute becomes `class="x"`.
- **The API sanitizes `custom_css`.** HTML tags and `{{` / `}}` are removed, so `content: "<i>"` becomes `content: ""` ([architecture.md](architecture.md#input-sanitization)). Use CSS escapes (`\3C`) if a literal `<` is needed.
