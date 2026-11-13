# FlowGenie asset schema

Table: `flow_genies`. Package: `processmaker/package-ai`.

## Metadata fields

| Field | Required | Notes |
|-------|----------|-------|
| name | yes | Unique. `alpha_spaces` (letters, digits, spaces, `-` `_` `.` `'`) |
| description | yes | Max 300 chars |
| flow_genie_category_id | yes | Existing category id. Look up; do not assume `1` |
| config | no on create | Full agent config. Set on PUT from Studio or API |
| projects | no | Project ids to attach as asset |

Categories: `flow_genie_categories`. `status` is `ACTIVE` or `INACTIVE`. List endpoint excludes system categories.

## Current `config` shape (agent schema)

Persisted by FlowGenie Studio on save. Keys the runtime reads:

| Key | Notes |
|-----|-------|
| agent_name | Usually matches genie name |
| provider | `openai`, `google`, `anthropic` |
| provider_api_key | Required for Gemini/Claude. Encrypted at rest |
| model | e.g. `gpt-4.1-mini`. Depends on provider |
| instructions | System prompt (agent behavior) |
| history | Training chat messages. Mustache `{{var}}` resolves against request data at run time |
| output_type | `Text (default)`, `JSON`, or `Table` |
| test_data | JSON string for Studio preview |
| collections | RAG collection ids/uuids. Only `COMPLETED` collections appear in Studio |
| limit | Max RAG sections retrieved (default 15) |
| match_threshold | RAG similarity minimum (default 0.15) |
| max_turns | Agent turn limit (default 30) |
| enhance_pdf_quality | Boolean for PDF processing |
| tools | Default tool ids (Web Search, API Request, etc.) |
| dynamic_tools | Additional tool configs |
| mcp_servers | Remote MCP server configs (auth encrypted at rest) |
| subagents | `[{ flow_genie_id, agent_name, as_tool_description, as_tool_name }]` |
| guardrails | Input/output/tool policies. See [tools-agents.md](tools-agents.md) |
| model_settings | `temperature`, `top_p`, `max_tokens`, `tool_choice`, `parallel_tool_calls`, etc. |

### `history` message shape

```json
{
  "role": "user",
  "content": [
    { "type": "text", "text": "Summarize {{document_text}}" },
    {
      "type": "image_url",
      "image_url": { "url": "..." },
      "include_request_variable": true,
      "request_variable_name": "invoice_image"
    }
  ]
}
```

Supported attachments in history: images (≤ 2 MB), PDF (≤ 2 MB, ≤ 3 pages), CSV (≤ 0.2 MB), public URLs. Set `include_request_variable: true` and `request_variable_name` to bind a file from request data.

### Legacy config (still normalized at runtime)

Detected when `configuration` key is present (pre–FlowGenie V2):

```json
{
  "configuration": { "model": "...", "responseFormat": "...", "temperature": 0.5 },
  "messages": [],
  "system_message": { "role": "system", "content": [{ "type": "text", "text": "..." }] }
}
```

Prefer the current agent schema for new genies. `FlowGenieProcessConfigNormalizer` converts legacy shape before execution.

## Response format

| output_type | Runtime behavior |
|-------------|------------------|
| Text (default) | Plain text or structured agent output |
| JSON | Agent prompted to return JSON; runtime auto-decodes JSON strings into objects |
| Table | HTML table output |

Downstream steps must parse the stored request variable accordingly.

## Permissions

| Permission | Purpose |
|------------|---------|
| create-flow_genies | Create, duplicate |
| view-flow_genies | List, show, export |
| edit-flow_genies | Update config |
| delete-flow_genies | Delete |
| import-flow_genies / export-flow_genies | Package import/export |
| create/view/edit/delete-flow_genies_categories | Categories |

## Designer paths

| Path | Purpose |
|------|---------|
| `/designer/flow-genies` | List |
| `/designer/flow-genies/{id}/edit` | FlowGenie Studio |
| `/designer/flow-genies/import` | Import package |
