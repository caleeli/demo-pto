---
name: pm4-decision-table
description: Configure ProcessMaker Decision Tasks and the Decision Tables they run (package-decision-engine) — pm:config, input/output mappings, hit policies, DMN rules, Collection lookups, Excel/CSV import/export, and /execute previews. Use when adding or editing a Decision Task, creating or importing a Decision Table, choosing a hit policy, or debugging a Decision Task that fails or returns wrong values. Do not use for Form Tasks, to scaffold a demo process, or to start requests unless the user asked.
---

# Configure a Decision Task

A **Decision Task** is a `bpmn:serviceTask` with `implementation="package-decision-engine/decision-table"`. It runs one or more **Decision Tables** (DMN stored in the database) on the queue worker, and writes their outputs into request data. Not `bpmn:task` (Form Task), not `scriptTask`, not `businessRuleTask`.

Three things must agree: the node's `pm:config`, the stored table (DMN), and the request data. Most failures are silent until run time. The **hit policy is set in `pm:config`, never in the DMN.**

This skill works on an **existing** process and instance. It needs `package-decision-engine` installed. Do not create a demo process, start a request, or complete a task unless the user asked.

References: [config-attrs.md](config-attrs.md) (node schema), [architecture.md](architecture.md) (runtime), [hit-policies.md](hit-policies.md), [collections.md](collections.md) (Collection lookups), [excel.md](excel.md) (Excel/CSV), [asset-map.md](asset-map.md) (endpoints, files), [verify.md](verify.md) (checks, failures). Examples: [examples/](examples/).

User docs: [Decision Task](https://docs.processmaker.com/docs/decision-task-connector), [Decision Tables](https://docs.processmaker.com/docs/decision-tables). Where they disagree with these files (datetime type, MIN/MAX on strings, Magic Variables, deleted tables), trust these files: they were checked against the code and a running instance.

## Not this skill

| User wants | Use instead |
|------------|-------------|
| Human task with a screen (Form Task / User Task / Manual Task) | [pm4-form-manual-task](../pm4-form-manual-task/SKILL.md) |
| Generic 422 / PMQL / pagination on other endpoints | [pm-api-contracts](../pm-api-contracts/SKILL.md) |
| Local instance down, queue worker not running | [setup-processmaker](../setup-processmaker/SKILL.md) |
| Editing the decision engine's Vue UI | [vue2-pm4-patterns](../vue2-pm4-patterns/SKILL.md) |
| Editing the decision engine's PHP | [laravel-pm4-patterns](../laravel-pm4-patterns/SKILL.md) |

## Before changing anything

Ask only if missing:

1. **Process**: existing name or id.
2. **Table(s)**: existing name, or the rules to create one (inputs, outputs, types, rules).
3. **Inputs**: which request variable feeds each table input, when names differ.
4. **Outputs**: which request variable receives each output, when names differ.
5. **Hit policy**: default `FIRST`; see [hit-policies.md](hit-policies.md).
6. **Condition**: only if the table should run conditionally (ProcessMaker expression syntax, `==` not `=`; see [architecture.md](architecture.md#conditions-and-formulas-are-not-dmn-feel)).

Then look up real ids ([asset-map.md](asset-map.md#lookups-never-invent-ids)). **Never invent table, category, collection, or record ids.**

## Required (every Decision Task)

1. **Element and implementation**: `bpmn:serviceTask`, `implementation="package-decision-engine/decision-table"`, copied exactly.
2. **`pm:config` with all three keys**: `decisionTables`, `decTableOutboundMappings`, `decTableResponseMappings`, with `[]` when empty.
3. **`decisionTables[]`**: `{id, name, hitPolicy, executeConditionally, condition}`, with the `id` looked up. Tables run in array order; each one's outputs feed the next.
4. **Every table input resolvable**: a request variable with the same name, an outbound mapping, a formula, or an earlier table's output. Otherwise the token fails.

## Creating or changing a table

- Create: `POST /api/1.0/decision_tables` with `name`, `description`, `decision_table_category_id`.
- Rules: `PUT /api/1.0/decision_tables/{id}` with `definition_arrays` ([examples/definition-arrays.json](examples/definition-arrays.json)). Prefer this to hand-writing DMN. **Always include `inputLabels` and `outputLabels`**, or output types are lost.
- Rule cells are FEEL: `"gold"` (quoted strings), `> 1000`, `[1..10]`, `-` (any), `contains(?, "x")` (`?` is the column value). Never leave a cell empty. `{{var}}` inserts request data; of the magic variables only `{{_request_id}}`, `{{_task_name}}`, `{{_task_user_id}}` work. End `FIRST` tables with a catch-all row: no match is an error.
- Column types: `string`, `number`, `boolean`, `date`, `time`, or `formula`. Never `datetime` ([architecture.md](architecture.md#column-types)). RULE ORDER, SUM, MIN, and MAX need a single output column ([hit-policies.md](hit-policies.md)).
- From a spreadsheet: use the upload flow in [excel.md](excel.md), never the legacy `excel-import` (it quotes every cell).
- Values from a Collection: [collections.md](collections.md).

## Workflow

```
- [ ] 1. Confirm it is a Decision Task (serviceTask + exact implementation), not a Form/Script Task
- [ ] 2. Look up table ids (and collection/record ids if rules use lookups). Stop if missing
- [ ] 3. Create or update the table if asked (definition_arrays, labels included)
- [ ] 4. Preview the table: POST /api/1.0/decision_tables/{id}/execute?hp=<policy>
- [ ] 5. Set pm:config: tables (order, hitPolicy, condition), outbound and response mappings
- [ ] 6. Check each input resolves and boolean/number outputs are not renamed (hit-policies.md known issue)
- [ ] 7. Save + Publish if editing in Designer (drafts are not live)
- [ ] 8. Static check (verify.md). Do not start a request unless asked
```

## Done when (static)

```
- [ ] serviceTask with implementation="package-decision-engine/decision-table"
- [ ] Name is action-oriented, not the default "Decision Task"
- [ ] pm:config has all three keys; every table id exists on this instance
- [ ] hitPolicy set per table in pm:config
- [ ] Every table input resolves; outputs land where downstream steps read them
- [ ] /execute returns the expected outputs for at least one input per rule
- [ ] Collection lookups resolve on this instance
```

When moving a process to another instance, collections referenced in rules are **not** exported with it. Follow the checklist in [collections.md](collections.md#moving-a-process-to-another-instance).
