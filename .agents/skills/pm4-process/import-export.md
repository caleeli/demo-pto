# Import and export a process

Use official ProcessMaker exports. Do **not** hand-write a `process_package` JSON (UUIDs, exporter classes, hashes). Import will reject invented packages.

## Export (get a shareable file)

```
POST /api/1.0/processes/{id}/export
```

Or Designer → Processes → ⋮ → **Export** → Basic (include screens when needed).

The returned JSON has `type: process_package`. That file is what **Designer → Processes → Import** accepts.

## Import

Designer → Processes → **Import** → choose `*.json` → Basic → Import as New.

Requirements:

- File must be a **process** export, not a **screen** export (`type` differs).
- Same or **older** platform version exporting into this instance.
- Password if the export was encrypted.
- After import: assignment users/groups may need remapping; email notifications are not imported.

## When to export vs create via API

| Situation | Path |
|-----------|------|
| New process on local instance | [create-edit.md](create-edit.md) API or Designer |
| Move process between instances | Export from source → Import on target |
| Version control in git | Some teams store BPMN XML separately; still verify on instance |

## Failures

| Symptom | Cause |
|---------|--------|
| Import: Invalid Format / not a process | Screen package, truncated JSON, or invented package |
| Import succeeds, Call Activity broken | Child process not included or ids remapped wrong — [pm4-sub-process/verify.md](../pm4-sub-process/verify.md) |
| Import succeeds, placeholder screens | Screen refs missing or not included in export |
