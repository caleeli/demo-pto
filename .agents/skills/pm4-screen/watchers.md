# Watchers

A watcher runs a **script** or a **Data Connector** from the browser when a screen variable changes or when the screen loads, and writes the result back into the screen data. Watchers live in the screen's `watchers` column (a JSON array) and are edited in Screen Builder under **Watchers**.

- The script itself (its code, executor, run-as user) and the connector (its endpoints and mappings) are not covered by these skills.
- This file covers only how a screen calls them.

Sources: screen-builder `mixins/watchers.js`, `mixins/extensions/Watchers.js`, `components/watchers-form.vue`; core `ScriptController::execute()` and `ScreenExporter`; `package-data-sources` `DataSourceController`. Checked against the 52 watchers on the check instance (51 script, 1 data source).

## Shape

### Script watcher

```json
{
  "name": "Get Transcripts",
  "watching": "studentId",
  "script_id": "42",
  "script_key": null,
  "script": { "id": "script-42", "title": "Retrieve transcripts", "…": "full script snapshot" },
  "input_data": "{\"studentId\": \"{{studentId}}\"}",
  "script_configuration": "{\"collectionId\": 2}",
  "output_variable": "transcripts",
  "synchronous": false,
  "show_async_loading": false,
  "run_onload": true,
  "byPass": false,
  "uid": "17488908158461",
  "order": 1
}
```

### Data source watcher

```json
{
  "name": "Load Users",
  "watching": "trigger",
  "script_id": "10",
  "script_key": "package-data-sources/data-source-task-service",
  "script": { "id": "data_source-10", "title": "Users", "name": "Users", "description": "…" },
  "input_data": "{}",
  "script_configuration": "{\"dataSource\": \"10\", \"endpoint\": \"list\", \"dataMapping\": [{\"key\": \"users\", \"value\": \"data\"}]}",
  "output_variable": "users",
  "synchronous": true,
  "show_async_loading": false,
  "run_onload": true,
  "byPass": false,
  "uid": "17531068939951",
  "order": 1
}
```

| Key | Script watcher | Data source watcher |
|-----|----------------|---------------------|
| `script.id` | `script-{scriptId}` | `data_source-{dataSourceId}` |
| `script_id` | Script id (string) | **Data Connector id** (string) |
| `script_key` | `null` or `""` | `package-data-sources/data-source-task-service`. **A non-empty `script_key` is what makes the renderer treat the watcher as a data source** |
| `input_data` | JSON string, Mustache-rendered, sent as the script's `$data` | Ignored. The whole screen data is sent |
| `script_configuration` | JSON string, Mustache-rendered, sent as the script's `$config` | `dataSource` (connector id), `endpoint` (endpoint name), `dataMapping`, optional `outboundConfig` |
| `output_variable` | Receives the script's whole output | Not used at run time; results arrive through `dataMapping` |
| `watching` | Variable that triggers the watcher | same |
| `uid` | Unique per watcher; used as the debounce key | same |

`input_data` and `script_configuration` are **strings** holding JSON, not objects. Screen Builder rejects invalid JSON in them.

## When a watcher runs

- **The `watching` variable must belong to this screen.** It has to be a control's `name`, a loop's `varname`, a dynamic panel variable, or another watcher's `output_variable`. If not, the renderer drops the watcher entirely (`filterWatchers`), and it never runs, not even on load.
  - Request variables with no control don't count, and neither do calculated properties.
- **On change:** the renderer watches `vdata.{watching}` and queues the watcher when the new value is not `undefined`. Calls are debounced by 500 ms per `uid`.
- **On load:** if `run_onload` is true, the watcher is also queued when the screen mounts.
- **`byPass: true`** disables the watcher without deleting it.
- **`synchronous: true`** locks the screen with a "running" popup until the watcher finishes; errors show in that popup. With `false` the user keeps working; set `show_async_loading` to show a loading indicator. Errors appear as a red alert.
- Screen Builder's validation console shows "Using watchers with Scripts can slow the performance of your screen." when the screen has a script watcher. It's only a warning; the screen still saves and runs.
- Watchers run in the browser renderer only: tasks, Web Entry, and the Screen Builder preview. **They never run in EMAIL screens** ([screen-types.md](screen-types.md#email)) or in scripts.

## What a watcher calls

| Type | Call | Server |
|------|------|--------|
| Script | `POST /api/1.0/scripts/execute/{script_id}` with `{watcher: uid, data: <input_data>, config: <script_configuration>, sync: true}`. On a task page the URL gets `?task_id={taskId}` | Runs the script **inline** and returns `{output}`. The HTTP request stays open for the whole run (the renderer sets no timeout) |
| Data source | `POST /api/1.0/requests/{requestId}/data_sources/{script_id}` (or `/requests/data_sources/{id}` outside a request) with `{config, data: <screen data>}` | Merges the screen data over the request data, calls the endpoint, applies the mapping |

After a successful call:

- **Script:** `output_variable` = the script's whole return value.
- **Both:** for each `script_configuration.dataMapping` entry `{key, value}`, the screen variable `key` = `response[key]`.

Who can trigger them:

- Script execution is authorized by `ScriptPolicy::execute`. By default any non-anonymous user may run **any** script through this endpoint. The script runs as its own run-as user. Keep watcher scripts read-only, or check permissions inside them.
- The data source execute routes carry no `can:` middleware.

## Mustache in `input_data`

The renderer runs `Mustache.render(input_data, screenData)` and parses the result as JSON. Verified with screen-builder's own Mustache:

| Template | Value | Result |
|----------|-------|--------|
| `"{{name}}"` | `O'Brien & "Co"` | `"O&#39;Brien &amp; &quot;Co&quot;"` (HTML-escaped) |
| `"{{url}}"` | `https://x.test/a?b=1` | `"https:&#x2F;&#x2F;x.test&#x2F;a?b&#x3D;1"` |
| `"{{{name}}}"` | `O'Brien & "Co"` | Unescaped; the `"` **breaks the JSON** |
| `{{obj}}` | `{"a": 1}` | `[object Object]`, invalid JSON |

So:

- Pass ids and simple values: `{"studentId": "{{studentId}}", "count": {{count}}}`.
- Do not pass objects, arrays, URLs, or free text through `input_data`. Pass an id and let the script load the rest, or decode HTML entities in the script.
- Data source watchers avoid this, because they send the screen data as JSON.

## Export and import

| | Script watcher | Data source watcher |
|-|----------------|---------------------|
| Exported by | core `ScreenExporter` | `package-data-sources` (`ScreenExporterExtension`) |
| Missing target on export | Logged and skipped. The watcher keeps pointing at a missing id | **Export fails** (`DataSource::findOrFail`) |
| Rewritten on import | `script_id`, `script.id`, `script.title`, `script.description` | same |
| Not rewritten | ids inside `script_configuration` (e.g. `collectionId`) | **`script_configuration.dataSource`** |

After importing a screen with a data source watcher, `script_id` holds the new connector id but `script_configuration.dataSource` keeps the **old** one. Run time uses `script_id`, so the watcher still works. Screen Builder, however, reads `dataSource` to list endpoints and warnings. On the check instance the only data source watcher has `script_id: "10"` (exists) and `dataSource: "159"` (404). Set `dataSource` equal to `script_id` when editing.

## Adding or changing a watcher

1. Look up the script or Data Connector id on this instance ([asset-map.md](asset-map.md#lookups-never-invent-ids)). Never invent one.
2. Make sure the `watching` variable is a control on this screen.
3. Prefer Screen Builder: **Watchers → + Watcher**, then save and publish. It fills in `script`, `script_key`, `uid`, and the mapping.
4. When editing JSON:
   - Clone an existing watcher of the same kind from this instance.
   - Give it a new `uid`.
   - Set both `script_id` and `script.id` (and `dataSource` for a data source watcher).
   - Keep `input_data` / `script_configuration` as JSON strings.
   - Save with the draft flow ([architecture.md](architecture.md#updating-config)).
5. Check: open the task or the Screen Builder preview, change the watched field, and confirm the output variable fills. The browser console logs `Watcher <name>` on success and on error.
