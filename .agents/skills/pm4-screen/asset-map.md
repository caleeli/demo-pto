# Where things live

Paths are relative to each repo: `processmaker` (core), `screen-builder`, `modeler`, and `package-*` / `connector-*`. Endpoints are under `/api/1.0`.

## Lookups (never invent ids)

```
GET /api/1.0/screens?filter=Invoice&per_page=15              # id, title, type, screen_category_id
GET /api/1.0/screens?type=FORM,CONVERSATIONAL&per_page=15    # by type (comma-separated)
GET /api/1.0/screens?interactive=true                        # FORM + CONVERSATIONAL (screen_types.is_interactive)
GET /api/1.0/screens/{id}                                    # full row: config, computed, watchers, custom_css
GET /api/1.0/screen_categories?per_page=50                   # screen_category_id
GET /api/1.0/templates/screen?is_public=1&screen_type=FORM   # shared templates; is_public=0 = mine
```

- `filter` matches title, description, and category name. `pmql` is also accepted (`title = "Approve Invoice"`).
- Other list params: `order_by` (default `title`), `order_direction`, `per_page` (default 10), `fields` (sparse columns), `exclude`, `include` (`categories`, `categoryCount`), `key`, `selectList` (filter by title only).
- `interactive=true` overrides `type`.
- `screen_category_id` comes back as a **string** (`"41"`). Send it back as the same string on `PUT`, or omit it.

## Screens API (`routes/api.php`)

| Method + path | Permission | Purpose |
|---------------|------------|---------|
| `GET screens` | checked in controller | List (see Lookups) |
| `GET screens/{id}` | checked in controller | Show; `include=` loads relations |
| `POST screens` | `create-screens` | Create; 201 with the row |
| `PUT screens/{id}` | `edit-screens` | Update metadata and publish the latest draft. Drops `config` unless the conditions in [architecture.md](architecture.md#updating-config) hold |
| `PUT screens/{id}/draft` | `edit-screens` | Save a draft (`config`, `computed`, `watchers`, `custom_css`); 204 |
| `POST screens/{id}/close` | `edit-screens` | Discard the draft; 204 |
| `PUT screens/{id}/duplicate` | `create-screens` | Copy all columns; body may set `title`, `description`, `screen_category_id` |
| `DELETE screens/{id}` | `delete-screens` | Delete; 204. Processes that reference it break |
| `POST screens/{id}/export` | `export-screens` | Legacy v1 export; returns `{url}` to `/designer/screens/{id}/download/{key}` |
| `POST screens/import` | `import-screens` | Legacy v1 import (multipart `file` from the export above) |
| `POST screens/{id}/translate/{language}` | `edit-screens` | Apply translations (package-translations) |
| `GET/POST/PUT/DELETE screen_categories[/{id}]` | `view|create|edit|delete-screen-categories` | Categories |

`title`, `description`, and `type` are required on `POST`, `PUT`, and `PUT …/draft`.

## Export / import (v2, shared with processes)

| Method + path | Permission | Purpose |
|---------------|------------|---------|
| `GET export/manifest/screen/{id}/` | `export-processes` | Package JSON with every dependent asset (nested screens, scripts, categories, env vars, executors) |
| `POST import/preview` | `export-processes` | Multipart `file`; lists what would be created or updated |
| `POST import/do-import` | `import-processes` | Multipart `file` **and** `options` as a **file**: `{"<asset uuid>": {"mode": "copy" \| "update" \| "discard"}}`. Response key is `processId` even for screens. Runs synchronously and can take minutes |

Modes (`ProcessMaker/ImportExport/Options.php`): `update` (default) overwrites the matching asset, `copy` creates a new one and links the imported dependents to it, `discard` keeps the existing asset. Sending `options` as a plain form field returns 500 `Path must not be empty`. What travels with a screen: [architecture.md](architecture.md#export-and-import).

## Screen Templates API

All routes go through the `template-authorization` middleware (`ProcessMaker/Http/Middleware/TemplateAuthorization.php`), which checks `view|edit|archive|create|import|export-screen-templates`. Core seeds only `publish-screen-templates` and `sync-screen-templates`; tested here with an admin token only. Bodies and results: [templates.md](templates.md).

| Method + path | Purpose |
|---------------|---------|
| `GET templates/screen` | List (`is_public`, `screen_type`, `filter`, `pmql`, `per_page`) |
| `POST template/screen/{screenId}` | Save a screen as a template |
| `POST template/create/screen/{templateId}` | Create a screen from a template |
| `POST template/screen/{templateId}/apply` | Apply CSS / Layout / Fields to an existing screen |
| `POST template/screen/{id}/publish` | Make a template shared (`publish-screen-templates`) |
| `PUT template/screen/{id}/update` | Update a template (manifest or data) |
| `PUT template/settings/screen/{id}` | Update name, description, category, visibility, media |
| `DELETE template/screen/{id}` | Delete (owner only) |
| `POST template/screen/do-import`, `POST templates/screen/import/validation` | Import a template file |
| `GET screen-builder/screen/{id}` | Open a template's editing screen |

## Web (`routes/web.php`)

| Path | Page |
|------|------|
| `/designer/screens` | List (permission `view-screens`) |
| `/designer/screens/{id}/edit` | Screen settings: title, description, category |
| `/designer/screen-builder/{id}/edit/{processId?}` | Screen Builder |
| `/designer/screens/preview?node=…` | Preview a node's screen (used by Modeler) |
| `/designer/screens/{id}/export`, `/designer/screens/import` | Legacy export/import pages |
| `/designer/screens/categories` | Categories |
| `/screen-template/{id}/edit`, `/screen-template/{id}/export`, `/screen-template/import` | Template pages |

## Permissions

Seeded in core: `view|create|edit|delete|export|import-screens`, `view|create|edit|delete-screen-categories`, `publish-screen-templates`, `sync-screen-templates`.

## Core (`processmaker`)

| Path | What |
|------|------|
| `ProcessMaker/Models/Screen.php` | Model: `rules()`, JSON casts, categories, `getScreenByKey()` (system screens from `database/processes/screens/{key}.json`) |
| `ProcessMaker/Models/ScreenVersion.php` | Published and draft versions |
| `ProcessMaker/Models/ScreenType.php`, `database/seeders/ScreenTypeSeeder.php` | `screen_types` (`name`, `is_interactive`); core seeds FORM and DISPLAY |
| `ProcessMaker/Models/ScreenCategory.php` | Categories |
| `ProcessMaker/Http/Controllers/Api/ScreenController.php` | API: `store`, `update` + `updateScreenDetails()`, `draft`, `close`, `duplicate`, `preview` |
| `ProcessMaker/Http/Controllers/Process/ScreenBuilderController.php` | Screen Builder page |
| `ProcessMaker/Managers/ScreenBuilderManager.php` | Collects package scripts for the builder (`addScript`, `addPackageScripts`) |
| `ProcessMaker/Events/ScreenBuilderStarting.php` | Event packages listen to, to add controls and types |
| `ProcessMaker/Http/Middleware/SanitizeInput.php` | Strips HTML from input, except `label` on DISPLAY screens |
| `ProcessMaker/Cache/Screens/ScreenCacheFactory.php` | Compiled screen cache, cleared on every update |
| `ProcessMaker/ImportExport/Exporters/ScreenExporter.php` | Exports categories, script watchers, nested screens; title clashes get ` 2` |
| `ProcessMaker/Assets/ScreensInScreen.php` | Finds nested screens (`FormNestedScreen` → `config.screen`); 10-level limit |
| `ProcessMaker/Assets/ScreensInProcess.php` | Finds `pm:screenRef` and `pm:interstitialScreenRef` in BPMN |
| `ProcessMaker/Assets/ScriptsInScreen.php`, `DataSourcesInScreen.php` | Watcher dependencies |
| `ProcessMaker/Templates/ScreenTemplate.php` | Template save, create, apply, publish |
| `ProcessMaker/Templates/ScreenComponents.php` | Components that template "apply" can copy |
| `ProcessMaker/Models/ScreenTemplates.php`, `ImportExport/Exporters/ScreenTemplatesExporter.php` | Template model and exporter |
| `resources/js/processes/screen-builder/screen.vue`, `typeForm.js`, `typeDisplay.js` | Builder page and core type setup |
| `resources/js/processes/modeler/initialLoad.js` | Which node accepts which screen type |

## Screen Builder (`screen-builder`)

| Path | What |
|------|------|
| `src/form-builder-controls.js` | Core controls: `component`, `editor-component`, `editor-control`, default `config`, `inspector` |
| `src/components/vue-form-builder.vue` | Builder canvas, pages, inspector |
| `src/components/vue-form-renderer.vue`, `screen-renderer.vue` | Runtime renderer |
| `src/components/watchers-*.vue` | Watchers UI |
| `src/components/computed-properties.vue` | Calculated properties UI |
| `src/components/custom-css.vue` | Custom CSS editor |

## Modeler (`modeler`)

| Path | What |
|------|------|
| `src/components/modeler/Modeler.vue` | Sets `screenRef` when an asset is dropped on a node |
| `src/setup/initialLoad.js` | Screen previews for `bpmn:Task` and `bpmn:ManualTask` |

## Packages that store screen ids

| Package | Field |
|---------|-------|
| `package-webentry` | Web Entry config `screen_id` (copied from the start event `pm:screenRef`), `completed_screen_id` (falls back to the "Web Entry Generic Thank You" screen when the id is missing) |
| `connector-send-email` | Send Email `pm:config.screenRef` (EMAIL); task `pm:config.email_notifications.notifications[n].screenRef` (EMAIL) |
| `package-actions-by-email` | `pm:config.screenRef` on ABE `callActivity` (EMAIL); exporter types `abe_email_screen`, `abe_completed_screen` — [pm4-action-by-email](../pm4-action-by-email/SKILL.md) |
| `package-collections` | Collection `create_screen_id`, `read_screen_id`, `update_screen_id` |
| `package-savedsearch` | Report `screen_id` (`SavedSearchReport`); collection create/update screens for collection searches |

On process import, core (`ProcessExporter::importScreens`) rewrites `pm:screenRef`, `pm:interstitialScreenRef`, and `screenEmailRef`, and `connector-send-email` (`src/ImportExport/ProcessExporterExtension.php`) rewrites its `screenRef` values. A Send Email `screenRef` that does not exist fails when the email is sent (`Screen::findOrFail`), not when the process is saved.

## Packages that add types or controls

| Package | Adds |
|---------|------|
| `package-conversational-forms` | `CONVERSATIONAL` type (`src/Seeds/ConversationalFormsSeeder.php`), builder in `resources/js/typeConversational.js` |
| `connector-send-email` | `EMAIL` type (`src/Seeds/EmailSendSeeder.php`), email builder on `ScreenBuilderStarting` |
| `package-savedsearch`, `package-actions-by-email` | `EMAIL` type when missing |
| `package-data-sources` | Data source watchers and their export |
| `package-files`, `package-photo-video`, `package-pm-blocks`, `package-ai`, `package-vocabularies`, `package-versions`, `package-collections`, `package-webentry`, `package-plg` | Controls or builder behavior; see [controls.md](controls.md) |
