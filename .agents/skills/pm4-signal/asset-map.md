# Where things live

Paths are relative to each repo: `processmaker` (core), `modeler`, `package-collections`.

## Lookups (never invent ids)

```
GET /api/1.0/signals?filter=order&per_page=50     # id, name, detail, type, processes[]
GET /api/1.0/signals/{id}                         # one signal + the processes that use it
GET /api/1.0/collections?filter=Orders            # collection id for collection_{id}_* signals
GET /api/1.0/processes/{id}                       # signal_events: start-event refs this process listens to
```

`filter` matches signal `id` or `name` (case-insensitive substring). Match on **`id`**: that is the `signalRef`.

Each `processes[]` item has `id`, `name`, `is_system`, and `catches[]`. Despite the name, `catches[]` lists **every** event in that process that references the signal, throws included, each with `id`, `name`, and `type` (`startEvent`, `intermediateCatchEvent`, `boundaryEvent`, `intermediateThrowEvent`, `endEvent`). A process can appear with an empty `catches` when it only declares the signal. `global_signals` is one of those. **Archived and inactive processes are listed too**: the list scans every process regardless of status. They never receive signals, but they still block `DELETE`.

## API (`routes/api.php`, prefix `/api/1.0`)

Controller: `ProcessMaker/Http/Controllers/Api/SignalController.php`.

| Method + path | Permission | Purpose |
|---------------|------------|---------|
| `GET signals` | `view-signals` | List. `filter`, `order_by` (default `id`), `order_direction`, `per_page` (default 10), `page`, `pmql`. Includes system processes in `processes[]` |
| `GET signals/{id}` | `view-signals` | Show. `processes[]` **excludes** system processes |
| `POST signals` | `create-signals` | Create a global signal: `id`, `name`, `detail`. Returns 200 `{id, name}`, or 422 `{errors: {id: [...], name: [...]}}` |
| `PUT signals/{id}` | `edit-signals` | Rename, or change id and detail, everywhere it is used. 403 `System signals cannot be modified.` |
| `DELETE signals/{id}` | `delete-signals` | Remove from every process. 403 `Signals present in processes and system processes cannot be deleted.` |

Quirks:

- `pmql` filters **processes**, not signals. It limits which processes' BPMN is scanned. A pmql that excludes `global_signals` hides every global signal that no other process declares.
- `GET signals/{id}` for an unknown id returns **200 with an empty body**, not 404.
- `DELETE` returns **201 with an empty body**, including when the id does not exist.
- `last_page` is `floor(total / per_page) + 1`, so when `total` divides evenly the last page is empty.
- `POST` accepts a name that another signal already has, and rejects a unique name when the new `id` equals an existing name. This is a known bug ([verify.md](verify.md#known-issues)).
- `POST` and `PUT` don't use a FormRequest. Errors come from `SignalManager::validateSignal` in the shape above, not Laravel's `message` + `errors`.

Permissions (`database/seeders/PermissionSeeder.php`): `create-signals`, `view-signals`, `edit-signals`, `delete-signals`. The Modeler receives them as `ProcessMaker.modeler.signalPermissions` (`SignalManager::permissions`).

## Web (`routes/web.php`, prefix `/designer`)

| Path | Page | Permission |
|------|------|------------|
| `/designer/signals` | List, create | `view-signals` (the menu entry also requires `edit-processes`) |
| `/designer/signals/{id}/edit` | Edit name and detail | `edit-signals` |

## Core (`processmaker`)

| Path | What |
|------|------|
| `ProcessMaker/Managers/SignalManager.php` | `getAllSignals` (parses every process), `addSignal`, `replaceSignal`, `removeSignal`, `validateSignal`, `getSignalCatchEvents`, `getGlobalSignalProcess`, `PROCESS_NAME = 'global_signals'` |
| `ProcessMaker/Models/SignalData.php` | Value object: id, name, detail |
| `database/seeders/SignalSeeder.php`, `database/processes/GlobalSignals.bpmn` | Seeds the `global_signals` system process |
| `ProcessMaker/Providers/WorkflowServiceProvider.php` `register()` | Attaches the `SignalEventDefinition` listener to the engine's event bus (skipped when `globalEvents` is false) |
| `ProcessMaker/Nayra/Managers/WorkflowManagerDefault.php` | `throwSignalEventDefinition` (payload from `pm:config`), `throwSignalEvent`, `throwSignalEventProcess`, `throwSignalEventRequest` |
| `ProcessMaker/Nayra/Managers/WorkflowManagerRabbitMq.php` | Microservice variants of the per-process and per-request delivery |
| `ProcessMaker/Jobs/ThrowSignalEvent.php` | Selects target processes and requests by `signal_events`; `bpmn` queue |
| `ProcessMaker/Jobs/CatchSignalEventProcess.php` | Fires signal start events in one process |
| `ProcessMaker/Jobs/CatchSignalEventRequest.php` | Chunk of request ids → `CatchSignalEventInRequest` |
| `ProcessMaker/Jobs/CatchSignalEventInRequest.php` | Fires intermediate and boundary catches in one request; applies the catch `pm:config` |
| `ProcessMaker/Jobs/CatchSignalEvent.php` | Older fan-out job. Not dispatched anywhere in core or the packages; ignore it when tracing |
| `ProcessMaker/Observers/ProcessObserver.php` `saving()` | Sets `processes.signal_events` |
| `ProcessMaker/Models/Process.php` `getUpdatedStartEventsSignalEvents()` | Start-event signal refs |
| `ProcessMaker/Models/ProcessRequest.php` `updateCatchEvents()` | Sets `process_requests.signal_events` from active tokens (called from `Repositories/TokenRepository.php`) |
| `ProcessMaker/ImportExport/Exporters/ProcessExporter.php` `exportSignals()` / `importSignals()` | Signals as dependents, id rewrite on copy, discard |
| `ProcessMaker/ImportExport/Exporters/SignalExporter.php` | Duplicate check, `_2` increment |
| `ProcessMaker/ImportExport/Psudomodels/Signal.php` | Export model; `save()` adds missing global signals; `removeFromProcess()` |
| `ProcessMaker/ImportExport/SignalHelper.php` | Global vs. local lookups. `processessReferencedByThrowSignals()` is unused |
| `resources/js/processes/modeler/components/inspector/SignalPayload.vue` | Throw payload inspector (no `EXPRESSION` option) |
| `resources/js/processes/modeler/initialLoad.js` | Registers `SignalPayload` on the intermediate signal throw and signal end events |
| `resources/js/processes/signals/components/SignalListing.vue` | Designer > Signals list |

## Modeler (`modeler`)

| Path | What |
|------|------|
| `src/components/nodes/signalEventDefinition/index.js` | Shared inspector data and handler; `signalSelector()`; auto-creates `bpmn:Signal` in `rootElements` |
| `src/components/inspectors/SignalSelect.vue` | Signal dropdown, "+ Signal" (local), edit and remove in diagram, `xsd:ID` check |
| `src/components/inspectors/requestVariableSettings.js` | Catch "Request Variable" field → `config` |
| `src/components/nodes/signalStartEvent/`, `intermediateSignalCatchEvent/` | Catch nodes with Request Variable |
| `src/components/nodes/boundarySignalEvent/` | Boundary catch, interrupting toggle, no Request Variable |
| `src/components/nodes/intermediateSignalThrowEvent/`, `signalEndEvent/` | Throw nodes |

## Collections package (`package-collections`)

| Path | What |
|------|------|
| `src/Observers/RecordObserver.php` | Throws `collection_{id}_{op}` with `(array) $record->data` |
| `src/Observers/CollectionObserver.php` | Renames the three signals on collection rename; deletes the signal process on collection delete |
| `src/Models/Collection.php` `createRecordProcess()` | Creates the system "Collection {id} signal process" |
| `resources/assets/create_collection_template.bpmn` | Declares the three signals |
| `database/migrations/2020_10_07_144644_add_fields_signals.php` | `signal_create`, `signal_update`, `signal_delete` (default `false`) |

## Other packages that throw signals

| Path | Signal |
|------|--------|
| `package-auth/src/Observers/SettingObserver.php` | `start_ldap_sync` |
| `package-actions-by-email/src/Console/Helpers/StopHandlerProcess.php` | `abe_stop_handler` |

## Tests and fixtures (core)

| Path | Covers |
|------|--------|
| `tests/Feature/Api/SignalTest.php`, `GlobalSignalsTest.php`, `SignalPermissionsTest.php` | API, validation, permissions |
| `tests/Feature/Api/SignalEventTest.php` | `ThrowSignalEvent` process selection (active vs. inactive) |
| `tests/Feature/Api/TriggerSignalInRequestTest.php` | Collection-style `throwSignalEvent` into waiting requests |
| `tests/Feature/ImportExport/Exporters/SignalExporterTest.php` | Discard, update, copy (`_2`) |
| `tests/Feature/Api/bpmnPatterns/SignalWithCustomPayload*.bpmn`, `tests/Feature/Api/processes/SignalSimple.bpmn` | Throw payload configs |
| `tests/Fixtures/signal_catch_with_payload.json` | Catch `pm:config` variable on start and intermediate events |
