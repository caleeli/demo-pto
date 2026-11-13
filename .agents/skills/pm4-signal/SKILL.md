---
name: pm4-signal
description: Configure/debug PM4 global or local Signal start/intermediate/boundary/end events, throw/catch payloads, Collection record signals, the /api/1.0/signals API, and cross-instance moves. Use for named signals that start/resume processes or react to Collection changes. Not for message, timer, conditional, error, or terminate events (pm4-process), or running requests.
---

# Configure signal events

A **signal** is a broadcast. A throw event (intermediate signal throw, or signal end event) sends it through the `bpmn` queue to every **other** active process with a matching signal start event, and to every active request currently waiting on a matching intermediate or boundary signal event. There is no addressee and no acknowledgement: **a signal that nothing catches just disappears**, without an error.

Three things must agree: the **signal id** (`signalRef`) on both sides, the thrower's **payload** (`pm:config` JSON on the event definition), and the catcher's **Request Variable** (`pm:config` on the event element). Most failures are one of those, or a delivery precondition (worker, `ACTIVE`, published, token waiting).

This skill works on an **existing** process and instance. Do not create a demo process, start a request, or create Collection records unless the user asked.

References: [architecture.md](architecture.md) (storage, throw/catch runtime, routing), [config-attrs.md](config-attrs.md) (node types, payload modes, XML), [collections.md](collections.md) (Collection signals, import/export), [asset-map.md](asset-map.md) (API, files), [verify.md](verify.md) (checks, failures, known issues). Examples: [examples/](examples/).

User docs: [Signals](https://docs.processmaker.com/docs/signals), [Signal Start Event](https://docs.processmaker.com/docs/signal-start-event), [Intermediate Signal Throw Event](https://docs.processmaker.com/docs/intermediate-signal-throw-event). Where they disagree with these files, trust these files: they were checked against the code. The main disagreements: an empty Request Variable on the throw sends `{}`, not all data; the "expression" payload works but is not in the inspector; and signals only reach requests that are waiting at that moment.

## Not this skill

| User wants | Use instead |
|------------|-------------|
| Message events (point-to-point, `throwMessageEvent`) | not covered here |
| Collection CRUD, screens, records | [pm4-collection](../pm4-collection/SKILL.md) |
| Screens | [pm4-screen](../pm4-screen/SKILL.md) |
| Generic 422 / PMQL / pagination on other endpoints | [pm-api-contracts](../pm-api-contracts/SKILL.md) |
| Local instance down, queue worker not running | [setup-processmaker](../setup-processmaker/SKILL.md) |
| Editing the signal PHP (SignalManager, jobs) | [laravel-pm4-patterns](../laravel-pm4-patterns/SKILL.md) |
| Editing the signal inspectors (SignalSelect, SignalPayload) | [vue2-pm4-patterns](../vue2-pm4-patterns/SKILL.md) |

## Before changing anything

Ask only if missing:

1. **Processes**: which process throws, and which process(es) catch. Existing names or ids.
2. **Signal**: existing id, or the id and name to create. Global (listed in Designer > Signals) or local to the diagrams.
3. **Catch type**: start a new request (signal start event), resume a waiting request (intermediate catch), or interrupt or branch from a task (boundary, interrupting or not).
4. **Payload**: what the catcher needs. Default to one variable (`REQUEST_VARIABLE`), not `ALL_REQUEST_DATA`.
5. **Where it lands**: the catcher's variable name (Request Variable), unless merging into the root is intended.
6. **Collection signals only**: which Collection, and which operation (create, update, delete).

Then look up the real ids ([asset-map.md](asset-map.md#lookups-never-invent-ids)). **Never invent signal or collection ids.** Match signals by `id`, not by name.

## Required (every signal event)

1. **One `bpmn:signalEventDefinition` with `signalRef`** set to the signal id, identical on thrower and catchers.
2. **A `<bpmn:signal id="...">`** declaring that id in the same `<bpmn:definitions>`. Without it, Modeler clears the ref when the event is opened.
3. **No self-trigger by accident, and no loops.** A signal start event in the **throwing** process fires too: the engine delivers the signal locally, and a new request of the same process starts. Only put a start event on a signal the process itself throws when that is the intent, and never throw the signal again on that start event's path.
4. **One use per signal in a process that starts on it.** If the process has a signal start event, don't also catch the same signal on an intermediate or boundary event. A known bug then stores the payload under the wrong variable ([verify.md](verify.md#known-issues)).
5. **Payload set on purpose**: throw `pm:config` JSON with `NONE`, `REQUEST_VARIABLE`, or `EXPRESSION` (XML only), unless all request data is really needed ([config-attrs.md](config-attrs.md#throw-payload)).
6. **Catch variable set** (`pm:config="varName"` on the start or intermediate catch event), so the payload doesn't overwrite root variables. Boundary events have no inspector field for it.

## Creating or changing a signal

- Global: `POST /api/1.0/signals` with `id` (`^[a-zA-Z_][\w.-]*$`), `name`, `detail`. Or Designer > Signals. The API does not reliably reject duplicate names, so check first.
- Local: select or "+ Signal" in the event's inspector. Selecting an existing signal copies its `<bpmn:signal>` into the diagram.
- Renaming (`PUT /api/1.0/signals/{id}`) rewrites the name, **and the id if changed**, in every process that uses it. System signals (Collections) return 403.
- Deleting is refused while any event references the signal. Remove the events first.
- Collection signals: turn on `signal_create` / `signal_update` / `signal_delete` on the Collection; the signal id is `collection_{id}_{op}` ([collections.md](collections.md)).

## Workflow

```
- [ ] 1. Identify thrower and catcher(s), and the catch type (start / intermediate / boundary)
- [ ] 2. Look up or create the signal id; for Collections, look up the collection id and enable the flag
- [ ] 3. Throw event: signalRef + payload pm:config (prefer REQUEST_VARIABLE)
- [ ] 4. Catch event(s): same signalRef + Request Variable (pm:config on the event)
- [ ] 5. Every file declares <bpmn:signal id="..."> for each ref it uses
- [ ] 6. Save + Publish each process in Designer (drafts are not live; signal_events updates on save). Over the API: read
        the XML with GET /api/1.0/processes/{id}/bpmn (look up the numeric id first), edit it, then
        PUT /api/1.0/processes/{id} with description and the whole bpmn (422 without description)
- [ ] 7. Static check (verify.md). Do not throw a test signal unless asked
```

## Done when (static)

```
- [ ] signalRef identical on thrower and every catcher; <bpmn:signal> declared in each file
- [ ] Event names describe the business event, not the default names
- [ ] Throw payload is not ALL_REQUEST_DATA unless required
- [ ] Start and intermediate catches set a Request Variable, or root merge is intended and safe
- [ ] Start-event catcher is ACTIVE and published; if it is the thrower itself, that is intended and no loop is possible
- [ ] A process with a signal start event doesn't also catch that signal on another event
- [ ] Signal name checked for duplicates before creating (the API doesn't enforce it)
- [ ] GET /api/1.0/signals/{id} lists the expected throw and catch events
- [ ] Collection signals: collection id is this instance's, and the signal_* flag is on
```

When moving processes to another instance, partner processes are **not** exported with a process, and `copy` mode renames signal ids. Follow the checklist in [collections.md](collections.md#moving-signal-connected-processes-to-another-instance).
