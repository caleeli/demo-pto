# Pools, lanes, and message flows

Load [SKILL.md](SKILL.md) first. Use this when a diagram has more than one participant, lanes, or message flows between pools. Message configuration (declarations, payload, receivers) is in [events.md](events.md#messages-declaration-targeting-and-payload). Fixture: [examples/message-collaboration.bpmn.xml](examples/message-collaboration.bpmn.xml).

## Identity

| | Pool | Lane |
|--|------|------|
| Modeler id | `processmaker-modeler-pool` | `processmaker-modeler-lane` |
| BPMN type | `bpmn:participant` | `bpmn:lane` |
| Palette | Yes | No. Created from the pool's crown menu |
| Configuration | Name, id, documentation only | Name, id, documentation only |

Pools live under a `bpmn:collaboration`. Each participant references a `bpmn:process` through `processRef`. Lanes go in that process's `laneSet`, with `flowNodeRef` children.

There is no `pm:config` and no `implementation` on a pool or lane.

## Inspector fields

| Field | Attribute | Required |
|-------|-----------|----------|
| Name | `name` | Yes |
| Node identifier | `id` | Yes (Advanced) |
| Documentation | `bpmn:documentation` child | No |

## Rules

1. Lanes never stand alone: create the `participant` first, then the process `laneSet`. One or many pools → one `collaboration` with one participant per pool.
2. With a collaboration, Plane `bpmnElement` is the **collaboration** id; include `BPMNShape`s for the participant and each lane. Node shapes stay inside their lane bounds. DI edges: [bpmn-document.md](bpmn-document.md#rules). Pool shape ~600×300.
3. Executable work stays in the process that owns the flow nodes (`isExecutable` on that process).
4. Cross-pool links are `bpmn:messageFlow`, never `sequenceFlow`. See [gateways.md](gateways.md#sequence-flows-versus-message-flows).
5. Each flow node lives in its pool's process and, when lanes are used, in a lane `flowNodeRef`.
6. A message flow line does not transfer data on its own — configure `messageRef` and event data mappings ([events.md](events.md#messages-declaration-targeting-and-payload)).


## Single pool with lanes

```xml
<bpmn:collaboration id="collaboration_0">
  <bpmn:participant id="node_pool" name="Leave request" processRef="ProcessId" />
</bpmn:collaboration>
<bpmn:process id="ProcessId" name="demo-leave-request" isExecutable="true">
  <bpmn:laneSet id="LaneSet_1">
    <bpmn:lane id="lane_employee" name="Employee">
      <bpmn:flowNodeRef>node_1</bpmn:flowNodeRef>
      <!-- …other employee nodes… -->
    </bpmn:lane>
    <bpmn:lane id="lane_manager" name="Manager">
      <bpmn:flowNodeRef>node_manager</bpmn:flowNodeRef>
    </bpmn:lane>
  </bpmn:laneSet>
  <!-- start, tasks, gateways, end, sequenceFlows -->
</bpmn:process>
<!-- DI: Plane → collaboration_0; shapes for pool, lanes, nodes; edges per bpmn-document -->
```

## Multi-pool sketch

```xml
<bpmn:collaboration id="collaboration_0">
  <bpmn:participant id="node_pool_a" name="Applicant" processRef="Process_A" />
  <bpmn:participant id="node_pool_b" name="Back Office" processRef="Process_B" />
  <bpmn:messageFlow id="mf_1" sourceRef="node_task_a" targetRef="node_task_b" />
</bpmn:collaboration>
<bpmn:process id="Process_A" isExecutable="true">
  <bpmn:laneSet>
    <bpmn:lane id="node_lane_a" name="Submit">
      <bpmn:flowNodeRef>node_task_a</bpmn:flowNodeRef>
    </bpmn:lane>
  </bpmn:laneSet>
  <!-- start, tasks, end for Process_A -->
</bpmn:process>
<bpmn:process id="Process_B" isExecutable="false">
  <!-- counterpart process -->
</bpmn:process>
```

## Failures

| Symptom | Cause |
|---------|-------|
| The pool is treated like a task | A pool is a participant with a `processRef`, not an activity |
| The flow between pools is rejected or does nothing | A `sequenceFlow` was used. Use `messageFlow` |
| A node is missing from the lane view | It is not listed in any lane's `flowNodeRef` |
| Pool or lane is invisible or collapsed | Missing or too small diagram bounds |
| Lanes render without a pool / broken strip | `laneSet` without `collaboration` + `participant`, or Plane still points at the process |
