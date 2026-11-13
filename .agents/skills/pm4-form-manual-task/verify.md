# Verify Form Task and Manual Task configuration

Static checks are the default. Browser tooling checks require access to the intended assets; runtime checks require an explicit user request. A successful API edit or stub assertion does not prove rendering, assignment, authorization, notification delivery, or workflow progression.

## Static checks

### Node and screen

- Read the intended process metadata and `/processes/{id}/bpmn`; identify the exact node ID, not only its label.
- XML is well-formed; node ID is unique across the model and matches existing flows/diagram references. Preserve imported `userTask` and unrelated graph/DI/settings.
- Intended type is Form (`bpmn:task`) or Manual (`bpmn:manualTask`), not Script/Service/CallActivity. Name is descriptive, not the default label.
- `pm:screenRef` resolves to the exact screen title/ID/type through [lookup.md](lookup.md): FORM/supported CONVERSATIONAL for Form, DISPLAY for Manual.
- FORM has a Submit action; inputs match requested data entry, if any. Manual DISPLAY has no inputs/Submit. CONVERSATIONAL has its required package and package completion ([screen requirements](../pm4-screen/screen-config.md#completion-by-screen-type)).
- Missing-screen fallback is not used as deliberate configuration; successful API persistence does not prove Designer required-screen checks pass.

### Assignment and options

- `pm:assignment` matches intent; `user_group` has looked-up IDs and an eligible pool; process-variable expressions refer to actual preceding request data.
- Ordered rule entries/default assignees are valid; no unintended blank condition precedes conditional rules. Default/fallback and Self Service rule pooling are distinct paths.
- Self Service is a JSON boolean in `pm:config`, enabled only with `user_group`, `process_variable`, or `rule_expression`; its pool is actually claimable when runtime work is expected.
- Process-manager assignment has a valid process `manager_id` list, not a user's manager. Verify the relevant process-version managers when inspecting an existing request.
- For `user_group` after API process creation, resave the existing process and verify assignment persistence. Both task tags are covered by the saving trait; an XML ID list alone is not proof of populated rows.
- Lock/reassignment flags preserve intended behavior; alternative candidates and any required runtime integration are available. Do not assume a checkbox proves working manager escalation/reassignment.
- Due hours are intentional. Dynamic due references the actual payload and numeric output; note the shared 72-hour fallback and Designer zero behavior.
- Preserve documentation, loop characteristics/I/O references, destination JSON, interstitial DISPLAY screen, and conditional redirects unless changes were requested.

### Notifications and save behavior

- Re-read process `task_notifications`; map keys are BPMN node IDs, recipients/events match intent, and untouched combinations remain unchanged.
- Do not synthesize notification XML flags, raw setting rows, or unrelated email configuration. Omit the notification map for XML-only changes.
- Review draft-write impact: notification rows can persist immediately despite unpublished BPMN. They are not safely isolated per draft/request version.
- Confirm the intended published/draft BPMN before a direct PUT; send required `description` and the whole intended XML. Avoid unintentionally publishing another draft.
- Read back XML/settings/warnings after saving. Designer BPMN changes require the applicable publish action; screen drafts are published separately.

## Example fixtures

- [form-task-minimal.bpmn.xml](examples/form-task-minimal.bpmn.xml): replace `FORM_SCREEN_ID` with the real ID of the intended FORM screen. Its `node_2` Form Task, existing node/flow IDs, and DI support the preserved execution regression.
- [manual-task-minimal.bpmn.xml](examples/manual-task-minimal.bpmn.xml): replace `DISPLAY_SCREEN_ID` with the real DISPLAY screen ID. Its `manual_task` has Request Starter assignment, not assignment `manual`.
- Both process XML files have exactly one Start → human task → End path, unique identifiers, matching incoming/outgoing flow references, a BPMN plane referring to the process, shapes with bounds, and edges with waypoints.
- After substitution, no instance placeholder remains; screen IDs are numeric and independently checked against the target screen's actual type. Static parsing alone cannot establish that an ID exists on an instance.
- Screen dependencies are [FORM](../pm4-screen/examples/form-minimal.json) and [DISPLAY](../pm4-screen/examples/display-minimal.json). `SCREEN_CATEGORY_ID` is a category placeholder in screen JSON, not the screen ID used by process XML. Adapt sample content/bound variables only when requested; do not assume invoice display text already describes warehouse work.
- FORM dependency has its Submit action; DISPLAY has read-only controls and no screen-level Submit/input. Available CONVERSATIONAL support is a separate package-backed choice, not an unverified replacement for DISPLAY.
- The fixture unit tests check XML/reference/diagram structure and preserve the existing Form loader. They do not run the engine or browser. These files are not JSON export packages and do not authorize creating/running a test process.

## Designer and screen tooling

Only inspect/edit the assets covered by the user's request. Do not create a demo process to prove a task edit.

1. Open the existing process in Modeler; node type, screen, assignment, due/options, and diagram agree with the saved configuration.
2. Check warnings, including required screen selection. Existing imported node compatibility does not justify changing its type silently.
3. Open the linked screen in Screen Builder and preview it: page/control metadata is loadable, the intended content appears, and package controls render.
4. DISPLAY preview does not need a screen Submit button. The platform Complete Task action belongs to the runtime task host, so preview alone does not prove acknowledgment works.
5. New-node defaults and UI labels can differ from existing/API-created settings. Re-read actual saved notification/destination data instead of assuming defaults.

## Runtime only if explicitly requested

Use a dedicated authorized test process/request and record the host, versions/packages, actors, expected behavior, actual outcome, and IDs. Never print tokens or mutate production tasks as a smoke test. Agree which test assets will be cleaned up or retained before creation.

1. Start only the requested test process through its permitted start event; wait boundedly for workflow activation.
2. Find the runtime token using the test request and configured `element_id`. Read `/tasks/{id}?include=screen,definition,bpmnTagName,processRequest`; distinguish runtime IDs from BPMN IDs.
3. Confirm both Form and Manual tasks remain active until user completion. Form renders FORM/available CONVERSATIONAL; Manual renders DISPLAY instructions plus platform Complete Task.
4. Test the intended assignment actor and eligible pool. For Self Service, claim first and re-read assignment before working the task. An administrator-only success does not prove ordinary-user authorization or a backend claim-before-complete requirement.
5. Submit/acknowledge only when requested. The shared task API takes `status: COMPLETED` with appropriate data; observe the task becoming closed and the request advancing/completing after processing, not merely a successful PUT response.
6. Verify expected business data persists and unrelated values are not lost. For Manual work, do not invent an input or completion variable unless requested.
7. Observe due timestamps before/after authorized claim/reassignment; confirm static/dynamic/fallback behavior without assuming days of waiting are necessary.
8. Verify configured notification recipients/events and actual available channels. The inspected overdue path is triggered for the current user's active overdue tasks through notification listing; do not infer independent scheduled email delivery or unclaimed-task receipt.
9. Test requested destination/interstitial, repeated/looped work, and child-process waiting behavior with explicit expected results. Record which scenarios were not exercised.
10. Inspect request errors and final state. Clean up only created test assets according to the agreed policy, or record intentionally retained evidence and later-cleanup responsibility.

## Failures

| Symptom | Check |
|---------|-------|
| Default/placeholder instructions instead of intended screen | Missing/unresolvable `screenRef`; runtime fallback hides invalid design config |
| FORM task cannot complete | No working `event: "submit"` action; a Regular Button does not submit |
| Manual Task has inputs or a screen Submit control | Wrong screen type; DISPLAY content and host Complete Task are separate |
| Manual work appears automatically completed | Wrong node/type/flow or task was completed elsewhere; core mapping still requires acknowledgment |
| CONVERSATIONAL screen cannot render | Required package/renderer absent |
| Task reaches an unexpected Process Manager or errors | Missing candidate data, empty pool, missing assignment rows, or invalid default; fallback is not the intended assignment |
| User/group selection differs from “random” docs | Source uses cyclical selection based on eligible IDs/prior assignments |
| Previous assignee is unexpected in parallel work | Latest task-token ID does not identify a named business predecessor |
| Manager/reassignment option has no effect | Permission/options or workflow-user-manager/assignment-process integration unavailable |
| Self Service queue is empty/unclaimable | Toggle unsupported, pool empty, evaluated IDs wrong, or actor ineligible |
| Due time defaults unexpectedly | Wrong units, absent/nonnumeric Mustache value, or Designer zero removing `dueIn` |
| Notifications change before BPMN publication | Draft endpoint persists process-scoped notification rows immediately |
| Notification checkbox does not produce email | Shared map controls recipients/events; channel/package configuration and actual delivery require verification |
| In-progress task screen changes after screen publish | Current screen version resolver uses latest published screen, not request-start locking |
| Next task or redirect differs from intent | Destination/interstitial/conditional flags differ from inspector-generated configuration |

Correct the task documentation/configuration within the requested scope. Source references and known limits: [architecture.md](architecture.md), [asset-map.md](asset-map.md). Platform defects are separate findings/tickets; do not bypass permissions or weaken checks to hide them.
