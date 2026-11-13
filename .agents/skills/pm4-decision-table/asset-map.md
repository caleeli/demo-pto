# Where things live

Paths are relative to each repo: `processmaker` (core), `package-decision-engine`, `package-collections`, `modeler`.

## Lookups (never invent ids)

```
GET /api/1.0/decision_tables?filter=Risk&per_page=15          # id, title (= table name), category
GET /api/1.0/decision_tables/{id}                             # name + definition (DMN XML)
GET /api/1.0/decision_table_categories?per_page=15            # decision_table_category_id for create
```

`filter` matches name, description, and category name. `pmql` is also accepted (`name = "Risk Rules"`).

**In the list, `name` is the category name** (e.g. `Uncategorized`), because the query joins the category. Match tables by **`title`**, which holds the table name. `GET /decision_tables/{id}` returns the table name in `name`.

A table can belong to several categories: `decision_table_category_id` accepts a comma-separated list (`"1,4"`).

## API (`package-decision-engine/routes/api.php`, prefix `/api/1.0`)

| Method + path | Permission | Purpose |
|---------------|------------|---------|
| `GET decision_tables` | `view-decision_tables` | List (`filter`, `pmql`, `order_by`, `per_page`) |
| `POST decision_tables` | `create-decision_tables` | Create: `name` (unique, max 100, `alpha_spaces`: letters, digits, spaces, `-` `_` `.` `'`), `description` (required, max 400, same charset), `decision_table_category_id` |
| `GET decision_tables/{id}` | `view-decision_tables` | Show |
| `PUT decision_tables/{id}` | `edit-decision_tables` | Update. `definition_arrays` regenerates the DMN; `definition` sets raw XML |
| `PUT decision_tables/{id}/duplicate` | `create-decision_tables` | Copy (body: new `name`, `description`, category) |
| `DELETE decision_tables/{id}` | `delete-decision_tables` | Delete. Processes that reference it fail at run time and on export |
| `POST decision_tables/{id}/execute?hp=` | `view-decision_tables` | Preview with a JSON body; see [verify.md](verify.md) |
| `POST decision-tables/import-file/upload` | `view-decision_tables` | Parse xlsx/xls/csv, no save; see [excel.md](excel.md) |
| `POST decision_tables/{id}/excel-import` | `import-decision_tables` | Legacy Excel import (avoid) |
| `POST decision_tables/{id}/export-excel` | `import-decision_tables` | Build xlsx from body arrays |
| `POST decision_tables/{id}/export` | `export-decision_tables` | Export a table package (`.json`) |
| `POST decision_tables/import` | `import-decision_tables` | Import a table package. Same platform version only. A name that already exists becomes `<name> 2`, and the table gets a new id |
| `GET/POST/PUT/DELETE decision_table_categories` | `*-decision_tables_categories` | Categories |

Permissions are seeded by `src/Seeds/DecisionTablePermissionSeeder.php`: `create|view|edit|delete|import|export-decision_tables` and `create|view|edit|delete-decision_tables_categories`.

## Web (`routes/web.php`, prefix `/designer`)

| Path | Page |
|------|------|
| `/designer/decision-tables` | List |
| `/designer/decision-tables/{id}/edit` | Table settings (name, description, category) |
| `/designer/decision-tables/table-builder/{id}/edit` | Table editor (rules, types, formulas, preview, Excel/DMN import/export) |
| `/designer/decision-tables/import-file/format/download` | Excel template |
| `/designer/decision-tables/categories` | Categories |

## Decision engine package

| Path | What |
|------|------|
| `src/PackageServiceProvider.php` | Registers the service implementation, the modeler script, and the process exporter extension |
| `src/DecisionTableImplementation.php` | Runtime: `run()`, `executeTable()`, `parseResult()`, `_collection` lambda; see [architecture.md](architecture.md) |
| `src/Connectors/DecisionTableImplementation.php` | **Dead code.** Not registered, does not compile cleanly. Do not copy |
| `src/Managers/DmnCreator.php` | `definition_arrays` → DMN XML |
| `src/Repositories/DecisionTableImportRepository.php` | Excel/CSV upload parser |
| `src/Managers/ExcelToDmn.php`, `TableExtractor.php` | Legacy Excel import |
| `src/Helpers/DataAccess.php` | Mustache data wrapper (throws on missing or null variables) |
| `src/ImportExport/ProcessExporterExtension.php` | Adds referenced tables to process export; rewrites `decisionTables[].id` on import |
| `src/ImportExport/DecisionTableExporter.php` | Table exporter (categories only; no collections) |
| `resources/js/connectors/decision-tables/index.js` | Modeler node: `bpmn:ServiceTask`, implementation string, default `config`, inspector handler |
| `resources/js/connectors/decision-tables/inspector-*.vue` | Tables, input mapping, output mapping inspectors |
| `resources/js/decision-tables/components/TableEdit/*` | Table editor |
| `tables-engine/target/decision_tables-1.1.jar` | Drools engine invoked with `shell_exec` (needs Java) |
| `tests/feature/*` | Per-hit-policy tests and DMN fixtures (`tests/feature/fixtures/`) |

## Core (`processmaker`)

| Path | What |
|------|------|
| `ProcessMaker/Enums/ExporterMap.php` | `decision_table` → `DecisionTable` + `DecisionTableExporter` |
| `ProcessMaker/ImportExport/Utils.php` | `getServiceTasks($process, $implementation)`, `getPmConfig()`, used by the exporter extension |
| `ProcessMaker/Models/BundleAsset.php` | DevLink bundles: type `decision_table`, edit URL `/designer/decision-tables/table-builder/{id}/edit` |
| `ProcessMaker/Http/Middleware/CustomAuthorize.php` | `decision_table` is a project asset |
| `resources/js/components/shared/decisionTableNavigation.js` | Designer navigation actions |

## Modeler

| Path | What |
|------|------|
| `src/components/modeler/Modeler.vue` `registerNode()` / `getParser()` | Parser order custom → implementation → default; exact `implementation` match |
| `src/components/documenting/NodeDocumentation.vue` | Table icon and "Decision Table" label (display only) |

## Collections package

| Path | What |
|------|------|
| `src/Models/Collection.php` `record($id)` | Resolves lookups by record `id` |
| `src/ImportExport/CollectionExporter.php` | Renames on name clash (`handleDuplicatesByIncrementing`) |
| `src/ImportExport/RecordExporter.php` | Records with original ids, only with `include_records_on_export` |

See [collections.md](collections.md).
