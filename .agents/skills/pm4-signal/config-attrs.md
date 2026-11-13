# Signal event BPMN attributes

Namespace `pm` (`http://processmaker.com/BPMN/2.0/Schema.xsd`).

Every signal event is a standard BPMN event with **one** `<bpmn:signalEventDefinition signalRef="...">` child. `signalRef` is the signal **id**, and a `<bpmn:signal id="...">` with that id must exist in the same `<bpmn:definitions>`.

## Node types

| Modeler node | Element | Role | Payload setting |
|--------------|---------|------|-----------------|
| Signal Start Event | `bpmn:startEvent` | Catch: starts a new request | Request Variable (`pm:config` on the event) |
| Intermediate Signal Catch Event | `bpmn:intermediateCatchEvent` | Catch: waits in a running request | Request Variable (`pm:config` on the event) |
| Boundary Signal Event | `bpmn:boundaryEvent` (`cancelActivity` true/false) | Catch: interrupts or branches from an activity | **None in the inspector**. Payload merges into the request root unless you add `pm:config` by hand; the runtime honors it (verified). Whether the Modeler keeps it on save is unverified, so check the XML after editing in Designer |
| Intermediate Signal Throw Event | `bpmn:intermediateThrowEvent` | Throw, then continue | Signal Payload (`pm:config` JSON on the definition) |
| Signal End Event | `bpmn:endEvent` | Throw, then end | Signal Payload (`pm:config` JSON on the definition) |

The two `pm:config` attributes are **different**:

- **Throw**: a JSON string on `bpmn:signalEventDefinition`.
- **Catch**: a plain variable name on the **event element** (`startEvent` / `intermediateCatchEvent` / `boundaryEvent`), not on the definition.

## Throw payload

`pm:config` on the throw's `signalEventDefinition`, written by core's `SignalPayload.vue` inspector:

```json
{ "payload": [ { "id": "REQUEST_VARIABLE", "variable": "order", "expression": "" } ] }
```

Only `payload[0]` is read (`WorkflowManagerDefault::throwSignalEventDefinition`):

| `id` | Sends | Inspector label |
|------|-------|-----------------|
| `ALL_REQUEST_DATA` | The whole request data | All Request Data (default) |
| `NONE` | `{}`. A catch with a Request Variable still gets that variable set to `[]` | No Request Data |
| `REQUEST_VARIABLE` | `{ <variable>: <value> }`. A dotted `variable` (`order.total`) keeps its path: `{ "order": { "total": 10 } }` | Specify Request Variable |
| `EXPRESSION` | `{ <variable>: <result of expression> }`, evaluated with `FormalExpression` (ProcessMaker expression syntax, not DMN FEEL) over request data | **Not offered in the inspector**; set it in the XML |
| missing `pm:config`, or unknown `id` | The whole request data | |

With `REQUEST_VARIABLE` and an empty `variable`, the payload is `{}`, and the catcher's Request Variable becomes `[]`. The docs say that an empty Request Variable sends the whole request data; the code (`if ($payload->variable)`) and a running instance send nothing. To send everything, use `ALL_REQUEST_DATA`.

A `variable` that does not exist in the thrower's data is still sent, as `null`: `{ "missing_var": null }`.

Verified on a running instance, with thrower data `{ "order": { "total": 5 }, "secret": "x", "price": 2, "qty": 3 }` and a catch Request Variable `incoming`:

| Throw config | `incoming` on the new request |
|--------------|-------------------------------|
| `ALL_REQUEST_DATA` | `{ "order": { "total": 5 }, "secret": "x", "price": 2, "qty": 3 }` |
| `NONE` | `[]` |
| `REQUEST_VARIABLE` `order` | `{ "order": { "total": 5 } }` |
| `REQUEST_VARIABLE` `order.total` | `{ "order": { "total": 5 } }` |
| `REQUEST_VARIABLE` empty | `[]` |
| `EXPRESSION` `total` = `price * qty` | `{ "total": 6 }` |

`ALL_REQUEST_DATA` is the default, and it is usually wrong: every catcher that has no Request Variable gets all of the thrower's variables merged over its own. Send only what the catcher needs.

## Catch: where the payload lands

| Catch `pm:config` | Result |
|-------------------|--------|
| `pm:config="incoming"` | `data.incoming = <payload>` (the whole payload object) |
| absent or empty | Each top-level payload key is written to the root of request data, **overwriting** existing variables of the same name |

The inspector field is "Request Variable" (`requestVariableSettings`, `name: 'config'`). It exists only on the signal start event and the intermediate signal catch event.

## Signal elements

```xml
<bpmn:signal id="order_approved" name="Order approved" />
```

- `id`: must match `^[a-zA-Z_][\w.-]*$` (letters, digits, `_`, `.`, `-`, not starting with a digit), and be unique across the instance when created through the API. The Modeler checks the same pattern (`xsd:ID`) but only for uniqueness within the diagram.
- `name`: required, and a label only. Matching uses `id`.
- `detail`: optional description, only set through the API or the Signals page.

## How the Modeler writes it

`modeler/src/components/inspectors/SignalSelect.vue`:

- The dropdown lists global signals and signals from other processes (`GET /api/1.0/signals?pmql=id!=<this process>`), plus the signals already declared in this diagram.
- **Selecting a signal copies a `<bpmn:signal>` into this process's `rootElements`** if it is not there yet. That is how processes share a signal: each one declares the same id.
- The "+ Signal" button (on throw and boundary events) creates a **local** signal in this diagram only. To make it global, create it with `POST /api/1.0/signals` or on Designer > Signals.
- Removing a signal from the list only removes it from this diagram. It is refused while an event in the diagram still references it.
- Without the `view-signals` permission, the dropdown is disabled.

## XML examples

Throw only `order` (quotes in `pm:config` are escaped as `&#34;` or `&quot;`):

```xml
<bpmn:intermediateThrowEvent id="node_5" name="Notify order approved">
  <bpmn:incoming>node_4-node_5</bpmn:incoming>
  <bpmn:outgoing>node_5-node_6</bpmn:outgoing>
  <bpmn:signalEventDefinition signalRef="order_approved"
    pm:config="{&#34;payload&#34;:[{&#34;id&#34;:&#34;REQUEST_VARIABLE&#34;,&#34;variable&#34;:&#34;order&#34;,&#34;expression&#34;:&#34;&#34;}]}" />
</bpmn:intermediateThrowEvent>
```

Throw a computed value (`EXPRESSION`, XML only):

```xml
<bpmn:endEvent id="node_9" name="Send total">
  <bpmn:incoming>node_8-node_9</bpmn:incoming>
  <bpmn:signalEventDefinition signalRef="order_approved"
    pm:config="{&#34;payload&#34;:[{&#34;id&#34;:&#34;EXPRESSION&#34;,&#34;variable&#34;:&#34;total&#34;,&#34;expression&#34;:&#34;price * qty&#34;}]}" />
</bpmn:endEvent>
```

Start a request and store the payload in `incoming`:

```xml
<bpmn:startEvent id="node_1" name="On order approved" pm:config="incoming">
  <bpmn:outgoing>node_1-node_2</bpmn:outgoing>
  <bpmn:signalEventDefinition signalRef="order_approved" />
</bpmn:startEvent>
```

Interrupting boundary signal on a task:

```xml
<bpmn:boundaryEvent id="node_7" name="Order cancelled" attachedToRef="node_3" cancelActivity="true">
  <bpmn:outgoing>node_7-node_8</bpmn:outgoing>
  <bpmn:signalEventDefinition signalRef="order_cancelled" />
</bpmn:boundaryEvent>
```

Every referenced signal is declared once, under `<bpmn:definitions>` next to `<bpmn:process>`:

```xml
<bpmn:signal id="order_approved" name="Order approved" />
<bpmn:signal id="order_cancelled" name="Order cancelled" />
```

Full diagrams: [examples/](examples/).
