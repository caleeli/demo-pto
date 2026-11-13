# slfi-process

SLFI (investor confirmation) workflow migrated from PM3 `SLFI_Process-9.pmx`.

## Architecture

- [database.md](./architecture/database.md)
- [workflow.md](./architecture/workflow.md)
- [frontend.md](./architecture/frontend.md)

## BPMN

| File | Process name | process_id |
| --- | --- | --- |
| `projects/slfi-process/bpmn/slfi-process-main.xml` | SLFI Process | `2` (`slfi-process-main`) |

## Screens (`pm:screenRef` → YAML → Vue)

| Screen title | YAML | Vue root |
| --- | --- | --- |
| `slfi-process-upload-investors-confirmation` | `projects/slfi-process/screens/slfi-process-upload-investors-confirmation.yaml` | `resources/js/projects/slfiProcess/uploadInvestorsConfirmation/SlfiProcessUploadInvestorsConfirmation.vue` |
| `slfi-process-show-investors-confirmation` | `projects/slfi-process/screens/slfi-process-show-investors-confirmation.yaml` | `resources/js/projects/slfiProcess/showInvestorsConfirmation/SlfiProcessShowInvestorsConfirmation.vue` |
| `slfi-process-review-investors-list` | `projects/slfi-process/screens/slfi-process-review-investors-list.yaml` | `resources/js/projects/slfiProcess/reviewInvestorsList/SlfiProcessReviewInvestorsList.vue` |
| `slfi-process-tracking-form` | `projects/slfi-process/screens/slfi-process-tracking-form.yaml` | `resources/js/projects/slfiProcess/trackingForm/SlfiProcessTrackingForm.vue` |

## Scripts (`pm:scriptRef` → PHP → use case)

| Script title | Wrapper | Use case |
| --- | --- | --- |
| `slfi-process-initialize-upload-task` | `projects/slfi-process/scripts/slfi-process-initialize-upload-task.php` | `InitializeUploadTask` |
| `slfi-process-import-investors-file` | `projects/slfi-process/scripts/slfi-process-import-investors-file.php` | `ImportInvestorsFile` |
| `slfi-process-load-investors-grid` | `projects/slfi-process/scripts/slfi-process-load-investors-grid.php` | `LoadInvestorsGrid` |
| `slfi-process-load-tracking-grid` | `projects/slfi-process/scripts/slfi-process-load-tracking-grid.php` | `LoadTrackingGrid` |
| `slfi-process-evaluate-submit-flag` | `projects/slfi-process/scripts/slfi-process-evaluate-submit-flag.php` | `EvaluateSubmitFlag` |
| `slfi-process-evaluate-zip-button` | `projects/slfi-process/scripts/slfi-process-evaluate-zip-button.php` | `EvaluateZipButton` |
| `slfi-process-cancel-cleanup` | `projects/slfi-process/scripts/slfi-process-cancel-cleanup.php` | `CancelCaseCleanup` |
| `slfi-process-set-case-year` | `projects/slfi-process/scripts/slfi-process-set-case-year.php` | `SetCaseYear` |

## Table prefix

`slfi_`

## PM3 gaps (manual)

- **Message start `LEAD`**: BPMN uses a normal start in git; wire message catch on PM4 if inbound leads are required.
- **Plugin `psSLFIFunctions`**: JS/CSS, investor file parser, notification templates, external form URL — not in `pm3-original/` export.
- **PM Tables**: `PMT_SLFI_*` → `slfi_*` tables / Collections (see database.md).
- **Output document** Investors External Form, **virtual input** Investors List, **Remove Assign Screen** redirect, **Save investor emails** queue, **Send Notifications** full parity.
- **Tax group** object permission (VIEW all tasks).
