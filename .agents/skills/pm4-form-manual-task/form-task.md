# Form Task: interactive work

Use a Form Task when a participant enters data, reviews information, or makes a decision through an interactive screen. An acknowledgment-only FORM can show information and a Submit button without collecting an invented input field.

## Node and screen

- New nodes use `bpmn:task` (Modeler `bpmn:Task`). The Modeler also recognizes imported `bpmn:UserTask`; preserve the original type during an existing-node edit.
- Resolve `pm:screenRef` to a real `FORM` screen, or a `CONVERSATIONAL` screen supported on the target instance. Never use DISPLAY or EMAIL for this node.
- Both title and type must match the intended screen. An API-created screen row does not prove its controls or package renderer work.
- Core's picker uses `type: "FORM,CONVERSATIONAL"` and `interactive: true`; this is not a universal FORM-only contract.

Lookups: [lookup.md](lookup.md). Creating/editing the screen: [pm4-screen](../pm4-screen/SKILL.md). Shared attributes: [bpmn-attrs.md](bpmn-attrs.md).

## Completion

| Screen | What completes the task |
|--------|------------------------|
| FORM | A Submit button with `event: "submit"`; `editor-control` is `FormSubmit`. A Regular Button with `event: "script"` does not submit |
| CONVERSATIONAL | `package-conversational-forms` renders the conversation and provides answer submission/completion; do not add a FORM Submit control |

FORM field names must match the user's data-entry requirements. Screen JSON, required control metadata, validation, drafts, and publishing belong to [screen-config.md](../pm4-screen/screen-config.md) and [controls.md](../pm4-screen/controls.md).

The renderer emits a submit event to the hosting task page; the authorized runtime API invokes the workflow manager. Assignment, task submission, and screen persistence are different operations. Configuring this node does not authorize submitting it.

## Configuration sequence

1. Identify the existing process and task ID. Do not select a node by its default label alone.
2. Look up the existing screen and verify type/package availability.
3. Set the requested name, `pm:screenRef`, assignment, and due/options. Leave unrelated graph, diagram, screen, and notification settings intact.
4. Save the process through the [shared workflow](SKILL.md#workflow); publish Designer BPMN changes when needed.
5. Run [static verification](verify.md#static-checks). Do not start a request or complete the task unless explicitly asked.

Full Form fixture: [form-task-minimal.bpmn.xml](examples/form-task-minimal.bpmn.xml), with the existing `node_1` / `node_2` / `node_3` identities and diagram intact. Replace `FORM_SCREEN_ID` with the looked-up FORM screen ID. Screen dependency: [pm4-screen/examples/form-minimal.json](../pm4-screen/examples/form-minimal.json); use its returned screen ID after explicitly requested creation, not `SCREEN_CATEGORY_ID`. It is process XML, not an importable ProcessMaker export package; process creation/import is handled by [pm4-process](../pm4-process/SKILL.md).

## Package-sensitive behavior

- CONVERSATIONAL requires its package wherever the screen is edited or rendered. A stored screen of that type is not proof that the package is installed.
- The inspected Web Entry package registers an inspector on Start Events and Form Tasks, not Manual Tasks. Anonymous Web Entry can disable ordinary authenticated-user assignment; preserve existing Web Entry configuration and confirm intent before changing it. Field contract: [pm4-web-entry](../pm4-web-entry/SKILL.md).
- A task's own `pm:screenRef` is not the Web Entry `web_entry.screen_id`. That field is for a Start Event. A Form Task with Web Entry uses its task screen. A parallel multi-instance loop disables Web Entry on the task.
- Nested JSON in `pm:config` must be XML-escaped.
- Custom task email notifications and embedded Actions by Email are separate capabilities, not prerequisites for a core Form Task. Do not replace a Form Task with an Actions by Email call activity just to send a notification.

Runtime, screen-version resolution, and capability differences: [architecture.md](architecture.md). Source references and package ownership: [asset-map.md](asset-map.md).
