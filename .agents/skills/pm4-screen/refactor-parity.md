# Refactor parity (native screen to Vue component)

Use when you **rewrite** an existing native Screen Builder screen as a [Vue Component Editor](vue-component-editor.md) screen: a redesign, a design-system restyle, or any "make it nicer" rewrite. The rewrite must keep the original's **request data contract**, **behavior**, and **help content**. A prettier subset that drops variables, calcs, watchers, nested screens, or help material breaks gateways, scripts, and emails, and leaves users without the guidance the original gave.

Read the original with `GET /api/1.0/screens/{id}`.

## Why computed and watchers do not run on a Vue-only screen

Screen Builder wires `computed` and `watchers` from the variables it parses out of native controls (for example a `FormButton` named `search`). On a screen whose only control is `VueComponentRenderTemplate`, those names are not in the variable list, and the watcher loader drops any watcher whose `watching` key is missing. Copying the original `computed` and `watchers` arrays onto the new screen therefore does **nothing**. Reimplement them inside the component and send `computed: []` and `watchers: []`.

## Inventory first

Do not start the component until this table exists for the original screen:

| Source | Capture |
|--------|---------|
| Controls | Every `config.name` (including nested paths such as `contact.phone`, FileUpload `data_name`, button `name` + `event` + `fieldValue`) |
| Control config | The **full** native `config` per control (see below) |
| Visibility | Every `conditionalHide` on the control **and its ancestors**; `readonly`; `disabled` |
| Selects | `optionsList`, `dataObject`, Collection, or Data Connector source |
| Computed | `property`, `type`, full `formula` |
| Watchers | `watching`, `script_id` or data source, `input_data`, `output_variable`, sync flags |
| Nested screens | Every nested-screen control: nested screen id, title, where it mounts, and a full inventory of **that** screen (call `GET /screens/{id}` on each; follow deeper nests) |
| Help content | Every visible `FormImage`, instructional `FormHtmlViewer`/Rich Text (tips, "how to find X", diagrams) and helper link: purpose, the field it supports, the asset or URL |

Tick every row when the new screen ships, including every nested screen id.

## Classify every control

`conditionalHide` means "show when the expression is true".

| Original | In the Vue component |
|----------|----------------------|
| Control or **ancestor** has literal `conditionalHide: "false"` | Never shown. Keep the key on `data` (watchers, scripts, emails may need it). **Do not** render an input |
| `conditionalHide` expression (for example `contact_found`) | Mirror it with `v-if` / `v-show` on the same request-data condition |
| `readonly` or disabled | Do not expose a free input. Show read-only if it was visible, otherwise omit |
| Present only to feed a watcher's `input_data` | Data-only: initialize on `data`, no control |
| Visible and interactive | Bind it in the UI |

Adding an editable control for a hidden data-only key (for example a Country select the user never saw) is a parity bug.

## Behavior config to carry over

For every native control, read its `config` and `inspector` values. Look may change; behavior must not.

| Area | Typical keys | In Vue |
|------|--------------|--------|
| Variable | `name`, `data_name` | Same request-data key |
| Type and format | `type`, `dataFormat`, `rows`, masks, currency | Same input semantics |
| Validation | `validation`, required, min/max, regex | Same rules |
| Visibility and state | `conditionalHide`, `readonly`, `disabled`, `hidden` | Same show/hide and editability |
| Data-affecting defaults | default value, placeholder that sets data | Same defaults |
| Options | `optionsList`, `dataObject`, Collection or connector ids, value/content keys | Same codes and sources |
| Buttons and events | `event`, `fieldValue`, `variant` when it changes the payload | Same submit/trigger payload |
| File and signature | `data_name`, accepted types, signature variable | Same persistence keys and limits |
| Rich Text with Mustache | bound `{{...}}` | The data stays on request `data` |

Free to change for a redesign: colors, fonts, spacing, borders, icons, button chrome, `customCss`, layout wrappers (as long as no field or rule is dropped), and label wording only if the user asked for a copy refresh.

Reading only `config.name` and rebuilding a pretty input while ignoring validation, hide rules, options, `fieldValue`, or file limits is a parity bug.

## Help and instructional content

Keep every teaching image, how-to text, tip alert, and helper link, restyled or condensed if you want (accordion, drawer, compact callout), but **not deleted**. For example a diagram showing where a serial number appears on a product, plus the text that points at it: dropping either is a parity bug even though neither has a variable.

## Nested screens become shared components

1. Record every nested-screen id and which parents use it.
2. Recreate each nested screen as a **dedicated shared Vue component**, registered as its own catalog row ([registration](vue-component-editor.md#registering-a-new-official-component)).
3. Every parent that nested the same id mounts or imports that **one** component. Do not copy the UI into each parent.
4. Bind it to the same request-data paths the nested screen used. Its calcs and watchers move into the shared component.
5. If the nested screen is also a standalone task screen, publish that screen as a thin mount of the **same** component.

Header, footer, and brand-only nests may be folded into the parent chrome only if the user agrees. Domain fields, summaries, and Mustache-bound data in nests are not optional.

## Calcs and watchers in the component

| Original | After |
|----------|-------|
| Computed (frontend/DOM: `window`, `document`, redirects) | Vue methods, `watch`, `mounted` |
| Computed (derived request data) | Vue `computed` plus `$set` onto `data` when BPMN, emails, or later screens read the key |
| Watcher with a backend script | Call it from Vue with the same script id and `input_data` fields: `POST /api/1.0/scripts/execute/{script_id}` with `{ data, config, sync: true }`. Write the result to the same `output_variable`. Secrets stay in the script |
| Watcher with a Data Connector or Collection | Call the same endpoint or collection id from Vue |
| Frontend-only watcher | Vue method or `watch` |

Trigger hygiene:

- A user action replaces the old FormButton: Search becomes a method that runs the script.
- Still write the legacy trigger keys to `data` when downstream assets read them (`search: "1"`, `verify: "1"`, `approve: "Yes"`), but do not expect Screen Builder watchers to react.
- Match the original button `fieldValue` when a script or gateway expects a specific value.
- Never overwrite a domain value with a timestamp.
- A temporary mock flag is fine for Preview without an external API. The live path must still call the real script or connector.

## Packaging

The new screen keeps the original `type` (FORM or DISPLAY) unless the user changes the task type, and carries:

1. A thin `VueComponentRenderTemplate` mount ([vue-component-editor.md](vue-component-editor.md)).
2. `computed: []`.
3. `watchers: []`.

Multi-option selects use a seeded Collection with the **same codes** BPMN already tests.

## Check

```
- [ ] Inventory covers fields, full control config, ancestor conditionalHide, readonly, calcs, watchers, help content
- [ ] Every nested screen id is inventoried and recreated as one shared component used by all its parents
- [ ] Every original config.name is on request data; UI only for visible interactive controls
- [ ] No always-hidden or data-only key rendered as an editable input
- [ ] Behavior config (validation, visibility, options, events, defaults) is reimplemented
- [ ] Help images, tips, and helper links are present (restyled or condensed, not omitted)
- [ ] Every computed property and watcher is implemented in the component with the same script id / endpoint / output_variable
- [ ] Screen sends computed: [] and watchers: []
- [ ] Spot check: Preview, a task, or a web entry writes the request-data keys the process expects
```

Anti-patterns: an Arco-style shell with a subset of inputs; showing every `config.name` as an editable control; inlining the same nested UI in three parents; mutating `search` and expecting a watcher to fire; a hard-coded mock as the only production path; secrets or `getenv` equivalents in the component.
