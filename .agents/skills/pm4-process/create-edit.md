# Create or edit a process

A skill is instructions. ProcessMaker will **not** create a process from SKILL.md alone. Use **Designer** or the **API** on the instance.

Do not start a request, complete a task, deliver an event, or exercise a schedule unless the user asked. Saving/publishing an ACTIVE process with automatic starts can enable new requests: confirm activation is intended, not merely a verification side effect.

## Path A — Designer (no API)

1. Designer → **Processes** → Create blank process (or open existing).
2. Set **name**, a real **description** (what the process does — required; do not leave blank), and **category**. Default category: **Uncategorized** unless the user named another.
3. Add **Start** → nodes → **End**. Connect sequence flows.
4. Configure each node:
   - Form/Manual Task → [pm4-form-manual-task](../pm4-form-manual-task/SKILL.md)
   - Script Task → pick Script asset (`pm:scriptRef`)
   - Call Activity → [pm4-sub-process](../pm4-sub-process/SKILL.md)
   - Gateway / sequence flow → [gateways.md](gateways.md): Exclusive/Inclusive conditions and fallback; Parallel/Event-Based use neither
   - Non-signal event → [events.md](events.md): definition/ref, data or timing contract, attachment and restrictions
   - Signal event → [pm4-signal](../pm4-signal/SKILL.md)
5. Run [static verification](verify.md), including each entry path and any join or boundary route.
6. **Save + Publish** when activation is intended. Draft BPMN is not live.

## Path B — API

Base URL: `http://processmaker-octane.test` (or your local host). Auth: session cookie or `Authorization: Bearer <token>`.

### 1. Resolve category id (default Uncategorized)

```
GET /api/1.0/process_categories?filter=Uncategorized&per_page=15
```

See [lookup.md](lookup.md). **Default** to the category named `Uncategorized` unless the user specifies another. Match by name; use the looked-up `id`. Do not assume id `1`.

### 2. Create assets first

Order matters:

```
Screens (FORM) → Scripts → child processes → parent process
```

Screen shell: [pm4-screen/examples/form-shell.json](../pm4-screen/examples/form-shell.json). Replace the category placeholder, then add the requested controls and a Submit button in Screen Builder before wiring the Form Task ([screen configuration](../pm4-screen/screen-config.md)).

BPMN template: [examples/start-form-end.bpmn.xml](examples/start-form-end.bpmn.xml). Replace `SCREEN_ID` with the looked-up screen id.

For branching or event-driven graphs, use [gateways.md](gateways.md) / [events.md](events.md) and [pm4-expressions](../pm4-expressions/SKILL.md). Match the real evaluator's input shape; do not add a `data` wrapper to gateway conditions merely because an API response wraps request data that way. Keep unsupported/unverified event variants out of executable models.

### 3. POST the process

Run [static verification](verify.md) before the write. The ACTIVE example below is live configuration, not a safe scratch test for scheduled/event starts; use INACTIVE for unapproved activation and leave existing process status unchanged during unrelated edits.

```
POST /api/1.0/processes
Content-Type: application/json
```

```json
{
  "name": "ApproveInvoiceProcess",
  "description": "Collect invoice details and route for approval",
  "status": "ACTIVE",
  "process_category_id": CATEGORY_ID,
  "bpmn": "<bpmn:definitions ...>...</bpmn:definitions>"
}
```

`description` is **required** on create: a short summary of what the process does (not an empty string). `process_category_id` defaults to the looked-up **Uncategorized** id unless another category was requested.

Expect **201** and process `id`.

Process **name** must be unique and use only letters, numbers, spaces, and `- _ . '`. A 422 on the name means a duplicate or another character. The message may read `The Name may only contain alphanumeric characters`; spaces and `- _ . '` are still accepted.

### 3b. Resave after POST if any `user_group`

`ProcessTaskAssignmentsTrait` runs on `saving` and returns when `!$process->exists`. The first `POST` stores BPMN (`pm:assignedUsers` / `pm:assignedGroups` in XML) but **does not** insert `process_task_assignments` rows. Runtime `user_group` reads those rows.

After 201, PUT the same `bpmn` (or Designer **Save**):

```
PUT /api/1.0/processes/{id}
Content-Type: application/json
```

```json
{ "description": "<same description>", "bpmn": "<same bpmn string>" }
```

`description` is required on every PUT; without it core returns 422.

Then confirm one row per `user_group` task: `process_task_id` = node id, `assignment_id` = the looked-up user or group id.

`requester`, `process_manager`, and `previous_task_assignee` do not need those rows.

### 4. Confirm in Designer

`GET /api/1.0/processes/{id}` often **omits** `bpmn`. Read it with `GET /api/1.0/processes/{id}/bpmn`, which returns the raw XML, or open Designer and confirm the graph, Form Task screen link, and Call Activity targets. Do not treat an empty `bpmn` field as “no BPMN.”

For gateway/events, compare saved conditions, default-flow references, event definitions, message/error declarations, and boundary attachments with the intended XML. A successful save or parse is not proof that a request routes correctly.

### 5. Publish (Designer edits only)

A process created with `"status": "ACTIVE"` via API is already live. **Publish** applies to changes made afterward in Designer: Save → Publish.

Process `status` accepts only `ACTIVE`, `INACTIVE`, `ARCHIVED`. There is no `PUBLISHED` status. Do not invent `PUT /processes/{id} {"status": "PUBLISHED"}`.

## Editing an existing process

Designer:

1. Designer → open process → edit BPMN.
2. Lookup any new screen/script/child process ids before wiring.
3. Preserve unrelated IDs, asset refs, message/error declarations, data mappings, and diagram geometry. Recheck gateway direction/defaults and boundary attachment after reconnecting flows.
4. Run static [verify.md](verify.md), then Save + Publish only when intended. Existing requests use their saved process version; do not force progress to apply diagram changes.

API:

1. Look up the process id: `GET /api/1.0/processes?filter=<name>`.
2. Read the XML: `GET /api/1.0/processes/{id}/bpmn`.
3. Lookup any new screen/script/child process ids, then change only the elements you need in the XML. Preserve unrelated configuration/geometry and update reciprocal flow/default/event refs consistently. Run [static verification](verify.md) before sending.
4. Send it back:

```
PUT /api/1.0/processes/{id}
Content-Type: application/json
```

```json
{ "description": "<existing description>", "bpmn": "<whole updated bpmn string>" }
```

Expect **200** with the process. A 422 on `description` means it was left out. The PUT also rebuilds `process_task_assignments`, so a `user_group` change needs no extra resave.

## Failures

| Symptom | Cause |
|---------|--------|
| 422 on POST /processes | Invalid BPMN, missing category, or `bpmn` not a string |
| Process exists, tasks broken | Wrong screen/script/process id in BPMN |
| `user_group` task never assigned | POST only — no `process_task_assignments` row. Resave (step 3b) |
| Changes not live | Saved but not Published |
| Call Activity does nothing | Wrong `calledElement`, start event, or child process — [pm4-sub-process/verify.md](../pm4-sub-process/verify.md) |
| Wrong route or join waits | Condition/default/data-context or synchronization mismatch — [gateways.md](gateways.md#verification-and-troubleshooting) |
| Event never triggers | Definition/ref, active receiver/attachment, scheduler/worker/package or version mismatch — [events.md](events.md#static-verification-and-troubleshooting) |
