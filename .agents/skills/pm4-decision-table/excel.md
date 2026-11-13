# Excel and CSV import/export

There are **two** import endpoints with different layouts and behavior. Use the upload endpoint. The legacy one corrupts numeric and range rules.

| Endpoint | Saves? | Layout | Use |
|----------|--------|--------|-----|
| `POST /api/1.0/decision-tables/import-file/upload` (note the **hyphen**) | No, returns parsed arrays | Standard (below) | **Yes** |
| `POST /api/1.0/decision_tables/{id}/excel-import` | Yes, overwrites `definition` | Legacy | No |
| `POST /api/1.0/decision_tables/{id}/export-excel` | n/a | Standard | Export |

Template: `package-decision-engine/storage/Decision-Table-Format.xlsx` (also downloadable from the UI at `/designer/decision-tables/import-file/format/download`). Text example: [examples/decision-table-format.csv](examples/decision-table-format.csv).

The template is a blank form: its type row is empty, so uploading it unchanged returns 400 `The file format doesn't match...`. Fill in names, types, and rules first. An export from `export-excel` is a ready-to-edit starting point.

## Standard layout

Only the first sheet is read. Column A is ignored, apart from its role as a rule-number column.

| Row | Column A | Columns B... (inputs, then outputs) | Last column |
|-----|----------|-------------------------------------|-------------|
| 1 | empty | `Inputs` in **B**, empty cells for the other inputs, then `Outputs`, empty cells for the other outputs | empty |
| 2 | empty | Labels (display names) | `Description` |
| 3 | empty | Variable names, `^[a-zA-Z_]\w*$` | empty |
| 4 | `Type` | Types: `String`, `Number`, `Boolean`, `Date`, `Time` (case-insensitive). Avoid `Date/Time`: it becomes `date/time`, which the engine can't compile ([architecture.md](architecture.md#column-types)) | empty |
| 5+ | Rule number | FEEL rule cells | Rule description (may be empty) |

Rule cells are FEEL and are stored **as written**:

- strings need quotes: `"gold"` (in CSV: `"""gold"""`)
- numbers and ranges: `1000`, `> 1000`, `[1..10]`, `not(5)`
- any value: `-`
- booleans: `true` / `false`

Curly quotes (`“ ”`) are normalized to straight quotes. Empty rows after row 4 are skipped.

### Validation (412 `Invalid file format.`)

The upload returns 412 when any of these hold:

- `Inputs` is not in column B.
- `Description` is not the last column in row 2.
- The number of input and output columns differs from the number of valid variable names in row 3. **Invalid names (spaces, leading digits) are dropped silently**, so they surface as this count mismatch.
- It differs from the number of non-empty types in row 4.

**An empty rule cell is not caught.** A CSV with a blank rule cell uploads with 200 and the cell comes back as `""`. Saved as-is, the whole table stops compiling (`Unable to create KieModule` on every execution). Before saving, reject any rule with an empty cell and use `-` for "any".

Other responses: 400 `Invalid file type...` (only `xlsx`, `xls`, `csv`), 400 `The CSV file does not have a valid delimiter.` (CSV delimiters detected from the first line: `,` `;` `:` tab), 400 `The file format doesn't match the decision table format...` (parse exception).

Column comparisons are string-based, so tables wider than column Z may fail validation.

## Import with the API

1. **Upload and parse** (multipart, field `file`):
   ```
   POST /api/1.0/decision-tables/import-file/upload
   ```
   The 200 response looks like:
   ```json
   {
     "result": {
       "data": [
         ["Amount", "Customer Tier", "Risk"],
         ["amount", "customer_tier", "risk"],
         ["> 1000", "\"gold\"", "\"medium\"", "Large amount, gold customer"]
       ],
       "indexOfLastInput": 1,
       "dataTypes": ["number", "string", "string"]
     }
   }
   ```
   `data[0]` holds labels (without Description), `data[1]` holds variable names, and each further row is a rule with its description as the **last** element.

2. **Convert to `definition_arrays`**, the same way the table editor does (`TableEdit.vue` `getDataView()`). Let `n = indexOfLastInput + 1`:

   | Key | Value |
   |-----|-------|
   | `inputs` / `outputs` | `data[1]` split at `n` |
   | `inputLabels` / `outputLabels` | `data[0]` split at `n` (**always send**; see below) |
   | `inputTypes` / `outputTypes` | `dataTypes` split at `n` |
   | `rules` | each row of `data[2..]` without its last element |
   | `descriptions` | the last element of each row |
   | `formulas` | one per input: `{ "expression": "<input name>", "type": "<input type>" }` |

3. **Save**:
   ```
   PUT /api/1.0/decision_tables/{id}
   { "definition_arrays": { ... } }
   ```
   `DmnCreator` regenerates the DMN. The table must already exist (create it first with `POST /api/1.0/decision_tables`: `name`, `description`, `decision_table_category_id`).

4. **Preview** with `/execute` ([verify.md](verify.md)).

Send the labels. `DmnCreator` writes an output's `typeRef` **only when `outputLabels` has an entry for it**. Without labels, boolean and number outputs are treated as strings at run time.

In the UI, the table editor's file import runs the same upload. It asks for confirmation, then **replaces the whole table** in the editor with the file contents. The editor autosaves by default (`AUTOSAVE_DECISION_ENGINE_ENABLED`, 5 s delay), so the replacement is persisted within seconds and cannot be undone.

## Export

```
POST /api/1.0/decision_tables/{id}/export-excel
{ "definition_arrays": { inputs, outputs, inputLabels, outputLabels, inputTypes, outputTypes, rules, descriptions } }
```

- The file is built from the **request body**, not from the database. Without `definition_arrays` it returns a 500 (`Trying to access array offset on null`). To export the stored table, rebuild the arrays from the DMN first (inputs from `inputData`, outputs and rules from `decisionTable`), or export from the editor, which sends its current state.
- It returns `{ "url": "<app>/storage/DecisionTable_<uuid>.xlsx" }`. The file is written to `storage/app/public`, is publicly reachable by URL, and is not cleaned up. It needs `php artisan storage:link` for the URL to work.
- The output is in the standard layout, with a type dropdown in row 4.
- **Formula expressions are not exported.** A formula input comes back as a plain column, and on re-import its formula resets to the variable name.

Round trip: export, edit in a spreadsheet, upload, PUT `definition_arrays`, then `/execute`.

## Legacy `excel-import` (avoid)

`POST /api/1.0/decision_tables/{id}/excel-import` (`ExcelToDmn` + `TableExtractor`):

- It overwrites the table's `definition` immediately, with no format validation.
- **It wraps every rule cell in double quotes** (`addQuotes`), so `> 1000` becomes the string literal `"> 1000"` and `-` becomes `"-"`. Numeric comparisons, ranges, and wildcards stop working.
- Its layout differs: an optional first row labeling every column `Input`, `Output`, or `Description`. Without that row, the first column is the description and the last column is the only output.
- A standard-layout file (e.g. an `export-excel` download) is accepted with 200 but corrupts the table: labels become input names, the `Type` row becomes a rule, and `Description` becomes the only output.

Use it only for tables whose cells are all plain string equality. Otherwise use the upload flow.

## DMN files

The table editor also imports and exports `.dmn` files. The import is parsed in the browser and saved with the table, and the export downloads the stored `definition`. There is no API endpoint for DMN import. Use `PUT /api/1.0/decision_tables/{id}` with `definition` (raw XML) or `definition_arrays`.
