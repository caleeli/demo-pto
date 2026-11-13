# Events (PM3 BPMN)

Use with [pm3-process](SKILL.md) for canvas placement. Message payload types: [pm3-message-type](../pm3-message-type/SKILL.md). Email body templates: [pm3-email-template](../pm3-email-template/SKILL.md). Public start: [pm3-web-entry](../pm3-web-entry/SKILL.md). Timer/message continuation depends on workspace **cron** jobs, not only Designer save.

Executable PM3 canvas events are the types below (empty/timer/message/email/web entry). Do not import PM4 Boundary, Conditional, Signal, Error, Terminate, or Event-Based race patterns into PM3 designs.

## Event inventory

### Start — create a case

| Variant | Role | Configuration / deps |
|---------|------|----------------------|
| Empty start | Normal case start from Home / API / permissions | Start permissions on the process |
| Timer start | Creates cases on a schedule | `TimerEvent` API; `TMREVN_OPTION` in `HOURLY`, `DAILY`, `MONTHLY`, `EVERY`, `ONE-DATE-TIME`; cron `timereventcron.php` |
| Message start | Starts when a matching message arrives | [pm3-message-type](../pm3-message-type/SKILL.md) + `message-event-definition`; cron `messageeventcron.php` |
| Web entry | External/anonymous start | [pm3-web-entry](../pm3-web-entry/SKILL.md) linked to start `ACT_UID` |

Start has no incoming sequence flow. Multiple starts are possible; validate each entry path.

### Intermediate — wait or emit during a case

| Variant | Role | Configuration / deps |
|---------|------|----------------------|
| Timer catch | Delay until duration or datetime | `TimerEvent` with `WAIT-FOR` (`TMREVN_DAY` / `TMREVN_HOUR` / `TMREVN_MINUTE`) or `WAIT-UNTIL-SPECIFIED-DATE-TIME`; cron `timereventcron.php` |
| Message throw | Send structured message | Message type + event definition mapping |
| Message catch | Wait for matching message | Same `MSGT_NAME` / definition as thrower; cron `messageeventcron.php` |
| Email event | Send email when the event is reached | `EmailEvent` API (from/to/subject + template); email server in Setup |

Intermediate events have incoming and outgoing sequence flows (except when used only as terminals the designer allows). After a gateway, prefer targets the engine supports — see [gateways.md](gateways.md#engine-supported-connections-gateways). **Gateway → Intermediate Email Event is not executable.**

### End — finish a path

| Variant | Role |
|---------|------|
| Empty end | Completes the arriving path |
| Email end | Sends configured email, then ends |
| Message end (send message) | Throws message, then ends |

End has no outgoing sequence flow. An ordinary End does not cancel other open parallel threads by itself.

## Timer events (`TimerEvent`)

API: `Project\TimerEvent` (`timer-event` routes). Rows link `EVN_UID` on the BPMN project (`PRJ_UID`).

| Field | Notes |
|-------|-------|
| `TMREVN_UID` | Timer config id |
| `EVN_UID` | BPMN event node |
| `TMREVN_OPTION` | Start: `HOURLY`, `DAILY`, `MONTHLY`, `EVERY`, `ONE-DATE-TIME`. Intermediate: `WAIT-FOR`, `WAIT-UNTIL-SPECIFIED-DATE-TIME` |
| `TMREVN_START_DATE` / `TMREVN_END_DATE` | Schedule window for recurring starts |
| `TMREVN_DAY` / `TMREVN_HOUR` / `TMREVN_MINUTE` | Schedule components (validated per option) |
| `TMREVN_CONFIGURATION_DATA` | Weekdays (daily) or months (monthly) array |
| `TMREVN_NEXT_RUN_DATE` | Next execution / one-shot datetime |
| `TMREVN_STATUS` | `ACTIVE`, `INACTIVE`, `PROCESSED` |

**Runtime:** overdue timers do not advance until `timereventcron.php` runs. Confirm cron/Task Scheduler on the workspace before diagnosing “stuck on timer.”

## Email events (`EmailEvent`)

API: `EmailEvent`. Typical fields: `EMAIL_EVENT_UID`, event/`EVN_UID`, `EMAIL_EVENT_FROM`, `EMAIL_EVENT_TO`, `EMAIL_EVENT_SUBJECT`, plus template/content linkage.

- Requires a working Email Server (Setup).
- Template bodies use `@@` placeholders — [pm3-email-template](../pm3-email-template/SKILL.md).
- Do not confuse with task assignment notifications (also templates) or with message throw/catch.

## Message events

Configure identity and variables with [pm3-message-type](../pm3-message-type/SKILL.md):

1. Same `MSGT_NAME` on throw and catch sides.
2. `message-event-definition` maps variables; include `_label` companions when suggest/dropdown values must travel.
3. Cross-process messaging uses separate pools/processes per BPMN practice; correlation must be valid in a test environment before production.
4. Receive/intermediate message correlation is sequential — not multi-correlation like simultaneous send/receive signal patterns.
5. Continuation of waiting catches depends on `messageeventcron.php`.

Misconfigured correlation/message type can leave cases unrecoverable after cron runs; validate in development first.

## Web entry starts

Designer web-entry start events link canvas activity UIDs to `WEB_ENTRY` configuration. Full route/password/completion-screen rules: [pm3-web-entry](../pm3-web-entry/SKILL.md).

## Cron and scheduler dependencies

| Mechanism | Advances |
|-----------|----------|
| `timereventcron.php` / Task Scheduler timer jobs | Timer start and intermediate timer catches |
| `messageeventcron.php` | Message catch / related message applications |
| Case Scheduler asset | Separate scheduled **case creation** — [pm3-case-scheduler](../pm3-case-scheduler/SKILL.md); not a canvas timer event |

A correctly drawn event that never fires usually means cron/email server/message type mismatch, not a missing sequence flow.

## Validation checklist

1. Every Intermediate Timer/Message/Email event has complete API/Designer configuration (not only a shape on the canvas).
2. Timer options match event type (start vs intermediate).
3. Message throw/catch share message type and mapping; correlation tested.
4. Email events have server + recipients + subject/template.
5. No Start → Gateway; no Gateway → Intermediate Email Event ([gateways.md](gateways.md)).
6. Workspace cron jobs are active before runtime testing.

## Common mistakes

- Expecting timers/messages to fire without cron
- Treating PM4 Event-Based races or Boundary timers as PM3 Designer patterns
- Confusing Email Event with Message Event or task notification templates
- Message throw without catch (or wrong `MSGT_NAME`) → stuck cases
- Web entry configured in Admin but not linked to the BPMN start event
