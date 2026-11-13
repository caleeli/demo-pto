# PM3 asset → PM4 skill mapping

Use PM3 skills to **read the source**; PM4 skills to **implement the target**. Automated converters may only partially cover the PM4 side.

| PM3 asset / concept | PM3 skill | PM4 target | PM4 skill | Migration notes |
|-------------------|-----------|------------|-----------|-----------------|
| BPMN process, tasks, steps | `pm3-process` | Process BPMN | `pm4-process` | Edit PM3 BPMN structure in `pm3-process`; after import, re-test flow conditions as FEEL (`pm4-expressions`) |
| Classic map routing | `pm3-classic-process` | BPMN redesign | `pm4-process` | Not a 1:1 auto conversion |
| Dynaform / xmlform / grid | `pm3-dynaform` | FORM screen | `pm4-screen` | **`colSpan`** rows → often **`FormMultiColumn`**; **`type: grid`** (control) → **`FormRecordList`** + row form page; nested **`type: form`** may flatten; SQL suggest → static or connector; dependent dropdowns often need manual rules |
| `@@` syntax | `pm3-expressions` (templates, labels); BPMN Designer gateway routing rules → `pm3-process` [gateways.md](../pm3-process/gateways.md); classic map transitions → `pm3-classic-process` | `data.*`, FEEL, Mustache | `pm4-expressions` | After import, gateway/flow bodies must be valid FEEL, not copied PM3 `@@` text |
| Process variables | `pm3-variables` | Request `data` keys | `pm4-expressions` | Rename and bind controls explicitly |
| Triggers / webbots | `pm3-trigger` | Script tasks, watchers, assignment, redirect, email | `pm3-pm4-triggers` → `pm4-script` / `pm4-screen` / `pm4-form-manual-task`; email peel-off → `pm3-pm4-email-notifications` | After `pm-analyze`; rewrite intent — no broad PHP translation. Known mail helpers are statically classified only |
| Task assignment | `pm3-process` | `pm:assignment`, groups | `pm4-form-manual-task` | Reconfigure after import |
| Email templates / task mail / Email Events | `pm3-email-template` | Task `email_notifications`, EMAIL screens, Send Email, HITL | `pm3-pm4-email-notifications` → `pm4-send-email` | Run `report-email-notifications`; `convert` emits safe task-start/task-end entries and safe Email Events. External content tables, unresolved IDs, custom server/From, parallel waits, and attachments remain HITL |
| Actions by Email (EE) | `pm3-actions-by-email` | Actions by Email call activity | `pm4-action-by-email` | Different product model |
| PM Tables (workspace) | `pm3-pm-table` | Collection | `pm4-collection` | Schema + CRUD screens |
| Report tables (process) | `pm3-report-table` | Collection or reporting design | `pm4-collection` | Data migration separate |
| DB connection | `pm3-db-connection` | Data connector / env | `pm4-data-connector` | Credentials on target instance |
| Input document | `pm3-input-document` | FileUpload on FORM screen | `pm3-pm4-input-documents` → `pm4-screen` | No PM4 Input Document asset. `convert` emits FileUpload on the task screen (`merge`, including VIRTUAL file steps). Upload-only tasks stay manual. Output documents are a later skill |
| Output document | `pm3-output-document` | EMAIL + Send Email; Dompdf script + scriptTask (step PDF) | `pm3-pm4-output-documents` → `pm4-send-email` / `pm4-screen` / `pm4-script` | No PM4 Output Document asset. `convert`: `email_body` + step `generate_pdf` when eligible; trigger/JRXML and full `hitl[]` parity manual |
| Web entry | `pm3-web-entry` | Start/Form `web_entry` (package-webentry) | [pm3-pm4-web-entry](../pm3-pm4-web-entry/SKILL.md), `pm4-process`, `pm4-screen`, `pm4-form-manual-task` | `report-web-entry` + coverage (Setup + `webEntryEvent`); `convert` partial for canvas HTML (`html_form_task` / `html_setup_dyn`); Setup/WS/completed manual |
| Case scheduler (`CASE_SCHEDULER`) | `pm3-case-scheduler` | BPMN timer/conditional start or ops scheduling | `pm4-process` | No PM3 Case Scheduler table in PM4; redesign schedule + delegate user |
| Message types | `pm3-message-type` | Message events (not signals) | `pm4-process` | Not `pm4-signal` |
| Subprocess | `pm3-process` | Call Activity | `pm4-sub-process` | After convert: [pm3-pm4-subprocess](../pm3-pm4-subprocess/SKILL.md); include child `.pmx2` or `--subprocess-pmx2` |
| Signals (PM4) | — | Signal events | `pm4-signal` | PM3 message ≠ PM4 signal |
