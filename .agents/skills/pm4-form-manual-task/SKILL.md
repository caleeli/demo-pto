---
name: pm4-form-manual-task
description: Configure ProcessMaker 4 Form Tasks (User Tasks) and Manual Tasks — typed screen references, assignment rules, due dates, task notifications, and destination/interstitial settings on an existing process. Use when configuring a human task's screenRef, eligible users or groups, assignment, due date, or task options. Do not use to create or edit screen controls (pm4-screen), build or change the process graph (pm4-process), configure Script Tasks or automated service tasks, or create demo processes. Do not start requests, claim tasks, or complete Inbox tasks unless the user asked.
---

# Configure Form Tasks and Manual Tasks

ProcessMaker has two human-task types. Both use `pm:*` attributes for their screen reference and shared assignment/due configuration:

| Task | Canonical new BPMN element | Screen | Completion |
|------|----------------------------|--------|------------|
| Form Task (User Task terminology) | `bpmn:task` | `FORM`, or `CONVERSATIONAL` when its package is available | FORM screen Submit action; Conversational completion is provided by its package |
| Manual Task | `bpmn:manualTask` | `DISPLAY` instructions/context for offline work | Assignee acknowledges with the platform **Complete Task** button, outside the screen |

A Manual Task is not screenless or automatically completed. Do not add inputs or a FORM Submit control to its DISPLAY screen. The task type is not `pm:assignment="manual"`; configure an intended assignee or eligible Self Service pool.

Set **name**, a correctly typed **screen**, and **assignment**. Missing/incorrect configuration can show a placeholder or leave nobody able to work the task. The Modeler recognizes imported `bpmn:UserTask`; preserve existing imported types instead of silently converting them. New Form Tasks use `bpmn:task`.

**After PM3 migration:** accelerated imports often leave `pm:assignment` as `requester` or `user` without groups or rules. Reconfigure every human task on the target instance; do not assume PM3 assignment survived import. See [pm3-pm4-migration](../pm3-pm4-migration/SKILL.md).

This skill is **generic ProcessMaker 4**. It configures a task on an **existing** process. It does not assume package-plg. Do not create a demo process, start a request, or complete a task unless the user asked for that.

References: [form-task.md](form-task.md), [manual-task.md](manual-task.md), [bpmn-attrs.md](bpmn-attrs.md) (shared schema/options), [lookup.md](lookup.md), [architecture.md](architecture.md) (storage/runtime), [asset-map.md](asset-map.md) (APIs, permissions, source evidence), [verify.md](verify.md).

Official UI: [Form Task](https://docs.processmaker.com/docs/form-task), [Manual Task](https://docs.processmaker.com/docs/manual-task). Screen construction belongs to [pm4-screen](../pm4-screen/SKILL.md). Creating/importing a process is separate, explicitly requested work: [create-and-import.md](create-and-import.md).

## When to use

- Configure an existing Form/User Task or Manual Task: name, typed screen reference, assignment, due date, or shared task options.
- Select interactive input/decision work versus offline work acknowledged by the assignee.
- Diagnose configuration such as wrong screen type, empty assignment pool, unexpected Process Manager fallback, missing notification settings, or incorrect due/destination options.
- Configure a human task inside a child process; the parent Call Activity contract still belongs to [pm4-sub-process](../pm4-sub-process/SKILL.md).

## Not this skill

| User wants | Use instead |
|------------|-------------|
| PHP/JS script task (no Inbox) | [pm4-script](../pm4-script/SKILL.md) |
| Deep Web Entry (`web_entry` in `pm:config`): [pm4-web-entry](../pm4-web-entry/SKILL.md) | Capability-specific config; anonymous Form Task Web Entry **disables** assignment. Do not assume Manual Tasks support it |
| Create or edit a screen (FORM/DISPLAY/CONVERSATIONAL/EMAIL) | [pm4-screen](../pm4-screen/SKILL.md) |
| Insert/connect a task, scaffold a process, publish/import/export | [pm4-process](../pm4-process/SKILL.md) |
| Vue 2 PM core UI | [vue2-pm4-patterns](../vue2-pm4-patterns/SKILL.md) |
| 422 / PMQL / tasks list query | [pm-api-contracts](../pm-api-contracts/SKILL.md) |
| Local instance down | [setup-processmaker](../setup-processmaker/SKILL.md) |

Do not invent a `process_package` JSON.

## Before changing anything

Ask only if missing:

1. **Process** — existing name or id
2. **Task type and name** — Form for interactive input/decisions, Manual for offline work; action-oriented (`Approve invoice`, `Move merchandise`)
3. **Screen** — existing **title** (preferred) or numeric id. Form: `FORM` or supported `CONVERSATIONAL`; Manual: `DISPLAY`
4. **Who works it** — Request Starter vs users/groups vs previous assignee vs process variable vs rules vs Process Manager
5. **Due In / other options** — hours, notifications, destination, or loops only when requested; both task types share the source-verified 72-hour default

Then [lookup.md](lookup.md): resolve screen / user / group ids. **Never invent ids.**

The task lives in the process BPMN. There is no separate design-time create-task endpoint: edit the XML and save the process ([pm4-process/create-edit.md](../pm4-process/create-edit.md#editing-an-existing-process)). Runtime task tokens are not the design-time nodes.

## Asset schema

| Concern | Field / location | Contract |
|---------|------------------|----------|
| Task identity | BPMN tag, `id`, `name` | Intended Form/Manual type; node ID unique across the model; descriptive name |
| Input/instructions | `pm:screenRef` | Required Designer selection; real correctly typed screen ID |
| Assignment | `pm:assignment`, users/groups/rules | Default `requester`; eligible targets/data required for selected mode |
| Due time | `pm:dueIn`, `pm:isDueInVariable`, `pm:dueInVariable` | Calendar hours; shared default 72; Mustache for dynamic due |
| Assignment options | `pm:assignmentLock`, `pm:allowReassignment`, `pm:config` | Preserve unrelated keys; manager options are capability-sensitive |
| Task notifications | Process `task_notifications` | Node-ID-keyed recipient/event map, not BPMN notification flags |
| Process Managers | Process `manager_id` | Looked-up user ID array, not the requester's boss |
| Destination/interstitial | `pm:elementDestination`, `pm:allowInterstitial`, `pm:interstitialScreenRef` | Inspector-generated JSON/flags; DISPLAY interstitial |
| Documentation/loops | BPMN child structures | Preserve documentation and loop/I/O references; do not invent config keys |

Full required/optional mapping and serialization: [bpmn-attrs.md](bpmn-attrs.md). FORM needs its Submit action, not an invented input; Manual DISPLAY uses the platform Complete Task action; CONVERSATIONAL needs its package ([screen completion](../pm4-screen/screen-config.md#completion-by-screen-type)). Runtime fallback does not make a missing screen valid design configuration.

If the repo versions BPMN with `${screen:title}`, keep that in git and publish it with `package-plg`. Still resolve a real screen with the correct type on the instance. Through the process API or Designer, `pm:screenRef` must be the numeric screen id: the API does not resolve named refs, and an unresolved ref leaves the task unassigned. See [portable references](../pm4-process/bpmn-document.md#portable-references-package-plg-deploy-only).

## Assignment Types

| Inspector | `pm:assignment` | When to use |
|-----------|-----------------|-------------|
| Request Starter | `requester` | First human step after start (default) |
| Users / Groups | `user_group` | Eligible users/group members; source cycles sorted IDs unless Self Service |
| Previous Task Assignee | `previous_task_assignee` | Latest task-token assignee in this request; parallel-work caveat |
| Process Variable | `process_variable` | Request data holds IDs; attributes are FEEL data expressions, not literal ID lists |
| Rule Expression | `rule_expression` | FEEL rules; set a default assignee |
| Process Manager | `process_manager` | Managers from the request's process version. Not each user's boss |

Self Service is a **toggle** (`config.selfService`), not its own type. Only with `user_group`, `process_variable`, or `rule_expression`. Empty users/groups → nobody can claim.

Legacy on load: `self_service` → `user_group` + `config.selfService`; `user_by_id` → `process_variable`.

## Assignment options / due

- **Lock User Assignment** — reuses a prior node/request assignee when that branch applies; matching rule selection precedes it.
- **Allow Reassignment** — needs authorized actors and actual alternative candidates; runtime integration must also work.
- **User-manager escalation** — core exposes integration hooks, not guaranteed bare-core routing to a user's boss. Verify `workflow.UserManager` / assignment-process capability. Process Manager assignment is a different business role ([manager options](bpmn-attrs.md#pmconfig-json-camelcase-keys)).
- After `POST /processes`, `user_group` needs a resave or the assignment row is missing — [pm4-process/create-edit.md](../pm4-process/create-edit.md) step 3b.
- Due values are calendar hours, default **72** for both types. Dynamic nonnumeric output falls back to 72; Designer zero input does not reliably disable due dates ([due dates](bpmn-attrs.md#due-dates)).
- Anonymous Form Task Web Entry disables assignment; this is not a universal Manual Task capability.

Empty candidate selection can fall back to Process Managers or fail. Do not rely on fallback to hide a broken intended pool. Self Service requires a claimable pool and claim-first work when runtime execution is requested.

## Notifications, loops, and destinations

- Change task notification selections through process `task_notifications`; true creates a setting, false deletes it, omitted combinations stay unchanged. Omit this field for an XML-only change.
- Draft saves can persist notification settings immediately, even while BPMN stays unpublished. Notification recipients are process-scoped, not a snapshot locked to each request's BPMN version.
- Task destination coordinates interstitial/next-task flags. Current Modeler selects Display Next Assigned Task in its destination dropdown; preserve serialized settings and any existing conditional redirect.
- Loop/multi-instance configuration uses BPMN child structures and I/O references. Link graph/boundary-event work to `pm4-process`; expression authoring to `pm4-expressions`.

Detailed contracts: [bpmn-attrs.md](bpmn-attrs.md). Verified persistence path and remaining runtime checks: [architecture.md](architecture.md).

## Workflow

```
- [ ] 1. Confirm Form versus Manual; exclude Script/Service/CallActivity configuration
- [ ] 2. Lookup screen (and users/groups if needed). Stop if missing, wrong type, or required package is unavailable
- [ ] 3. Change only requested node fields; preserve its ID, unrelated graph/DI, config, loops, and notification metadata
- [ ] 4. Populate assignedUsers / assignedGroups / rules / Process Manager when required
- [ ] 5. Self Service only if a queue is intended
- [ ] 6. Inspect the intended draft/published version before writing. Publish Designer BPMN changes when required. Over the API: read the XML with
        GET /api/1.0/processes/{id}/bpmn, change the task, then PUT /api/1.0/processes/{id} with
        description and the whole bpmn (422 without description)
- [ ] 6b. If the process was just POSTed and this task is `user_group`, resave so assignment rows exist
- [ ] 6c. Send task_notifications only if requested; draft notification writes are not isolated from live settings
- [ ] 7. Re-read intended BPMN/settings, inspect warnings, and run verify.md. Do not start, claim, or complete a request/task unless asked
```

## Done when (static)

```
- [ ] Element matches the intended Form or Manual task type; imported userTask is not silently converted
- [ ] Name is not the default Form Task / Manual Task
- [ ] pm:screenRef is a real correctly typed screen id (looked up, not guessed)
- [ ] FORM has its Submit action; Manual DISPLAY has no inputs/Submit; CONVERSATIONAL has its package
- [ ] pm:assignment matches intent; users/groups/variables filled if required
- [ ] Process Manager set if assignment is process_manager (process `manager_id`, not User.manager_id)
- [ ] user_group: assignment row exists after API create; group has members if it must run
```

Use [verify.md](verify.md) for complete static and explicitly optional runtime checklists. The complete examples below illustrate the contracts; they are not authorization to create or run a demo.

## Examples and screen dependencies

| Process XML | Screen dependency | Placeholder |
|-------------|-------------------|-------------|
| [form-task-minimal.bpmn.xml](examples/form-task-minimal.bpmn.xml) — Start → Approve invoice → End | [FORM screen example](../pm4-screen/examples/form-minimal.json) | `FORM_SCREEN_ID` |
| [manual-task-minimal.bpmn.xml](examples/manual-task-minimal.bpmn.xml) — Start → Move merchandise → End | [DISPLAY screen example](../pm4-screen/examples/display-minimal.json) | `DISPLAY_SCREEN_ID` |

Both XML files include an executable process, flows, shapes, bounds, and edge waypoints. They use Request Starter assignment and a 48-hour due time without hardcoded instance user/group IDs. Preserve the Form example's node IDs when using its existing execution regression.

Resolve an existing screen with the intended title/type through [lookup.md](lookup.md), then replace the appropriate screen placeholder. If screen creation was requested, follow `pm4-screen`, replace its JSON `SCREEN_CATEGORY_ID` with a looked-up category, and use the **returned screen ID**, not the category ID, in BPMN. Reuse the screen fixtures' control metadata; adapt their sample invoice content and bound variables only to requested requirements. The DISPLAY example is a read-only shape, not already-written warehouse instructions.

FORM uses its screen Submit action; Manual DISPLAY has no input/Submit control and relies on the host Complete Task action. Available CONVERSATIONAL screens can be wired into a Form Task when their package is installed; the minimal Form fixture itself assumes FORM.

These are XML fixtures for Modeler or a process API `bpmn` string, not ProcessMaker JSON import packages. Do not create screens/processes or start/complete requests just to substitute placeholders. [Fixture verification](verify.md#example-fixtures) is static; Builder/Modeler/runtime usability needs separately authorized checks.

## Docs versus code

The [source map](asset-map.md#source-map) pins the inspected checkouts. Live Designer/runtime checks remain pending; do not claim source tracing or stub edits prove execution.

- Manual Tasks have DISPLAY instructions and a platform acknowledgment action; they are neither screenless nor auto-skipped.
- Form Task screen selection supports package-backed CONVERSATIONAL, not universally FORM only.
- Users/groups are selected cyclically in the inspected source, despite documentation describing random selection.
- Process Managers are distinct from each user's manager; manager toggles are not all exposed or implemented by bare core.
- Notification settings use a separate process map/rows, and draft writes can change them immediately. A Due flag does not prove independently scheduled email delivery.
- Both task types share the 72-hour due default; zero-input and nonnumeric-variable behavior require care.
- Screen version locking is skipped in the inspected `versionFor()` path; publishing a shared screen can affect in-progress tasks.
- Display Next Assigned Task moved into the destination dropdown in the current inspector. Preserve version-specific settings instead of copying an old checkbox layout.

## Common mistakes

- Guessing IDs, selecting the first similarly named screen, or confusing runtime task IDs with BPMN node IDs.
- Using a FORM Submit control for Manual DISPLAY, adding an invented input to an acknowledgment-only FORM, or assuming a Conversational package is available everywhere.
- Treating `assignment="manual"` as a Manual Task, an empty group as intentional assignment, or a Process Manager as the requester's boss.
- Overwriting all `pm:config` or notification selections when asked to change one setting.
- Assuming drafts isolate notification changes, a metadata-only PUT cannot publish pending BPMN, or a successful API save proves a usable screen/task.
- Starting requests or completing tasks to prove a configuration-only edit.
