# PM4 Collection — reference

Deep detail for `pm4-collection`. Load `SKILL.md` first.

## Storage model

Each collection gets dedicated tables on create (`CollectionObserver::created`):

| Table | Purpose |
|-------|---------|
| `collection_{id}` | Live records |
| `collection_{id}_versions` | Previous versions on update |

Standard columns: `id`, `uuid`, `data` (JSON), `created_by_id`, `updated_by_id`, timestamps, `collection_id`, `is_template`, `status`.

Record updates snapshot the previous row into `_versions` before saving (`RecordController::handleVersion`).

## Collection vs request data

| | Request `data` | Collection `record.data` |
|--|----------------|------------------------|
| Scope | One process request / case | Global to the instance |
| Lifecycle | Created with request, travels with token | CRUD via API, screens, scripts |
| Schema | Implicit in screens/scripts | Derived from collection screens |
| Query | BPMN expressions, Mustache | PMQL on `/collections/{id}/records` |

Copy between them explicitly in script tasks.

## RAG collections

`type: RAG` uses system screens (`rag-create-screen`, `rag-view-screen`; update reuses create). `POST` create and `PUT` update set `status: PENDING`; `PATCH` does not (table default is `COMPLETED`). Field connector mapping uses file metadata (`file`, `name`, `uploads`, `source_type`).

## Data source auto-provisioning

When the Data Sources package (`ProcessMaker\Packages\Connectors\DataSources`) is installed, each new collection dispatches `CreateDataSource` with:

- Endpoint: `/collections/{id}/records`
- Create/update field lists from screen controls
- Name: collection name (with numeric suffix if duplicate)

Use this data source from screen watchers and select lists.

## PMQL on records

Query parameter on `GET /collections/{id}/records`:

```
data.customerId = "123"
data.amount > 100
```

`order_by` supports dot notation converted to JSON path (`data.field` → `data->field`). Default `per_page` is 10000.

## CSV import

`POST /collections/{id}/import-csv` with multipart `csv`, `mapping` (JSON column map), `hasHeader`.

Runs async via `ImportCsvCollection` job.

## Export / import

| Action | Endpoint |
|--------|----------|
| Export | `POST /collections/{id}/export` (async job) |
| Import | `POST /collections/import` (multipart file; runs synchronously via `handle()`) |

`include_records_on_export` controls whether records are bundled.

## Script SDK (PHP)

Full examples in package `docs/PhpSdkExamples.md`. Minimal patterns:

```php
$apiInstance = $api->collections();
$records = $apiInstance->getRecords($collectionId, 'data.status = "active"', 100);
$record = $apiInstance->createRecord($collectionId, (new \ProcessMaker\Client\Model\RecordsEditable())->setData(['name' => 'Acme']));
$apiInstance->patchRecord($collectionId, $recordId, (new \ProcessMaker\Client\Model\RecordsEditable())->setData(['status' => 'done']));
```

Requires docker PHP executor with `$api` and `API_TOKEN` for `run_as` user.

## Signal payload

`RecordObserver::sendSignal()` calls `WorkflowManager::throwSignalEvent($signalId, (array) $record->data)`. Only the `data` JSON is passed — not `id`, `uuid`, or audit fields. Signals fire in `creating` / `updating` / `deleting` (before the DB write completes), so do not assume the record id exists in the catch process unless you stored it in `data`. Signal ids are listed in `SKILL.md`.

Renaming a collection updates signal display names (`CollectionObserver::updating`). Deleting a collection removes its signal process (`package_key = package-collections/collection-{id}`).

## Catching collection signals

Quick reference for collection-specific catch setup. For full signal throw/catch detail, see [`pm4-signal`](../pm4-signal/SKILL.md) and [`pm4-signal/collections.md`](../pm4-signal/collections.md). The hidden process `package-collections/collection-{id}` is platform infrastructure — build your own catcher process.

**Setup**

1. Enable `signal_create`, `signal_update`, or `signal_delete` on the collection (Configuration → Collection Signals).
2. Add a **Signal Start Event** (or boundary signal on a running request) with `signalRef` exactly `collection_{id}_{create|update|delete}`.
3. Declare a matching root signal: `<bpmn:signal id="collection_{id}_create" name="..." />` (`id` must equal `signalRef`).
4. Save the process as **ACTIVE**. PM4 indexes `signal_events` on save so `ThrowSignalEvent` can find catchers.

**Payload → request `data`**

Thrown payload is `record.data` only. On catch, each key becomes a **top-level** request `data` key (e.g. record `data.email` → `data.email` on the new request). Custom mapping requires `pm:config` on the signal start (advanced; see `pm4-signal/config-attrs.md`).

**Example**

See `examples/signal-catch-minimal.bpmn.xml` (pattern from PM4 `TriggerSignalInRequestTest.bpmn`).

## Obfuscate and record keys

When `obfuscate` is true, `Record::getUriKeyAttribute()` exposes `uuid` instead of numeric `id` in record CRUD URLs and API paths. `scopeByUriKey` resolves records by `uuid` when obfuscate is enabled. Route pattern for `recordId` allows `[A-Za-z0-9-]+`.

**Exception:** `GET .../records/{record}/relationships/{rel}/records` binds `{record}` with pattern `[0-9]+` and resolves by numeric `id` only — not uuid.

## Relationship query mechanics

`RelationshipsController::records` and the collection UI build PMQL as:

```
{child_collection_key} = "{parent_value}"
```

Keys come from `/collections/{id}/columns` (`current` for parent, `foreign` for child) — typically `data.{fieldName}` or `id`. Parent value is read from the parent record via `parent_collection_key`.

## Permissions detail

`Collection::permissionsOfUser()` merges direct user permissions and group-inherited permissions. Administrators bypass checks.

Record API middleware (gate ability → routes):

- `view` (`read` permission) → list/show records, `GET/PUT /columns`
- `create` → store, import-csv, `GET /record-fields`
- `update` → put, patch
- `delete` → destroy

`raw` is stored on `collection_permissions` for the web UI; no REST middleware uses it.

## Bundling in PM Blocks

When a process is published as a PM Block, dependent collections copy with `asset_type = PM_BLOCK`. Re-map collection ids in the target instance after import.
