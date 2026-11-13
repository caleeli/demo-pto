# Gateways and sequence flows (PM3 BPMN)

Use with [pm3-process](SKILL.md) for graph changes and [pm3-expressions](../pm3-expressions/SKILL.md) for `@@` condition syntax. Classic Process Map has no BPMN gateways — use [pm3-classic-process](../pm3-classic-process/SKILL.md).

PM3 BPMN Designer solution work uses **Exclusive (XOR)**, **Parallel (AND)**, and **Inclusive (OR)** gateways. An `EVENTBASED` gateway type exists in storage/adapters but is **not** a Designer palette tool for PM4-style message-vs-timer races; if present it is mapped like exclusive evaluation (`EVALUATE`), not as a competing-catch race.

## Choose a gateway

| Need | Type | Outgoing behavior | Join behavior |
|------|------|-------------------|---------------|
| Choose exactly one route from case data | Exclusive | First matching routing rule (list order), or default flow | Pass-through merge; does **not** synchronize parallel work |
| Always run every branch | Parallel | All outgoing paths; **no** conditions | Wait until **all** incoming parallel paths complete |
| Run one or more routes from case data | Inclusive | Every rule whose condition is true, or default if none | Wait until **selected** (true) paths complete |

## Direction and topology

- A gateway is **diverging** (one in, many out) or **converging** (many in, one out). It must **not** be both on the same node.
- Close threads with a converging gateway of the **same type**: Exclusive–Exclusive, Parallel–Parallel, Inclusive–Inclusive. Mixing types at split/join causes runtime errors.
- Do **not** build loops made only of gateways; place a task, event, or other element between gateway hops.
- Gateway-to-gateway is allowed when nest/close order is correct (like matched brackets). Prefer simple topologies for production processes.

## Configure routing rules (Exclusive / Inclusive)

Right-click gateway → **Properties**:

| UI control | Meaning |
|------------|---------|
| Add Routing Rule | New outgoing path |
| Next Task | Target task, subprocess, end event, or (when supported) another element already on the map |
| Condition | `@@` / `@#` expression; required for non-default Exclusive/Inclusive rules |
| [@@] | Insert a process/case variable into the condition |
| Rule order | Drag to change evaluation priority |

Conditions use [pm3-expressions](../pm3-expressions/SKILL.md). Numeric fields often use `@#amount`; string compares use `@@VAR == 'value'`.

### Exclusive: first match wins

1. Place the Exclusive Gateway after the step that sets decision variables.
2. Order rules so the **most specific** condition is evaluated first. Later rules that share a prefix never run once an earlier rule matches.
3. Ensure at least one path always applies, or set a **Default Flow**.
4. Do not use an Exclusive merge to join Parallel/Inclusive branches — downstream work can run more than once.

Example (specific rule first):

```
@@decision == 'yes' && @@contractType == 'renewing'   → Renew path
@@decision == 'yes'                                   → Approve path
@@decision == 'no'                                    → Reject path
```

### Inclusive: all matching routes

1. Conditions may overlap; multiple true rules open concurrent paths.
2. Pair with a converging Inclusive Gateway that waits for the paths that actually opened.
3. If a selected parallel case is cancelled, deleted, or never completed, the converging Inclusive join may never proceed.
4. Set a Default Flow when none of the conditions may match.

### Parallel: no conditions

1. Diverging Parallel opens all outgoing branches; do not attach conditions or a default for selection.
2. Converging Parallel waits for **every** incoming parallel path.
3. Do not feed a Parallel join from Exclusive branches that are mutually exclusive — the join can wait forever.
4. Asynchronous subprocess after a Parallel Gateway is **not** supported by the engine; keep async children sequential instead.
5. Cancelling or abandoning one parallel path can leave the converging Parallel stuck.

## Default Flow

Available on Exclusive and Inclusive when the gateway already has outgoing connections:

1. Right-click gateway → **Default Flow** → choose target element (backslash marker on the flow).
2. That path no longer needs a condition in Properties (condition field disabled/cleared).
3. Clear with **Default Flow → None**.

Default is the fallback when **all** non-default conditions are false — not an “always also run” path.

## Engine-supported connections (gateways)

Documented executable patterns:

- Gateway ↔ Task
- Gateway ↔ synchronous Sub-process
- Gateway → End Event (empty, email, or send message)
- Intermediate Event → Gateway

**Not** supported for execution:

- Start Event (any) → Gateway
- Exclusive / Inclusive / Parallel Gateway → Intermediate **Email** Event

When a design needs email (or another intermediate event) after a decision, prefer **Gateway → Task → Event** (or another supported sequence). Do not rely on Gateway → Intermediate Email Event even if the canvas allows drawing it.

## Validation checklist

1. Diverging and converging gateways are separate nodes of the same type when synchronization is required.
2. Exclusive/Inclusive rules reference variables set before the gateway.
3. Exclusive rule order matches intended specificity; Inclusive overlaps are intentional.
4. Default Flow exists when “no match” is possible.
5. No gateway-only loops; no Start→Gateway; no Gateway→Intermediate Email Event.
6. Parallel/Inclusive joins cannot be starved by cancelled or unreachable branches.

## Common mistakes

- Mixing Parallel split with Exclusive/Inclusive join (or the reverse)
- Putting the general Exclusive condition above the specific one
- Missing Default Flow → case stuck when all conditions false
- Using Exclusive merge after Parallel work → duplicate downstream activation
- Designing a PM4-style Event-Based “message OR timer race” — not a supported PM3 Designer pattern; model waits with Intermediate Events on supported paths instead
- Editing gateway rules on a classic-map process

## Official references

- [Gateways (PM3 wiki)](https://wiki.processmaker.net/3.1/Gateways)
- Condition syntax: [pm3-expressions](../pm3-expressions/SKILL.md)
