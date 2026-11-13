# Manual Task: offline work with acknowledgment

Use a Manual Task for work ProcessMaker cannot observe directly, such as moving warehouse merchandise. The assignee reads instructions, performs the work offline, then acknowledges completion in ProcessMaker.

It is **not** an automatically skipped annotation, a script, or a screenless task.

## Node and screen

- Use `bpmn:manualTask` (Modeler `bpmn:ManualTask`, node ID `processmaker-modeler-manual-task`).
- Resolve `pm:screenRef` to an existing `DISPLAY` screen containing instructions/context. Core's inspector requires screen selection and offers DISPLAY screens.
- DISPLAY is read-only: do not add FORM inputs or a screen-level Submit control. Creating its content belongs to [pm4-screen](../pm4-screen/SKILL.md).
- Configure assignment normally. `pm:assignment="manual"` is not the way to choose a Manual Task: it selects no user before core's fallback handling. The BPMN tag chooses the node type.

Shared assignment, due dates, notifications, and destinations: [bpmn-attrs.md](bpmn-attrs.md). ID/type lookups: [lookup.md](lookup.md).

## Completion contract

Core maps `manualTask` to the regular task activity implementation. The runtime resource retains the original XML local name as `bpmn_tag_name`; the task renderer adds **Complete Task** outside the DISPLAY screen when that name is `manualTask`.

The request waits while the task is active. It advances only when an authorized completion is processed. The button confirms that the person says the offline work is done; it does not prove ProcessMaker measured the physical activity.

Self Service users should claim before completing the task. The renderer displays a claim-required overlay; do not assume that this UI rule is an extra backend prohibition beyond the task policy. Do not use an administrator token to claim that ordinary-assignee authorization was tested.

## Configuration sequence

1. Identify the existing process and Manual Task ID; use an action-oriented name such as `Move merchandise`.
2. Look up the intended DISPLAY screen. Stop if it is missing or incorrectly typed; do not silently replace it with a FORM screen.
3. Set only the requested assignment, due date, and options. Verify the eligible pool, including actual group members when runtime work is required.
4. Save the process through the [shared workflow](SKILL.md#workflow), preserving unrelated BPMN/DI and notification settings.
5. Run [static verification](verify.md#static-checks). Screen creation, process creation, and runtime execution require their own user request.

Illustrative node fragment, not a complete importable process:

```xml
<bpmn:manualTask id="manual_task" name="Move merchandise"
  pm:screenRef="DISPLAY_SCREEN_ID"
  pm:assignment="user_group" pm:assignedGroups="GROUP_ID" pm:dueIn="48">
  <bpmn:incoming>flow_into_manual</bpmn:incoming>
  <bpmn:outgoing>flow_out_of_manual</bpmn:outgoing>
</bpmn:manualTask>
```

The PM namespace is `http://processmaker.com/BPMN/2.0/Schema.xsd`. Replace the screen/group placeholders through lookup; the incoming/outgoing flows must exist in the containing process. General graph construction belongs to [pm4-process](../pm4-process/SKILL.md).

Full process XML: [manual-task-minimal.bpmn.xml](examples/manual-task-minimal.bpmn.xml) has Start → `manual_task` → End with complete BPMN DI, Request Starter assignment, and a 48-hour due time. Replace `DISPLAY_SCREEN_ID` with a looked-up DISPLAY screen ID; no user/group ID is hardcoded. If group assignment was requested, use a looked-up group and the appropriate assignment attributes, not the fragment's literal `GROUP_ID` placeholder.

Screen dependency: [pm4-screen/examples/display-minimal.json](../pm4-screen/examples/display-minimal.json). It provides read-only control metadata with sample invoice content; adapt that content to requested offline-work instructions through `pm4-screen` when screen editing was authorized. Replace its category placeholder separately, then wire the returned screen ID. Never add a FORM Submit control to this screen to make the fixture complete. [Static fixture checks](verify.md#example-fixtures) do not prove the runtime acknowledgment action renders.

## Not for data entry

A Manual Task shows a DISPLAY screen and waits for acknowledgement. It merges little or no data back into the request. When users must edit and submit fields, use a Form Task. The Modeler filters the screen picker to DISPLAY screens, so a FORM screen here is wrong.

## Missing screens and package boundaries

- Runtime can fall back to `default-display-screen` when the referenced screen is absent. This is troubleshooting behavior, not acceptable deliberate configuration.
- Core FORM/DISPLAY support needs no Conversational package. Do not require Web Entry, custom email, or Actions by Email to acknowledge a Manual Task.
- The Manual Task user documentation repeats some Web Entry and manager-toggle text. The inspected package registers Web Entry only on Start/Form nodes; verify any claimed additional capability on the target instance.
- Manager escalation and runtime reassignment depend on the installed workflow-user-manager integration as well as configuration/permission. Use the process-manager assignment when the requirement is explicitly the Process Manager, not an assignee's boss.

Engine mapping, fallback, and completion source: [architecture.md](architecture.md). Permissions and file ownership: [asset-map.md](asset-map.md). Runtime tests remain optional and separately authorized: [verify.md](verify.md#runtime-only-if-explicitly-requested).
