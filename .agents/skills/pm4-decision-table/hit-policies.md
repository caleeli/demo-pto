# Hit policies

Set per table in `pm:config.decisionTables[].hitPolicy` ([config-attrs.md](config-attrs.md)). The stored DMN always has `hitPolicy="FIRST"`; the runtime swaps it in before execution. Do not change the hit policy inside the DMN.

For previews, pass it as the `hp` query parameter: `POST /api/1.0/decision_tables/{id}/execute?hp=RULE%20ORDER`.

| Value | Inspector label | Sent to engine | Result |
|-------|-----------------|----------------|--------|
| `FIRST` | Single - First (F) | `FIRST` | Output of the first matching rule (top to bottom) |
| `UNIQUE` | Single - Unique (U) | `UNIQUE` | Output of the only matching rule. More than one match is an error |
| `ANY` | Single - Any (A) | `ANY` | Output if all matching rules agree. Conflicting outputs are an error |
| `RULE ORDER` | Multiple - Rule Order (R) | `RULE ORDER` | List of outputs of all matching rules, in rule order |
| `SUM` | Multiple - Collect Sum (C+) | `COLLECT` + `aggregation="SUM"` | Number |
| `MIN` | Multiple - Collect Min (C<) | `COLLECT` + `aggregation="MIN"` | Number |
| `MAX` | Multiple - Collect Max (C>) | `COLLECT` + `aggregation="MAX"` | Number |
| `COUNT` | Multiple - Collect Count (C#) | `COLLECT` + `aggregation="COUNT"` | Number |

Any other value falls back to `FIRST`.

## How results are cast (`formatValue`)

- **FIRST, UNIQUE, ANY:** cast by the output's DMN `typeRef`. `boolean` → `true`/`false`, `number` → int/float, anything else → string.
- **SUM, MIN, MAX, COUNT:** always cast to a number. COUNT counts matching rules, so it works with any output type. SUM, MIN, and MAX need a numeric output: on a string output, SUM fails in the engine (`Cannot invoke "Object.toString()"...`), and MIN and MAX return a **500** (`Unsupported operand types: string + int`). The official docs say MIN/MAX accept strings, dates, and times; in this code they only work on numbers.
- **RULE ORDER:** JSON-decoded if the engine returns valid JSON. Otherwise a `[a, b]` string is split on `, ` into a list of strings. A value that itself contains `, ` is split incorrectly: rules returning `"a, b"` and `"c"` give `["a", "b", "c"]`.

**Multiple-result policies work only with one output column.** With two or more outputs, RULE ORDER fails with `Drools response does not have a value for output variable '...'`, and SUM returns a 500. Split the outputs into separate tables.

**No matching rule is an error**, not a null. The engine returns `WARN: No rule matched for decision table 'default' and no default values were defined...`, which becomes `Table Decision evaluation had errors: ...` and fails the token (400 on `/execute`). Add a catch-all last rule (`-` in every input) when using `FIRST`, unless failing is intended.

A `UNIQUE` table with more than one match fails with `... Evaluation failed. Status: FAILED`.

## Choosing one

| Need | Use |
|------|-----|
| Ordered rules, first match wins, catch-all at the bottom (`-` in every input) | `FIRST` |
| Rules must not overlap, and overlap should fail loudly | `UNIQUE` |
| Overlap allowed if outputs agree | `ANY` |
| Every matching output, e.g. a list of flags | `RULE ORDER` |
| Score by adding points per matching rule | `SUM` |
| How many rules matched | `COUNT` |

## Known issue: mapped outputs lose their type

`parseResult()` looks up the output type by the **mapped request variable name** instead of the DMN output name. When `decTableResponseMappings` maps `risk` → `risk_level`, the lookup misses and the value is cast as a string. `true` arrives as `"true"`, and `10` arrives as `"10"`.

Until it is fixed:

- Leave outputs unmapped (write under the DMN output name) when downstream logic needs a real boolean or number, or
- Name the request variable the same as the DMN output, or
- Compare as strings downstream (`risk_level == "true"`).

Aggregator policies are unaffected because they always cast to a number.
