# Create/import only when explicitly requested

The [main skill](SKILL.md) configures human tasks on an existing process. Missing assets are a reason to ask, not authorization to create a demo, start a request, or complete a task.

## Delegate to the asset owner

| Requested work | Owner |
|----------------|-------|
| Create/edit/publish a FORM, DISPLAY, CONVERSATIONAL, or EMAIL screen | [pm4-screen](../pm4-screen/SKILL.md), including its [builder-loadable config](../pm4-screen/screen-config.md) |
| Create a process, insert/connect nodes, publish BPMN | [pm4-process/create-edit.md](../pm4-process/create-edit.md) |
| Import/export a process | [pm4-process](../pm4-process/SKILL.md); use an actual ProcessMaker export |
| Configure a Form/Manual Task after assets exist | [SKILL.md](SKILL.md), [lookup.md](lookup.md), [verify.md](verify.md) |

## If creation was requested

1. Resolve real categories and required assets through the owning skill. Never assume category/user/group/screen ID `1`.
2. Create the correctly typed screen first. The [FORM shell](../pm4-screen/examples/form-shell.json) opens in Builder but needs its intended controls and Submit action before task use; DISPLAY instructions do not require a screen Submit control. Preserve the screen draft/publish corrections in [pm4-screen/architecture.md](../pm4-screen/architecture.md#updating-config).
3. Assemble the requested process through `pm4-process`, then configure its human nodes here. The [Form fixture](examples/form-task-minimal.bpmn.xml) and [Manual fixture](examples/manual-task-minimal.bpmn.xml) have BPMN DI and separate `FORM_SCREEN_ID` / `DISPLAY_SCREEN_ID` placeholders; they are XML for a process API body, not importable export packages. Resolve typed screens and use their returned IDs ([example dependencies](SKILL.md#examples-and-screen-dependencies)).
4. After initial API process creation, resave the existing process when a Form or Manual node uses `user_group`, so assignment rows exist. A normal process response may omit BPMN; use `/processes/{id}/bpmn` or Designer rather than treating omission as an empty model.
5. Verify name/description and status contracts with the owning skill. There is no invented `PUBLISHED` process status: `ACTIVE`, `INACTIVE`, and `ARCHIVED` are the supported values. Designer BPMN changes and screen drafts have separate publishing flows.
6. Stop after the requested configuration. Running a request or acknowledging a task needs separate explicit authorization.

## If an import/export was requested

Do not hand-author `process_package` JSON, UUIDs, exporter names, or hashes. Use an actual ProcessMaker export and the owning skill's current import/version/options contract. Process and screen export packages are different artifacts. After import, re-resolve screen references, user/group mappings, package availability, and saved notification settings; do not assume instance IDs or notification behavior survived unchanged.
