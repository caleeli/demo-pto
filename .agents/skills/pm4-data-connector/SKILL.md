---
name: pm4-data-connector
description: >-
  Creates and edits ProcessMaker 4 REST Data Connectors. Use when adding or
  changing a data source, a REST resource, auth, or wiring a Data Connector
  node (outbound and response mapping). Do not use for the process graph
  (pm4-process), screen config (pm4-screen), or SOAP.
---

# PM4 Data Connector

A **Data Connector** is a `data_sources` asset (`package-data-sources`). A **resource** is one key in `endpoints`. A process calls it from a `bpmn:serviceTask` with `implementation="package-data-sources/data-source-task-service"`.

This skill creates and edits REST connectors and wires one resource on a process node. It does not draw the process graph. Do not call the external API or start a request unless the user asked.

## When to use

- Create or edit a REST Data Connector, its auth, or a resource (method, URL, params, headers, body).
- Wire a Data Connector node: which connector, which resource, outbound values, where the response lands.
- A Collection already has a connector in the **Generated** category. Look it up and use it. Do not create another.
- Process graph: [pm4-process](../pm4-process/SKILL.md). Screen or watcher: [pm4-screen](../pm4-screen/SKILL.md). SOAP is out of this skill.

## Asset schema

| Field | Required | Notes |
|-------|----------|-------|
| name | yes | Unique. `alpha_spaces` (letters and spaces) |
| type | yes | `REST` |
| authtype | yes | `NONE`, `BASIC`, `OAUTH2_BEARER`, `OAUTH2_PASSWORD`, `OAUTH2_AUTHORIZATION_CODE` |
| data_source_category_id | yes | Existing category id. Do not assume `1` |
| endpoints | no | Object keyed by resource `purpose`. See [resources.md](resources.md) |
| status | no | `ACTIVE` or `INACTIVE` |
| description | no | Short summary |
| credentials | no | Object. Never put secrets in examples. `NONE` needs none |

## Rules

- Look up category, connector, and resource ids on this instance. See [lookup.md](lookup.md).
- `endpoints` is an object, not a list. The key is the resource name (`purpose`). The node `endpoint` must match that key.
- `{{variable}}` inserts request data. A value without braces is sent as literal text.
- On update, GET the connector first and PUT the full `endpoints` object so other resources stay.
- Credentials on PUT must be an object. A string is ignored.
- Leave node `callback` false unless the user needs an async callback. `true` replaces the Service Task with a Call Activity. See [wire.md](wire.md).
- Do not invent connector ids, resource names, or endpoint shapes. Copy them from the instance.
- Never put secrets in examples, notes, or process descriptions.
- Needs `package-data-sources` installed.
- A Collection creates its own connector. For select-list catalogs use [pm4-collection](../pm4-collection/SKILL.md).

## Links to other assets

- Process node `pm:config.dataSource` is the connector id. `endpoint` is the resource `purpose`. See [wire.md](wire.md).
- Outbound `PARAM`, `HEADER`, and `BODY` keys must exist on that resource.
- Response `dataMapping.key` is the request variable that receives the value.
- A Collection connector (category Generated) exposes `ListAll`, `GetRecord`, `CreateRecord`, `UpdateRecord`, `DeleteRecord`, `TruncateCollection`.

## Validation

See [verify.md](verify.md). Category exists. Resource has method and URL. Outbound keys match the resource. Response mapping has a request variable. Do not start a request unless asked.

## Minimal example

`POST /api/1.0/data_sources`. Replace `CATEGORY_ID`. Full resource shape: [examples/rest-get.json](examples/rest-get.json).

```json
{
  "name": "List Universities",
  "description": "Public university search",
  "type": "REST",
  "authtype": "NONE",
  "status": "ACTIVE",
  "data_source_category_id": "CATEGORY_ID",
  "endpoints": {
    "listUniversities": {
      "purpose": "listUniversities",
      "method": "GET",
      "url": "http://universities.hipolabs.com/search",
      "params": [{ "key": "country", "value": "{{country}}", "required": true }],
      "headers": [],
      "body": ""
    }
  }
}
```

Create and edit steps: [create-edit.md](create-edit.md).
