---
name: pm4-flow-genie
description: >-
  PM4 FlowGenies; graph: pm4-process.
---

# PM4 FlowGenie

A **FlowGenie** is a `flow_genies` asset (`processmaker/package-ai`). A process runs it from a `bpmn:serviceTask` with `implementation="package-ai/processmaker-ai-task"`. The node stores a genie id and an output variable in `pm:config`; the genie stores the agent config (instructions, history, tools, RAG).

This skill works on an **existing** instance with `package-ai` installed. Do not create a demo process, start a request, or call the AI microservice unless the user asked.

References: [genie-schema.md](genie-schema.md) (asset config), [bpmn-attrs.md](bpmn-attrs.md) (node schema), [tools-agents.md](tools-agents.md) (MCP, subagents, guardrails), [lookup.md](lookup.md) (endpoints, ids), [asset-map.md](asset-map.md) (API routes, code paths), [verify.md](verify.md) (checks, failures). Examples: [examples/](examples/).

User docs: [FlowGenie](https://docs.processmaker.com/docs/flowgenie), [Create a Genie](https://docs.processmaker.com/docs/create-a-genie-in-the-flowgenie-studio), [Add a Genie in a Process](https://docs.processmaker.com/docs/add-a-genie-in-a-process). Where they disagree with these files (legacy inline prompts, exact config keys), trust these files: they were checked against `package-ai` source.

## Not this skill

| User wants | Use instead |
|------------|-------------|
| Form Task / User Task / Manual Task with a screen | [pm4-form-manual-task](../pm4-form-manual-task/SKILL.md) |
| Decision Task / Decision Table | `pm4-decision-table` |
| REST Data Connector node | `pm4-data-connector` |
| Screen FORM/DISPLAY | `pm4-screen` |
| Add or connect a FlowGenie service task in the BPMN graph | `pm4-process` |
| Generic process graph (Start, gateways, publish) | `pm4-process` |
| Admin → Settings → Integrations → AI (global defaults) | Platform admin; not genie-specific |
| Generic 422 / PMQL / pagination | [pm-api-contracts](../pm-api-contracts/SKILL.md) |
| Local instance down, AI microservice unreachable | [setup-processmaker](../setup-processmaker/SKILL.md) |

## Before changing anything

Ask only if missing:

1. **Process** — existing name or id (when wiring a node).
2. **Genie** — existing name or id, or the behavior to create one (instructions, inputs, output format).
3. **Provider and model** — OpenAI (default platform key), Gemini, or Claude (needs `provider_api_key`).
4. **Inputs** — which request variables feed mustache `{{var}}` in instructions or history.
5. **Output** — which request variable receives the response (`output_variable` on the BPMN node; default `ai_task_output`).
6. **Files** — image/PDF/CSV request variables or Studio attachments with "Use Process Variable".

Then look up real ids ([lookup.md](lookup.md)). **Never invent genie, category, collection, or subagent ids.**

## Required (every FlowGenie node)

1. **Element and implementation**: `bpmn:serviceTask`, `implementation="package-ai/processmaker-ai-task"`, copied exactly.
2. **`pm:config`**: `flow_genie_id` (looked-up integer) and `output_variable` (string; default `ai_task_output` if omitted at runtime).
3. **Genie exists** on this instance with instructions and history configured.
4. **Every `{{var}}` in instructions/history** resolves from request data before the node runs, or the agent receives empty values.

Legacy inline `pm:config` (`prompt`, `file`, `response_format`) still renders in the inspector but **does not run** at request time. Convert to a linked genie or create a new one.

## Creating or changing a Genie

- Create metadata: `POST /api/1.0/package-ai/flow_genies` with `name`, `description`, `flow_genie_category_id`.
- Save full config: `PUT /api/1.0/package-ai/flow_genies` with `id`, `name`, `description`, `flow_genie_category_id`, `config` (object). See [genie-schema.md](genie-schema.md).
- Metadata only: `PATCH /api/1.0/package-ai/flow_genies/{id}/metadata`.
- Preview without a request: `POST /api/1.0/package-ai/flow_genies/{id}/execute` with sample `data`. See [verify.md](verify.md).
- `name` is unique, `alpha_spaces`. `description` max 300 chars, required.
- Never put API keys or MCP secrets in skill examples or git; the model encrypts `provider_api_key` and MCP auth at rest.

## Workflow

```
- [ ] 1. Confirm package-ai is installed and the task is a FlowGenie serviceTask (exact implementation)
- [ ] 2. Look up genie id (and category, RAG collection, subagent ids if needed). Stop if missing
- [ ] 3. Create or update the Genie if asked (instructions, history, model, tools, RAG)
- [ ] 4. Preview: POST .../flow_genies/{id}/execute with sample data
- [ ] 5. Set pm:config on the node: flow_genie_id, output_variable
- [ ] 6. Confirm every {{var}} and file variable exists upstream in the process
- [ ] 7. Save + Publish if editing in Designer (drafts are not live)
- [ ] 8. Static check (verify.md). Do not start a request unless asked
```

## Done when (static)

```
- [ ] serviceTask with implementation="package-ai/processmaker-ai-task"
- [ ] Name is action-oriented, not the default "FlowGenie"
- [ ] pm:config has flow_genie_id; genie id exists on this instance
- [ ] output_variable set when downstream steps read a specific key (not ai_task_output)
- [ ] Genie instructions and history match the intended behavior; output_type matches downstream parsing
- [ ] RAG collections are COMPLETED; subagents have handoff descriptions
- [ ] /execute returns expected output for at least one sample input
```

When moving a process to another instance, linked genies are exported with the process package. Re-import or recreate genies on the target instance and verify ids still match.

Create and edit steps: [create-edit.md](create-edit.md).
