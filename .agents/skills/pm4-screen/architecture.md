# How screens work

Checked against core `2026.15.3` and a running instance. File paths are in [asset-map.md](asset-map.md).

## Storage

A screen is one row in `screens` plus its rows in `screen_versions`.

| Column | Type | Notes |
|--------|------|-------|
| `title` | string | Unique; `alpha_spaces` |
| `description` | string | Required |
| `type` | string | A `screen_types.name`: FORM, DISPLAY, CONVERSATIONAL, EMAIL |
| `screen_category_id` | string | One id or a comma-separated list; returned as a string |
| `config` | JSON array | Pages and controls ([config-schema.md](config-schema.md)) |
| `computed` | JSON array | Calculated properties |
| `watchers` | JSON array | Watchers ([watchers.md](watchers.md)) |
| `custom_css` | text | Scoped to the screen at render time |
| `translations` | JSON | package-translations |
| `key` | string | Set only on system screens (`Screen::getScreenByKey()` loads `database/processes/screens/{key}.json`) |
| `is_template`, `asset_type` | | `1` / `SCREEN_TEMPLATE` on a template's editing copy ([templates.md](templates.md)) |

`screen_types` lists the types the instance knows and whether each is interactive (`is_interactive`): FORM and CONVERSATIONAL are, DISPLAY and EMAIL are not. Core seeds FORM and DISPLAY; packages add the others ([screen-types.md](screen-types.md)). The seeder uses `create`, so an instance can have duplicate FORM/DISPLAY rows; that is harmless.

## Versions and drafts

`Screen` uses `HasVersioning`:

- **Every save of the screen row creates a published version** in `screen_versions` and deletes the draft. `POST /screens`, `PUT /screens/{id}`, and duplicate all publish.
- **A draft is one `screen_versions` row with `draft = 1`.** `PUT /screens/{id}/draft` creates or replaces it; the `screens` row is not touched, so `GET /screens/{id}` still returns the published config.
- **`POST /screens/{id}/close` deletes the draft.**
- **Run time uses the latest published version** (`versionFor()` → `getLatestVersion()`; the code notes "Skip version locking for now"). Screens are not locked to the version that was live when a request started: **publishing changes the screen for tasks already in progress.**
- Screen queries carry a global `published` scope: a screen without any published version is invisible to the API.

## Updating config

`PUT /screens/{id}` fills the model from the request, then `updateScreenDetails()` decides whether to keep the submitted `config` or replace it with the latest version (draft first, else published). It replaces it when **any** of these differ from the stored row:

- `title` or `description`;
- `screen_category_id`, compared with strict `!==` (the stored value is a string, so an integer always differs; omitting it is fine);
- `projects`. When `projects` is missing or not a JSON array string, `explode(',', …)` returns `[""]`, which counts as a change.

Verified on an instance (all return 204):

| Request | `config` |
|---------|----------|
| `projects` omitted | discarded |
| `projects: ""` | discarded |
| `projects: "[]"`, category omitted | **saved** |
| `projects: "[]"`, `screen_category_id: "41"` (stored string) | **saved** |
| `projects: "[]"`, `screen_category_id: 41` (integer) | discarded |
| Title or description changed | discarded |

That replacement is how Designer publishes: the builder saves a draft, then the plain `PUT` copies the draft config into the row and saves a new published version. So the reliable API flow is:

```
PUT  /api/1.0/screens/{id}/draft   { title, description, type, config, computed, watchers, custom_css }
PUT  /api/1.0/screens/{id}         { title, description, type }        # publishes the draft
GET  /api/1.0/screens/{id}                                             # confirm
```

`title`, `description`, and `type` are required on both calls, or the API returns 422.

## Input sanitization

The `SanitizeInput` middleware runs on screen requests. Only **top-level** string fields are cleaned: `title`, `description`, and `custom_css` lose HTML tags and `{{` / `}}` (in CSS, `content: "<i>"` becomes `content: ""`; `>` combinators survive). Values nested in `config`, `computed`, and `watchers` are stored unchanged, including HTML, `<script>`, and Mustache, on every type. The renderer decides what to show.

So `"description": "<b>Pay</b> {{amount}}"` is stored as `Pay amount`, while a control `label` or `content` keeps its HTML and `{{var}}`.

## Rendering and packages

- The page that opens Screen Builder, a task, a request summary, or a preview fires `ScreenBuilderStarting` with the screen type. Packages listen to it to add their scripts.
- `ScreenBuilderManager::addPackageScripts($type)` also loads `vendor/processmaker/packages/*/js/screen-builder-{type}-components.js` from every installed package, one file per type (`screen-builder-form-components.js`, `…-display-…`, and so on).
- Controls and types exist only in the browser after those scripts load. **A screen that uses a package control renders that control as missing on an instance without the package**, and a CONVERSATIONAL or EMAIL screen has no builder there. The API accepts any `component` and any `type` string.

## Cache

Compiled screens are cached per process, process version, language, screen, and screen version (`ScreenCompiledManager`, key `pid_{process}_{version}_{lang}_sid_{screen}_{screenVersion}`). Every `PUT /screens/{id}` clears **all** compiled screens, because nested screens can affect any parent. `php artisan cache:screens-clear` does the same.

## Nested screens

- A `FormNestedScreen` item stores the child screen id in `config.screen` (an integer).
- Run time and export resolve the child's latest published version.
- `ScreensInScreen` walks nesting recursively and throws after 10 levels (`Max screen recursion depth of 10 exceeded. Is a child screen referencing its parent?`). A screen that nests itself or a parent cannot be exported.
- A task loads the task screen, its interstitial screen, and all their nested screens (`ProcessRequestToken::getScreenAndNestedIds()`).

## Export and import

Two formats exist. Prefer v2.

**v2** (`GET export/manifest/screen/{id}/`, `POST import/do-import`): one JSON package with every dependent, keyed by asset uuid. For a screen it includes:

| Dependent | Included | Notes |
|-----------|----------|-------|
| Screen categories | yes | |
| Nested screens | yes, recursively | Each with its own categories |
| Script watchers' scripts | yes | Each script brings its environment variables, run-as user, and executor |
| Data source watchers | only with `package-data-sources` | Core skips them (`script.id` starting with `data_source`) |
| Package controls (JS) | no | The target needs the package installed |

On import each asset gets a mode: `update` (default; overwrite the match), `copy` (new asset, dependents re-linked to it), or `discard` (keep the existing one). A `copy` whose title already exists gets a numeric suffix: `Approve Invoice Form` → `Approve Invoice Form 2`. Ids change; `ScreenExporter::import()` rewrites nested `config.screen` ids and watcher script ids, and the process exporter rewrites `pm:screenRef`. The imported screen is saved, so it is published.

A screen with `key = "interstitial"` is the system interstitial screen. Importing another one drops its `key`, so there stays one default interstitial screen.

`POST import/do-import` takes `file` and `options` as **files** in a multipart body, runs synchronously, and returns `{"processId": <id>}` even for a screen.

**v1 (legacy)**: `POST /screens/{id}/export` returns a download URL; `POST /screens/import` takes that file. Kept for old exports.

Process export pulls in every screen the BPMN references (`ScreensInProcess`: `pm:screenRef`, `pm:interstitialScreenRef`; Actions by Email screens; `connector-send-email` adds its `screenRef` values).
