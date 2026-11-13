# PM4 PM Block — import/export reference

Deep detail for `pm4-pm-block`. Load `SKILL.md` first.

## Publish (process → block)

`POST /api/1.0/pm-blocks` with `asset_id` = source process id:

1. Export manifest of source process + dependencies.
2. Re-import as copy with `asset_type = PM_BLOCK` on Process, Screen, Script, Collections, DataConnector.
3. Create `PmBlock` row linked to new subprocess (`editing_process_id`).
4. Set subprocess `package_key` = `process_package_key` (`pm-block-{uniqid}`).

The PM Block record itself keeps `asset_type = null`; only the subprocess and bundled assets use `PM_BLOCK`.

## Standalone block export/import

| Action | Endpoint |
|--------|----------|
| Export | `POST /api/1.0/pm-blocks/export/download/{id}` |
| Manifest | `GET /api/1.0/pm-blocks/{id}/export/manifest` |
| Pre-import validation | `POST /api/1.0/pm-blocks/import/validation` |
| Import | `POST /api/1.0/pm-blocks/import/do-import` |

`PmBlockExporter` bundles the block subprocess and categories. On import:

- `meta.isImported = true`
- `editing_process_id` / `editing_process_uuid` point to the new subprocess
- Duplicate names increment via `handleDuplicatesByIncrementing` on `name` and `process_package_key`

## Parent process export with PM Blocks

`ProcessExporterExtension` (registered in `PackageServiceProvider`):

1. **preExport**: finds `bpmn:callActivity` elements with `processKey` in `pm:config`; adds `PmBlock` and subprocess dependents.
2. **preImport**: rewrites `processKey`, `calledElement` (`ProcessId-{id}`), and `processId` to match imported ids; syncs `editing_process_id` on the block.

`PmBlockUtils::getPmBlockElements()` identifies PM Block call activities by presence of `processKey` in config (not present on generic call activities).

## Locking imported blocks

| Condition | Effect |
|-----------|--------|
| `meta.isImported` | Block edit UI disabled (`editBlock.js`) |
| `meta.isImported` + `meta.isLocked` | `ProcessPolicy` blocks subprocess edit (`is_imported_locked`) |

## Identifying PM Block call activities in BPMN

A PM Block call activity `pm:config` always includes `processKey` shaped as:

```
processmaker-{process_package_key}-call-activity
```

Generic call activities may use `calledElement` without `processKey` in `pm:config`.
