# BPMN document rules

Load [SKILL.md](SKILL.md) first. This file is the contract for the BPMN XML itself: skeleton, required structure, asset references, and order of work. For individual nodes see [bpmn-elements.md](bpmn-elements.md), [gateways.md](gateways.md) and [events.md](events.md).

## What a Process is

A Process is a record whose executable definition is a BPMN 2.0 XML string in `processes.bpmn`, with ProcessMaker extensions in the `pm:` namespace (`http://processmaker.com/BPMN/2.0/Schema.xsd`).

| Field | Required | Notes |
|-------|----------|-------|
| `name` | yes | Unique. Letters, numbers, spaces, and `- _ . '` (`alpha_spaces`) |
| `description` | yes | Required on create and on every PUT |
| `status` | no | `ACTIVE`, `INACTIVE`, or `ARCHIVED` |
| `process_category_id` | no | Must exist |
| `bpmn` | no | Full BPMN XML. When omitted, the platform uses an empty `OnlyStartElement` template. That template is **not** runnable: add a Start Event before running |

`POST /api/1.0/processes` also accepts an uploaded BPMN `file`. The schema is validated, and HTML entities in the BPMN are normalized.

Update BPMN with `PUT /api/1.0/processes/{id}`. See [create-edit.md](create-edit.md).

## Minimum runnable skeleton

```xml
<?xml version="1.0" encoding="UTF-8"?>
<bpmn:definitions xmlns:bpmn="http://www.omg.org/spec/BPMN/20100524/MODEL"
  xmlns:bpmndi="http://www.omg.org/spec/BPMN/20100524/DI"
  xmlns:dc="http://www.omg.org/spec/DD/20100524/DC"
  xmlns:di="http://www.omg.org/spec/DD/20100524/DI"
  xmlns:pm="http://processmaker.com/BPMN/2.0/Schema.xsd"
  id="Definitions_1" targetNamespace="http://bpmn.io/schema/bpmn" exporter="ProcessMaker Modeler">
  <bpmn:process id="ProcessId" name="my-process" isExecutable="true">
    <bpmn:startEvent id="node_1" name="Start">
      <bpmn:outgoing>node_4</bpmn:outgoing>
    </bpmn:startEvent>
    <bpmn:task id="node_2" name="Form Task" pm:screenRef="SCREEN_ID" pm:assignment="requester">
      <bpmn:incoming>node_4</bpmn:incoming>
      <bpmn:outgoing>node_5</bpmn:outgoing>
    </bpmn:task>
    <bpmn:endEvent id="node_3" name="End">
      <bpmn:incoming>node_5</bpmn:incoming>
    </bpmn:endEvent>
    <bpmn:sequenceFlow id="node_4" sourceRef="node_1" targetRef="node_2" />
    <bpmn:sequenceFlow id="node_5" sourceRef="node_2" targetRef="node_3" />
  </bpmn:process>
  <bpmndi:BPMNDiagram id="BPMNDiagram_1">
    <bpmndi:BPMNPlane id="BPMNPlane_1" bpmnElement="ProcessId"><!-- shapes and edges --></bpmndi:BPMNPlane>
  </bpmndi:BPMNDiagram>
</bpmn:definitions>
```

A complete file with diagram shapes: [examples/start-form-end.bpmn.xml](examples/start-form-end.bpmn.xml).

## Rules

1. Include `xmlns:pm` whenever a `pm:` attribute is used. The `bpmn:process` has `isExecutable="true"`.
2. Exactly **one** `bpmndi:BPMNDiagram`. The API validates this.
3. A Form Task is `bpmn:task`, not `bpmn:userTask`. Keep an imported `userTask` as it is.
4. Node ids match the Advanced inspector pattern `^[a-zA-Z][^\s][a-zA-Z0-9_-]+$` (for example `node_1`).
5. Every flow has a `BPMNEdge` with `di:waypoint`s, and every shape has bounds. Shapes without edges → Modeler sunburst from `(0,0)`.

6. **Every sequence flow also appears as `<bpmn:outgoing>` on the source and `<bpmn:incoming>` on the target.** The engine wires the runtime graph from those child references. `sourceRef` and `targetRef` alone are not enough: a Start Event then has no outgoing flow and the request never reaches the first task.
7. Do not put Form Task assignment values such as `pm:assignment="requester"` on a Start Event. Start permissions use `user`, `group`, or `process_manager`, or are omitted. See [events.md](events.md#entry-and-exit-settings).
8. Interstitial: if `pm:allowInterstitial="true"`, set `pm:interstitialScreenRef` as well. See [events.md](events.md#entry-and-exit-settings).
9. Escape `&`, `<` and `>` in condition expressions and in JSON stored in a `pm:config` attribute.

## Screen, script and process references

Every Form Task and Manual Task needs a real screen before the process can run. Every Script Task needs a script.

- **Through the API or Designer on a live instance:** set `pm:screenRef`, `pm:scriptRef`, and `calledElement` to ids you looked up ([lookup.md](lookup.md)). The process API does not resolve named references. A reference like `${screen:Title}` saved this way leaves the task unassigned in Designer and at runtime.
- After saving, read the BPMN back (`GET /api/1.0/processes/{id}/bpmn`) and confirm every `pm:screenRef` and `pm:scriptRef` is numeric.

### Portable references (package-plg deploy only)

When BPMN lives in git and is published with `package-plg` (`POST /api/1.0/package-plg/deploy-process`), use named references instead of ids. Deploy resolves them to ids on the target instance, and a pull restores the names.

| Attribute or field | Portable form |
|--------------------|---------------|
| `pm:screenRef` | `${screen:exact-screen-title}` |
| `pm:scriptRef` | `${script:exact-script-title}` |
| `calledElement` on a Call Activity | `${process:exact-process-name}` |
| `web_entry.screen_id` and `completed_screen_id` | `${screen:exact-screen-title}` |

Rules for portable refs:

- Use the exact asset title or process name.
- Leave other `pm:config` values as the Modeler wrote them. Do not invent `${...}` inside other JSON keys.
- Publish order: scripts, screens, child processes (Call Activity targets), then the parent process.
- The BPMN file basename must equal the process `name`.
- An unresolved `${...}` makes deploy fail with 422. Do not send portable refs through the process API.
- Do not commit numeric ids to git when portable refs are available.

## Request data

Request data is one mutable associative array for the request.

| Node | In | Out |
|------|----|-----|
| Form Task | Request data bound into the FORM screen | Submitted fields merge into request data |
| Manual Task | Request data shown on a DISPLAY screen | Acknowledgement only. Little or no field merge |
| Script Task | Request data and `$config` | The returned array merges into request data |
| Service Task | Request data and decoded config | The implementation output merges into request data |
| Call Activity | Whole parent data copied to the child | New or changed child keys merge back |
| Gateway flows | Conditions read request data | An Exclusive flow's optional `pm:config.update_data` writes a key |

## Inspector and XML

The Modeler writes:

- Top-level `pm:*` attributes on the element.
- Nested BPMN such as `eventDefinitions`, `conditionExpression`, and timer definitions.
- `pm:config`, a stringified JSON attribute used for Web Entry, notifications, connector settings, and self service.

Connector settings go in the node's `implementation` attribute and its `pm:config` JSON. Do not invent `implementation` strings. See [Service Task connectors](bpmn-elements.md#service-task-connectors).

## Order of work

1. Look up the process by name. Avoid a duplicate `name`.
2. **Catalogs first.** For every select, radio, or checkbox group with more than two options, create and seed a Collection ([pm4-collection](../pm4-collection/SKILL.md)). Keep the collection id. Confirm the Data Connector ([pm4-data-connector](../pm4-data-connector/SKILL.md)).
3. Create or look up every screen, script, and child process. Keep a title to id map. Screens that use a collection get its numeric id.
4. Write the BPMN: Start, tasks and gateways, End. Use the map for `pm:screenRef` and `pm:scriptRef`. Gateway conditions use the catalog `code` values.
5. Run [static verification](verify.md).
6. Save through the API ([create-edit.md](create-edit.md)) or deploy through `package-plg`.
7. Read it back. Every Form and Manual Task has a screen id, and the path from Start to End is complete.

## Do not

- Use `bpmn:userTask` for a new Form Task.
- Save `${screen:...}`, `${script:...}` or `${process:...}` through the process API.
- Leave a blank create with no Start Event and expect it to run.
- Put gateway conditions on the gateway. They belong on the outgoing **sequence flows**.
- Add a package `implementation` for a connector that is not installed.
- Stop after creating screens without wiring them into the BPMN.
- Leave a multi-option select as a hard-coded list instead of a Collection.
