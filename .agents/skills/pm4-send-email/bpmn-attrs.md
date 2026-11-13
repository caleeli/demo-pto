# Send Email BPMN attributes

Namespace `pm` (`http://processmaker.com/BPMN/2.0/Schema.xsd`).

The element **must** be `bpmn:serviceTask` with `implementation="connector-send-email/processmaker-communication-email-send"`. Copy exactly. Modeler node id: `processmaker-communication-email-send`. Label: **Send Email**.

Wrong implementation → generic Service Task, no Send Email inspector, no email at runtime.

## Attributes

| Attribute | Value |
|-----------|-------|
| `id` | Node id, unique in the diagram |
| `name` | Action-oriented, e.g. `Notify approver` |
| `implementation` | `connector-send-email/processmaker-communication-email-send` |
| `pm:config` | JSON string — see [config-schema.md](config-schema.md) |

Only **`pm:config`** carries connector settings. No `pm:screenRef` on the task element (screen id is **inside** `pm:config` when `type` is `screen`).

## Example node

Replace `SCREEN_ID` when using an EMAIL screen body. Connect `incoming` / `outgoing`.

```xml
<bpmn:serviceTask id="node_2" name="Notify approver"
  implementation="connector-send-email/processmaker-communication-email-send"
  pm:config="{&#34;emailServer&#34;:&#34;&#34;,&#34;type&#34;:&#34;text&#34;,&#34;subject&#34;:&#34;Invoice {{invoice_id}} approved&#34;,&#34;textBody&#34;:&#34;Hello {{requester_name}}, your invoice was approved.&#34;,&#34;screenRef&#34;:null,&#34;toRecipients&#34;:[{&#34;type&#34;:&#34;email&#34;,&#34;value&#34;:&#34;{{notify_email}}&#34;}],&#34;ccRecipients&#34;:[],&#34;bccRecipients&#34;:[],&#34;attachments&#34;:[]}">
  <bpmn:incoming>flow_1</bpmn:incoming>
  <bpmn:outgoing>flow_2</bpmn:outgoing>
</bpmn:serviceTask>
```

Full process: [examples/send-email-text.bpmn.xml](examples/send-email-text.bpmn.xml).

## Loop Activity

Send Email supports the standard Loop Activity panel (No Loop, Loop, Multi-instance Parallel/Sequential), same as other service tasks.

Process graph: [pm4-process](../pm4-process/SKILL.md).
