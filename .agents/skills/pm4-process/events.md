# Start, Intermediate, End, and Boundary events

Use with [pm4-process](SKILL.md) for graph edits, [gateways.md](gateways.md) for event races, and [pm4-expressions](../pm4-expressions/SKILL.md) for expression scope. **Signal configuration belongs to [pm4-signal](../pm4-signal/SKILL.md)**; this reference does not duplicate its payload or delivery contract.

Complete fixtures, setup, and expected outcomes: [examples.md](examples.md), including [scheduled delay](examples/timer-start-delay.bpmn.xml), [interrupting](examples/boundary-timer-interrupting.bpmn.xml) / [non-interrupting](examples/boundary-timer-noninterrupting.bpmn.xml) boundary timers, [message collaboration](examples/message-collaboration.bpmn.xml), [normal/terminate endings](examples/normal-terminate-end.bpmn.xml), and [child error handling](examples.md#error-propagation-to-caller-boundary-error).

Do not start requests, complete tasks, deliver messages/signals, enable listeners, or exercise schedules just to verify a change without user authorization. Publishing a scheduled/event-start process can itself enable automatic request creation; confirm that activation is intended before publishing.

## Evidence and support boundary

Reviewed core declares `2026.16.1`, PHP Nayra is `1.12.4`, and relevant Modeler source files match standalone `1.69.50` and installed `1.69.49`. Inspected installed PM moddle schema is `0.16.1`, not locked `0.16.3`. Recheck the actual instance, browser bundle, engine driver, and optional packages before assuming compatibility.

Matrix status: **S** = source implementation identified; **T** = relevant existing test assertions inspected, not executed; **R** = restricted/unverified combination; **P** = package-dependent; **D** = delegate to the signal skill. **No event workflow scenario in this guide has been runtime-verified.** Editor registration, XML parsing, and test-file presence alone do not establish runtime support.

All names below are BPMN XML element names. Standard attributes such as `attachedToRef`, `cancelActivity`, `messageRef`, and `errorRef` are not `pm:*` extensions. Names are business labels; IDs and references establish identity.

## Event selection and configuration matrix

### Start: create a new request

| Variant | Element / definition | UI → persisted configuration | Trigger / dependencies | Status |
|---|---|---|---|---|
| None | `startEvent`, no definition | Ordinary Start settings; assignment/interstitial/web-entry settings are separate | User/API invocation with the configured entry permissions | S |
| Timer | `startEvent/timerEventDefinition` | Timing Control start date, repeat/period, end settings → `timeCycle` expression | Scheduler, executable ACTIVE process, unpaused timer starts | S + T |
| Message | `startEvent/messageEventDefinition` | Message selector → `messageRef` to root `message` declaration | Matching message in the loaded engine/collaboration context; not a global name-based subscription | S + limited T |
| Conditional | `startEvent/conditionalEventDefinition/condition` | Condition field → formal-expression body | Scheduler evaluation of ACTIVE processes against global-store data | S + T |
| Signal | `startEvent/signalEventDefinition` | Ref/declaration and catch variable per `pm4-signal` | Signal delivery, publication and process availability | D |
| Email | Start event with package configuration | Event `pm:config` JSON at `email_start`: enablement, listener, initial user, filters, destination variable | Email Start package, configured mailbox/listener and processing schedule | P + R for full delivery contract |

Start events have **no incoming sequence flow**. Validate each intended entry independently; a process can expose multiple configured starts. A timer/conditional start has no existing request whose data it can read.

### Intermediate: wait or emit during a request

| Variant | Element / definition | UI → persisted configuration | Trigger / dependencies | Status |
|---|---|---|---|---|
| Timer catch | `intermediateCatchEvent/timerEventDefinition` | Type Duration / Date-Time / Cycle → one timer child expression | Active catch, due timer, scheduler and applicable worker | S + T |
| Message catch | `intermediateCatchEvent/messageEventDefinition` | Message selector → `messageRef`; direct-completion destination can use definition `pm:variableName` | Matching message or authorized direct catch completion while active | S |
| Message throw | `intermediateThrowEvent/messageEventDefinition` | Message ref plus Data Inputs & Assignments → input/data-association elements | Reaching event dispatches message; configure receiver context and mappings | S + limited T |
| Conditional catch | `intermediateCatchEvent/conditionalEventDefinition/condition` | Condition → formal-expression body | Engine evaluation against waiting instance-store data | S + T |
| Signal catch | `intermediateCatchEvent/signalEventDefinition` | Ref and catch Request Variable per `pm4-signal` | Matching signal while request is waiting | D |
| Signal throw | `intermediateThrowEvent/signalEventDefinition` | Ref and throw payload per `pm4-signal` | Signal broadcast when reached; signal-specific queue/runtime | D |

Intermediate events have incoming and outgoing sequence flows. A message throw does not wait for an acknowledgement merely because a receiver exists. For competing catches after an Event-Based Gateway, follow [gateway target restrictions](gateways.md#target-restrictions), including one incoming per competing catch.

### End: complete a branch or terminate work

| Variant | Element / definition | UI → persisted configuration | Trigger / scope | Status |
|---|---|---|---|---|
| None | `endEvent`, no definition | Name/ID; redirect remains separate | Ends the arriving branch, not other pending tasks | S + T |
| Message | `endEvent/messageEventDefinition` | Message ref plus Data Inputs & Assignments | Throws when reached, then ending behavior; no recipient acknowledgement implied | S |
| Error | `endEvent/errorEventDefinition` | Error Name → root `error@name`; definition `errorRef` points to its ID | Error/terminate handling; child-to-caller boundary propagation has an existing test | S + T for that scope |
| Terminate | `endEvent/terminateEventDefinition` | Type selection adds the definition | Terminates pending work; concurrent-request/child scope needs target-specific verification | S + T for one-request test |
| Signal | `endEvent/signalEventDefinition` | Ref/payload per `pm4-signal` | Signal throw followed by ending semantics | D |

End events have **no outgoing sequence flow**. An ordinary End is not equivalent to “cancel all remaining tasks.” Error and Terminate must not be substituted for normal completion merely to make a request appear finished.

### Boundary: alternate routing from attached work

| Variant | Definition / configuration | UI fields | Trigger / restrictions | Status |
|---|---|---|---|---|
| Timer | `timerEventDefinition`; attachment and `cancelActivity` | Interrupting toggle, timer Type/expression | Attached activity activation starts waiting; due timer triggers only against active work | S + T |
| Error | `errorEventDefinition`; optional `errorRef`, `cancelActivity` | Basic inspector has **no Interrupting toggle** | Activity exception; no ref is catch-all. XML non-interrupting tests retain original work in FAILING | S + T, restricted UI |
| Message | `messageEventDefinition`; `messageRef`, attachment, `cancelActivity` | Message selector, Interrupting toggle | Active attached work; generic attachment checks disagree with CallActivity-only component intent | S + R for attachment combinations |
| Conditional | `conditionalEventDefinition/condition`; attachment, `cancelActivity` | Condition, Interrupting toggle | Instance-data condition evaluated while attached work is active | S; boundary fixture not proof of execution coverage |
| Signal | `signalEventDefinition`; attachment/interrupting config | Use `pm4-signal` | Matching signal against active attached work | D + T |
| Escalation | Registered `escalationEventDefinition` | Registered inspector includes Interrupting; absent from normal boundary dropdown | No escalation parser mapping/runtime implementation identified in reviewed Nayra | **R: do not generate executable guidance** |

Boundary events have **no incoming sequence flow**. Store `attachedToRef="ACTIVITY_ID"` on the boundary event and give its alternate path an outgoing flow. Drawing an event beside a task without a saved attachment is insufficient.

## Timers: exact persisted contract

Choose one child of `timerEventDefinition`; keep its expression body nonempty and valid after dynamic rendering.

| Choice | Child element | Illustrative body | Meaning |
|---|---|---|---|
| Duration | `timeDuration` | `PT30M` or `P1D` | Wait a duration after activation |
| Date/Time | `timeDate` | `2030-01-15T09:00:00Z` | Absolute instant; choose a real future date for the target |
| Cycle | `timeCycle` | `R4/2030-01-15T09:00:00Z/P1D` | Repeating interval with start and count |

These are expression bodies, not complete process examples or cron strings. Full process examples belong under [examples/](examples/). Start Timer's reviewed inspector writes `timeCycle` even though the engine can parse all three child types: do not claim the Start editor preserves arbitrary date/duration XML without a round-trip check. Intermediate and Boundary Timer inspectors expose all three choices.

**Configure:** select timing type, set a deliberate value, and inspect the saved event definition. Verify unique IDs and preserve references when the inspector replaces the definition. Start Timer's repeat/end controls and weekly schedules can produce more complex cycle strings; preserve editor-generated values rather than hand-inventing recurrence encodings.

**Timezone:** use `Z` or an explicit offset for absolute instants. The editor normalizes date selections to UTC; a timezone-less string can depend on PHP/application settings. A day/month recurrence and an elapsed-hour delay are not interchangeable around calendar/timezone boundaries. Confirm recurrence timing, DST behavior, and clock precision on the actual instance; do not promise second-exact execution from an ISO value alone.

**Dynamic data:** `TimerExpression` renders Mustache before date/interval parsing. For initialized root input `interval: "PT8M"`, a duration body `{{interval}}` uses that request value. Instance timers receive request-store data; start scheduling uses engine/global data. Confirm fields exist before activation; an empty rendered date can be interpreted by the underlying date parser rather than safely disabling the timer. Do not use the Boolean gateway-expression evaluator to calculate an ISO interval implicitly.

**Prerequisites:** executable/published configuration, Start process ACTIVE and not paused, registered schedule, working scheduler and the applicable queue/engine workers. Core's `bpmn:timer` command processes timers **and Conditional Starts**; it is not a read-only diagnostic. Do not invoke it merely to check availability. Use [setup-processmaker](../setup-processmaker/SKILL.md) for environment repairs, not process rewiring to hide an infrastructure failure.

**Cleanup:** the boundary scheduler looks for ACTIVE attached-activity tokens before firing. Test activity-completes-first, interruption, recurrence, and event-race losing branches separately. Absence of an active task and physical removal of a scheduled row are different assertions; neither proves the other.

## Messages: declaration, targeting, and payload

### Configure identity and receiver

1. Identify the thrower, catcher, and whether delivery should create a request (Start) or resume existing work (Intermediate/Boundary). Identify the intended request when concurrent requests can wait for the same event.
2. Set `messageEventDefinition@messageRef` on both ends to the intended root `bpmn:message` declaration's **ID**. Modeler's selector resolves/creates that declaration. Names alone do not establish a match; never leave refs empty as a correlation strategy.
3. For modeled cross-pool messaging, use a collaboration Message Flow, not a sequence flow between pools. The line alone does not guarantee payload transfer or unique delivery.
4. Define sender/receiver data mappings and check active receiver state. The engine's optional explicit target event/instance filters operate within its loaded context; do not assume an unaddressed message is a globally queued or durable broadcast. For broadcast requirements, use `pm4-signal` deliberately instead.

### Keep payload paths distinct

| Delivery path | Persisted configuration / runtime behavior |
|---|---|
| Modeled BPMN message throw | Sender `dataInput`, `inputSet`, and `dataInputAssociation/assignment/from,to` are configured by Data Inputs & Assignments. Engine evaluates sender associations, then optional receiver `dataOutputAssociations`, into receiver data. It does not automatically transfer all request data. |
| Direct catch completion through core jobs | `Jobs/CatchEvent` and `Jobs/BoundaryEvent` use `pm:variableName` on **messageEventDefinition**, not the event element. Nonempty value puts supplied data under that key; empty/`undefined` writes supplied keys at the root and can overwrite existing keys. Confirm UI exposure separately. |
| Core `Jobs/ThrowMessageEvent` | Explicit target catch/instance properties; writes supplied payload keys into the target store before dispatch. This is not the same mapping path as a BPMN throw. |

Do not confuse the installed schema's older `message@pm:payload` string with the reviewed association-based throw contract. Preserve existing mappings and references during edits. Establish whether nested keys are intended before assuming root writes are a recursive merge.

### Source-backed API catch completion

For an **explicitly authorized** completion of an existing active catch, the reviewed core route is `POST /api/1.0/requests/{request}/events/{event}`. `{request}` is the real request ID and `{event}` the BPMN catch node ID, not message ID/name. This directly completes the waiting catch; it is **not** a generic message publication endpoint or a Message Start invocation.

- The route is inside the authenticated API middleware group. The controller checks a CatchEvent definition in the request's version and an ACTIVE token at that node, then enforces configured `whitelist`, `allowedUsers`, and `allowedGroups`. Do not infer that authentication alone permits every configured event.
- The controller passes the **whole JSON body** as catch data. A body `{"payment_id":"P123"}` supplies that key directly; adding `{"data":...}` instead changes the supplied structure. Message-definition `pm:variableName` controls placement in the direct-completion job as described above.
- Source returns an empty response on success; reviewed guards use 404 for no active token, 423 for a non-catch element, and 403 for failed event restrictions. Deployed middleware/validation can add other failures.
- No live API invocation has been performed for this guide. Verify instance route, access policy, version, and payload effects before use. Do not use this endpoint to bypass a timer/condition, diagnose a missing message by forcing progress, or substitute for Boundary Event delivery: boundary tokens belong to attached activities, not a matching intermediate-catch token.

## Conditional events: state and evaluation trigger

Set the Condition field, persisted under `conditionalEventDefinition/condition` as a formal-expression body. Use the verified operators and data shape in [expression-contract.md](../pm4-expressions/expression-contract.md), not a generic FEEL example.

| Position | Input data | When evaluation is driven |
|---|---|---|
| Start | Engine/global store, backed by core's global-variable cache | Scheduler selects ACTIVE processes with conditional starts; `StartEventConditional` loads/evaluates them |
| Intermediate catch | Waiting instance-store data | Engine dispatches conditional definitions when stepping reaches quiescence |
| Boundary | Attached activity's instance-store data and active token | Engine conditional evaluation while that activity is active |

The reviewed definition compares previous/current truth values and triggers on false/unknown → true. Empty conditions are explicitly false, unlike blank gateway formal expressions. This is not a browser visibility rule and not a guarantee that any database edit immediately wakes a request. Establish how a real data change causes engine evaluation and how previous values survive reload before promising polling frequency, repeat starts, or re-arming behavior. Do not run state-changing expression helpers as diagnostics.

## Boundary attachment and interruption

1. Select the intended activity and add/attach the boundary event. Save `attachedToRef` to that activity's ID; do not add a sequence flow into the event.
2. Configure its trigger definition and alternate outgoing path, with a reachable intended outcome.
3. Choose **Interrupting** (`cancelActivity="true"`, engine default) or **Non-interrupting** (`cancelActivity="false"`) where the type supports that UI/configuration. Keep the normal activity outgoing path; it is taken if work completes normally.
4. Check the business consequence: interrupting diverts the active work; non-interrupting adds a branch while the original work continues. Repeated cycles can produce repeated branches, so do not assume “non-interrupting” means exactly one reminder.

Generic Modeler target checks include Task, ScriptTask, ServiceTask, SendTask, ReceiveTask, UserTask, GlobalTask, ManualTask, and CallActivity types. This is a list of editor-accepted types, **not** proof that every event/type combination executes. Use [pm4-form-manual-task](../pm4-form-manual-task/SKILL.md) for human-task settings and [pm4-sub-process](../pm4-sub-process/SKILL.md) for the called child's contract.

Type-specific cautions:

- **Message:** component code suggests CallActivity-only intent, but shared attachment logic accepts more types. Treat non-CallActivity attachment as unverified; even the intended pattern still needs target-specific testing.
- **Error:** reviewed inspector has no Interrupting toggle. Existing XML-driven non-interrupting tests retain the original Script Task/Call Activity in **FAILING**, not normal ACTIVE execution. Do not present that as an ordinary parallel continuation. A definition without an error ref is catch-all; a configured ref matches error identity, not the display name.
- **Signal:** use `pm4-signal` for ref/declaration/payload details, including catch-variable limitations.
- **Escalation:** editor registration is not runtime support. The reviewed engine has no corresponding storage mapping; do not generate runnable escalation events or modify the platform to add them under this ticket.

For every boundary type, verify the activity-completes-first case: the alternate path must not fire after its attachment is no longer eligible. Capture token and scheduled-job state separately; cleanup of timer rows and event subscriptions still needs execution evidence.

## Entry and exit settings

### Start permissions

A Start Event's **Start Permissions** are `pm:assignment`, `pm:assignedUsers`, and `pm:assignedGroups`. They say who may start a request. Use `user`, `group`, or `process_manager`, or leave them out.

- Do not copy task assignment values such as `requester` onto a Start Event.
- Empty permissions do not mean "everyone". Set users and groups on purpose.
- Anonymous Web Entry overrides the assignment with the anonymous user ([pm4-web-entry](../pm4-web-entry/SKILL.md)).

### Interstitial: Display Next Assigned Task

The Modeler checkbox is **Display Next Assigned Task to Task Assignee**. It writes `pm:allowInterstitial`.

| `pm:allowInterstitial` | `pm:interstitialScreenRef` | Meaning |
|------------------------|----------------------------|---------|
| Omitted or `false` | Omit | The starter is not redirected into the next task |
| `true` | A **DISPLAY** screen | The starter sees that screen while the next task is prepared |

- Set both attributes together, or neither. For the screen, use the looked-up id of a DISPLAY screen, for example the system screen titled "Screen Interstitial" (key `interstitial`). Runtime also accepts the key string, but the Modeler screen lookup does not resolve a literal key (see the migration note below), so prefer the id.
- At runtime an empty ref falls back to the system `interstitial` screen, but do not rely on it. When that screen is missing or the ref is wrong, the UI can hang on loading and the next task never appears.
- The same pairing applies to a Form Task and a Manual Task. See [pm4-form-manual-task](../pm4-form-manual-task/SKILL.md).

```xml
<bpmn:startEvent id="node_1" name="Start"
  pm:allowInterstitial="true"
  pm:interstitialScreenRef="SCREEN_ID">
  <bpmn:outgoing>flow_1</bpmn:outgoing>
</bpmn:startEvent>
```

Replace `SCREEN_ID` with the looked-up DISPLAY screen id.

### End: summary screen and destination

- **Summary Screen** (`pm:screenRef` on an End Event) is a **DISPLAY** screen shown for the completed request. It is only a view: the request completes without it.
- `pm:elementDestination` is a redirect after completion, not a termination scope.
- A Signal End also carries a payload setting (`NONE`, `ALL_REQUEST_DATA`, or `REQUEST_VARIABLE`). See [pm4-signal](../pm4-signal/SKILL.md).

```xml
<bpmn:endEvent id="node_99" name="End" pm:screenRef="DISPLAY_SCREEN_ID" />
```

### Message catch restrictions

A Message catch event can limit who completes it with `pm:allowedUsers`, `pm:allowedGroups`, and `pm:whitelist` (an IP or domain allow list). Set them when the event is reachable by API. Leaving them empty is a security decision, not a default to ignore.

### Error end

An Error End Event sets the error name through its `errorRef`. Pair it with an Error Boundary Event on a parent activity or Call Activity to catch it.

### Modeler palette ids

| Palette id | Element and definition |
|------------|------------------------|
| `processmaker-modeler-start-event` | `startEvent`, none |
| `processmaker-modeler-start-timer-event` | `startEvent`, timer |
| `processmaker-modeler-message-start-event` | `startEvent`, message |
| `processmaker-modeler-signal-start-event` | `startEvent`, signal |
| `processmaker-modeler-conditional-start-event` | `startEvent`, conditional |
| `processmaker-modeler-intermediate-catch-timer-event` | `intermediateCatchEvent`, timer |
| `processmaker-modeler-intermediate-message-catch-event` | `intermediateCatchEvent`, message |
| `processmaker-modeler-intermediate-message-throw-event` | `intermediateThrowEvent`, message |
| `processmaker-modeler-intermediate-signal-catch-event` | `intermediateCatchEvent`, signal |
| `processmaker-modeler-intermediate-signal-throw-event` | `intermediateThrowEvent`, signal |
| `processmaker-modeler-intermediate-conditional-catch-event` | `intermediateCatchEvent`, conditional |
| `processmaker-modeler-boundary-timer-event` | `boundaryEvent`, timer |
| `processmaker-modeler-boundary-message-event` | `boundaryEvent`, message |
| `processmaker-modeler-boundary-error-event` | `boundaryEvent`, error |
| `processmaker-modeler-boundary-signal-event` | `boundaryEvent`, signal |
| `processmaker-modeler-boundary-conditional-event` | `boundaryEvent`, conditional |
| `processmaker-modeler-boundary-escalation-event` | `boundaryEvent`, escalation. Registered, but not runnable (see above) |
| `processmaker-modeler-end-event` | `endEvent`, none |
| `processmaker-modeler-terminate-end-event` | `endEvent`, terminate |
| `processmaker-modeler-message-end-event` | `endEvent`, message |
| `processmaker-modeler-signal-end-event` | `endEvent`, signal |
| `processmaker-modeler-error-end-event` | `endEvent`, error |

## Ending and ordinary entry/exit configuration

- **Normal End:** completes the arriving branch. Existing core tests leave other tasks pending; a request is not necessarily complete just because one branch reaches End.
- **Message/Signal End:** also emits its configured event. Sending is not acknowledgement; sender completion and receiver delivery must be checked separately.
- **Error End:** declares/references an error and uses error/termination handling. A tested source scenario propagates a child's error to the caller's Boundary Error. Do not assume every uncaught or multi-instance error has the same scope.
- **Terminate End:** closes pending work rather than waiting for all branches. Reviewed core's termination subscriber iterates instances on the loaded owner process; verify concurrent-request and child-process scope before claiming isolation. It is not a substitute for a missing join or fallback.

Start assignment, `pm:allowInterstitial`, `pm:interstitialScreenRef`, and web-entry `pm:config` ([pm4-web-entry](../pm4-web-entry/SKILL.md)) are entry configuration, not event definitions. End `pm:elementDestination` is a redirect, not termination scope. Keep screen IDs, permissions, and child start selection under the owning screen/task/sub-process skills. Email Start uses package `pm:config.email_start`; do not invent a standard `emailEventDefinition` or assume installation means a listener is enabled.

A PM3 manual start converted by `pm-migrate` ships with `pm:allowInterstitial="true"` and no `pm:interstitialScreenRef`. Runtime falls back to the system screen whose key is `interstitial`. Timer, message, and signal starts (`evn_marker` other than empty) keep `pm:allowInterstitial="false"`. Leave the screen ref empty: a literal key fails the Modeler screen lookup, and import remaps only numeric ids.

## Static verification and troubleshooting

- [ ] Event position and definition match the requirement; IDs and root message/error declarations resolve.
- [ ] Start has no incoming; End has no outgoing; Intermediate has both; Boundary has a valid attachment and alternate outgoing path, not an incoming flow.
- [ ] Timer expression is nonempty after rendering, has explicit timezone where relevant, and uses the intended recurrence/type.
- [ ] Conditional inputs exist in the correct global/instance context; expression uses verified syntax and deliberate types.
- [ ] Message identity, intended receiver, payload mapping, and direct-completion versus modeled-delivery path are explicit.
- [ ] Boundary interruption, activity completion, error state, repeated firing, and parent/child consequences are intentional.
- [ ] Required package, publication/activation, scheduler, workers, and API restrictions are identified without triggering them as a test.
- [ ] Editor-only and unverified variants remain labelled; parse/round-trip results are not reported as workflow execution.

| Symptom | Inspect before changing the process |
|---|---|
| Timer does not fire | Saved child/body, rendered value, timezone, ACTIVE/unpaused start, registered schedule, scheduler/worker logs, still-active catch/attachment |
| Timer fires unexpectedly | Blank dynamic value, timezone-less date, recurrence boundaries, rounding/precision, unintended publication of a scheduled start |
| Message catch never resumes | Message ID/ref/declaration, actual receiver request/version, active token, pool/context, worker/delivery path |
| Message data overwrites fields | Root-write direct completion versus named destination, sender/receiver association mappings, unexpected JSON wrapper |
| API completion is refused | Real request/node IDs, active token, authentication, allowed users/groups, source-IP/domain restriction; do not remove access controls to test |
| Conditional never triggers or repeats unexpectedly | Global versus instance data, missing variables, false-to-true history, engine evaluation/reload behavior, scheduler for Starts |
| Boundary path does not run | Saved `attachedToRef`, attachment still active, event-specific target restriction, trigger definition and outgoing path |
| Normal path disappears / work remains FAILING | Interrupting choice, Boundary Error semantics, child error propagation |
| End reached but tasks remain | Ordinary branch End versus intended synchronization/termination; inspect all active branches |
| Changes do not affect a waiting request | Published version versus draft; request uses its version. Do not force-complete existing work to apply a diagram edit |

When runtime testing is authorized, exercise both successful/late delivery and activity-completes-first scenarios; include wrong-ID/no-token cases, payload placement, timer delay/recurrence, conditional changes, both race winners, and ending scope. Record exact versions, input/output, tokens, remaining schedules, and which existing tests actually ran.

## Source references

Paths below are relative to the named repository; they are evidence pointers, not dependencies on an installed user's filesystem.

- **Modeler:** `src/setup/registerNodes.js`; `src/components/nodes/{startTimerEvent,intermediateTimerEvent,boundaryTimerEvent}/index.js`; `src/components/inspectors/{TimerExpression,IntermediateTimer}.vue`; shared `messageEventDefinition` / `conditionalEventDefinition`; `boundaryEvent` helpers and per-type boundary files; `errorEndEvent` declaration/inspector code.
- **Nayra 1.12.4:** `src/ProcessMaker/Nayra/Storage/BpmnDocument.php`; `Bpmn/Models/{TimerEventDefinition,MessageEventDefinition,ConditionalEventDefinition,ErrorEventDefinition,EventDefinitionBus}.php`; `Bpmn/BoundaryEventTrait.php`, `EndEventTrait.php`; `Engine/EngineTrait.php` conditional dispatch.
- **Core:** `ProcessMaker/Models/{TimerExpression,GlobalDataStore}.php`; `Managers/TaskSchedulerManager.php` timer execution and `evaluateConditionals`; `Console/Commands/BpmnTimer.php`; `Jobs/{CatchEvent,BoundaryEvent,ThrowMessageEvent,StartEventConditional}.php`; `Listeners/BpmnSubscriber.php` termination subscriber.
- **API path:** core `routes/api.php` authenticated group and `requests/{request}/events/{event}`; `ProcessMaker/Http/Controllers/Api/ProcessRequestController.php::activateIntermediateEvent`. Source-reviewed, not live-tested.
- **Existing tests inspected, not executed here:** core `tests/Feature/Api/{TimerStartEventTest,IntermediateTimerEventTest,ConditionalStartEventTest,BoundaryEventsTest,ProcessPatternsTest}.php`; `tests/Feature/Processes/MessageTest.php`; `tests/Feature/TerminatedEndEventTest.php`. Some tests fake jobs or mock managers; fixture presence alone is not execution coverage.
- **Email package:** `package-email-start-event/src/Observers/ProcessObserver.php` reads non-draft event config and syncs listener settings; full package UI/delivery contract remains unverified.

Official event-family references: [Start](https://docs.processmaker.com/docs/start-type-events), [Intermediate](https://docs.processmaker.com/docs/intermediate-type-event-objects), [End](https://docs.processmaker.com/docs/end-event-type-objects), [Boundary](https://docs.processmaker.com/docs/boundary-type-event-objects). Use code/version-specific restrictions above rather than assuming every standard BPMN combination is executable in PM4.
