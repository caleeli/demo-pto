# Wire a Data Connector node

The process graph (Start, flows, End) is [pm4-process](../pm4-process/SKILL.md). This file is only the Data Connector node.

Element: `bpmn:serviceTask`. Implementation, copied exactly:

```
package-data-sources/data-source-task-service
```

`pm:config` is one JSON string.

| Key | Value |
|-----|--------|
| dataSource | Numeric connector id from [lookup.md](lookup.md) |
| endpoint | Resource `purpose` (the `endpoints` key) |
| type | `REST` |
| outboundConfig | Array. One object per param, header, or body property you send |
| dataMapping | Array. Where the response is written in request data |
| callback | `false` unless the user asked for a callback |

`callback: true` makes the modeler replace this Service Task with a Call Activity. Leave it false here.

## Outbound

```json
{ "type": "PARAM", "key": "country", "value": "{{country}}", "format": "mustache" }
```

`type` is `PARAM`, `HEADER`, or `BODY`. `key` is the property name on the resource. `value` is `{{requestVariable}}`, or a literal string if it has no braces. `format` is `mustache`.

A `key` that the resource does not declare is not sent. Map every `required` param and header.

## Response

```json
{ "key": "universities", "value": "", "format": "dotNotation" }
```

`key` is the request variable that receives the data. `value` is the path inside the response (`dotNotation`). An empty `value` stores the whole response in that variable. The value `$status` stores the HTTP status code.

```json
{ "key": "httpStatus", "value": "$status", "format": "dotNotation" }
```

`type` is `REST` here. A node saved by the modeler can have it empty; both run.

## Error handling

Optional `pm:errorHandling` on the node: `timeout`, `retry_attempts`, `retry_wait_time`.

## Example node

Replace `DATA_SOURCE_ID`. Connect `incoming` and `outgoing` in the process.

```xml
<bpmn:serviceTask id="node_2" name="List universities"
  implementation="package-data-sources/data-source-task-service"
  pm:config="{&#34;dataSource&#34;:DATA_SOURCE_ID,&#34;endpoint&#34;:&#34;listUniversities&#34;,&#34;type&#34;:&#34;REST&#34;,&#34;outboundConfig&#34;:[{&#34;type&#34;:&#34;PARAM&#34;,&#34;key&#34;:&#34;country&#34;,&#34;value&#34;:&#34;{{country}}&#34;,&#34;format&#34;:&#34;mustache&#34;}],&#34;dataMapping&#34;:[{&#34;key&#34;:&#34;universities&#34;,&#34;value&#34;:&#34;&#34;,&#34;format&#34;:&#34;dotNotation&#34;}],&#34;callback&#34;:false}">
  <bpmn:incoming>flow_1</bpmn:incoming>
  <bpmn:outgoing>flow_2</bpmn:outgoing>
</bpmn:serviceTask>
```

## Callback variant (async)

Use only when the user needs the external API to call back later. Otherwise use the Service Task above.

| | Service Task (sync) | Call Activity (callback) |
|--|--|--|
| Modeler id | `data-source-task-service` | `data-source-call-activity` |
| BPMN type | `bpmn:serviceTask` | `bpmn:callActivity` |
| Marker | `implementation="package-data-sources/data-source-task-service"` | `calledElement="data_source_callback-data_source_sub_process"` |

Setting `callback: true` in the modeler replaces the node type. In hand-written BPMN, use the Call Activity marker. A plain Service Task with `callback: true` does not work.

Extra `pm:config` keys when `callback` is `true`:

| Key | Notes |
|-----|-------|
| `callback_url` | Request variable that receives the callback URL |
| `callback_variable` | Request variable where the callback payload is stored |
| `callback_methods` | Array, for example `["POST"]`. Pairs with `callback_data_types` |
| `callback_data_types` | Array, for example `["FORM"]` |
| `callback_authentication` | `null` or `basic`, with `callback_authentication_username` and `callback_authentication_password` |
| `callback_whitelist` | IP allow list. Empty allows all |

```xml
<bpmn:callActivity id="node_dc_cb" name="Data Connector"
  calledElement="data_source_callback-data_source_sub_process"
  pm:config="{&#34;dataSource&#34;:DATA_SOURCE_ID,&#34;endpoint&#34;:&#34;asyncJob&#34;,&#34;dataMapping&#34;:[],&#34;outboundConfig&#34;:[],&#34;callback&#34;:true,&#34;callback_url&#34;:&#34;callback_url&#34;,&#34;callback_variable&#34;:&#34;callback&#34;,&#34;callback_methods&#34;:[&#34;POST&#34;],&#34;callback_data_types&#34;:[&#34;FORM&#34;],&#34;callback_authentication&#34;:null,&#34;callback_whitelist&#34;:[]}">
  <bpmn:incoming>flow_1</bpmn:incoming>
  <bpmn:outgoing>flow_2</bpmn:outgoing>
</bpmn:callActivity>
```

Every `sequenceFlow` must also appear as `<bpmn:incoming>` and `<bpmn:outgoing>` on its nodes. See [pm4-process](../pm4-process/SKILL.md).

After a Designer edit, Save + Publish the process. Draft BPMN is not live.

Screens and watchers that call a connector belong to [pm4-screen](../pm4-screen/SKILL.md).
