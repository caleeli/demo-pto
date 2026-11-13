# Decision Task BPMN attributes

Namespace `pm` (`http://processmaker.com/BPMN/2.0/Schema.xsd`).

The element **must** be `bpmn:serviceTask` with `implementation="package-decision-engine/decision-table"`. Copy the string exactly. Not `bpmn:task` (that is a Form Task), not `bpmn:scriptTask`, and not `bpmn:businessRuleTask`. With any other element or implementation string, the engine never runs the table and Modeler opens the node as a generic Service Task.

## Attributes

| Attribute | Value |
|-----------|-------|
| `id` | Node id, unique in the diagram (`node_12`) |
| `name` | Action-oriented, e.g. `Calculate risk` |
| `implementation` | `package-decision-engine/decision-table` |
| `pm:config` | JSON string, schema below |

Modeler node id: `processmaker-decision-engine-tables`. Package: `package-decision-engine`.

## `pm:config` schema

All three keys are **required**, even when empty (`[]`). The runtime reads both mapping lists without null checks, so a missing key fails the token.

```json
{
  "decisionTables": [
    {
      "id": 7,
      "name": "Risk Rules",
      "hitPolicy": "FIRST",
      "executeConditionally": false,
      "condition": ""
    }
  ],
  "decTableOutboundMappings": [
    { "requestVar": "form_amount", "tableVar": "amount" }
  ],
  "decTableResponseMappings": [
    { "tableOutVar": "risk", "requestVar": "risk_level" }
  ]
}
```

### `decisionTables[]`

Tables run **in array order**. The outputs of each table are merged into the data before the next one runs, so a later table can use an earlier table's output as an input. The merge uses the **mapped** name, so don't rename an output that a later table reads.

| Key | Notes |
|-----|-------|
| `id` | Numeric Decision Table id. Look it up ([asset-map.md](asset-map.md#lookups-never-invent-ids)), never guess. |
| `name` | Display only (the inspector copies the table title). Not used at runtime. |
| `hitPolicy` | `FIRST` (default), `UNIQUE`, `ANY`, `RULE ORDER`, `SUM`, `MIN`, `MAX`, `COUNT`. See [hit-policies.md](hit-policies.md). **This is the only place the hit policy is set.** The stored DMN always says `FIRST`. |
| `executeConditionally` | `false` = always run. `true` = run only when `condition` is truthy. |
| `condition` | ProcessMaker expression over request data (the docs' "FEEL Expression Syntax", Symfony-based), **not** DMN FEEL: `amount > 1000`, `tier == "gold"` (`==`, not `=`). A missing variable fails the token. Empty string with `executeConditionally: true` still runs. See [architecture.md](architecture.md#conditions-and-formulas-are-not-dmn-feel). |

### `decTableOutboundMappings[]` (request → table input)

| Key | Notes |
|-----|-------|
| `requestVar` | Request data variable to read. |
| `tableVar` | DMN input name it feeds. |

An input with no mapping is read from a request variable **with the same name**. If that variable is missing, the token fails with `The variable 'x' does not exist in the request data`.

### `decTableResponseMappings[]` (table output → request)

| Key | Notes |
|-----|-------|
| `tableOutVar` | DMN output name. |
| `requestVar` | Request variable to write. |

An output with no mapping is written under the **DMN output name**. Mapping an output to a different name currently loses its boolean/number cast (see [hit-policies.md](hit-policies.md#known-issue-mapped-outputs-lose-their-type)).

## XML example

`pm:config` is a JSON string inside an attribute, so quotes are escaped:

```xml
<bpmn:serviceTask id="node_2" name="Calculate risk"
  implementation="package-decision-engine/decision-table"
  pm:config="{&#34;decisionTables&#34;:[{&#34;id&#34;:7,&#34;name&#34;:&#34;Risk Rules&#34;,&#34;hitPolicy&#34;:&#34;FIRST&#34;,&#34;executeConditionally&#34;:false,&#34;condition&#34;:&#34;&#34;}],&#34;decTableOutboundMappings&#34;:[{&#34;requestVar&#34;:&#34;form_amount&#34;,&#34;tableVar&#34;:&#34;amount&#34;}],&#34;decTableResponseMappings&#34;:[{&#34;tableOutVar&#34;:&#34;risk&#34;,&#34;requestVar&#34;:&#34;risk_level&#34;}]}">
  <bpmn:incoming>node_1-node_2</bpmn:incoming>
  <bpmn:outgoing>node_2-node_3</bpmn:outgoing>
</bpmn:serviceTask>
```

`&quot;` works as well as `&#34;`. Full diagram: [examples/decision-task.bpmn.xml](examples/decision-task.bpmn.xml).

## How the inspector writes it

The Modeler inspector (`package-decision-engine/resources/js/connectors/decision-tables/index.js`) edits three virtual fields, `tables_config`, `outbound_config`, `response_config`, and merges each into the single `config` JSON. Editing in Designer and editing the XML produce the same shape.

When the inspector opens, it **drops entries whose `id` no longer exists** on the instance. Saving then removes them from the BPMN. If a table disappears from a Decision Task after opening it in Designer, the table was deleted or the id came from another instance.

The official docs say deleting a table does not affect processes that use it. In the code it does: the inspector drops the entry, process export fails (`findOrFail`), and the runtime cannot load the table.

## Other node settings

Per the official Decision Task docs, the node also has the standard **Loop Activity** panel (Loop, Multi-instance parallel or sequential) and accepts **boundary events**. A table error (no match, missing variable, engine error) fails the token, so attach a Boundary Error Event when the process should route around a failure instead of stopping.
