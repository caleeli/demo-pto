# FlowGenie BPMN attributes

Namespace `pm` (`http://processmaker.com/BPMN/2.0/Schema.xsd`).

The element **must** be `bpmn:serviceTask` with `implementation="package-ai/processmaker-ai-task"`. Copy the string exactly. Not `bpmn:task` (Form Task), not `bpmn:scriptTask`, and not a generic service task with a different implementation. Wrong implementation → no FlowGenie inspector, no agent execution.

Modeler node id: `processmaker-ai-task`. Label: **FlowGenie**.

Do **not** use the Designer helper node `processmaker-ai-assistant` (`package-ai/processmaker-ai-assistant`, empty config): it is an authoring aid, not a runnable FlowGenie.

## Attributes

| Attribute | Value |
|-----------|-------|
| `id` | Node id, unique in the diagram |
| `name` | Action-oriented, e.g. `Summarize invoice` |
| `implementation` | `package-ai/processmaker-ai-task` |
| `pm:config` | JSON string, schema below |

Only **`pm:config`** is used. No `pm:screenRef`, `pm:assignment`, etc.

## `pm:config` schema (current)

Both keys are required for runtime. The inspector saves:

```json
{
  "flow_genie_id": 42,
  "output_variable": "ai_summary"
}
```

| Key | Notes |
|-----|-------|
| `flow_genie_id` | Integer FlowGenie id. Look up ([lookup.md](lookup.md)). `AiTask` calls `FlowGenie::findOrFail`. |
| `output_variable` | Request variable that receives the agent output. Default at runtime: `ai_task_output` if omitted. |

At run time, `AiTask` returns `[ $outputVariable => $result ]`. On agent failure, the value is `{ "error": "<message>" }`. JSON string outputs are auto-decoded to arrays/objects.

## Legacy inline config (do not use for new work)

Inspector still detects old nodes:

```json
{
  "prompt": "Summarize {{document}}",
  "file": "document",
  "response_format": "json",
  "output_variable": "summary"
}
```

This format is **not executed** by `AiTask` at request time (runtime requires `flow_genie_id`). Upgrade via `FlowGenieConverter` or recreate as a linked genie.

## Loop Activity

FlowGenie nodes support the standard Loop Activity panel (No Loop, Loop, Multi-instance Parallel/Sequential). Same as other service tasks. See [Add a Genie in a Process](https://docs.processmaker.com/docs/add-a-genie-in-a-process).

## Example node

Replace `GENIE_ID`. Connect `incoming` and `outgoing` in the process.

```xml
<bpmn:serviceTask id="node_2" name="Summarize invoice"
  implementation="package-ai/processmaker-ai-task"
  pm:config="{&#34;flow_genie_id&#34;:GENIE_ID,&#34;output_variable&#34;:&#34;ai_summary&#34;}">
  <bpmn:incoming>flow_1</bpmn:incoming>
  <bpmn:outgoing>flow_2</bpmn:outgoing>
</bpmn:serviceTask>
```

Full process example: [examples/flowgenie-service-task.bpmn.xml](examples/flowgenie-service-task.bpmn.xml).

Process graph (Start, flows, End, publish): [pm4-process](../pm4-process/SKILL.md).
