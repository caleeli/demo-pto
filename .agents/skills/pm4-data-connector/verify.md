# Verify a Data Connector (static)

Run after every connector or node change. This is a **static** check. Do not call the external API or start a request unless the user asked.

## Connector

- `type` is `REST`
- `authtype` is `NONE`, `BASIC`, `OAUTH2_BEARER`, `OAUTH2_PASSWORD`, or `OAUTH2_AUTHORIZATION_CODE`
- `data_source_category_id` is an `ACTIVE` category on this instance
- `name` is unique and `alpha_spaces` (letters and spaces)
- `status`, if set, is `ACTIVE` or `INACTIVE`
- No token, password, or client secret in the saved example or the request body you keep

## Resource

- `endpoints` is an object
- Each key equals that resource's `purpose`
- `method` is `GET`, `POST`, `PUT`, `PATCH`, or `DELETE`
- `url` is set
- `{{variable}}` names match request data the process will have
- `POST`, `PUT`, and `PATCH` have a `body` when the API requires one

## Process node

| Check | Pass |
|-------|------|
| Element | `bpmn:serviceTask` |
| implementation | `package-data-sources/data-source-task-service` |
| dataSource | Connector id from this instance |
| endpoint | An `endpoints` key on that connector |
| type | `REST` |
| outboundConfig | Every `required` param and header; `key` exists on the resource |
| dataMapping | Each `key` is the request variable downstream steps read |
| callback | `false` unless the user asked |
| Callback node | If `callback` is `true`: element is `bpmn:callActivity` with `calledElement="data_source_callback-data_source_sub_process"`, and the `callback_*` keys are set ([wire.md](wire.md)) |

Designer edits to the process need Save + Publish.

## Failures

| Symptom | Likely cause |
|---------|----------------|
| 422 on create | Name, authtype, or category |
| Node does nothing | Wrong `dataSource` or `endpoint` |
| Missing query param | Required param not in `outboundConfig` |
| Empty request variable | `dataMapping.key` does not match what the next node reads |
| Literal `{{country}}` sent to the API | Value was stored as text without mustache rendering, or the request has no `country` |
| Other resources gone | PUT replaced `endpoints` |

Fix the connector or the node, publish the process, and re-run this list. Start a new request only if the user asked.
