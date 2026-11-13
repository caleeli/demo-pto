# Verify a process (static)

Run before saving/publishing every process change and compare the persisted result afterward. This is a **static** check of the BPMN graph, not workflow execution. Do not start requests, complete tasks, deliver events, or exercise schedules unless asked. Publication of automatic starts also needs intended activation.

## Graph

- `bpmn:process` has `isExecutable="true"`
- Validate each configured Start entry independently; do not require exactly one Start or one path
- Each intended entry has a route to an End; inspect branch/join behavior rather than assuming graph reachability proves execution
- No orphan tasks/gateways/Intermediate events; Boundary alternate paths are activated through valid `attachedToRef`, not incoming sequence flows
- IDs are unique; `sourceRef`/`targetRef` and node incoming/outgoing flow IDs agree; sequence flows stay within the process/pool
- Default refs point to an outgoing flow of the same Exclusive/Inclusive Gateway; event message/error/signal refs resolve to definitions-level declarations

## Document structure

Rules and reasons: [bpmn-document.md](bpmn-document.md#rules).

- The XML is well formed, and `xmlns:pm` is declared when any `pm:` attribute is used.
- There is exactly one `bpmndi:BPMNDiagram`. Every node and flow has a shape or edge with bounds.
- Node ids match `^[a-zA-Z][^\s][a-zA-Z0-9_-]+$`.
- A new Form Task is `bpmn:task`, not `bpmn:userTask`.
- Every sequence flow is listed as `bpmn:outgoing` on its source and `bpmn:incoming` on its target.
- `pm:allowInterstitial="true"` comes with a `pm:interstitialScreenRef` that points to a DISPLAY screen.
- A Start Event has no task assignment value such as `requester`. Start permissions, and Web Entry if any, are consistent with how the process is launched.
- Every Start has a trigger that fits the use case, and every token path reaches an End or an intentional Terminate.
- Asset refs: `pm:screenRef` and `pm:scriptRef` are looked-up ids, `calledElement` is non-empty, and the child is published first. Through the API, no `${screen:...}`, `${script:...}` or `${process:...}` is left unresolved.
- Service Task `implementation` is a known value from [bpmn-elements.md](bpmn-elements.md#service-task-connectors), its `pm:config` is valid JSON, and its package is installed.
- Web Entry: `web_entry` is `null` or an object on purpose, and the completion screen or URL fields are complete.
- For portable git BPMN published with `package-plg`: named refs are used instead of ids, the file basename equals the process `name`, and no `${...}` is unresolved (a deploy returns 422).

## Gateway and event checks

| Type | Static check |
|---|---|
| Exclusive | Valid non-default conditions, initialized inputs and intended fallback; no unintended overlaps; merge is not mistaken for synchronization |
| Inclusive | Conditions may overlap; default is fallback only; direction matches separate split/join topology; selected routes can reach the intended join |
| Parallel | No conditions/default; all incoming connections at the join can receive a token |
| Event-Based | At least two direct competing catches; one incoming per catch; no conditions/default; supported target restrictions and one-winner outcome merge |
| Timer | Valid nonempty rendered body, correct date/duration/cycle, explicit timezone where applicable; schedule/worker and activation dependencies identified |
| Message | Matching IDs/declarations, intended receiver context and payload path; root overwrite versus named destination is deliberate |
| Conditional | Correct global/instance input context, initialized values, valid expression; evaluation/false-to-true behavior understood |
| Boundary | Valid attachment and outgoing route, no incoming flow; type-specific interruption/target restrictions and completion-first case identified |
| End | Normal branch completion versus message/error/terminate effects intentional; termination scope not assumed |

Use [gateways.md](gateways.md), [events.md](events.md), and [pm4-expressions](../pm4-expressions/SKILL.md) for exact fields and restrictions. Signal ref/payload/delivery checks belong to [pm4-signal](../pm4-signal/SKILL.md). Keep registered-only/unverified variants labelled; a schema/lint pass is not runtime evidence.

## Per node type

| Node | Check |
|------|-------|
| Form Task (`bpmn:task`) | `pm:screenRef` is a real FORM or supported CONVERSATIONAL screen id; assignment set — see [pm4-form-manual-task/verify.md](../pm4-form-manual-task/verify.md). After API create, `user_group` also needs a `process_task_assignments` row ([create-edit.md](create-edit.md) step 3b) |
| Manual Task (`bpmn:manualTask`) | `pm:screenRef` is a real DISPLAY screen id; assignment set; no screen-level Submit control — see [pm4-form-manual-task/verify.md](../pm4-form-manual-task/verify.md). The API-create `user_group` resave requirement also applies |
| Script Task | `pm:scriptRef` is a real script id; `pm:config` and `pm:errorHandling` are valid JSON ([pm4-script](../pm4-script/SKILL.md#script-task-node)) |
| Call Activity | `calledElement` and `pm:config` — [pm4-sub-process/verify.md](../pm4-sub-process/verify.md) |
| Start Event | No incoming flow; intended event definition/permissions/dependencies; Web Entry / interstitial config intentional if set |
| Intermediate Event | Incoming/outgoing flow and correct catch/throw definition; data/timing/receiver contract set |
| Boundary Event | `attachedToRef` resolves to intended eligible activity; alternate outgoing path and `cancelActivity` intentional |
| End Event | No outgoing flow; intended normal/error/terminate/throw behavior; `pm:elementDestination` is a separate redirect |

## Publish

Designer: **Save + Publish**. Unpublished drafts do not create live requests.

API-created process with `"status": "ACTIVE"` is live; Designer edits after that still need Publish.

Confirm automatic request creation is authorized before enabling timer, conditional, message, signal, or package-dependent starts. Existing requests retain their version; publishing is not a reason to complete or reroute them.

## Runtime (only if user asked)

1. Start a request as the expected actor.
2. Confirm the flow reaches each node in order (or correct gateway branch).
3. Human tasks: Inbox / To Do, correctly typed screen renders; Form submits or Manual acknowledges through Complete Task — [pm4-form-manual-task](../pm4-form-manual-task/SKILL.md).
4. API: `GET /api/1.0/tasks?pmql=(status = "Active")` — see [pm-api-contracts](../pm-api-contracts/SKILL.md).
5. Branches/events: run only the authorized scenarios in [gateway verification](gateways.md#verification-and-troubleshooting) and [event verification](events.md#static-verification-and-troubleshooting). Include fallback/no-match/overlap, selected-branch joins, both race winners/late losers, payload placement, boundary completion-first/interruption, and ending scope as applicable.
6. Record exact versions, inputs, expected/actual paths, active/closed tokens, and schedules/subscriptions. Report static, Modeler round-trip, stub/evaluator, and real workflow results separately; mark blocked scenarios unverified.

## Failures

| Symptom | Likely cause |
|---------|----------------|
| Request stuck after Start | Disconnected flow, gateway with no matching condition |
| Script never runs | Wrong element type or missing `pm:scriptRef` |
| Subprocess not invoked | Wrong `calledElement`, start event, or child process — [pm4-sub-process/verify.md](../pm4-sub-process/verify.md) |
| Form Task placeholder | Missing `pm:screenRef` |
| `user_group` never assigned | XML has `assignedGroups` but no assignment row (POST without resave) |
| Changes not visible | Not published |
| Scratched / sunburst lines in Modeler | Missing flow `BPMNEdge`s — [bpmn-document.md](bpmn-document.md#rules); lanes/pool strip — [pools.md](pools.md) |
| Unconditional or wrong branch | Blank/Mustache-rendered-empty expression, wrong data context, type coercion or Exclusive overlap — [pm4-expressions](../pm4-expressions/SKILL.md) |
| Join waits / duplicate continuation | Optional branches feeding Parallel join, unreachable branch, or non-synchronizing merge — [gateways.md](gateways.md) |
| Event never triggers / unexpected data changes | Wrong ref/receiver/attachment, delivery payload path, version or infrastructure dependency — [events.md](events.md) |

Fix the intended configuration, repeat static checks, and save/publish only when authorized. If runtime testing was requested, use the approved disposable environment and a new request where needed; do not force existing work forward to make a verification pass.
