# Create or edit a FlowGenie

Needs `processmaker/package-ai` installed. Do not call the AI microservice or start a request unless the user asked.

## Path A — Designer (no API)

1. Designer → **FlowGenie** → **New Genie** (or **+ Genie** from the list).
2. Set name, description, category → **Save**.
3. In FlowGenie Studio: provider, model, instructions, training chat, tools, RAG, subagents → **Save Genie**.
4. Wire the process node in Modeler: select the genie, set **Output Variable Name** → Save + Publish the process.

## Path B — API

Base URL: local site host (see [setup-processmaker](../setup-processmaker/SKILL.md)). Auth: session cookie or `Authorization: Bearer <token>`.

All paths below are under `/api/1.0/`.

### 1. Resolve category id

```
GET package-ai/flow_genie_categories?status=ACTIVE&per_page=15
```

See [lookup.md](lookup.md). Do not assume category `1`.

### 2. POST metadata (create shell)

```
POST package-ai/flow_genies
Content-Type: application/json
```

Body: [examples/genie-minimal.json](examples/genie-minimal.json). Replace `CATEGORY_ID`.

Expect **201** and genie `id`. `config` is optional on create.

### 3. PUT full config

```
PUT package-ai/flow_genies
Content-Type: application/json
```

Required body keys: `id`, `name`, `description`, `flow_genie_category_id`, `config` (object).

Send the full config object from [genie-schema.md](genie-schema.md). Example payload: [examples/genie-config.json](examples/genie-config.json). On update, include the entire `config`; partial merges are not guaranteed.

Metadata-only change:

```
PATCH package-ai/flow_genies/{id}/metadata
```

### 4. Preview (no request)

```
POST package-ai/flow_genies/{id}/execute
Content-Type: application/json

{ "id": 42, "data": { "document_text": "Sample input" } }
```

See [verify.md](verify.md).

### 5. Wire the process node

Set `pm:config` on the service task. See [bpmn-attrs.md](bpmn-attrs.md). Save + Publish the process.

### Duplicate, export, import

```
PUT  package-ai/flow_genies/{id}/duplicate
POST package-ai/flow_genies/{id}/export
POST package-ai/flow_genies/import   (multipart file)
```

Import on another instance may assign a new id. Update `flow_genie_id` in BPMN or re-import the process package.

## Editing an existing genie

1. `GET package-ai/flow_genies/{id}` — read current config.
2. Change instructions, history, model, tools, etc.
3. `PUT package-ai/flow_genies` with full payload.
4. Preview with `/execute` before publishing process changes.

Genie changes take effect on the next request; no process republish needed unless the BPMN node config changed.

## Secrets and pitfalls

- `provider_api_key` and MCP server auth are write-only secrets: never echo them in chat, git, or examples. Prefer the instance-level AI provider settings; set a per-genie key only for providers that need it (Gemini, Claude).
- Empty `instructions` means the agent runs with a blank system prompt. Set them before wiring the node.
- A genie that no node references does nothing; a node without a valid `flow_genie_id` is invalid in Modeler. Create the asset first, then wire the BPMN.
- `PUT` replaces the whole `config`. Read it with `GET`, change it, and send it back whole.
- `package-ai` must be installed and the AI microservice reachable, or runs fail with `{ "error": "<message>" }` in the output variable.
- A genie needs a category. If the category list is empty, create one in Designer first.
