# REST resource (`endpoints`)

`endpoints` is an object. The key is the resource name and must equal `purpose`. Methods: `GET`, `POST`, `PUT`, `PATCH`, `DELETE`.

```json
{
  "listUniversities": {
    "purpose": "listUniversities",
    "method": "GET",
    "url": "http://universities.hipolabs.com/search",
    "description": "Universities by country",
    "params": [
      { "key": "country", "value": "{{country}}", "required": true }
    ],
    "headers": [],
    "body": "",
    "body_type": "json",
    "timeout": 0,
    "retry_attempts": 0,
    "retry_wait_time": 5
  }
}
```

## Other resource fields

| Field | Notes |
|-------|-------|
| `purpose` | Required. Equals the map key |
| `method`, `url` | Required |
| `description` | Short text |
| `params`, `headers` | Arrays of `{ key, value, required }` |
| `body`, `body_type` | `body_type` is `json` or `raw` |
| `timeout` | Seconds. `0` is no limit |
| `retry_attempts`, `retry_wait_time` | Retry count and seconds between tries |
| `outboundConfig` | Array. Usually empty on the resource |
| `testData` | A JSON **string** used by the Designer test panel. Not an object |

With `api_base_url` set on the connector, `url` can be `{{__api_base_url__}}/api/users`.

## Mustache

`url`, param `value`, header `value`, and `body` accept `{{variable}}`. That reads request data at call time. A value without `{{ }}` is sent as that literal string.

`required: true` on a param or header is copied into the process node's `outboundConfig` when the resource is selected in the modeler. If you write BPMN by hand, copy those required keys yourself ([wire.md](wire.md)).

## Body

Used for `POST`, `PUT`, and `PATCH`. `body_type` `json` sends `Content-Type: application/json` and keeps JSON types. Example:

```json
"body": "{\"name\": \"{{name}}\"}",
"body_type": "json"
```

`GET` and `DELETE` leave `body` empty.

## What not to invent

Do not send `endpoints` as an array. Do not use a method outside the five above. Do not rename `purpose` without updating every process node whose `endpoint` uses the old key.
