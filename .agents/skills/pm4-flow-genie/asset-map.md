# Where things live

Paths are relative to each repo: `processmaker` (core), `package-ai`, `modeler`.

## Lookups (never invent ids)

See [lookup.md](lookup.md). Summary:

```
GET /api/1.0/package-ai/flow_genies?filter=Summarize&per_page=15
GET /api/1.0/package-ai/flow_genies/{id}
GET /api/1.0/package-ai/flow_genie_categories?status=ACTIVE&per_page=15
GET /api/1.0/package-ai/rag_collections?status=COMPLETED&per_page=15
```

`filter` and `pmql` match name and description. List excludes `config` unless `exclude=config` is removed from the query.

## API (`package-ai/routes/api.php`, prefix `/api/1.0`)

| Method + path | Permission | Purpose |
|---------------|------------|---------|
| `GET package-ai/flow_genies` | `view-flow_genies` | List (`filter`, `pmql`, `order_by`, `per_page`, `exclude`) |
| `POST package-ai/flow_genies` | `view-flow_genies` | Create metadata: `name`, `description`, `flow_genie_category_id`; optional `config` |
| `GET package-ai/flow_genies/{id}` | `view-flow_genies` | Show (full config) |
| `PUT package-ai/flow_genies` | `edit-flow_genies` | Update: `id`, `name`, `description`, `flow_genie_category_id`, `config` (full object) |
| `PATCH package-ai/flow_genies/{id}/metadata` | `edit-flow_genies` | Metadata only; preserves `config` |
| `PUT package-ai/flow_genies/{id}/duplicate` | `create-flow_genies` | Copy |
| `DELETE package-ai/flow_genies/{id}` | `delete-flow_genies` | Delete. Referencing processes fail at run time |
| `POST package-ai/flow_genies/{id}/export` | `export-flow_genies` | Export genie package |
| `POST package-ai/flow_genies/import` | `import-flow_genies` | Import genie package |
| `POST package-ai/flow_genies/{id}/execute` | (auth) | Preview with sample `data`; see [verify.md](verify.md) |
| `POST package-ai/runGenieTest` | (auth) | Studio inline test (legacy shape) |
| `GET/POST/PUT/DELETE package-ai/flow_genie_categories` | `*-flow_genies_categories` | Categories |
| `GET package-ai/rag_collections` | `view-flow_genies_categories` | RAG collections for Sources |
| `GET flow-genie/logs/execution` | (auth) | Runtime execution logs |
| `GET flow-genie/logs/design` | (auth) | Studio/design-time logs |

Create validation (`FlowGenie::rules`): `name` unique, `alpha_spaces`; `description` required, max 300; `flow_genie_category_id` required.

Permissions seeded by `database/seeds/FlowGeniePermissionSeeder.php`: `create|view|edit|delete|import|export-flow_genies` and `create|view|edit|delete-flow_genies_categories`.

## Web (`package-ai/routes/web.php`, prefix `/designer`)

| Path | Page |
|------|------|
| `/designer/flow-genies` | List |
| `/designer/flow-genies/{id}/edit` | FlowGenie Studio |
| `/designer/flow-genies/{id}/export` | Export |
| `/designer/flow-genies/import` | Import |

## package-ai runtime

| Path | What |
|------|------|
| `src/AiServiceProvider.php` | Registers `package-ai/processmaker-ai-task` → `AiTask` |
| `src/ServiceTaskImplementations/AiTask.php` | Runtime: loads genie by `flow_genie_id`, runs agent, writes `output_variable` |
| `src/Services/RunFlowGenieAgent.php` | Mustache render, file resolution, agent microservice call |
| `src/Services/FlowGenieProcessConfigNormalizer.php` | Current + legacy config → agent payload |
| `src/Services/FlowGenieSubagentNormalizer.php` | Expands `subagents[]` by `flow_genie_id`; cycle detection |
| `src/Services/FlowGenieConverter.php` | Migrates legacy inline BPMN config to linked genie |
| `src/Models/FlowGenie.php` | Asset model; encrypts `provider_api_key` and MCP auth at rest |
| `resources/js/flow-genie/components/FlowGenieConfiguration.vue` | Studio settings UI |
| `resources/js/flow-genie/components/FlowGenieEdit.vue` | Studio training chat |

## Modeler

FlowGenie node id: `processmaker-ai-task`. Implementation string must match exactly: `package-ai/processmaker-ai-task`. Inspector saves `pm:config` with `flow_genie_id` and `output_variable`. See [bpmn-attrs.md](bpmn-attrs.md).

## Core

| Path | What |
|------|------|
| `ProcessMaker/ImportExport/Utils.php` | `getServiceTasks($process, $implementation)` used by process export |

Genies referenced in a process are included in process export packages. After import on another instance, verify ids or re-import genie packages.
