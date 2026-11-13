# Vue Component Editor (mandatory for new FORM and DISPLAY screens)

When you **create** a FORM or DISPLAY screen, or **rewrite** one, the main UI is rendered by Screen Builder's **Vue component Editor** control (`component: "VueComponentRenderTemplate"`, inspector group **TCE Controls**), not by a tree of native controls. The control is provided by `package-plg`.

Scope:

| Case | Rule |
|------|------|
| New FORM or DISPLAY screen | Vue Component Editor is mandatory |
| Rewrite or "make it nicer" of an existing screen | Vue Component Editor is mandatory; follow [refactor-parity.md](refactor-parity.md) |
| Small edit to an existing screen (add or change a field, rename a label) | Keep the screen's current structure: add a native control to a native screen, or edit the Vue component of a Vue screen. Do not convert unless asked |
| EMAIL and CONVERSATIONAL screens | Out of scope; they follow [screen-types.md](screen-types.md) |

If the instance does not have the control (`package-plg` not installed or the control missing from Screen Builder), **stop and tell the user**. Do not fall back to a native layout silently.

## The rule

1. `page.items` holds one `VueComponentRenderTemplate` control that mounts one (or a few) named Vue components.
2. `contentVue` is a **thin mount**: a component tag with bindings, for example `<PetRegistrationForm v-model="data" :submit="submit" />`.
3. All layout, fields, copy, and interactivity live **inside the Vue component**, not in `contentVue`.
4. Native controls (`FormInput`, `FormSelectList`, `FormHtmlViewer`, ...) are not the primary layout. A submit `FormButton` is acceptable only when the Vue component does not own submit.
5. Never invent a component key. Copy `{ name, key }` from a working screen or from the catalog.

Good:

```html
<PetRegistrationForm v-model="data" :submit="submit" :configuration="configuration" />
```

Bad: a `<div>` with headings, `<input v-model="data.name">`, selects, and styles inside `contentVue`. If `contentVue` grows past a mount tag and a trivial wrapper, move the markup into a component.

## How the control runs

- `contentVue` is compiled as a Vue 2 template string.
- `handlerVue` is evaluated as a function body that **must `return { ... }`**; the object is merged as a mixin.
- The request data is injected as `data` (the same object as the screen's transient data).
- Tags in `contentVue` resolve from `config.components` (`{ name, key }`), then from globally registered components.
- `submit()` is available to the mounted component and mixin.

Do not rely on parsing `contentVue` to find dependencies. Every catalog or custom tag in it needs a `config.components` entry.

## Control shape

```json
{
  "component": "VueComponentRenderTemplate",
  "editor-component": "VueComponentEditor",
  "editor-control": "VueComponentEditor",
  "label": "Vue component Editor",
  "inspector": [],
  "config": {
    "icon": "fas fa-pencil-ruler",
    "interactive": true,
    "componentName": "PetRegistrationForm",
    "contentVue": "<PetRegistrationForm v-model=\"data\" :submit=\"submit\" />",
    "handlerVue": "return {\n  created() {\n    if (this.data && this.data.petName === undefined) {\n      this.$set(this.data, 'petName', '');\n    }\n  },\n};",
    "components": [
      { "name": "PetRegistrationForm", "key": "@/projects/pets/PetRegistrationForm.vue" }
    ],
    "renderVarHtml": null,
    "ignoreMustache": true
  }
}
```

The key above shows the **shape** only. Full request body: [examples/vue-component-form.json](examples/vue-component-form.json).

| Field | Purpose |
|-------|---------|
| `componentName` | Inspector and docs label only; runtime uses the tag and `components` |
| `contentVue` | Thin Vue 2 template mounting registered tags |
| `handlerVue` | Trusted mixin body, must `return`. Initialize `$data` keys with `created()` + `this.$set(this.data, ...)`. No UI markup |
| `components` | `{ name, key }` for every catalog (`@/...vue`) or custom (`custom://slug`) tag used in `contentVue` |
| `ignoreMustache` | Keep `true` for Vue templates |
| `interactive` | Keep `true` |

`data` is the request data. Bind it into the child with `v-model` or props and write back with emits or `$set`. The signed-in user is `this.data._user` or `ProcessMaker.user`, not `this._user`.

## Where the component comes from

| Key shape | Kind | Use |
|-----------|------|-----|
| `@/.../Name.vue` | Official component, in the `package-plg` catalog | Components shipped with `package-plg` |
| `custom://slug` | Custom component stored in the database (Admin: Custom Vue Components) | No official fallback; must be enabled and valid or rendering fails |

Rules:

1. **Reuse first.** `GET /api/1.0/screens/{id}` on a similar screen and copy its `contentVue`, `handlerVue`, and each `components` entry.
2. If the UI does not exist, create the component **first** (an official SFC plus catalog registration and build, or a `custom://` component), then create the screen that mounts it. Do not ship the UI as inline `contentVue` while waiting.
3. The screens API stores screens only. Component source lives outside the screen JSON.
4. For the admin side (customizing an official component without a rebuild, caching, security), see `package-plg`'s `ai-docs/custom-vue-components.md`.

## Registering a new official component

Screen Builder loads official tags only from the catalog `resources/js/designer/public-screen-components.json` in `package-plg`. A `.vue` file on disk is not enough: without a catalog row the preview stays blank while sibling components still render.

1. **Catalog**: append `{ "name": "YourForm", "key": "@/.../YourForm.vue" }`. If the parent imports child components, list them under the parent's nested `components` array **and** as their own top-level rows.
2. **Build** in the `package-plg` checkout: `npm run prod`.
3. **Publish** in the `processmaker` checkout: `php artisan vendor:publish --tag=package-plg --force`. ProcessMaker serves the published copy under `public/vendor/processmaker/packages/package-plg/`, not the package's own `public/`.
4. Hard-refresh Screen Builder.

Do this **before** calling the screen done. A screen whose component is not registered, built, and published is an incomplete delivery even if the `POST` succeeded.

## Domain fields live inside the component

Do not place native `FileUpload`, `SignaturePad`, or `FormSelectList` controls beside the Vue mount. Implement them in the component:

| Need | Implementation |
|------|----------------|
| File upload | Uploader in the SFC posting to `/api/1.0/requests/{id}/files` with `data_name` = the field name (request id from `meta[name=request-id]`); store the file id(s) on request data. Validate required files before `submit()`. Never fake an upload with a checkbox |
| Select with more than two options | A Collection catalog: `GET collections/{id}/records` (or its Data Connector), map `data.code` to the value and `data.label` to the label, bind a Vue `<select>`. Pass the numeric collection id as a prop from `contentVue`. Never hard-code the options. Build and seed the catalog first: [pm4-collection catalogs.md](../pm4-collection/catalogs.md). Gateways must test the **code** values |
| Yes/No and other fixed binary enums | May be hard-coded |
| Signature | Signature pad in the SFC; store the data URL on request data; block submit while empty if signing is mandatory. Never replace it with an "I signed" checkbox |

## Authoring rules

- Vue 2 Options API only in `handlerVue` and database components: no `<script setup>`, no TypeScript.
- Keep `handlerVue` thin: initialize `data`, small helpers, the submit bridge.
- No secrets, tokens, or tenant credentials in `contentVue`, `handlerVue`, or component source. Call backend scripts for secret-bearing work.

## Config safety

- Every control in `page.items` needs `"inspector": []` (or a real inspector). Without it Screen Builder crashes on `item.inspector.forEach`.
- Pages carry `"items": []` even when empty.
- Send `"computed": []` and `"watchers": []`, never `null`.
- Optional full-height CSS many Vue screens use in `custom_css`:

```css
[data-cy="screen-renderer"] { height: 100%; }
[data-cy="screen-renderer"] > div,
[data-cy="screen-renderer"] > div > div,
[data-cy="screen-renderer"] > div > div > div { height: 100%; }
```

## Check

```
- [ ] Type is FORM or DISPLAY; control is VueComponentRenderTemplate with "inspector": []
- [ ] page.items has the Vue mount only (plus a thin submit FormButton if the component does not own submit)
- [ ] contentVue is a short mount of named tags; no page of markup
- [ ] handlerVue starts with "return"; ignoreMustache is true
- [ ] Every non-global tag in contentVue has a copied { name, key } in components
- [ ] New @/ keys are in public-screen-components.json, built (npm run prod), and published (vendor:publish)
- [ ] Multi-option selects use a seeded Collection and the numeric id; uploads and signatures work inside the component
- [ ] computed and watchers are []; Screen Builder preview renders the component
```
