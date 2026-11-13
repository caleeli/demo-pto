# Seeding Collection test data

Load `SKILL.md` first. Use this when adding sample, demo, or catalog rows to an existing Collection.

## Payload

Each record is one `POST /api/1.0/collections/{id}/records` call. The body wraps the field values in `data`:

```json
{
  "data": { "first_name": "Ada", "last_name": "Lovelace" }
}
```

To seed several rows, send one request per row. See [examples/record-minimal.json](examples/record-minimal.json) for another payload.

Each record is stored in `collection_{id}`.

## How to choose keys

1. Read the collection (`GET /api/1.0/collections/{id}`) and note `create_screen_id`.
2. Read that screen (`GET /api/1.0/screens/{create_screen_id}`) and list the control names. Those are the field names. For a screen that mounts a Vue component, use the `v-model` bindings of that component.
3. Use exactly those keys in each record's `data`.
4. If unsure, list an existing populated collection (`GET /api/1.0/collections/{id}/records`) and copy the `data` shape. `GET /api/1.0/collections/{id}/record-fields` also returns the field list derived from the create screen.

For select-list catalogs, seed at least the values the process screens will show, for example `{ "code": "PERSONAL", "label": "Personal" }`. An empty collection gives an empty dropdown. See [catalogs.md](catalogs.md).

## Limits

- Keep demo batches small, tens of rows and not thousands. For large loads use `POST /api/1.0/collections/{id}/import-csv` (see [reference.md](reference.md)).
- The server does not validate keys against the screen. A wrong key is stored and never shown.
- The caller needs create permission on the collection. Administrators pass.

## Pitfalls

1. Put the field values inside `data`, not at the top level of the body.
2. Only add test rows when the user asked for sample data. Truncating or wiping a production collection is out of scope here.
3. Do not edit existing rows to "fix" test data unless the user asked. Use `PATCH` to merge and `PUT` only to replace the whole `data`.
