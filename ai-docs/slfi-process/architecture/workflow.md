# SLFI — workflow

## Phases (PM4)

1. **Start** — Requester (PM3 used message `LEAD`; open if API-driven case creation is required).
2. **Initialize** — Script `slfi-process-initialize-upload-task`: year, dates, submitter, tax user, contact email, case ids, external form base URL.
3. **Upload** — Task `slfi-process-upload-investors-confirmation`; post-submit script `slfi-process-import-investors-file` (when wired in Modeler).
4. **Review** — Self-service / Tax pool task `slfi-process-review-investors-list`; gateway on `SLFI_FLAG_CANCEL_CASE` (`""` → tracking, `RETURN` → upload, else → end).
5. **Tracking** — Script `slfi-process-load-tracking-grid` then task `slfi-process-tracking-form`.

## Routing (FEEL)

| Flow | Condition |
| --- | --- |
| → Tracking | `SLFI_FLAG_CANCEL_CASE == ""` |
| → Upload (return) | `SLFI_FLAG_CANCEL_CASE == "RETURN"` |
| → End (cancel/submit) | `SLFI_FLAG_CANCEL_CASE != "" and SLFI_FLAG_CANCEL_CASE != "RETURN"` |

## Assignments

| Task | PM3 | PM4 target |
| --- | --- | --- |
| Upload | Requester | `requester` |
| Review | EVALUATE + SELF_SERVICE Tax | `user_group` + process variable `SLFI_TAX_USER` (manual) |
| Tracking | User | `user` assigned to tax user |

## Notifications

PM3 task notifications + `Send Notifications` trigger used `psSLFIFunctions` template store. PM4: Send Email tasks or package notifications using `projects/slfi-process/resources/email/SLFIEmailTemplate.html`.

## Deploy order

Scripts → screens → `slfi-process-main` BPMN. Record IDs here after upsert (do not commit numeric refs into BPMN).
