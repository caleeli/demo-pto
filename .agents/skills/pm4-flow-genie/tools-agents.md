# Tools, MCP, subagents, and guardrails

Advanced FlowGenie config in `config`. See [genie-schema.md](genie-schema.md) for the full object.

## Default tools

Added from the ProcessMaker MCP Server in Studio (**+ Add Tool → Tool**):

| Tool | Purpose |
|------|---------|
| Chain of Thought | Step-by-step reasoning |
| Web Search | Search the public web |
| Web Scrape / Batch Scrape | Fetch page content |
| API Request | Call external HTTP APIs |

Stored in `config.tools` and `config.dynamic_tools`. Set `model_settings.tool_choice` to `auto` (default), `required`, or `none`. Enable `parallel_tool_calls` only when tool order does not matter.

## Remote MCP servers

**+ Add Tool → MCP Server**. Stored in `config.mcp_servers[]`:

- Connection: HTTP-SSE or HTTP
- Authentication: None, Access Token/API Key, Custom Headers, OAuth 2.0 (Client Credentials or PKCE)
- Auth secrets encrypted at rest; OAuth tokens refreshed at runtime

Select which MCP tools the agent may call. Test connection in Studio before saving.

## Subagents

A parent genie delegates to child genies listed in `config.subagents`:

```json
{
  "flow_genie_id": 56,
  "agent_name": "Legal Reviewer",
  "as_tool_description": "Use for contract clause analysis and compliance checks",
  "as_tool_name": "legal_reviewer"
}
```

- Each child is invoked as a tool by the parent.
- **`as_tool_description` is required** for reliable delegation (handoff description in Studio).
- If a subagent stops early, increase `max_turns` on the **child** genie.
- Avoid circular subagent references.

## RAG (Sources)

`config.collections` — ids of RAG collections with `status=COMPLETED`.

| Setting | Default | Notes |
|---------|---------|-------|
| limit | 15 | Max document sections retrieved |
| match_threshold | 0.15 | Higher = stricter similarity |

Look up collections: [lookup.md](lookup.md).

## Guardrails

`config.guardrails`:

```json
{
  "enabled": true,
  "policies": [{
    "type": "pattern|required_substring|json_schema|llm_judge|pii",
    "severity": "block|redact|warn|log",
    "when": ["on_input", "before_response", "before_tool", "after_tool"],
    "enabled": true,
    "rule": { }
  }]
}
```

Use for PII redaction, prompt-injection detection, JSON schema validation, and custom LLM judges. Blocked guardrails return errors visible in execution logs.

## Model settings

`config.model_settings` (common keys):

| Key | Notes |
|-----|-------|
| temperature | 0 = consistent; higher = creative |
| top_p | Token diversity |
| max_tokens | Response length cap |
| tool_choice | `auto`, `required`, `none` |
| parallel_tool_calls | Concurrent tool execution |
| frequency_penalty / presence_penalty | Repetition control |

Provider-specific keys may appear for OpenAI reasoning models.

## File and URL inputs

At runtime, `RunFlowGenieAgent`:

- Mustache-renders `history` and instructions against request `$data`
- Resolves file variables (images, PDF→images, CSV text, URL scrape)
- Caches file access via `package-ai/getFile/{token}` (5 min TTL)

Limits: images/PDF ≤ 2 MB; PDF ≤ 3 pages; CSV ≤ 0.2 MB; URLs must be publicly accessible.
