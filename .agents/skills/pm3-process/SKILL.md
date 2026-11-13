---
name: pm3-process
description: >-
  ProcessMaker 3 BPMN processes. Use for tasks, steps, assignment, permissions,
  supervisors, subprocess, gateways, timer/email/message events, or .pmx export
  in BPMN Designer.
---

# PM3 Process (BPMN)

A **process** is the top-level container for all solution assets. BPMN Designer (`designer?prj_uid=`) edits the workflow graph.

## When to use

- Create or modify BPMN workflow structure
- Configure Exclusive, Parallel, or Inclusive gateways and default flows
- Configure Start / Intermediate / End timer, email, or message events on the canvas
- Configure task assignment, due dates, notifications
- Attach steps (dynaforms, input/output docs) to tasks
- Set start permissions, supervisors, subprocess calls
- Export/import process as `.pmx`
- Do not use for classic map transitions ([pm3-classic-process](../pm3-classic-process/SKILL.md)), standalone message-type CRUD ([pm3-message-type](../pm3-message-type/SKILL.md)), or web-entry admin tables alone ([pm3-web-entry](../pm3-web-entry/SKILL.md))

## Reference map

| Need | Read |
|------|------|
| Choose/configure Exclusive, Parallel, Inclusive; default flow; engine limits | [gateways.md](gateways.md) |
| Start/Intermediate/End timer, email, message; cron deps | [events.md](events.md) |
| `@@` / `@#` condition syntax | [pm3-expressions](../pm3-expressions/SKILL.md) |
| Message type schema and throw/catch mapping | [pm3-message-type](../pm3-message-type/SKILL.md) |
| Email template bodies for email events | [pm3-email-template](../pm3-email-template/SKILL.md) |
| Public/anonymous start | [pm3-web-entry](../pm3-web-entry/SKILL.md) |

## Asset schema

| Field | Required | Notes |
|-------|----------|-------|
| `PRO_UID` | auto | Process unique id |
| `PRO_TITLE` | yes | Process name |
| `PRO_DESCRIPTION` | no | Description |
| `PRO_STATUS` | yes | `ACTIVE`, `INACTIVE`, `DISABLED` |
| `PRO_TYPE` | no | Process type flags |
| `PRO_CATEGORY` | no | Category UID |

BPMN XML stored in `BPMN_*` tables linked by `PRJ_UID` (BPMN project). Classic processes use `PRO_UID` directly. Export key: `PROCESSDEFINITION`.

## BPMN elements

| Element | PM3 usage |
|---------|-----------|
| Start event | Case creation entry point (empty, timer, message, web entry) |
| User task | Human task with steps and assignment |
| Script task | Bound to trigger (`pm3-trigger`) |
| Service task | External service (enterprise/plugins) |
| Exclusive gateway | One route via ordered conditions or default |
| Parallel gateway | Concurrent paths; no conditions |
| Inclusive gateway | One or more routes via conditions or default |
| Subprocess | Call another process (`SubProcess` API) |
| Timer event | Delay/schedule (`TimerEvent` API) |
| Email event | Send email on event (`EmailEvent` API) |
| Message event | Throw/catch via message types |
| End event | Case path completion (empty, email, message) |

Element rules:

- Gateways are diverging **or** converging, never both on one node; pair split/join by the same type. Details: [gateways.md](gateways.md).
- Do not design PM4-style Event-Based message/timer races; Designer work uses Exclusive/Parallel/Inclusive only ([gateways.md](gateways.md)).
- Configure Exclusive/Inclusive conditions on gateway **Properties** routing rules (not only the flow label). Syntax: [pm3-expressions](../pm3-expressions/SKILL.md).
- Timer/message progress requires workspace cron (`timereventcron.php` / `messageeventcron.php`). Details: [events.md](events.md).

## Task configuration

| Concern | Configuration |
|---------|---------------|
| Assignment | User, group, self-service, value-based, cyclical |
| Steps | Ordered dynaforms, input docs, output docs |
| Due date | Relative or absolute from task start |
| Notifications | Email on assignment/overdue (uses `pm3-email-template`) |
| Triggers | Before/after routing, assignment hooks |

Assignment rules export separately as `ASSIGNMENTRULES`.

## Permissions and supervisors

| Export key | Purpose |
|------------|---------|
| `PERMISSIONS` | Who can start/view the process |
| `SUPERVISORS` | Supervisor users |
| `SUPERVISORSOBJECTS` | Objects supervisors can access |

APIs: `ProcessPermissions`, `ProcessSupervisors`.

## Process files

Process folder contains `templates/`, `public/`, `mailTemplates/`. Managed via `FilesManager` API. Fold into process documentation — no separate skill.

## Links to other PM3 assets

| Asset | Relationship |
|-------|--------------|
| `pm3-dynaform` | Steps on user tasks |
| `pm3-trigger` | Routing, assignment, script tasks |
| `pm3-variables` | Process variable definitions |
| `pm3-expressions` | Gateway and flow conditions |
| `pm3-input-document` / `pm3-output-document` | Task steps |
| `pm3-classic-process` | Alternative editor for same `PRO_UID` |
| `pm3-web-entry` | External start events |
| `pm3-message-type` | BPMN message events |
| `pm3-email-template` | Email event and task notification bodies |
| `pm3-case-scheduler` | Scheduled case creation (not canvas timer) |

## Validation checklist

1. Every user task with form interaction has at least one step
2. All Exclusive/Inclusive gateways have conditions and/or a default path; Parallel has matching join
3. Gateway topology follows [gateways.md](gateways.md) (no Start→Gateway; no Gateway→Intermediate Email Event)
4. Timer/email/message events fully configured per [events.md](events.md)
5. Start event has permissions for intended users/groups
6. Subprocess variable mapping defined for in/out
7. No orphan assets (dynaform/trigger not referenced)

## Common mistakes

- User task without steps → blank task screen
- Self-service task without catch configuration
- Subprocess call without variable propagation
- Editing BPMN on a process that users manage in classic map
- Exporting process without dependent triggers
- Mixing Parallel split with Exclusive/Inclusive join
- Assuming PM4 Event-Based races or Boundary events are PM3 Designer patterns
- Timer or message catch without cron → case never continues
