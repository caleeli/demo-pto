# Lookup instance ids (do not invent)

BPMN stores the **numeric** `flow_genie_id`. Genie config stores **numeric** subagent ids and RAG collection references. Guessing `1` or copying ids from another environment breaks execution.

Auth: session cookie or `Authorization: Bearer <token>`. Base URL: local site host (see [setup-processmaker](../setup-processmaker/SKILL.md)). Query contracts: [pm-api-contracts](../pm-api-contracts/SKILL.md).

All paths below are under `/api/1.0/`.

## FlowGenie

```
GET package-ai/flow_genies?filter=Summarize&per_page=15
GET package-ai/flow_genies?exclude=config
GET package-ai/flow_genies?pmql=name = "Summarize Invoice"
```

Pick the row whose **name** matches. Use that `id` as `pm:config.flow_genie_id`.

Full config:

```
GET package-ai/flow_genies/{id}
```

List excludes `config` by default unless removed from `exclude`.

## FlowGenie category (new genie)

```
GET package-ai/flow_genie_categories?status=ACTIVE&per_page=15
GET package-ai/flow_genie_categories?filter=Uncategorized
```

Use an `ACTIVE` category `id` on `POST package-ai/flow_genies`. Do not assume `1`.

## RAG collections (genie knowledge source)

Only fully analyzed collections work in Studio.

```
GET package-ai/rag_collections?status=COMPLETED&filter=HR%20Policies&per_page=15
```

Use the collection id/uuid in genie `config.collections`.

## Subagents

Subagents reference other genies by `flow_genie_id`. Look up each subagent the same way as the parent genie. Every subagent needs an `as_tool_description` (handoff description) or the parent may not delegate correctly.

## Process category (new process with a FlowGenie node)

```
GET process_categories?per_page=15
```

Use when creating a process via [pm4-process](../pm4-process/SKILL.md).

## Portable git refs

Some repos version BPMN with placeholder ids and resolve at deploy time. On the instance after save, `flow_genie_id` is numeric. When editing such a repo, verify the named genie exists on the instance.
