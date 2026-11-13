# Form Task and Manual Task schema

Namespace `pm`: `http://processmaker.com/BPMN/2.0/Schema.xsd`. New Form Tasks use `bpmn:task`; Manual Tasks use `bpmn:manualTask`. Preserve existing imported `bpmn:userTask` when editing. A screen reference does not turn a script/service task into a human task.

Sources and version boundaries: [asset-map.md](asset-map.md#source-map). Persistence/runtime: [architecture.md](architecture.md).

## Properties

| Inspector / concern | Field | Required / default | Contract |
|---------------------|-------|--------------------|----------|
| Node Identifier | BPMN `id` | Required, unique across the model | Preserve it on ordinary edits; flows, diagrams, and notifications refer to it |
| Name | BPMN `name` | Required in Designer | Action-oriented, not a default label; do not confuse it with the node ID |
| Screen For Input | `pm:screenRef` | Required in Designer for both types | Looked-up FORM/supported CONVERSATIONAL for Form; DISPLAY for Manual |
| Screen version metadata | `pm:screenVersion`, if already present | Preserve existing metadata | Not a reliable screen-pinning contract in this checkout; [version resolution](architecture.md#screen-resolution-and-versions) uses latest published screens |
| Assignment Type | `pm:assignment` | Defaults to `requester` | Six current types below; task tag, not assignment `manual`, selects offline work |
| Assigned Users | `pm:assignedUsers` | Needed when selected assignment uses it | Literal comma-separated IDs for `user_group`; FEEL data expression for `process_variable` |
| Assigned Groups | `pm:assignedGroups` | Needed when selected assignment uses it | Literal comma-separated IDs for `user_group`; FEEL data expression for `process_variable` |
| Rule Expression / Default Assignment | `pm:assignmentRules` | Required for a configured rule-based pool | JSON array; preserve order and use the inspector's rule/default shape |
| Lock User Assignment | `pm:assignmentLock` | Optional boolean | Reuse a prior assignee for this node/request when the lock branch applies; matching rule selection precedes it |
| Allow Reassignment | `pm:allowReassignment` | Optional boolean | Enables the permitted actor's reassignment path; does not guarantee alternate candidates or a working package integration |
| Assignment toggles / extensions | `pm:config` | Optional JSON string | Preserve unrelated keys; `selfService` and capability-specific manager flags below |
| Due In | `pm:dueIn` | Shared default 72 | Calendar hours, not business days; use positive whole hours |
| Use Request Variable | `pm:isDueInVariable` | Optional boolean | With a nonempty `dueInVariable`, use Mustache-rendered hours |
| Due variable | `pm:dueInVariable` | Needed for dynamic due | Example `{{sla_hours}}` when the actual request payload has top-level `sla_hours` |
| Task Destination | `pm:elementDestination` | Optional inspector JSON string | `{type, value}`; retain the current inspector's representation |
| Display Next Assigned Task / interstitial | `pm:allowInterstitial` | Optional boolean | Coordinated with Task Destination and any existing conditional redirect |
| Screen Interstitial | `pm:interstitialScreenRef` | Needed when `pm:allowInterstitial="true"` | DISPLAY screen ID. Set it whenever the flag is on. Runtime can use the system `interstitial` fallback, but a missing or wrong screen can leave the UI loading ([interstitial rules](../pm4-process/events.md#interstitial-display-next-assigned-task)) |
| Conditional Redirect | `pm:conditionalRedirect` | Optional | Expression plus destination. Preserve it when changing the destination |
| Documentation | `bpmn:documentation` | Optional | Describes the activity; not request data or an assignment rule |
| Loop Activity | BPMN `loopCharacteristics` / `ioSpecification` children | Optional | Standard BPMN structures, not invented `pm:config` loop keys |
| Notifications panel | Process `task_notifications` | Only when configured | Separate node-ID-keyed metadata, not `pm:*` XML flags |
| Process Manager | Process API `manager_id` | Needed for `process_manager` or intended fallback | Looked-up user ID array; not a user's `manager_id` |

Use JSON booleans inside `pm:config`; XML-escape JSON quotes as `&quot;`. Do not send `"false"` as a truthy JSON string or overwrite the whole config when changing one toggle.

## `pm:assignment` values

| Value | Inspector label | Required configuration / behavior |
|-------|-----------------|-----------------------------------|
| `requester` | Request Starter | Existing request starter; default for both new task types |
| `user_group` | Users / Groups | At least one looked-up user/group ID; one eligible user is selected cyclically unless Self Service is enabled |
| `previous_task_assignee` | Previous Task Assignee | Latest task-token assignee in this request; no named predecessor selector, so parallel workflows require care |
| `process_variable` | Process Variable | `assignedUsers` / `assignedGroups` contain expressions reading IDs from request data, not literal lookup lists |
| `rule_expression` | Rule Expression | Ordered FEEL rules and a deliberate default assignment; first matching rule selects the pool/user |
| `process_manager` | Process Manager | Managers from the request's process version; configure the process manager list, not the requester's boss |

The inspected selection path cycles sorted eligible IDs using prior assignments for this process/node; official UI text describing this as random is not the implementation contract. When candidate selection is empty, non-Self-Service paths can fall back to Process Managers or fail. Do not rely on fallback to hide an invalid intended pool.

Legacy inspector normalization: `self_service` → `user_group` with `config.selfService: true`; `user_by_id` → `process_variable`. Verify migrated expressions after loading/saving; the old Mustache user-by-ID path and current FEEL variable path are not interchangeable just because the attribute name stays the same.

### Process-variable expressions

For actual request data with top-level `reviewer_ids`, `pm:assignedUsers="reviewer_ids"` evaluates that value; it can resolve a single ID or an ID array. Group expressions follow the same rule. Do not substitute `"1,2"` or Mustache into a FEEL variable field.

This evaluator receives data through `DataManager`; rule evaluation receives the request data store. Confirm the actual keys/context before using a `data.*` prefix. General syntax guidance: [pm4-expressions](../pm4-expressions/SKILL.md), but these task-specific contracts take precedence over cross-context examples.

### Rule list and default assignment

The inspector stores entries with `type` (`user` / `group`), `assignee`, `expression`, and a display `assignmentName`. Its default has `default: true`, is last, and normally has `expression: null`:

```json
[
  { "type": "group", "assignee": "GROUP_ID", "assignmentName": "Finance", "expression": "amount > 1000" },
  { "type": "user", "assignee": "USER_ID", "assignmentName": "Reviewer", "expression": null, "default": true }
]
```

Replace ID placeholders and use the actual request key (here `amount`) before serializing this array into `pm:assignmentRules`. A blank expression evaluates true in `FormalExpression`, so it must not accidentally appear before conditional rules. `default: true` alone is not a bypass for a nonempty false condition. Ensure the actual runtime data/rules can be evaluated; when no candidate is produced, manager fallback is separate from the configured default.

For Self Service, the source aggregates matching user/group rules into a claimable pool and uses null-expression defaults when no rule matches. It is not the same single-user selection as an assigned task. Validate both modes on the target instance if asked to run them.

## `pm:config` JSON (camelCase keys)

| Key | Purpose | Capability boundary |
|-----|---------|---------------------|
| `selfService` | Leave task unassigned for eligible users/groups to claim | Core; only `user_group`, `process_variable`, or `rule_expression` |
| `escalateToManager` | Automatic user-manager assignment hook | Actual escalation requires `workflow.UserManager` and the `Assignment process` integration; not guaranteed by bare core |
| `assigneeManagerEscalation` | Permit the assignee's `#manager` escalation action | Core checks the flag; actual manager resolution uses the workflow-user-manager binding |
| `assignToManager` | Package-added assignment behavior | Not interpreted as an automatic core assignment switch; never assume it routes to the requester's boss |

Core's default inspector toggles are Self Service, Lock User Assignment, and Allow Reassignment. Manager options can be added by packages. Use `pm:assignment="process_manager"` plus the process `manager_id` list only when the business requirement really names the Process Manager.

Reassignment requires both applicable permissions/options and eligible alternative users. The inspected runtime `reassignTo()` also uses an assignment-process integration; do not claim `allowReassignment` alone proves the assignee can change on an untested instance.

## Due dates

- Both task types default to **72 calendar hours**, computed when the activity becomes active, including when waiting in Self Service.
- Dynamic due uses `isDueInVariable` plus `dueInVariable`; nonnumeric Mustache output falls back to 72. Check the value before activation rather than treating fallback as the intended SLA.
- Claim/reassignment does not recalculate `due_at` in the inspected path.
- Zero can produce no runtime timestamp when explicitly represented, but the Designer input deletes zero-valued `dueIn`, reverting to the default. Do not prescribe zero as a reliable UI way to disable deadlines.

Source details and target-instance caveats: [architecture.md](architecture.md#due-dates).

## Task notification map

Use top-level process `task_notifications`, keyed by the **BPMN node ID**, not runtime task ID. Recipients are `requester`, `assignee`, `participants`, and `manager`; event keys are `assigned`, `completed`, and `due`. Here `manager` means Process Managers, not each user's manager.

Core's notification-type list also accepts `default`, which can appear in stored metadata/new-node initialization. Preserve it when present; it is not one of the inspector's three ordinary event checkboxes and must not be repurposed as a guessed event or delivery guarantee.

Illustrative selective `PUT /api/1.0/processes/{id}` body:

```json
{
  "description": "EXISTING_DESCRIPTION",
  "bpmn": "WHOLE_INTENDED_BPMN_XML",
  "task_notifications": {
    "TASK_NODE_ID": { "assignee": { "completed": true } }
  }
}
```

Replace all placeholders with existing values. Only the selected node/recipient/event changes: true creates a row, false deletes one, omitted combinations are preserved. If only task XML is requested, omit `task_notifications` rather than guessing defaults or resending a fabricated map.

The persistence path writes `process_notification_settings` rows; Modeler initialization gives new nodes assignee Assigned/Due defaults. Reading saved settings is more reliable than assuming those defaults exist on an API-created process.

**Both draft and published writes can persist this map immediately.** The notification rows are not version-locked with BPMN. Review impact on existing requests and re-read saved settings. Do not use `notifyAfterRouting`, `notifyRequestCreator`, or a raw `notification_settings` write as substitutes.

Actual channel delivery, Self Service assignment timing, and the overdue-notification path require [runtime verification](verify.md#runtime-only-if-explicitly-requested), not only checking the map. Full flow: [architecture.md](architecture.md#task-notifications-metadata-not-bpmn-flags).

## Destinations, interstitials, and loops

`elementDestination` is a JSON string with `type` and `value`. Current Modeler options include `taskSource`, `taskList`, `processLaunchpad`, `homepageDashboard`, `customDashboard`, `externalURL`, and `displayNextAssignedTask`. Use the inspector's serialized object; custom values can be structured and are not interchangeable with an invented URL field.

Display Next Assigned Task is selected in the destination dropdown and coordinates `allowInterstitial` with a DISPLAY `interstitialScreenRef`. Preserve any existing conditional redirect and test alternative-destination behavior rather than changing only one flag. A next-task transition does not acknowledge offline work automatically.

`pm-migrate` leaves human tasks at `pm:allowInterstitial="false"` and omits `pm:elementDestination`. The default Task Destination stays `taskSource`. Turning the task interstitial on in the Modeler rewrites the destination to `displayNextAssignedTask` on load, so a converted process keeps the PM3 return-to-inbox behavior until someone chooses another destination.

Loop Mode is no loop, Standard Loop, Multi-instance Parallel, or Multi-instance Sequential. Preserve its `loopCharacteristics` (`loopMaximum` / `loopCondition`, or `isSequential` / data references / completion condition) and associated `ioSpecification` IDs. These are BPMN child elements. Use [pm4-process](../pm4-process/SKILL.md) for graph/boundary-event changes and [pm4-expressions](../pm4-expressions/SKILL.md) for conditions; use Modeler-generated structures instead of inventing JSON loop keys.

## Package and legacy fields

Preserve unrelated package configuration when editing core fields. Custom task email notifications live in `pm:config.email_notifications`; they are distinct from the process event-recipient map. The Form Task Actions by Email toggle uses `pm:isActionsByEmail="true"` and `pm:configEmail` in a separate core activation path (contract: [form-task-abe.md](../pm4-action-by-email/form-task-abe.md)). Neither is a prerequisite for a core Manual Task or a generic task-notification toggle; do not synthesize package configuration without checking the installed capability.

Standalone Send Email and the reply-waiting Actions by Email call activity have their own [skills](asset-map.md#packages-and-documentation). Their node contracts are not interchangeable with human-task notification settings.

## Minimal shared configuration

```xml
<bpmn:task id="node_2" name="Approve invoice"
  pm:screenRef="FORM_SCREEN_ID" pm:dueIn="48"
  pm:assignment="user_group" pm:assignedGroups="GROUP_ID"
  pm:config="{&quot;selfService&quot;:true}">
  <bpmn:incoming>flow_into_task</bpmn:incoming>
  <bpmn:outgoing>flow_out_of_task</bpmn:outgoing>
</bpmn:task>
```

This fragment assumes the PM namespace and existing flows in its containing process. Replace screen/group placeholders through [lookup.md](lookup.md). Full XML examples: [form-task-minimal.bpmn.xml](examples/form-task-minimal.bpmn.xml) uses `FORM_SCREEN_ID`; [manual-task-minimal.bpmn.xml](examples/manual-task-minimal.bpmn.xml) uses `DISPLAY_SCREEN_ID` and `bpmn:manualTask`. The fragment and fixtures are not ProcessMaker import packages. Verification stays static unless runtime work was explicitly requested.
