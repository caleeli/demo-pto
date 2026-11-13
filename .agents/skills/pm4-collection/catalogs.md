# Collections as select-list catalogs

Load `SKILL.md` first. This file covers creating a Collection that feeds a select, radio, or checkbox group, and the order of work around it.

## When a catalog Collection is required

Create a Collection whenever a process screen has a select, radio, or checkbox group with **more than two** options. Do it **before** building the screen that uses it.

| Examples | Create a Collection? |
|----------|----------------------|
| Leave types, approval decisions (3+), departments, countries, document types, channels, risk bands, product types | Yes, always |
| Yes/No, True/False only | No. Hard-code the two options in the screen |

Do not leave a multi-option list as a hard-coded array in the screen. The list then cannot change without editing the screen.

Typical record fields: `code` (stable value used in BPMN conditions), `label` (display text), and optionally `active` and `sort_order`.

## Workflow

1. Reuse existing screens when you can (`GET /api/1.0/screens`). Field names on the create screen become the record `data` keys.
2. If the three schema screens are missing, create them first with [pm4-screen](../pm4-screen/SKILL.md). A collection cannot be created without `create_screen_id`, `read_screen_id` and `update_screen_id`.
3. List collections (`GET /api/1.0/collections`) to avoid a duplicate name.
4. Create the collection (`POST /api/1.0/collections`). Keep the returned **numeric `id`**. The consuming screen needs it.
5. Seed the catalog rows. See [test-data.md](test-data.md).
6. Find the Data Connector for the collection. With `package-data-sources` installed, one is created automatically for `/collections/{id}/records`. It has the collection name, possibly with a `_N` suffix. Look it up in the connector list. If it is missing, create one with a `ListAll` resource that calls `/api/1.0/collections/{id}/records`. See [pm4-data-connector](../pm4-data-connector/SKILL.md).
7. In the consuming screen, load the records by the **numeric** collection id and bind them to the control. Do not look the collection up by name at runtime. Pass the id into the screen, for example as a prop or a config value.
8. Align gateway conditions in the process with the catalog `code` values. See [pm4-process](../pm4-process/SKILL.md) and [pm4-expressions](../pm4-expressions/SKILL.md).

## Screens that read the catalog

Whatever control renders the options, the records come from `GET /api/1.0/collections/{id}/records` or from the auto-created Data Connector. Each record is `{ id, data: { code, label, ... } }`. Use `data.code` as the option value and `data.label` as the text.

Which control to use, and how the screen mounts its component, is covered by [pm4-screen](../pm4-screen/SKILL.md).

## Pitfalls

1. The three screen ids are required for every collection. For `type: RAG`, the instance's `rag-create-screen` and `rag-view-screen` can be used, but validation still needs all three ids.
2. Creating a collection also creates the `collection_{id}` tables. Do not invent table names.
3. `package-collections` must be installed.
4. Forgetting to seed records gives empty selects at runtime.
5. Passing a name, a string, or an invented id as the collection id breaks the select. Use the id from the list or create response.
6. Changing a `code` after process data exists breaks gateway conditions and stored values. Change `label` for display changes.
