# Check a Decision Task

This is a **static** check plus a table preview. Do not start a request or complete a task unless the user asked.

## Static checklist (the node)

See [config-attrs.md](config-attrs.md).

```
- [ ] Element is bpmn:serviceTask
- [ ] implementation="package-decision-engine/decision-table" (exact)
- [ ] name is action-oriented, not the default "Decision Task"
- [ ] pm:config has all three keys: decisionTables, decTableOutboundMappings, decTableResponseMappings ([] when empty)
- [ ] Every decisionTables[].id exists on this instance (GET /api/1.0/decision_tables/{id})
- [ ] hitPolicy is set on each entry, not edited inside the DMN
- [ ] executeConditionally + condition only where intended; condition uses ProcessMaker syntax (==, and, or), not DMN FEEL, and only variables that always exist
- [ ] Every DMN input is either a request variable with the same name, an outbound mapping, a formula, or an output of an earlier table (under its mapped name)
- [ ] No datetime / date/time column types; no empty rule cells
- [ ] Multiple-result policies (RULE ORDER, SUM, MIN, MAX) only on single-output tables
- [ ] Outputs needed downstream as boolean/number are unmapped or mapped to the same name (hit-policies.md known issue)
- [ ] Every {{var}} in the DMN will be present and non-null
- [ ] Every {{#_collection}} lookup resolves on this instance (collections.md)
- [ ] FIRST tables end with a catch-all rule, unless no-match should fail
```

In Designer: Save, then Publish. Drafts are not live.

## Preview a table (no request needed)

```
POST /api/1.0/decision_tables/{id}/execute?hp=FIRST
Content-Type: application/json

{ "amount": 1500, "customer_tier": "gold" }
```

- The body keys are the **DMN input names**, since preview applies no mappings.
- `hp` is the hit policy, the same values as `pm:config` (URL-encode `RULE%20ORDER`).
- 200: an object of DMN output names to values, e.g. `{"risk": "medium"}`.
- 400: the error text (engine error, missing variable, lookup error).

Collection lookups and formulas run exactly as in a request. Preview does not test mappings, conditions, or table chaining, so check those statically.

Try at least one input per rule, plus one that should hit the catch-all.

## Failures

| Symptom | Likely cause |
|---------|--------------|
| Node opens as a plain Service Task, no table inspector | `implementation` not exact, or the decision engine's modeler script didn't load (package not installed, or assets not published) |
| Table vanished from the node after opening in Designer | Table id doesn't exist on this instance; the inspector drops it on open |
| Token fails reading mappings after XML edit | A `pm:config` key is missing; keep all three, even as `[]` |
| `The variable 'x' does not exist in the request data` | DMN input not in data and not mapped |
| `Variable 'x' does not exists or is empty.` | `{{x}}` in the DMN, and `x` is missing or null |
| `Table Decision evaluation had errors: WARN: No rule matched...` | No rule matched; add a catch-all or fix the inputs |
| `Table Decision evaluation had errors: ... Status: FAILED` | UNIQUE/ANY conflict, invalid FEEL in a cell, or malformed DMN |
| `... Unable to create KieModule ...` | The DMN doesn't compile: an empty rule cell, a `datetime`/`date/time` column, or an unquoted string lookup (architecture.md column types, excel.md, collections.md) |
| `Unexpected character "=" ... for expression` | DMN FEEL `=` used in a node condition or formula; use `==` |
| `Undefined variable "x" ... for expression` | A condition or formula references a missing variable, e.g. an output of a table that was skipped |
| `The variable 'x' does not exist...` in the second table only | The first table's output `x` is renamed by a response mapping; chained tables see the mapped name |
| `Drools response does not have a value for output variable` | RULE ORDER on a table with more than one output |
| 500 `Unsupported operand types: string + int` | MIN/MAX on a string output, or SUM on a table with several outputs |
| `Variable '_request' does not exists or is empty.` (or `_user`) | `{{_request.id}}` / `{{_user.id}}` in a cell; only `{{_request_id}}`, `{{_task_name}}`, `{{_task_user_id}}` exist at run time |
| `contains(...)` rule never matches | Written as `contains("{{var}}", "x")`; use `contains(?, "x")` |
| Boolean input `"false"` matches `true` rules | The request holds the string `"false"`; PHP truthiness turns it into `true`. Send a real boolean |
| Date rules never match, no error | The value isn't ISO `YYYY-MM-DD`, or the column is `date and time` |
| `Table Decision evaluation had errors:` with nothing after it | The engine printed nothing, usually because Java is not installed or not on `PATH` on the queue worker host |
| Hit policy ignored, always behaves as FIRST | Policy set in DMN instead of `pm:config`, or hand-written DMN without ` hitPolicy="FIRST" ` |
| Mapped boolean/number arrives as a string | Mapped outputs lose their type (hit-policies.md) |
| Boolean/number output is a string even unmapped | DMN output has no `typeRef`: saved without `outputLabels` (excel.md) |
| `Collections package not installed in the server.` / `Collection 'X' not exist.` / `Record 'N' ... not exist.` | See collections.md errors |
| Works locally, fails after import elsewhere | Collections not moved, records not included, or collection renamed on import |
| Process export fails with ModelNotFound | A referenced table id was deleted |
| 422 on `POST /decision_tables` | Name or description has characters outside letters, digits, spaces, `-` `_` `.` `'`; name not unique; or description/category missing. The message says `may only contain alphanumeric characters`, but the real rule is the list above: parentheses, commas, and `:` are rejected |
| 412 on Excel upload | Layout mismatch: `Inputs` not in B, `Description` not last, invalid variable name, or type count (excel.md) |
| 400 `The file format doesn't match...` on Excel upload | Blank template uploaded unchanged (empty type row) |
| `> 100` compares as text after Excel import | Legacy `excel-import` wrapped the cell in quotes; use the upload flow |

Fix the node or the table. Do not create a sample process to prove the config unless the user asked.
