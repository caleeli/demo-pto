# Collection lookups in rule cells

A rule cell can read a value from a Collection record at run time. This is the only link between `package-decision-engine` and `package-collections`: the Collections package has no decision-table code.

Source: `DecisionTableImplementation::createDataClass()` (the `_collection` Mustache lambda) and `package-collections/src/Models/Collection.php` `record()`.

## Syntax

```
{{#_collection}}"Collection Name".<record id>.<field>{{/_collection}}
```

- It is a Mustache **section** (`{{#...}}...{{/...}}`), not a variable. `{{_collection ...}}` does not work.
- The collection name is wrapped in quotes. The parser strips the first character and the character before the dot.
- The lookup is replaced by the **raw value** before the DMN reaches the engine. It is not quoted for you:
  - number: `> {{#_collection}}"Limits".3.max_amount{{/_collection}}` becomes `> 5000`
  - string: `"{{#_collection}}"Tiers".1.name{{/_collection}}"` becomes `"gold"`
  - Without the outer quotes, a string value is not valid FEEL and the engine fails with `Unable to create KieModule`.
- It works in any DMN text: input entries and output entries.
- The table editor shows lookups with a database icon (`TdEdit.vue`).

## Rules

| Part | Rule |
|------|------|
| Collection name | Matched by **exact `name`**. Renaming the collection breaks every table that references it. |
| `<record id>` | The record's database **`id`**, not a row position. The editor hint says `row`, which is misleading. Must be numeric. |
| `<field>` | A **top-level** key of the record's `data`. Dotted paths (`address.city`) are not supported: the expression is split on its last two dots, so `"C".5.address.city` is read as collection `C".5` and fails. Use scalar values; arrays and objects do not render usefully. |

The lookup runs on **every execution**, including `/execute` previews, with no caching. It reads the model directly, so Collection permissions are not checked. Only users who can edit the table can add a lookup.

## Find the ids (never guess)

```
GET /api/1.0/collections?filter=Limits&per_page=15
GET /api/1.0/collections/{collectionId}/records?per_page=50
GET /api/1.0/collections/{collectionId}/records/{recordId}
```

Use the collection's exact `name` in the expression, and the record's `id`. Confirm the field exists in `data` on that record. The records API adds `id` to the `data` it returns, but `id` is not stored there, so `.<record id>.id` fails with `Field 'id' ... not exist.`

## Errors

All are `DataAccessException`, which fails the token (400 on `/execute`):

| Message | Cause |
|---------|-------|
| `Collections package not installed in the server.` | `package-collections` is missing on this instance |
| `The collection expression, '...', is incorrect; it should have three sections: <collection-name>.<row>.<field-name>` | Fewer than two dots |
| `Row not defined or invalid.` | Middle segment is not numeric |
| `Collection 'X' not exist.` | Name mismatch (case, spacing, renamed), or a dotted field path mangled the parse |
| `Record 'N' from collection 'X' not exist.` | Row position used instead of record id, or the record was deleted |
| `Field 'f' from collection 'X' not exist.` | Field missing on that record's `data` |

## Moving a process to another instance

Lookups are the part of a Decision Task most likely to break after an import.

- **Process export does not carry collections.** `ProcessExporterExtension` adds each referenced Decision Table as a dependent, but neither it nor `DecisionTableExporter` looks inside the DMN for collection lookups. Collections have to be moved separately.
- **Records only travel when `include_records_on_export` is set** on the collection. When they do, `RecordExporter` inserts them with their **original ids** and restores the table's `AUTO_INCREMENT`, so record ids survive. In update mode, existing records are wiped and reloaded, still with the original ids.
- **A name clash renames the collection.** `CollectionExporter` has `handleDuplicatesByIncrementing = ['name']`. Importing as a copy, or into an instance that already has that name, turns `Limits` into `Limits 2`, and every `"Limits"` lookup fails.

Checklist before exporting or moving a process with Decision Tasks:

```
- [ ] List every {{#_collection}} in each referenced table (GET /api/1.0/decision_tables/{id}, search definition)
- [ ] For each collection: include_records_on_export is on, then export it
- [ ] Import collections on the target first, keep the same name (no copy mode, no name clash)
- [ ] Confirm name and record ids on the target (lookup calls above)
- [ ] Import the process, then preview each table with /execute
```
