# Create or edit a Data Connector

A skill is instructions. ProcessMaker will **not** create a connector from SKILL.md alone. Use **Designer** or the **API** on the instance.

Needs `package-data-sources`. Do not call the external API or start a request unless the user asked.

## Path A — Designer (no API)

1. Designer → **Data Connectors** → **+ Data Connector**.
2. Name, description, **REST Service**, auth, category → **Save**.
3. Endpoint configuration → add a resource. See [resources.md](resources.md).
4. **Send** on that resource only if the user asked to test the call.
5. Wire the process node in Designer, or follow [wire.md](wire.md). Save + Publish the process. The connector asset itself is live after Save.

## Path B — API

Base URL: `http://processmaker-octane.test` (or your local host). Auth: session cookie or `Authorization: Bearer <token>`.

### 1. Resolve category id

```
GET /api/1.0/data_source_categories?per_page=15
```

See [lookup.md](lookup.md). Do not assume category `1`.

### 2. POST the connector

```
POST /api/1.0/data_sources
Content-Type: application/json
```

Body: [examples/rest-get.json](examples/rest-get.json). Replace `CATEGORY_ID`.

Expect **201** and connector `id`.

`name` must be unique and `alpha_spaces` (letters and spaces). A 422 on the name means drop punctuation. `authtype` must be one of `NONE`, `BASIC`, `OAUTH2_BEARER`, `OAUTH2_PASSWORD`, `OAUTH2_AUTHORIZATION_CODE`. There is no `BEARER` or `NO_AUTH`.

`NONE` sends no credentials. For any other auth, set `credentials` as an object. Prefer Designer so secrets are not in a saved request or chat. Do not put tokens, passwords, or client secrets in the request you save, in notes, or in a process description.

| authtype | `credentials` keys |
|----------|--------------------|
| `NONE` | none |
| `BASIC` | `username`, `password` |
| `OAUTH2_BEARER` | `token` |
| `OAUTH2_PASSWORD` | `url`, `client_id`, `client_secret`, `username`, `password` |
| `OAUTH2_AUTHORIZATION_CODE` | `client_id`, `client_secret`, `token_url`, and the other OAuth code-flow keys. Set up in Designer |

Optional connector fields: `api_base_url` and `debug_mode`. When `api_base_url` is set, a resource `url` can start with `{{__api_base_url__}}`.

Prefer `REST`. SOAP connectors (WSDL/XSD, certificates) are set up in Designer and are out of scope here.

### Order of work

1. List existing connectors ([lookup.md](lookup.md)) to avoid a duplicate.
2. Look up the category.
3. Create the connector with at least one useful resource.
4. Wire the process node ([wire.md](wire.md)).

When a working connector already has the shape you need, GET it and copy its resource shape. Do not invent field names.

Select-list catalogs: create a Collection ([pm4-collection](../pm4-collection/SKILL.md)) instead of hand-building a connector. The package creates the connector for you. See [lookup.md](lookup.md).

### 3. Add or change a resource

`endpoints` is one column. A PUT that replaces it drops resources you omit.

```
GET /api/1.0/data_sources/{id}
```

Add or edit one key, keep the others, then:

```
PUT /api/1.0/data_sources/{id}
Content-Type: application/json
```

Send the `endpoints` object (and any other fields you are changing). Expect **204** and an empty body. Read the connector again with GET to confirm.

If `credentials` is included, it must be an object. A string is ignored and the stored credentials stay.

## Editing an existing connector

1. Look up the connector by name ([lookup.md](lookup.md)).
2. Change auth, category, or resources as above.
3. If a process node points at a renamed resource, update `pm:config.endpoint` to the new `purpose` ([wire.md](wire.md)) and Publish the process.

## Failures

| Symptom | Cause |
|---------|--------|
| 422 on name | Not unique, or not `alpha_spaces` |
| 422 on authtype | Value is not one of the five REST types |
| 422 on category | Missing or unknown `data_source_category_id` |
| Resource disappeared | PUT replaced `endpoints` without the other keys |
| Auth change did nothing | `credentials` was sent as a string |
| Node calls the wrong URL | `endpoint` does not match an `endpoints` key |
