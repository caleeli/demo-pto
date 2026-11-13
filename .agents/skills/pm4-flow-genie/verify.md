# Check a FlowGenie node and Genie

Static check plus an execute preview. Do not start a request unless the user asked.

## Static checklist (the node)

See [bpmn-attrs.md](bpmn-attrs.md).

```
- [ ] Element is bpmn:serviceTask
- [ ] implementation="package-ai/processmaker-ai-task" (exact)
- [ ] name is action-oriented, not the default "FlowGenie"
- [ ] pm:config has flow_genie_id; id exists (GET package-ai/flow_genies/{id})
- [ ] output_variable set when downstream reads a name other than ai_task_output
- [ ] Not legacy inline pm:config (prompt/file/response_format without flow_genie_id)
- [ ] Every {{var}} in genie instructions/history will exist in request data before this node
- [ ] File variables (include_request_variable) match upstream form/upload field names
- [ ] output_type (JSON/Table) matches what downstream steps expect
- [ ] RAG collections in config are COMPLETED on this instance
- [ ] Subagents exist; each has as_tool_description
```

In Designer: Save, then Publish. Drafts are not live.

## Static checklist (the genie asset)

```
- [ ] name unique, description ≤ 300 chars, category set
- [ ] instructions define role and output expectations
- [ ] history includes representative user messages for the task
- [ ] provider/model appropriate; provider_api_key set for non-OpenAI providers
- [ ] max_turns sufficient when tools or subagents are enabled
- [ ] guardrails enabled where PII or injection risk exists
- [ ] No secrets in git-tracked config exports
```

## Preview a genie (no request needed)

```
POST /api/1.0/package-ai/flow_genies/{id}/execute
Content-Type: application/json

{
  "id": 42,
  "data": {
    "document_text": "Invoice #123 for $500",
    "customer_name": "Acme Corp"
  }
}
```

- `data` keys match mustache variables in instructions/history.
- 200: agent output (may be string or object depending on `output_type`).
- Failure: error message from the agent microservice.

Preview does not test BPMN wiring or loop modes. Check those statically.

Studio inline test (design time only):

```
POST /api/1.0/package-ai/runGenieTest
```

Requires `configuration`, `conversation`, `system_message`. Used by Studio, not BPMN runtime.

## Failures

| Symptom | Likely cause |
|---------|--------------|
| Node opens as generic Service Task, no genie inspector | Wrong `implementation`, or `package-ai` not installed / assets not published |
| Token fails: model not found | Genie `flow_genie_id` missing or genie deleted |
| Request variable empty after node | Wrong `output_variable`; downstream reads a different key |
| `{ "error": "..." }` in output variable | Agent microservice failure, guardrail block, or tool error. Check execution logs |
| `{{var}}` renders empty | Variable not set upstream, or wrong variable name in history |
| JSON output is a string downstream expected as object | `output_type` not JSON, or agent returned plain text. Runtime decodes JSON strings when possible |
| RAG returns nothing | Collection not `COMPLETED`, match_threshold too high, or no matching sections |
| Subagent never called | Missing or vague `as_tool_description`; increase `max_turns` on child genie |
| 422 on POST flow_genies | Name not unique or invalid chars; description > 300 or missing; category missing |
| 422 deleting category | Category still has associated genies |
| Works in Studio, fails in process | Different request data than `test_data`; file variable missing at run time |
| Process import fails | Referenced genie id missing on target instance. Import genie package or update BPMN |

Fix the genie or the node. Do not create a sample process to prove the config unless the user asked.

Execution logs:

```
GET /api/1.0/flow-genie/logs/execution
GET /api/1.0/flow-genie/logs/design
```
