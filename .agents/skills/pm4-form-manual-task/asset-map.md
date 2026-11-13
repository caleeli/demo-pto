# Human-task assets, APIs, permissions, and sources

Human tasks belong to process BPMN. References below are source-checked; no live-instance authorization or delivery test is implied. Endpoint paths are under `/api/1.0` unless stated otherwise.

## Related assets

| Asset | Link / role | Owner |
|-------|-------------|-------|
| Existing process and task node | Process ID plus unique BPMN node ID; full BPMN/DI on edit | This skill for task configuration; [pm4-process](../pm4-process/SKILL.md) for graph/publish/import/export |
| Input/instruction screen | Numeric `pm:screenRef`; FORM/supported CONVERSATIONAL for Form, DISPLAY for Manual | [pm4-screen](../pm4-screen/SKILL.md) |
| Users and groups | Literal IDs for `user_group`; expressions/data for `process_variable`; group must have eligible members | [lookup.md](lookup.md), [bpmn-attrs.md](bpmn-attrs.md) |
| Process Managers | Process API `manager_id` array; stored in process properties and available on process versions | [lookup.md](lookup.md#process-managers) |
| Task notifications | Process `task_notifications` map keyed by node ID, persisted in notification-setting rows | [bpmn-attrs.md](bpmn-attrs.md#task-notification-map) |
| Interstitial screen | DISPLAY `pm:interstitialScreenRef` with inspector-generated destination/allow flag | [architecture.md](architecture.md#loops-destinations-and-interstitials) |
| Runtime request/token | Request ID, runtime task ID, `element_id`, `bpmn_tag_name`; never substitute a runtime ID for a BPMN node ID | [verify.md](verify.md#runtime-only-if-explicitly-requested) |
| Child process | Form/Manual configuration happens on the child process itself | [pm4-sub-process](../pm4-sub-process/SKILL.md) for the parent call contract |

## Design-time API

| Method + path | Purpose / constraints |
|---------------|-----------------------|
| `GET processes?filter=<name>&per_page=15` | Resolve the existing process; confirm exact name and paginate ambiguous lists |
| `GET processes/{id}` | Metadata, manager/notification settings, warnings; BPMN can be omitted |
| `GET processes/{id}/bpmn` | Raw XML; identify the node and preserve unrelated graph/DI |
| `PUT processes/{id}` | Whole intended BPMN plus required `description`; publishes through process saving. Include manager/notification updates only when requested |
| `PUT processes/{id}/draft` | Designer draft save; requires process validation fields. Task notifications are persisted separately even on this path |
| `GET screens?filter=<title>&per_page=15`, `GET screens/{id}` | Look up screen ID and verify actual type; package availability is a separate check |
| `GET users?filter=<name>&per_page=15`, `GET groups?filter=<name>&per_page=15` | Resolve assignment targets; do not create users/groups merely to configure a node |

There is no separate design-time `POST form_tasks` or `POST manual_tasks`. Process/screen creation and import/export are delegated through [create-and-import.md](create-and-import.md); they are not necessary to configure an existing node.

For screen permissions, category/project access, drafts, and export APIs, use [pm4-screen/asset-map.md](../pm4-screen/asset-map.md). For query contracts and 422/PMQL issues, use [pm-api-contracts](../pm-api-contracts/SKILL.md).

## Runtime API — only when explicitly requested

| Method + path | Purpose |
|---------------|---------|
| `GET tasks/{runtimeTaskId}?include=screen,definition,bpmnTagName,processRequest` | Read task/screen/original tag and request context |
| `GET requests/{requestId}/tokens` | Inspect tokens and correlate `element_id` with the configured node |
| `GET tasks?...` | Assigned/Self Service queries per [pm-api-contracts](../pm-api-contracts/SKILL.md); scope to the test request/actor |
| `PUT tasks/{runtimeTaskId}` with `user_id` | Claim/reassign through the task API when authorized; not a BPMN edit |
| `PUT tasks/{runtimeTaskId}` with `status: COMPLETED` and appropriate `data` | Shared Form/Manual completion flow; existing closed tasks are rejected |
| `GET requests/{requestId}?include=data` | Inspect request state/data after workflow processing |

A configuration-only request does not authorize any runtime PUT, claim, completion, request start, or unrelated asset creation.

## Permissions

- Design-time process viewing/editing uses `view-processes` / `edit-processes`; administrators and locked imported PM Blocks have policy-specific handling. Editing a process is not permission to edit every screen it references.
- Screens and assignment lookups require the instance's applicable permissions and category/project access. Stop on 403; do not fall back to another actor or create a replacement asset.
- Runtime task view/update policies check the assignee, Process Managers, eligible Self Service users, and administrator/anonymous handling. Reassignment additionally checks process-manager/reassignment permissions or `allowReassignment` for an authorized actor.
- A claim-required UI overlay is not a separate guarantee that the API enforces a prior claim. This skill still follows claim-first behavior and tests with the intended actor, not only an admin.

## Source map

Paths are relative to each repository. The reviewed snapshots are core `507f3a185`, Modeler `2ee2656a`, Screen Builder `8bee13bc`, conversational-forms `2b1acfb`, and webentry `0403aa5` (2026-10-08). Local package source is listed by path rather than assuming a public repository URL.

### Core

| Source | Evidence |
|--------|----------|
| [WorkflowServiceProvider.php](https://github.com/ProcessMaker/processmaker/blob/507f3a185/ProcessMaker/Providers/WorkflowServiceProvider.php#L126-L132) | Manual Task maps to the ordinary task activity implementation |
| [modeler/initialLoad.js](https://github.com/ProcessMaker/processmaker/blob/507f3a185/resources/js/processes/modeler/initialLoad.js#L157-L378), [validateScreenRef.js](https://github.com/ProcessMaker/processmaker/blob/507f3a185/resources/js/processes/modeler/validateScreenRef.js) | Typed required screen pickers and shared assignment/due/notification/interstitial inspectors |
| [TaskAssignment.vue](https://github.com/ProcessMaker/processmaker/blob/507f3a185/resources/js/processes/modeler/components/inspector/TaskAssignment.vue), [AssignExpression.vue](https://github.com/ProcessMaker/processmaker/blob/507f3a185/resources/js/processes/modeler/components/inspector/AssignExpression.vue) | Assignment types/options, normalization, and ordered rule/default shape |
| [modeler/index.blade.php](https://github.com/ProcessMaker/processmaker/blob/507f3a185/resources/views/processes/modeler/index.blade.php#L86-L96) | Six assignment types; core-default toggles exclude manager-escalation/assign-to-manager options |
| [ProcessTaskAssignmentsTrait.php](https://github.com/ProcessMaker/processmaker/blob/507f3a185/ProcessMaker/Traits/ProcessTaskAssignmentsTrait.php) | Initial-create guard and assignment-row rebuild for both task tags |
| [ProcessExecutionRawRepository.php](https://github.com/ProcessMaker/processmaker/blob/507f3a185/ProcessMaker/Repositories/ProcessExecutionRawRepository.php), [Process.php](https://github.com/ProcessMaker/processmaker/blob/507f3a185/ProcessMaker/Models/Process.php#L597-L1029) | Cyclical user selection, FEEL data/rules, manager fallback, lock priority, and integration hooks |
| [ProcessManagerAssigned.php](https://github.com/ProcessMaker/processmaker/blob/507f3a185/ProcessMaker/AssignmentRules/ProcessManagerAssigned.php), [PreviousTaskAssignee.php](https://github.com/ProcessMaker/processmaker/blob/507f3a185/ProcessMaker/AssignmentRules/PreviousTaskAssignee.php) | Versioned process-manager list and latest-task-token behavior |
| [FormalExpression.php](https://github.com/ProcessMaker/processmaker/blob/507f3a185/ProcessMaker/Models/FormalExpression.php#L391-L412) | Assignment expression context; empty expression evaluates true |
| [TokenRepository.php](https://github.com/ProcessMaker/processmaker/blob/507f3a185/ProcessMaker/Repositories/TokenRepository.php#L94-L262) | Runtime activation, evaluated Self Service pool, shared 72-hour due calculation |
| [ProcessRequestToken.php](https://github.com/ProcessMaker/processmaker/blob/507f3a185/ProcessMaker/Models/ProcessRequestToken.php) | Screen fallback, screen-version resolution, interstitial, claiming, and manager escalation hook |
| [HasVersioning.php](https://github.com/ProcessMaker/processmaker/blob/507f3a185/ProcessMaker/Traits/HasVersioning.php#L231-L237), [TaskResourceIncludes.php](https://github.com/ProcessMaker/processmaker/blob/507f3a185/ProcessMaker/Traits/TaskResourceIncludes.php#L139-L142) | Latest published screen behavior and original `bpmn_tag_name` |
| [TaskNotifications.vue](https://github.com/ProcessMaker/processmaker/blob/507f3a185/resources/js/processes/modeler/components/inspector/TaskNotifications.vue), [modelerInit.js](https://github.com/ProcessMaker/processmaker/blob/507f3a185/resources/js/processes/modeler/modelerInit.js), [ModelerApp.vue](https://github.com/ProcessMaker/processmaker/blob/507f3a185/resources/js/processes/modeler/components/ModelerApp.vue) | Node-keyed notification map, new-node defaults, autosave/publish payload |
| [Api/ProcessController.php](https://github.com/ProcessMaker/processmaker/blob/507f3a185/ProcessMaker/Http/Controllers/Api/ProcessController.php), [ProcessTrait.php](https://github.com/ProcessMaker/processmaker/blob/507f3a185/ProcessMaker/Traits/ProcessTrait.php#L84-L116) | Process/draft writes, selective notification rows, `manager_id` array handling |
| [Http/Resources/Process.php](https://github.com/ProcessMaker/processmaker/blob/507f3a185/ProcessMaker/Http/Resources/Process.php#L13-L30) | Process API response appends notification maps and exposes `manager_id` |
| [BpmnSubscriber.php](https://github.com/ProcessMaker/processmaker/blob/507f3a185/ProcessMaker/Listeners/BpmnSubscriber.php#L117-L170), [Api/NotificationController.php](https://github.com/ProcessMaker/processmaker/blob/507f3a185/ProcessMaker/Http/Controllers/Api/NotificationController.php#L435-L445) | Assigned/completed recipients and current-user overdue-notification path |
| [Api/TaskController.php](https://github.com/ProcessMaker/processmaker/blob/507f3a185/ProcessMaker/Http/Controllers/Api/TaskController.php#L363-L413), [ProcessRequestTokenPolicy.php](https://github.com/ProcessMaker/processmaker/blob/507f3a185/ProcessMaker/Policies/ProcessRequestTokenPolicy.php), [ProcessPolicy.php](https://github.com/ProcessMaker/processmaker/blob/507f3a185/ProcessMaker/Policies/ProcessPolicy.php) | Authorized shared completion/reassignment and process editing |

### Modeler and Screen Builder

| Source | Evidence |
|--------|----------|
| Modeler [task/index.js](https://github.com/ProcessMaker/modeler/blob/2ee2656a/src/components/nodes/task/index.js), [manualTask/index.js](https://github.com/ProcessMaker/modeler/blob/2ee2656a/src/components/nodes/manualTask/index.js) | New node types, requester default, imported UserTask support, task inspectors |
| Modeler [addLoopCharacteristics.js](https://github.com/ProcessMaker/modeler/blob/2ee2656a/src/setup/addLoopCharacteristics.js), [LoopCharacteristics.vue](https://github.com/ProcessMaker/modeler/blob/2ee2656a/src/components/inspectors/LoopCharacteristics.vue) | Standard loop/multi-instance structures and data references |
| Modeler [taskElementDestination.js](https://github.com/ProcessMaker/modeler/blob/2ee2656a/src/components/inspectors/taskElementDestination.js), [ElementDestination.vue](https://github.com/ProcessMaker/modeler/blob/2ee2656a/src/components/inspectors/ElementDestination.vue) | Destination values/serialization and next-task/interstitial coordination |
| Core [TaskInterstitial.vue](https://github.com/ProcessMaker/processmaker/blob/507f3a185/resources/js/processes/modeler/components/inspector/TaskInterstitial.vue) | DISPLAY interstitial picker and destination-driven allow flag |
| Screen Builder [task.vue](https://github.com/ProcessMaker/screen-builder/blob/8bee13bc/src/components/task.vue), [form-button.vue](https://github.com/ProcessMaker/screen-builder/blob/8bee13bc/src/components/renderer/form-button.vue) | Screen renderer selection, Manual Complete Task, claim overlay, submit event, and destinations |

### Packages and documentation

- `package-conversational-forms/src/Seeds/ConversationalFormsSeeder.php` registers interactive CONVERSATIONAL; `resources/js/components/ConversationalForm.vue` supplies its renderer.
- `package-webentry/resources/assets/js/register-inspector.js` registers only on Start Events and Form Tasks in the reviewed package.
- Workflow-user-manager integrations can supply manager escalation/assignment processes. Those capabilities were traced at their core hooks; an actual installed integration was not assumed or tested.
- Custom task email and embedded Actions by Email differ from shared task event notifications. [pm4-send-email](../pm4-send-email/SKILL.md) owns the standalone Send Email node; [pm4-action-by-email](../pm4-action-by-email/SKILL.md) owns its separate reply-waiting call activity.
- Official UI references: [Form Task](https://docs.processmaker.com/docs/form-task), [Manual Task](https://docs.processmaker.com/docs/manual-task). Use the [docs-versus-code notes](SKILL.md#docs-versus-code) when labels/claims differ from these snapshots.
