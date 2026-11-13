# Check a signal setup

This is a **static** check plus read-only lookups. Do not start a request, complete a task, or create records to "test the signal" unless the user asked.

## Static checklist (each signal event)

See [config-attrs.md](config-attrs.md).

```
- [ ] Exactly one bpmn:signalEventDefinition with a non-empty signalRef
- [ ] A <bpmn:signal id="<signalRef>"> exists under bpmn:definitions of the same file
- [ ] signalRef is identical (case included) on the thrower and every catcher; the name doesn't matter
- [ ] The signal exists on this instance (GET /api/1.0/signals/{id}) if it is meant to be global
- [ ] Throw: pm:config payload is intentional; not ALL_REQUEST_DATA unless the catcher needs everything
- [ ] Throw with REQUEST_VARIABLE / EXPRESSION: variable is set (empty sends {}; the catch variable becomes [])
- [ ] Catch (start / intermediate): Request Variable (pm:config on the event) set, unless merging into the root is intended
- [ ] Boundary catch: payload will merge into the root (no inspector field); no name clash with the task's data
- [ ] If the thrower also has a signal start event for the same signal, that is intended: it starts a new request of the same process
- [ ] No loop: the path after a signal start event never throws the same signal
- [ ] A process with a signal start event does not also use that signal on an intermediate or boundary event later in the XML (known bug: wrong Request Variable)
- [ ] Collection signals: collection_{id}_* uses this instance's collection id, and the matching signal_* flag is on
- [ ] Event names describe the business event ("Order approved"), not the default "Signal Start Event"
```

In Designer: Save, then **Publish**. Signal start events run the latest **published** version, and `processes.signal_events` is only updated when the process is saved.

## Read-only checks

```
GET /api/1.0/signals/{id}              # processes[].catches[] shows every event using the signal
GET /api/1.0/processes/{id}            # status == ACTIVE and signal_events contains the ref (for start events)
GET /api/1.0/requests/{id}?include=data  # for a waiting request: is a token on the catch event? did the payload land?
```

`GET /signals/{id}` hides system processes. Use `GET /signals?filter=<id>` to see them, for example Collection signal processes.

## "The signal was thrown and nothing happened"

Work down the list; the first failure is usually the cause.

```
- [ ] A queue worker is consuming the bpmn queue (ThrowSignalEvent and the catch jobs all run there)
- [ ] The throwing token actually passed the throw event (request history)
- [ ] signalRef matches exactly on both sides (typos, a "_2" suffix from a copy import)
- [ ] Start event: the catching process is ACTIVE, published, and processes.signal_events includes the ref
- [ ] Intermediate or boundary: the request is ACTIVE and a token was waiting on the event (or its activity) at the moment of the throw; signals are not stored for later
- [ ] Intermediate or boundary: the request is not a collaborating instance of the thrower (same engine run)
- [ ] Collection: the signal_create/update/delete flag is on for that collection
- [ ] Look for failed jobs (failed_jobs table, Horizon) from CatchSignalEventProcess / CatchSignalEventInRequest
```

## Failures

| Symptom | Likely cause |
|---------|--------------|
| Nothing happens, no error anywhere | No `bpmn` worker; signalRef mismatch; catcher not `ACTIVE`; start event added but process not saved or published; no token waiting at throw time |
| Throwing a signal starts a new request of the **same** process | The thrower has a signal start event for that signal; the engine delivers it locally. Remove it, or use a different signal |
| Requests keep multiplying | A signal start event's path throws the same signal again (loop). Archive the process to stop it, then break the loop |
| Start-event request has the payload under another event's variable, and its own variable is `[]` | Known bug: the same process uses the signal on another catch event later in the XML |
| Start event fires, but the new request runs old logic | Only the latest **published** version runs; the change is still a draft |
| Catching request's variables overwritten | Catch without Request Variable plus a thrower on `ALL_REQUEST_DATA`: every key is merged into the root |
| Payload arrives empty (catch variable is `[]`) | Throw on `NONE`, or `REQUEST_VARIABLE` / `EXPRESSION` with an empty `variable` |
| Payload is `{ "x": null }` | The thrower's `REQUEST_VARIABLE` names a variable that does not exist in its data |
| Payload is nested (`{ "order": { "total": 5 } }`) | `REQUEST_VARIABLE` with a dotted path keeps the path; read `order.total` |
| Boundary payload landed in the root | Boundary events have no Request Variable in the inspector; add `pm:config="var"` to the `boundaryEvent` in XML, or read from the root |
| Collection signal payload has no record id | Only `record->data` is sent; store the id in a field |
| Signal worked, stopped after an import | Imported in `copy` mode: the id became `{id}_2`. Or `discard`: the events have no `signalRef` ([collections.md](collections.md#import-and-export)) |
| Signal stopped after a Collection was deleted | The Collection's signal process was deleted; nothing throws `collection_{id}_*` any more |
| Signal event shows an empty signal in Modeler, and loses it on save | `signalRef` points to an id with no `<bpmn:signal>` in the file. `SignalSelect` clears the value when the event is selected. Add the `<bpmn:signal>` element before opening the diagram |
| 422 `The signal ID should be an alphanumeric string` | id does not match `^[a-zA-Z_][\w.-]*$` (spaces, leading digit) |
| 422 `The signal ID already exists` | Some process on the instance already declares that id |
| 403 `System signals cannot be modified.` | A system process (for example a Collection signal process) uses it |
| 403 `Signals present in processes and system processes cannot be deleted.` | Any process has an event that references it, **archived processes included**; remove the events first |
| Signals page or dropdown is slow | `getAllSignals` parses every process's BPMN on each call |

## Known issues

All three were reproduced on a running instance.

- **Signal name uniqueness is broken.** `SignalManager::validateSignal` checks `$sig['name'] === $newSignal->getId()`, comparing existing **names** with the new **id**. Two signals with the same name are accepted. A signal whose **id** equals an existing **name** is rejected with 422 `The signal name already exists`, even when its own name is unique. The Modeler checks names within one diagram only. Workaround: check names yourself (`GET /api/1.0/signals?filter=<name>`) before creating a signal.
- **The start event can store the payload in the wrong variable.** `CatchSignalEventProcess::handle` (and the RabbitMQ `throwSignalEventProcess` / `throwSignalEventRequest`) loop over **every** event that references the signal and keep the `pm:config` of the last one in the XML. If a process has a signal start event and also uses the same signal on an intermediate or boundary event that comes later in the file, the new request gets the payload under that event's variable, and the start event's own variable is `[]`. No error is raised. Workaround: don't combine a signal start event with other events on the same signal in one process, or use the same Request Variable on all of them.
- **`EXPRESSION` payload is backend-only.** `SignalPayload.vue` offers `NONE`, `ALL_REQUEST_DATA`, and `REQUEST_VARIABLE`. An `EXPRESSION` set in XML runs (`price * qty` delivered `{ "total": 6 }`), but the inspector shows an empty selection for it. Editing the event in Designer can overwrite it.

Fix the node or the signal. Do not create a sample process to prove the config unless the user asked.
