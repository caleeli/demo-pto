---
name: pm4-sub-process
description: >-
  Configure ProcessMaker subprocesses: prefer embedded bpmn:subProcess (or
  adHocSubProcess/transaction) to group a phase in one request; use
  bpmn:callActivity only for a reusable child process/request. Use when adding or
  editing a Sub Process, choosing embedded vs Call Activity, configuring a child
  start event, or debugging a child request that never starts or whose data never
  returns. Do not use to build the parent graph, publish, import, or export
  (pm4-process), to configure a Form or Manual Task inside a child
  (pm4-form-manual-task), or a Data Connector service call (pm4-data-connector).
  Do not start a request unless the user asked.
---

# PM4 Sub Process

ProcessMaker solutions use **two** subprocess shapes. Pick by design intent, not by habit.

| Shape | BPMN | Same request? | Use for |
|-------|------|---------------|---------|
| **Embedded Sub Process** | `bpmn:subProcess` | Yes (child scope) | Group a phase inside one diagram so the top level stays short and readable |
| **Ad-hoc Sub Process** | `bpmn:adHocSubProcess` | Yes | Optional / unordered work inside a phase, with optional completion condition |
| **Transaction** | `bpmn:transaction` | Yes | Atomic phase with cancel end + compensation activities |
| **Call Activity** (Modeler “Sub Process”) | `bpmn:callActivity` | No (new child request) | Reuse another published process, PM Block, or cross-process boundary |

**Default for new package BPMN:** embedded `bpmn:subProcess` to structure large workflows. Use Call Activity only when reuse, independent versioning, or a separate request lifecycle is required.

References: [design.md](design.md) (when to group), [architecture.md](architecture.md) (runtime), [asset-map.md](asset-map.md), [verify.md](verify.md).

Examples: [examples/embedded-subprocess.bpmn.xml](examples/embedded-subprocess.bpmn.xml), [examples/call-activity.bpmn.xml](examples/call-activity.bpmn.xml).

## When to use

- Collapse a multi-step phase (intake, review, enrichment, notification) into one top-level node with inner start → tasks/gateways → end.
- Prefer nested embedded subprocesses over a flat 40+ node canvas.
- Configure Call Activity: which child process, which Start Event, and how request data is copied / merged.
- A child **request** never starts, starts at the wrong event, or its data never shows up on the parent (Call Activity only).

## Not this skill

| User wants | Use instead |
|------------|-------------|
| Parent graph placement, publish, import, export | [pm4-process](../pm4-process/SKILL.md) |
| Form/Manual Task screen, assignment, due date | [pm4-form-manual-task](../pm4-form-manual-task/SKILL.md) |
| Screens, scripts, signals, decision tables | [pm4-screen](../pm4-screen/SKILL.md), [pm4-script](../pm4-script/SKILL.md), [pm4-signal](../pm4-signal/SKILL.md), [pm4-decision-table](../pm4-decision-table/SKILL.md) |
| Data Connector / service call (`calledElement` non-numeric) | [pm4-data-connector](../pm4-data-connector/SKILL.md) |
| Reusable packaged block | [pm4-pm-block](../pm4-pm-block/SKILL.md) |
| FEEL exit condition on Loop / Multi-instance | [pm4-expressions](../pm4-expressions/SKILL.md) |

## Choose the shape (read first)

See [design.md](design.md). Short rules:

1. **Same business phase, same request, clearer diagram** → `bpmn:subProcess` (embedded).
2. **Unordered optional tasks inside a phase** → `bpmn:adHocSubProcess`.
3. **All-or-nothing phase with compensation** → `bpmn:transaction`.
4. **Reuse an existing process / PM Block / separate request** → `bpmn:callActivity` ([Call Activity](#call-activity-separate-request)).
5. Never invent a second published process only to hide nodes; embed them instead.

## Embedded Sub Process (same request)

Inner nodes live **inside** the `bpmn:subProcess` element. Sequence flows must not cross the subprocess boundary. The parent token waits on the subprocess activity while the inner scope runs; data stays on the same request (no Call Activity copy/merge).

### Asset schema

| Stored value | Required | Notes |
|--------------|----------|-------|
| `id` / `name` | yes | Name the **phase** (“Review transcript”, “Enrich applicant”), not “Sub Process” |
| `triggeredByEvent` | no | Default `false`. `true` = event subprocess (started by inner message/signal/timer start; not sequence-flow entry) |
| Inner `startEvent` | yes (when not event-triggered) | At least one none start inside the subprocess |
| Inner tasks / gateways / flows / end | yes | Belong to this container; keep ids unique in the definitions |

```xml
<bpmn:subProcess id="Sub_Review" name="Review transcript">
  <bpmn:incoming>Flow_To_Review</bpmn:incoming>
  <bpmn:outgoing>Flow_After_Review</bpmn:outgoing>
  <bpmn:startEvent id="Start_Review">
    <bpmn:outgoing>Flow_Review_1</bpmn:outgoing>
  </bpmn:startEvent>
  <bpmn:task id="Task_Review" name="Review" pm:screenRef="SCREEN_ID" pm:assignment="requester">
    <bpmn:incoming>Flow_Review_1</bpmn:incoming>
    <bpmn:outgoing>Flow_Review_2</bpmn:outgoing>
  </bpmn:task>
  <bpmn:endEvent id="End_Review">
    <bpmn:incoming>Flow_Review_2</bpmn:incoming>
  </bpmn:endEvent>
  <bpmn:sequenceFlow id="Flow_Review_1" sourceRef="Start_Review" targetRef="Task_Review"/>
  <bpmn:sequenceFlow id="Flow_Review_2" sourceRef="Task_Review" targetRef="End_Review"/>
</bpmn:subProcess>
```

Full parent: [examples/embedded-subprocess.bpmn.xml](examples/embedded-subprocess.bpmn.xml).

### Embedded rules

- Top-level parent graph should read as a short story of phases; put detail inside each subprocess.
- Nested embedded subprocesses are allowed; nesting must be acyclic.
- Do not draw a sequence flow from an inner node to a parent-level node (or the reverse).
- Event subprocess (`triggeredByEvent="true"`) has no incoming sequence flow; it is started by its event start.
- Confirm the target engine/runtime supports embedded subprocesses (Nayra ScopeExecution). If the instance only supports Call Activity in Designer, still author embedded XML in versioned package BPMN when that is the project contract.

## Ad-hoc Sub Process

```xml
<bpmn:adHocSubProcess id="AdHoc_Extras" name="Optional follow-ups" ordering="Parallel">
  <bpmn:incoming>...</bpmn:incoming>
  <bpmn:outgoing>...</bpmn:outgoing>
  <bpmn:task id="Opt_A" name="Optional A" ... />
  <bpmn:task id="Opt_B" name="Optional B" ... />
  <bpmn:completionCondition xsi:type="bpmn:tFormalExpression">done === true</bpmn:completionCondition>
</bpmn:adHocSubProcess>
```

- `ordering`: `Parallel` (default) or `Sequential`.
- Nested `bpmn:subProcess` / containers inside ad-hoc are not auto-activated as ad-hoc activities.
- Completion condition is optional; without it, the scope completes when no active inner tokens remain.

## Transaction

```xml
<bpmn:transaction id="Tx_Commit" name="Commit changes">
  <!-- inner start → work → end; cancel end + isForCompensation activities as needed -->
</bpmn:transaction>
```

Use when the phase must cancel without taking success flows and may activate compensation activities. Prefer a normal embedded subprocess when you only need visual grouping.

## Call Activity (separate request)

In the Modeler the object is still labeled **Sub Process**; the BPMN element is `bpmn:callActivity` (`processmaker-modeler-call-activity`). The child is another process on the same instance and gets its **own** request. The parent token waits until that child request completes. The whole parent data model is copied into the child; new or changed keys merge back. There is no field-mapping control on the node.

User docs: [Sub Process](https://docs.processmaker.com/docs/sub-process). Where they disagree with the modeler and `CallActivity.php`, follow this file (see [Docs that disagree](#docs-that-disagree-with-the-code)).

### Before changing a Call Activity

Ask only if missing:

1. **Parent process** — existing name or id.
2. **Child process** — existing name or id on this instance. Never invent it.
3. **Start Event** — which plain Start Event of the child should run, when the child has more than one.

Look the ids up ([asset-map.md](asset-map.md#lookups-never-invent-ids)). **Never invent a process id or a start-event id.**

### Call Activity schema

| Stored value | Required | Notes |
|--------------|----------|-------|
| `name` | yes | Child process name; may append ` (start event name)` |
| `calledElement` | yes | `{startEvent.ownerProcessId}-{childProcess.id}` |
| `pm:config` | yes | JSON: `calledElement`, `processId`, `startEvent`, `name` |

```xml
<bpmn:callActivity id="node_2" name="Background Check"
  calledElement="ProcessId-CHILD_PROCESS_ID"
  pm:config="{&quot;calledElement&quot;:&quot;ProcessId-CHILD_PROCESS_ID&quot;,&quot;processId&quot;:CHILD_PROCESS_ID,&quot;startEvent&quot;:&quot;CHILD_START_EVENT_ID&quot;,&quot;name&quot;:&quot;Background Check&quot;}">
  <bpmn:incoming>flow_1</bpmn:incoming>
  <bpmn:outgoing>flow_2</bpmn:outgoing>
</bpmn:callActivity>
```

### Call Activity rules

- Child must exist, not be archived, and must not be the parent process itself.
- Start Event: no event definition, no Web Entry.
- Non-numeric `calledElement` suffix → Data Connector / service subprocess ([pm4-data-connector](../pm4-data-connector/SKILL.md)).
- Parent token waits; parallel gateway does not make this node async. Inbox `GET /tasks` omits it; use `all_tasks=true` or tokens API ([architecture.md](architecture.md)).
- Save parent BPMN per [pm4-process](../pm4-process/SKILL.md) (`description` required on PUT).
Inspector panels: Properties (name, process, start event), Documentation, and Advanced (node id). Loop Activity is added because the BPMN type is `bpmn:CallActivity`. Core also adds an **Assignment Rules** panel, "Start Sub Process As", which stores `pm:assignment` (see below). The node also supports interstitial settings, `pm:errorHandling`, and a custom icon.

### Start Sub Process As (`pm:assignment`)

The core Modeler registers this panel for Call Activities (`initialLoad.js`). Values:

| Value | Label |
|-------|-------|
| `""` | Anonymous |
| `requester` | Requester |
| `user_group` | Users / Groups (with `pm:assignedUsers`, `pm:assignedGroups`) |
| `previous_task_assignee` | Previous Task Assignee |
| `process_variable` | Process Variable |

It says who starts the child request. `CallActivity.php` does not read `pm:assignment` when it starts the child (the engine uses only `config.startEvent`), so treat the effect on the child's starter and permissions as **unverified** on a given instance. Leave it as the Modeler wrote it unless the user asks to change it, and test with the child's permissions in mind.

## Rules

- The child must already exist, must not be archived, and must not be the process that contains this node.
- The Start Event must have no event definition (not signal, message, or timer) and no Web Entry. The process picker hides processes that have none of these.
- If several Start Events qualify, record which one was chosen. It is stored as `startEvent` inside `pm:config`.
- A non-numeric suffix on `calledElement` is a service subprocess (Data Connector), not this skill.
- Through the API, `calledElement` is the real `{ownerProcessId}-{id}`. In git BPMN published with `package-plg`, write `${process:exact-process-name}`; deploy resolves it, and nested `pm:config.processId` can stay numeric ([portable references](../pm4-process/bpmn-document.md#portable-references-package-plg-deploy-only)). Publish the child first.
- The parent token waits until the child request completes. A parallel gateway lets other parent tokens continue. It does not make this node asynchronous. Inbox `GET /tasks` does not list that token. Use `all_tasks=true` on the same URL, or `GET /requests/{parentId}/tokens?element_id={callActivityId}`. The token `element_type` is `callActivity`; `subprocess_request_id` is the child request.
- The call copies the whole parent data model into the child. The engine also sets `_parent` (`process_id`, `request_id`, `node_id`, `config`) on the in-memory child store and uses `_parent.request_id` to set `parent_request_id`. `_parent` is a reserved magic variable: `GET /requests/{id}?include=data` strips keys that start with `_`, and merge-back does not write it onto the parent. Copied business keys are visible. The durable parent link is `parent_request_id`.
- On completion, keys that are new or changed in the child merge back into the parent.
- A child error is logged on the parent token. The child request has `parent_request_id`, so an end-event redirect on the child does not run.
- The call uses the child's published version, unless the token is already linked to a subprocess request version.
- Save the parent the way [pm4-process](../pm4-process/SKILL.md) saves any BPMN edit: `GET /api/1.0/processes/{id}/bpmn`, then `PUT /api/1.0/processes/{id}` with `description` and the whole `bpmn`. A 422 means `description` was left out.

## Links to other assets

| From | Field | Points to |
|------|-------|-----------|
| Sub Process | `calledElement` | `{ownerProcessId}-{child process id}` |
| Sub Process `pm:config` | `processId` | Child process numeric id |
| Sub Process `pm:config` | `startEvent` | Start Event element id inside the child |
| Child process | its own `pm:screenRef`, `pm:scriptRef`, and so on | Configured by the skill that owns that node |

## Validation

See [verify.md](verify.md).

Embedded:

```
- [ ] Phase uses bpmn:subProcess (or adHoc/transaction) when grouping inside one request
- [ ] Inner start/tasks/gateways/ends and flows stay inside the container
- [ ] Name describes the phase
- [ ] Top-level diagram is a short sequence of phases, not dozens of flat tasks
```

Call Activity:

```
- [ ] Element is bpmn:callActivity because a separate child process/request is required
- [ ] calledElement / pm:config agree; startEvent is a plain Start Event
- [ ] Child exists, is not archived, is not the parent
```

## Docs that disagree with the code

- The "Add a Sub Process" steps say to pick **Script Task** from the Objects menu. The Call Activity object is **Sub Process**.
- The docs describe an **Assignment Rules** panel ("Start Sub Process As"). Core registers that panel for Call Activities, so the inspector does show it. The engine starts the child with `call()` and only reads `config.startEvent`, so the panel's runtime effect is unverified (see above).
- The docs say a parallel gateway stops the parent request from waiting. The call-activity token still waits.
- `pm4-process` says `calledElement` is `ProcessId-{id}`. That is the common case. The stored value is `{ownerProcessId}-{databaseId}` from the selected Start Event.
- Older skills said embedded `bpmn:subProcess` was unsupported. Embedded is the preferred **design** shape for grouping; Call Activity remains the Modeler reuse shape.

## Common mistakes

- Flattening a large process instead of embedding phases.
- Creating a new published process + Call Activity only to hide nodes that belong in an embedded subprocess.
- Drawing sequence flows across an embedded subprocess boundary.
- Using `bpmn:callActivity` when the requirement is visual grouping in one request.
- Inventing the child process id or the start-event id.
- Setting `calledElement` to the numeric id alone.
- Expecting a field map. The whole data model is copied, and new or changed keys come back.
- Expecting `_parent` on `GET /requests/{id}?include=data`. That payload drops keys that start with `_`. Use `parent_request_id`.
- Looking in Inbox `/tasks` for the waiting Call Activity. It is a request token, not a user task.
- Choosing a signal, message, timer, or Web Entry start. The engine will not offer it, and a hand-written one does not start a valid child request.
- Pointing the node at the parent process itself, or building circular Call Activity chains.
- Publishing the parent before the child. Publish the child first.
- Pointing the node at a Web Entry-only start that cannot start a sub-process.
- Reading the Start Sub Process As setting as proof of who starts the child. Check the child's start permissions.
- Treating a parallel gateway as a way to make this node return before the child finishes.
- Configuring a Data Connector call activity here. That `calledElement` suffix is not numeric.
