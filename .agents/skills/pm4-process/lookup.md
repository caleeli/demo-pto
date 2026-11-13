# Lookup instance ids (do not invent)

Process BPMN stores **numeric** ids for screens, scripts, and child processes. Resolve them from the running instance. Guessing `1` or copying ids from another environment breaks the process.

Auth: session cookie or `Authorization: Bearer <token>`. Base URL: local site host (see [setup-processmaker](../setup-processmaker/SKILL.md)). Query contracts: [pm-api-contracts](../pm-api-contracts/SKILL.md).

## Process category (new process)

```
GET /api/1.0/process_categories?filter=Uncategorized&per_page=15
```

**Default:** use the seeded category named `Uncategorized` unless the user names another category. Match by **name**, then use that row's `id`. Do not assume id `1`. A seeded `Uncategorized` may exist; still use its looked-up id. Use an `ACTIVE` category on `POST /api/1.0/processes`.

To list all categories (when picking a non-default one):

```
GET /api/1.0/process_categories?per_page=15
```

## Existing process (Call Activity target)

```
GET /api/1.0/processes?filter=Upload%20Transcript&include=events,category&filter_without_assignments=true&per_page=15
```

Pick the row whose **name** matches. `calledElement` is `{ownerProcessId}-{id}`. Start event and `pm:config`: [pm4-sub-process](../pm4-sub-process/SKILL.md).

## Task screen (Form/Manual Task)

```
GET /api/1.0/screens?filter=Approve%20Invoice&per_page=15
```

Form Tasks use `FORM` or supported `CONVERSATIONAL`; Manual Tasks use `DISPLAY`, typically with `status` = `ACTIVE`. Verify the selected record's type and any required package before using `id` as `pm:screenRef` ([task lookups](../pm4-form-manual-task/lookup.md)).

Screen category for `POST /screens`:

```
GET /api/1.0/screen_categories?per_page=15
```

## Script (Script Task)

```
GET /api/1.0/scripts?filter=send-notification&per_page=15
```

Use `id` as `pm:scriptRef`.

## Users and groups

Needed when configuring Form/Manual Task assignment (see [pm4-form-manual-task/lookup.md](../pm4-form-manual-task/lookup.md)).

## Portable git refs

Some repos version BPMN with named placeholders (e.g. `${screen:Title}`, `${process:name}`) and resolve them at deploy time. That is a **repo convention**, not core ProcessMaker XML. On the instance after save, attributes are numeric. When editing such a repo, keep the named ref in git; still verify the asset exists on the instance.
