# BPMN elements in a PM4 process

Namespace `pm` (`http://processmaker.com/BPMN/2.0/Schema.xsd`). Workflow nodes and sequence flows live inside `bpmn:process`; referenced `bpmn:message`, `bpmn:signal`, and `bpmn:error` declarations belong at definitions level. Cross-pool message flows belong to a collaboration. `default`, `gatewayDirection`, `attachedToRef`, `cancelActivity`, and event refs are standard BPMN fields, not `pm:*` attributes.

## Node types

| Element | Purpose | Configuration / references |
|---------|---------|------------------|
| `bpmn:startEvent` | Request begins | None/timer/message/conditional definitions: [events.md](events.md); signals: [pm4-signal](../pm4-signal/SKILL.md). Web Entry ([pm4-web-entry](../pm4-web-entry/SKILL.md)) and interstitial settings are separate |
| `bpmn:intermediateCatchEvent` | Wait for an event during a request | Timer/message/conditional definitions: [events.md](events.md); signals: [pm4-signal](../pm4-signal/SKILL.md) |
| `bpmn:intermediateThrowEvent` | Emit message or signal and continue | Message ref/mappings: [events.md](events.md); signal payload: [pm4-signal](../pm4-signal/SKILL.md) |
| `bpmn:boundaryEvent` | Alternate path from attached work | `attachedToRef`, `cancelActivity`, type-specific definition/restrictions: [events.md](events.md#boundary-attachment-and-interruption) |
| `bpmn:endEvent` | Complete a branch or invoke error/terminate behavior | Event definitions and ending scope: [events.md](events.md); `pm:elementDestination` is a separate redirect |
| `bpmn:task` | Form Task (human) | `pm:screenRef`, `pm:assignment`, `pm:dueIn` — see [pm4-form-manual-task](../pm4-form-manual-task/bpmn-attrs.md) |
| `bpmn:manualTask` | Offline work acknowledged by a human | DISPLAY `pm:screenRef`, shared assignment/due settings — see [pm4-form-manual-task](../pm4-form-manual-task/bpmn-attrs.md) |
| `bpmn:scriptTask` | Runs a Script asset | `pm:scriptRef` (script id, required) |
| `bpmn:serviceTask` | FlowGenie (AI agent) | `implementation="package-ai/processmaker-ai-task"`, `pm:config` — see [pm4-flow-genie](../pm4-flow-genie/bpmn-attrs.md) |
| `bpmn:callActivity` | Runs a child process | `calledElement` (`{ownerProcessId}-{id}`), `pm:config` — see [pm4-sub-process](../pm4-sub-process/SKILL.md) |
| `bpmn:serviceTask` | Send Email | `implementation="connector-send-email/processmaker-communication-email-send"`, `pm:config` — see [pm4-send-email](../pm4-send-email/bpmn-attrs.md) |
| `bpmn:callActivity` | Child process or Actions by Email | `calledElement` (child process id). ABE: [pm4-action-by-email](../pm4-action-by-email/SKILL.md) |
| `bpmn:exclusiveGateway` | Choose one route / non-synchronizing merge | Outgoing conditions; gateway `default` flow reference: [gateways.md](gateways.md) |
| `bpmn:inclusiveGateway` | Select matching routes / synchronize selected work | `gatewayDirection`, outgoing conditions/default, reachability-based join: [gateways.md](gateways.md) |
| `bpmn:parallelGateway` | Run all branches / synchronize every incoming connection | `gatewayDirection`; no conditions/default: [gateways.md](gateways.md) |
| `bpmn:eventBasedGateway` | First competing catch wins | Direct catch targets, no conditions/default; restrictions: [gateways.md](gateways.md#event-based-race-between-catches) |
| `bpmn:sequenceFlow` | Connect nodes within a process/pool | `sourceRef`, `targetRef`, reciprocal flow IDs; `conditionExpression` only on Exclusive/Inclusive exits in Modeler |
| `bpmn:messageFlow` | Model cross-pool messaging | Collaboration-level source/target; message refs and payload configuration remain required: [events.md](events.md) |

## Rules

- New **Form Task** uses `bpmn:task`, not `serviceTask`, `scriptTask`, or `manualTask`; preserve imported `userTask` when editing an existing model.
- **Manual Task** uses `bpmn:manualTask` with DISPLAY instructions. It waits for acknowledgment through the platform Complete Task action; it is not automatically skipped.
- **Script Task** must be `bpmn:scriptTask` with `pm:scriptRef`. No Inbox task is created.
- **Call Activity** `calledElement` is `{ownerProcessId}-{id}` (often `ProcessId-34`). Child process, start event, and data: [pm4-sub-process](../pm4-sub-process/SKILL.md).
- Tasks/gateways and Intermediate events use incoming/outgoing sequence flows. Start has no incoming; End has no outgoing. Boundary events instead use attachment and an alternate outgoing path; do not require an incoming flow.
- Exclusive conditions should avoid unintended overlaps; Inclusive conditions may intentionally overlap. Their default is fallback only. Parallel/Event-Based exits use neither conditions nor default. See [gateways.md](gateways.md).
- Check each intended entry, branch, join, and attached alternate path. A normal End completes a branch, not every pending task. Event support and dependencies: [events.md](events.md).

## Service Task connectors

A Service Task has no settings of its own except its name. The work is set by the `implementation` attribute and the `pm:config` JSON. Copy `implementation` exactly from this table. Do not invent a string. The package that registers it must be installed on the instance.

Service Task (`bpmn:serviceTask`):

| Palette node | `implementation` | Skill |
|--------------|------------------|-------|
| Send Email | `connector-send-email/processmaker-communication-email-send` | [pm4-send-email](../pm4-send-email/SKILL.md) |
| Data Connector | `package-data-sources/data-source-task-service` | [pm4-data-connector](../pm4-data-connector/SKILL.md) |
| Decision Task | `package-decision-engine/decision-table` | [pm4-decision-table](../pm4-decision-table/SKILL.md) |
| FlowGenie | `package-ai/processmaker-ai-task` | [pm4-flow-genie](../pm4-flow-genie/SKILL.md) |
| PDF Generator | `connector-pdf-print/processmaker-communication-pdf-print` | [pm4-pdf-generator](../pm4-pdf-generator/SKILL.md) |
| Slack Notification | `connector-slack/processmaker-communication-slack` | [pm4-slack](../pm4-slack/SKILL.md) |
| UiPath | `package-rpa/processmaker-communication-rpa` | [pm4-uipath](../pm4-uipath/SKILL.md) |
| IDP | `connector-idp/processmaker-communication-idp` | [pm4-idp](../pm4-idp/SKILL.md) |

Call Activity (`bpmn:callActivity`) connectors, which are not Service Tasks:

| Palette node | Marker | Skill |
|--------------|--------|-------|
| Actions by Email | `calledElement` is `ProcessId-{id}` of the Actions by Email process | [pm4-action-by-email](../pm4-action-by-email/SKILL.md) |
| DocuSign | `calledElement` is `ProcessId-{id}` of `DocuSignSendEnvelope`, with `pm:allowInterstitial="true"` and `pm:interstitialScreenRef="DocuSignWait"` | [pm4-docusign](../pm4-docusign/SKILL.md) |
| Data Connector (callback) | `calledElement="data_source_callback-data_source_sub_process"` | [pm4-data-connector](../pm4-data-connector/wire.md#callback-variant-async) |

Rules:

- Load the connector skill before writing its `pm:config`.
- `pm:config` is JSON inside an XML attribute. It must be valid JSON and XML-escaped.
- An unknown or missing `implementation` does nothing, or fails at runtime.
- Some connector nodes support `pm:errorHandling` (timeout, retries).
- An email connector's own `screenRef` is not the Form Task `pm:screenRef`.

## Minimal linear example

Start → Form Task → End:

```xml
<bpmn:startEvent id="node_1" name="Start">
  <bpmn:outgoing>flow_1_2</bpmn:outgoing>
</bpmn:startEvent>
<bpmn:task id="node_2" name="Approve invoice"
  pm:screenRef="SCREEN_ID"
  pm:assignment="requester"
  pm:dueIn="48">
  <bpmn:incoming>flow_1_2</bpmn:incoming>
  <bpmn:outgoing>flow_2_3</bpmn:outgoing>
</bpmn:task>
<bpmn:endEvent id="node_3" name="End">
  <bpmn:incoming>flow_2_3</bpmn:incoming>
</bpmn:endEvent>
<bpmn:sequenceFlow id="flow_1_2" sourceRef="node_1" targetRef="node_2" />
<bpmn:sequenceFlow id="flow_2_3" sourceRef="node_2" targetRef="node_3" />
```

Replace `SCREEN_ID` with a real Form Screen id from [lookup.md](lookup.md).

## Script Task example

```xml
<bpmn:scriptTask id="node_4" name="Send notification" pm:scriptRef="SCRIPT_ID">
  <bpmn:incoming>flow_3_4</bpmn:incoming>
  <bpmn:outgoing>flow_4_5</bpmn:outgoing>
</bpmn:scriptTask>
```

## Call Activity example

Place the node on the parent graph here. Set `calledElement`, `pm:config.startEvent`, and the data contract with [pm4-sub-process](../pm4-sub-process/SKILL.md). Full parent XML: [pm4-sub-process/examples/call-activity.bpmn.xml](../pm4-sub-process/examples/call-activity.bpmn.xml).

```xml
<bpmn:callActivity id="node_5" name="Run subprocess" calledElement="ProcessId-CHILD_PROCESS_ID">
  <bpmn:incoming>flow_4_5</bpmn:incoming>
  <bpmn:outgoing>flow_5_6</bpmn:outgoing>
</bpmn:callActivity>
```
