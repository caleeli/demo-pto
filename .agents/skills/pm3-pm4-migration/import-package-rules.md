# PM3-accelerated import package rules

When validating or fixing JSON from a PM3→PM4 converter before or after import, these rules prevent common Designer and Preview failures. Details for screens: [pm4-screen](../pm4-screen/SKILL.md). BPMN wiring: [pm4-process](../pm4-process/SKILL.md).

## Process package

- Export type `process_package`, version **`"2"`** (string in JSON) for the **Import/Export manifest** format (`export` map, `ProcessExporter`, dependents). PM4 upload validation uses `(int) version === 2` and `ImportExport\Importer` preview (`ProcessController::validateImportedFile`). The legacy `ImportProcess` job’s `parseFileV2()` is empty — do not assume the old job path imports v2 manifests.
- **Target instance:** Import v2 preview calls `ExporterBase::getExtraAttributes()`, which requires **`ProcessMaker\Package\Translations\Models\Language`**. Enable/install the **Translations** package on the instance before importing converter packages (otherwise preview fails with `Class "…Language" not found`).
- Do **not** put **`anonymousUserId`** on process `attributes` in v2 manifests. That field belongs to legacy **`version: "1"`** exports (`ExportProcess` / `ImportProcess`), not the `processes` table. Anonymous web entry uses Start **`pm:assignment`** / **`pm:assignedUsers`** (and `web_entry` config) instead.
- Legacy **`version: "1"`** packages still exist (`ExportProcess` job) and use `ImportProcess::parseFileV1`.
- Process and screen **description** must be non-empty strings (use a sensible default if PM3 had none).
- Version suffix in names must avoid characters PM4 rejects (for example square brackets in titles).
- Every export entry's top-level `dependents` must be a JSON array, including
  `[]` when empty. Never emit `null`: `Manifest::buildParentModeMap()` iterates
  the field before exporter construction and PHP raises `foreach() argument must
  be of type array|object, null given`.
- Process `attributes.start_events` is a serialized JSON string. Use `"[]"`
  when the source has no start event; do not serialize the inner value as
  `"null"`.

## Screens

- In **v2 manifest** `attributes`, accelerated converters often set `config` as a **JSON string** (pages/controls). After import, `Screen` stores `config` as a **cast array** in the database — both shapes appear in the pipeline.
- Per-control `validation` is an **array** (`[]` or `[{value, helper, content}]`), never a bare string like `"required"`.
- `FormSelectList` `renderAs` is only **`dropdown`** or **`checkbox`**. PM3 radio → `checkbox` with `allowMultiSelect: false`.
- PM3 hidden → usually `FormInput` with a visibility rule. Confirm in Screen Builder Preview; converter-specific values are not universal (see [pm3-migration-hidden-conditionalhide](../../knowledge/findings/pm3-migration-hidden-conditionalhide.md)).
- `FormLoop` and inner `FormMultiColumn` need **`container: true`** and a proper `items` tree.

## BPMN (embedded in process export)

- **`convert` uses a flat PM4 diagram**: `bpmndi:BPMNPlane` references `ProcessId` (no collaboration pool/lanes in DI). Re-import if Modeler still shows a participant strip from an older package.
- **Web Entry (Start):** the FORM screen is in the same `process_package` as the process, but Web Entry stores a **second** id in `pm:config` → `web_entry.screen_id`. Import must remap it via process dependents type **`screen_id`** (`package-webentry` `preImport`) and type **`screens`** on the same `bpmn:startEvent` xpath (`ProcessExporter::importScreens`). Converter packages must use **numeric** placeholder ids in BPMN (not screen UUID strings). PM3 **ANONYMOUS** / Guest → Start **`user_assignment`** dependent with `fallbackMatches.username: "_pm4_anon_user"` (`ProcessExporter::importAssignments`). If import did not finish or the package predates these dependents, Modeler shows *Selected screen not found* or Web Entry **Authenticated** instead of **Anonymous**.
- **Agents:** do **not** hand-edit BPMN for PM3 trigger Script Tasks. Use **`skills/pm-analyze/pm-analyze attach-scripts`** with `trigger-plan.json` ([pm3-pm4-triggers](../pm3-pm4-triggers/SKILL.md)), or Modeler after a clean `convert` import.
- If scripts are in BPMN manually (human/export from PM4): each script needs an `export` entry and process `dependents` with **`meta.path`** (e.g. `"/bpmn:definitions/bpmn:process/bpmn:scriptTask[1]"`). `meta: null` → `ProcessExporter::importScripts()` error.
- **Send Email** (`connector-send-email`) EMAIL screens: process `dependents` type `screens` must include **`meta.path`** equal to the Send Email node's DOM path from `getNodePath()` (unindexed `.../bpmn:serviceTask` when the process has only one `serviceTask`; `.../bpmn:serviceTask[n]` when there are several — index is over all `serviceTask` siblings, not Send Email nodes only). Import matches paths with string equality. `meta: null` → `ProcessExporterExtension::preImport()` error when rewriting `pm:config.screenRef`.
- **Custom task email notification** screens are different: each dependent has type **`notification_screen`**, `meta.path` equal to the human task DOM path as **`/bpmn:definitions/bpmn:process/bpmn:task[n]`** (always indexed; importer resolves via XPath), and `meta.index` equal to that entry's zero-based position in `pm:config.email_notifications.notifications`. The importer uses both fields to rewrite that notification's `screenRef`. Do not use standalone Send Email's `screens` dependent shape here.
- Script exports from **`attach-scripts`** must include **`executor`** + **`user`** dependents (fallback match on target instance). Migrated PHP assets require `attributes.language === "php"` and an executor dependent with `fallbackMatches.title: "PHP Executor"`; **never use `php-nayra`** for PM3→PM4 Script Tasks. Intentional `javascript` or `python` scripts require the matching executor on the target instance; otherwise `script_executor_id` remains null and Script Builder breaks.
- Flow conditions: BPMN `conditionExpression` uses language **FEEL**. PM4 evaluates it through `FormalExpression` (Symfony `ExpressionLanguage` plus  PM-registered functions), not JavaScript. Converters often emit `@@`-stripped bodies such as `approval == "yes"` — validate every branch with [pm4-expressions](../pm4-expressions/SKILL.md) and the real `data.*` keys bound on screens.

## Assignment and notifications

- Converter BPMN often has `pm:assignment="requester"` or `pm:assignment="user"` without `pm:assignedUsers`, `pm:assignedGroups`, or `user_group` rules that match PM3 routing — reconfigure in [pm4-form-manual-task](../pm4-form-manual-task/SKILL.md).
- `pm:config.email_notifications.notifications` must remain valid JSON and preserve unrelated task config. `sendAt` is `task-start` or `task-end`; notification EMAIL screens require `notification_screen` dependents. See [pm3-pm4-email-notifications](../pm3-pm4-email-notifications/SKILL.md) and [pm4-send-email notifications](../pm4-send-email/notifications.md).
- Core `process.task_notifications` metadata is a separate PM4 feature; do not substitute it for migrated custom email configuration.
- Imported instance IDs still need review: users/groups, email servers, and screens can remap. `assignedUser`-only notifications may skip the current user unless `app.notifications_send_to_same_user` is enabled.
