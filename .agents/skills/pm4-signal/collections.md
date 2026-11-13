# Collection signals and import/export

## Collection signals

With `package-collections` installed, each Collection can throw a signal when a record is created, updated, or deleted. Processes use that signal to react to data changes. For example, a signal start event on `collection_5_create` runs a process for every new record.

### Signal ids

| Record event | Signal id | Name (auto) | Enabled by |
|--------------|-----------|-------------|------------|
| create | `collection_{id}_create` | `{collection name} create` | `signal_create` |
| update | `collection_{id}_update` | `{collection name} update` | `signal_update` |
| delete | `collection_{id}_delete` | `{collection name} delete` | `signal_delete` |

`{id}` is the numeric Collection id **on this instance**. Look it up (`GET /api/1.0/collections?filter=<name>`); never guess it.

The three flags are booleans on the Collection (`PUT /api/1.0/collections/{id}` or the Collection's settings page). All of them default to off.

### Where they come from

- When a Collection is created, `Collection::createRecordProcess()` creates a **system** process, "Collection {id} signal process" (`package_key = package-collections/collection-{id}`), from `resources/assets/create_collection_template.bpmn`. That process declares the three `<bpmn:signal>` elements and contains throw events for them, which is why they appear in the signal list.
- Renaming the Collection renames the three signals (`CollectionObserver::updating` → `SignalManager::replaceSignal`). The ids do not change.
- Deleting the Collection force-deletes that process, so its signals disappear. Processes that still reference them keep a `signalRef` that nothing throws.

### How they are thrown

`RecordObserver` calls `WorkflowManager::throwSignalEvent($signalId, (array) $record->data)` directly. The template process is **not** run. Delivery follows the normal broadcast ([architecture.md](architecture.md#throw-flow)).

| Observer hook | Fires | Payload |
|---------------|-------|---------|
| `creating` | before the insert | the new record's `data` |
| `updating` | before the update | the record's **new** `data` |
| `deleting` | before the delete | the record's `data` |

The payload is only `data`, the record's fields. **It does not include the record id**, and there is no `_collection` key. If the catching process needs the id, store it in a field of the record.

A catch event with no Request Variable merges every record field into the root of request data. Set a Request Variable (`pm:config="record"`) on the start or intermediate catch event, so the data lands in one place.

The throw happens inside the model event, before the write. If the write fails afterwards, the signal has already been queued.

### In the Signals list and API

- `GET /api/1.0/signals` marks these as `type: "collection"`, and **hides** the ones whose flag is off.
- They are system signals. `PUT` and `DELETE` on them return 403, because the system process throws them.

## Import and export

Signals travel with a process export as `signal` dependents (`ProcessExporter::exportSignals`, `Psudomodels/Signal.php`, `Exporters/SignalExporter.php`). Each signal is marked `global` if it exists in `global_signals` on the source instance.

On import, per signal:

| Mode | Result |
|------|--------|
| `update` | Keeps the id. If `global` and missing on the target, it is added to `global_signals`. Otherwise nothing changes. |
| `copy` | If the id already exists, the signal is **renamed** to `{id}_2` (`_3`, ...), and the imported process's `signalRef`s are rewritten to the new id. |
| `discard` | Removes the `<bpmn:signal>` **and the `signalRef` attribute** from every event in the imported process. The events stay, but they reference nothing. |

**Catching and throwing partners are not exported.** Only the signal definitions travel. `SignalHelper::processessReferencedByThrowSignals()` exists but is not called. Export and import each process on both sides of a signal yourself.

### Moving signal-connected processes to another instance

```
- [ ] List every process that throws or catches the signal (GET /api/1.0/signals/{id} → processes[].catches)
- [ ] Export and import each of them; the partners are not included automatically
- [ ] Import signals in "update" mode, not "copy": copy renames the id and breaks the link to the other processes
- [ ] Never "discard" a signal that the process uses: its events keep running with no signalRef
- [ ] Collection signals: the collection id differs on the target, so rewrite collection_{old}_* refs to the target's id, and enable signal_create/update/delete on it
- [ ] Open each imported process, check the signal on every signal event, and publish (drafts are not live)
- [ ] Confirm processes.signal_events holds the ref after publishing (GET /api/1.0/processes/{id})
```
