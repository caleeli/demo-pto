---
name: pm3-classic-process
description: >-
  ProcessMaker 3 classic Process Map only when the request names processes_Map
  or the classic map editor. Sequential routing and conditional transitions in
  that editor. Not BPMN exclusive, inclusive, or parallel gateways.
---

# PM3 Classic Process

The **classic Process Map** (`processes_Map?PRO_UID=`) is the legacy workflow editor. It uses sequential tasks and explicit conditions on transitions instead of BPMN gateways.

## When to use

- Process opens in classic map, not BPMN Designer
- Sequential or conditional routing without BPMN notation
- Building blocks panel: dynaforms, triggers, messages, report tables
- Consolidated cases tasks (enterprise)

## Classic map elements

| Element | Behavior |
|---------|----------|
| Task | User activity with steps |
| Subprocess | Linked child process |
| Start | Case entry (implicit) |
| End | Case termination |
| Transition | Arrow between tasks with optional condition |

Conditions use `@@` variable expressions (see `pm3-expressions`):

```
@@APPROVED == 'YES'
@@AMOUNT > 5000
```

## Classic vs BPMN

| Aspect | Classic map | BPMN Designer |
|--------|-------------|---------------|
| Editor URL | `processes_Map` | `designer` |
| Routing | Per-transition conditions | Gateways and sequence flows |
| Parallel paths | Limited patterns | Parallel gateways |
| Events | Basic | Timer, message, email events |
| Building blocks | Side panel (includes Messages) | Submenu (`processes.php`) |

Confirm which editor a process uses before making routing changes. Switching editors can break existing routing.

## Building blocks (classic panel)

From `processmap.js` panels:

- Dynaforms
- Input documents
- Output documents
- Triggers
- Messages
- Report tables

Same underlying assets as BPMN Designer — only the navigation UI differs.

## Consolidated cases

Enterprise license enables consolidated inbox UI in Process Map (`consolidated: "1"` in `processmap.js` options). Per-task configuration is stored in `CASE_CONSOLIDATED` table. See `pm3-consolidated`.

## Links to other PM3 assets

| Asset | Relationship |
|-------|--------------|
| `pm3-process` | Same `PRO_UID`, shared assets |
| `pm3-expressions` | Transition conditions |
| `pm3-dynaform` | Task steps |
| `pm3-trigger` | Derivation and step hooks |
| `pm3-consolidated` | Enterprise consolidated tasks |

## Validation checklist

1. Every non-terminal task has at least one outgoing transition
2. Decision tasks have mutually exclusive conditions or explicit default
3. No unreachable tasks in the map
4. Conditions reference variables set before the decision task

## Common mistakes

- Applying BPMN gateway patterns to classic map transitions
- Missing default path from decision task → case stuck
- Editing classic routing while team expects BPMN Designer
- Duplicate conditions that both evaluate true
