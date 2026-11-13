# Gateways and sequence flows

Use with [pm4-process](SKILL.md) for graph changes and [pm4-expressions](../pm4-expressions/SKILL.md) for expression syntax and input scope. Configure the **outgoing sequence flow**, not the gateway's name, when writing a routing condition.

Complete fixtures and expected input/routing cases: [gateway and event examples](examples.md), including [Exclusive](examples/exclusive-fallback.bpmn.xml), [Inclusive](examples/inclusive-split-join.bpmn.xml), [Parallel](examples/parallel-split-join.bpmn.xml), and [message/timer race](examples/message-timer-race.bpmn.xml).

## Choose a gateway

| Business requirement | BPMN element | Outgoing behavior | Merge/join behavior |
|---|---|---|---|
| Choose one route using request data | `bpmn:exclusiveGateway` | One matching route, or the default fallback | Merge passes arriving tokens individually; it does not synchronize parallel work |
| Choose one or more routes using request data | `bpmn:inclusiveGateway` | Every matching route, or default if none match | Join uses active tokens and path reachability; not an unconditional wait for every drawn incoming edge |
| Always run every branch | `bpmn:parallelGateway` | All outgoing routes; no conditions/default | Wait for a token on every incoming connection |
| Wait for the first competing event | `bpmn:eventBasedGateway` | First catch to trigger wins; no conditions/default | Use as an event race, not a parallel join |

These contracts are source-backed for reviewed core `2026.16.1` / PHP Nayra `1.12.4`, with relevant Modeler files matching standalone `1.69.50` and installed `1.69.49`. Workflow execution and Modeler round trips remain unverified for this guide. See [evidence and limits](#evidence-and-limits) before relying on version-sensitive behavior.

## Before editing

Establish the business choice: exactly one route, several selected routes, all routes, or an event race. Identify the request fields available at that point, fallback outcome, and which branches must join. Read the existing BPMN and preserve unrelated nodes, identifiers, asset references, and diagram geometry.

For API edits, follow [create-edit.md](create-edit.md): obtain the full XML, modify it, and send the full `bpmn` with `description`. Do not invent a separate gateway API or change process status to `PUBLISHED`.

## UI fields and XML contract

| Configuration | Persisted location | Rules |
|---|---|---|
| Gateway type | `exclusiveGateway`, `inclusiveGateway`, `parallelGateway`, `eventBasedGateway` | Type controls routing; a name such as “Approved” is only a label |
| Name / Node Identifier | Gateway `name` / `id` | IDs must be unique across the document |
| Inclusive/Parallel Direction | Gateway `gatewayDirection="Diverging"` or `"Converging"` | Default new-node direction is Diverging; use separate split/join nodes |
| Sequence flow endpoints | Flow `sourceRef` and `targetRef` | Resolve to real nodes in the same process/pool |
| Node connections | Node `incoming` and `outgoing` elements containing flow IDs | Keep reciprocal references consistent with endpoints |
| Exclusive/Inclusive flow Expression | Flow `conditionExpression` formal-expression body | Boolean decision using the caller's data context |
| Set as Default Flow | Source gateway `default="FLOW_ID"` | Reference an outgoing flow of that same Exclusive/Inclusive Gateway; one default at most |
| Exclusive Flow Variable | Flow `pm:config` JSON | Optional data update; separate from `conditionExpression` |

`default`, `gatewayDirection`, `sourceRef`, and `targetRef` are standard BPMN attributes, **not** `pm:*` extensions. `conditionExpression` is a child element, not a gateway attribute. The BPMN namespace is `http://www.omg.org/spec/BPMN/20100524/MODEL`; the PM extension namespace is `http://processmaker.com/BPMN/2.0/Schema.xsd`. A formal-expression type annotation uses the XML Schema Instance namespace `http://www.w3.org/2001/XMLSchema-instance`.

Escape `&` as `&amp;`, `<` as `&lt;` and `>` as `&gt;` in `conditionExpression` bodies. Combine conditions with `and` / `or` ([expression-contract.md](../pm4-expressions/expression-contract.md)):

```xml
<bpmn:sequenceFlow id="node_51" name="Yes" sourceRef="node_50" targetRef="node_60">
  <bpmn:conditionExpression xsi:type="bpmn:tFormalExpression">status == "Approved" and score &gt;= 70</bpmn:conditionExpression>
</bpmn:sequenceFlow>
```

Expression syntax and the input shape are in [pm4-expressions](../pm4-expressions/SKILL.md). Do not use an empty expression as a visual "else": mark the fallback with the gateway `default` reference. Every sequence flow also needs a diagram edge (`BPMNEdge`) and the reciprocal `incoming` and `outgoing` references ([bpmn-document.md](bpmn-document.md#rules)).

In Designer, select the outgoing flow and configure Expression where available. Select the fallback flow and use **Set as Default Flow**; confirm the marker and saved gateway reference. Modeler retains defaults only for Exclusive/Inclusive Gateways and removes conditions from flow sources that do not support them. When changing gateway type, recheck saved configuration rather than assuming previous conditions survive.

## Exclusive: exactly one route

1. Add the Exclusive Gateway after the step that supplies the decision data.
2. Connect the possible outcomes. Put explicit conditions on non-default outgoing flows.
3. Choose a fallback outcome and mark its outgoing flow as default. Omit a condition on that fallback; its expression is not used to select fallback routing.
4. Prefer mutually exclusive conditions. For initialized Boolean `approved`, use `approved === true` for approval and a default for the intended alternative; decide whether missing input should be rejected upstream rather than treated as rejection.
5. Check the later merge. An Exclusive merge does not wait for multiple branches: using it to merge parallel work can activate downstream work more than once.

The reviewed engine chooses the **first true conditioned transition in its collection**. This does not establish a user-visible priority based on left/right position, label, or connection creation order after a Modeler save. Preserve deliberate ordering when editing and test overlapping conditions on the actual target.

If no condition matches and no default is defined, no branch is selected by the reviewed logic. Treat this as an uncovered business outcome; exact persisted status/token behavior needs a runtime test. A default does not repair invalid expressions. Missing variables and syntax errors can fail evaluation before a fallback can be selected.

**Static checklist:** correct type; initialized inputs; non-default conditions use valid syntax; default resolves to an outgoing flow; overlaps are intentional; merge behavior matches the number of incoming active branches.

## Inclusive: all matching routes

1. Use a Diverging Inclusive Gateway to select optional branches independently. Conditions need not be mutually exclusive.
2. Set a default for the “none selected” outcome. Default means **fallback**, not an additional branch that runs alongside matches.
3. Use a separate Converging Inclusive Gateway when selected branches must synchronize. A split should have one incoming and multiple outgoing flows; a join should have multiple incoming and one outgoing flow.
4. Check whether every selected branch can reach the join, including loops, alternate exits, and early termination. Do not replace the join with Parallel unless every incoming branch is guaranteed to run.

Nayra's Inclusive join examines tokens and directed paths that can still reach empty incoming connections. It is neither “continue at the first arrival” nor “wait for every drawn branch regardless of selection.” In a simple optional-branch split/join, the expected intent is to synchronize the selected branches; verify one-selected, several-selected, and default-only cases on the target. Complex loops/nested joins need their own test.

The reviewed Modeler updates Inclusive direction when incoming connections change. Recheck `gatewayDirection` after rewiring. Direction and graph shape must agree; the label alone cannot fix an invalid graph.

**Static checklist:** conditions independently represent business choices; fallback is not always-on; split/join are separate; direction matches topology; selected branches have coherent paths to the join; no unintended unconditional output.

## Parallel: all branches, then synchronize

1. Use a Diverging Parallel Gateway to create all outgoing branches. Do not set conditions or a default.
2. Use a separate Converging Parallel Gateway when all branches must finish before continuing.
3. Ensure every incoming connection at the join can receive a token for the intended execution. An optional branch that is never activated, or a branch that ends before the join, can leave it waiting indefinitely.

A Parallel join requires a token on **each incoming connection**. It does not ask which incoming branches were selected earlier. Avoid an Exclusive split feeding a Parallel join that expects both mutually exclusive branches.

**Static checklist:** no flow conditions/default; split has one incoming/multiple outgoing; join has multiple incoming/one outgoing; all required branches reach the join; no bypass defeats synchronization.

## Event-Based: race between catches

Use the documented pattern **wait for a message OR a timer**, rather than testing data after an event has already happened:

```text
                    → Message Catch → Received path
Previous step → Race
                    → Timer Catch   → Timeout path
```

1. Add an Event-Based Gateway with at least two outgoing flows, following the documented modeling rule. No conditions or default.
2. Connect directly to separate Intermediate Catch Events. Each competing catch should have exactly one incoming sequence flow, from this gateway; do not add another entry into a competing catch.
3. Configure the message reference/declaration and delivery mapping, or the timer expression and scheduler prerequisites. The timeout is a Timer Catch branch, not a default flow.
4. Connect each catch to its outcome. If outcomes merge, use a non-synchronizing merge appropriate for one winner, not a Parallel join waiting for both events.

When a catch activates, the reviewed `EventBasedTransition` closes/consumes active tokens at other connected catches in that instance. This source path does **not** prove scheduled-job removal, late-message handling, simultaneous-delivery ordering, or behavior when multiple race activations coexist. Test both winners and late losing events before declaring the race verified.

### Target restrictions

- **Message/Timer Intermediate Catches:** documented baseline; still require target-specific execution checks.
- **Signal/Conditional Intermediate Catches:** Modeler's connection check accepts the generic intermediate-catch type, but race semantics remain unverified here. Do not promote editor acceptance to runtime support. Signal configuration belongs to [pm4-signal](../pm4-signal/SKILL.md).
- **Tasks, throw events, or End Events:** do not wire these directly as competing targets. The installed lint rule permissively lists EndEvent, conflicting with connection checks and the engine's catch-activation contract; that is not a usable runtime guarantee.

The reviewed event-based lint rule checks target type and incoming count but does not enforce the documented minimum of two outgoing flows. Include that check explicitly in your own static review.

**Static checklist:** two or more competing catches; one incoming per catch; no conditions/default; declarations/time settings resolve; outcome merge expects one winner; worker/scheduler requirements and unverified target combinations are identified.

### Modeler palette ids

| Palette id | Element |
|---|---|
| `processmaker-modeler-exclusive-gateway` | `bpmn:exclusiveGateway` |
| `processmaker-modeler-inclusive-gateway` | `bpmn:inclusiveGateway` |
| `processmaker-modeler-parallel-gateway` | `bpmn:parallelGateway` |
| `processmaker-modeler-event-based-gateway` | `bpmn:eventBasedGateway` |
| `processmaker-modeler-sequence-flow` | `bpmn:sequenceFlow` |

A message flow (`bpmn:messageFlow`) links pools. An association links a text annotation. Neither is a sequence flow. Process stage markers are set through the Stage Manager and stored in the flow's configuration. Preserve them when editing flows.

## Sequence flows versus message flows

- A `bpmn:sequenceFlow` connects workflow nodes inside a process/pool. Preserve its `sourceRef`/`targetRef` and each connected node's reciprocal flow-ID references. Start events have no incoming sequence flow; End events have no outgoing one. Boundary events are activated by `attachedToRef`, not an incoming sequence flow.
- A `bpmn:messageFlow` lives in a collaboration and models cross-pool messaging. Modeler applies different source/target and pool checks. Do not use it as a substitute for ordinary sequencing, or a sequence flow to bridge pools.
- The message line alone does not guarantee payload transfer or unique delivery. Event `messageRef`, message declarations, receiver context, and data mappings must be configured. Message names are labels; the reviewed message matcher compares referenced IDs.
- When reconnecting/deleting a default flow, update the source gateway's `default` reference too. A dangling ID or a default pointing at another gateway's flow is invalid even if the diagram looks correct.

## Optional Exclusive Flow Variable

Core's **Flow Variable** inspector is shown for an Exclusive source. It stores Variable Name and Value separately from the routing condition, under `pm:config`:

```json
{
  "update_data": {
    "variable": "routingOutcome",
    "expression": "\"approved\""
  }
}
```

This is the JSON value to encode in the flow's `pm:config` attribute, not a process API body. Quote/escape the JSON for XML when saving. The Value is an expression: a literal string therefore needs expression quotes as shown.

Core listens for conditioned-transition notifications, evaluates `update_data.expression` against the instance store, and writes a top-level key. Do not assume dot notation here creates nested objects. Its guard uses PHP `empty` for configuration fields; falsy values, default-flow notification, and ordering relative to other conditions need targeted tests. Prefer an explicit data-producing step when the workflow depends on such unverified mutation ordering. Preserve existing flow-variable configuration during unrelated rewiring.

## Verification and troubleshooting

Perform static review first. Start requests, complete tasks, deliver messages/signals, or exercise schedules only when requested. XML parsing and API-stub acceptance do not prove routing.

| Symptom | Inspect |
|---|---|
| No route selected | Missing fallback; all conditions false; missing input key; expression syntax/type error |
| Unexpected route always wins | Blank condition or bare Mustache renders empty → true; overlapping Exclusive conditions; wrong payload scope |
| Inclusive default runs unexpectedly or never runs | Wrong `default` reference; “fallback” confused with “always”; unconditional non-default branch |
| Join waits indefinitely | Parallel join after optional/exclusive branches; branch exits before join; Inclusive loop/reachability issue |
| Downstream task activates more than once | Exclusive merge used where synchronization was required |
| Event race waits forever | Neither event delivered/due; wrong message ID/receiver; scheduler/worker unavailable; unconfigured catch |
| Both race outcomes appear | Multiple activations, unsupported targets, or late-event behavior; capture token history before changing semantics |
| Conditions/default disappear after save | Gateway type changed to one that does not support them; saved XML differs from intended graph |

When runtime testing is authorized, cover Exclusive match/fallback/no-match/overlap, Inclusive one/multiple/no matches and subset joins, Parallel fork/join, and both event-race winners with a late losing event. Record exact versions, input payloads, selected routes, active/closed tokens, and remaining scheduled jobs. Do not label unexecuted scenarios as passing.

## Evidence and limits

Reviewed sources (paths relative to the named repositories):

- **Modeler:** `src/components/nodes/gateway/gateway.vue`, gateway-type `index.js` files, `inclusiveGateway/inclusiveGateway.vue`, `parallelGateway/parallelGateway.vue`, `sequenceFlow/index.js`, `genericFlow/SequenceFlow.js`, `genericFlow/MessageFlow.js`; default action in `src/components/crown/crownButtons/defaultFlowButton.vue`.
- **Installed bpmnlint-plugin-processmaker 1.5.0:** `rules/gateway-direction.js`, `rules/event-based-gateway.js`. These are validation hints, not engine tests.
- **Nayra 1.12.4:** `src/ProcessMaker/Nayra/Bpmn/ConditionedGatewayTrait.php`, `ConditionedExclusiveTransition.php`, `DefaultTransition.php`, `InclusiveGatewayTrait.php`, `InclusiveGatewayTransition.php`, `ParallelGatewayTrait.php`, `ParallelGatewayTransition.php`, `EventBasedGatewayTrait.php`, `EventBasedTransition.php`.
- **Core:** `resources/js/processes/modeler/components/inspector/GatewayFlowVariable.vue`; `ProcessMaker/Listeners/BpmnSubscriber.php::updateDataWithFlowTransition`; expression details in [expression-contract.md](../pm4-expressions/expression-contract.md).

Official references: [Exclusive](https://docs.processmaker.com/docs/exclusive-gateway), [Inclusive](https://docs.processmaker.com/docs/inclusive-gateway), [Parallel](https://docs.processmaker.com/docs/parallel-gateway), [Event-Based](https://docs.processmaker.com/docs/event-based-gateway), and [Sequence Flow](https://docs.processmaker.com/docs/sequence-flow-element). Where documentation simplifies Inclusive joins or suggests always-on default routing, use the source-specific distinctions above and verify the target behavior. Alternate broker engines and versions require separate confirmation.
