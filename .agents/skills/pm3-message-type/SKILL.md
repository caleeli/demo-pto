---
name: pm3-message-type
description: >-
  ProcessMaker 3 message types. Use for BPMN MESSAGE_TYPE definitions, MSGT_UID,
  message variables, throw/catch events, or MessageEventDefinition configuration.
---

# PM3 Message Type

**Message types** define structured messages for BPMN throw and catch events. Stored in `MESSAGE_TYPE` table.

## Asset schema — MESSAGE_TYPE

| Field | Required | Notes |
|-------|----------|-------|
| `MSGT_UID` | auto | Message type id (primary key) |
| `PRJ_UID` | yes | BPMN project UID |
| `MSGT_NAME` | yes | Message name (used in BPMN event matching) |

## Asset schema — MESSAGE_TYPE_VARIABLE

| Field | Required | Notes |
|-------|----------|-------|
| `MSGTV_UID` | auto | Variable id (primary key) |
| `MSGT_UID` | yes | Parent message type |
| `MSGTV_NAME` | no | Variable name |
| `MSGTV_DEFAULT_VALUE` | no | Default value |

API: `message-type-variable`.

## BPMN usage

| Event | Role |
|-------|------|
| Message throw (intermediate) | Sends message with payload |
| Message catch (intermediate/start) | Waits for matching message |
| Message event definition | Links event to `MSGT_UID` and variable mapping |

API: `message-event-definition`.

Payload variables carry case data between processes or external systems.

## Rules

- Throw and catch must reference the same `MSGT_NAME`
- Variable mapping defined on message event definition
- Orphan throw without matching catch leaves cases waiting
- Uses `PRJ_UID` (BPMN project), not `PRO_UID`

## Links to other PM3 assets

| Asset | Relationship |
|-------|--------------|
| `pm3-process` | Canvas message events and cron deps: [events.md](../pm3-process/events.md) |
| `pm3-variables` | Payload variable mapping |
| `pm3-expressions` | `@@` in mapping expressions |

## Validation checklist

1. Every throw has matching catch (same `MSGT_NAME`)
2. Message type variables defined in `MESSAGE_TYPE_VARIABLE`
3. Variable mapping complete on event definition

## Common mistakes

- Message throw without catch → stuck cases
- Using `MSG_UID` instead of `MSGT_UID`
- Confusing message events with email events (`pm3-email-template`)
- Using `PRO_UID` instead of `PRJ_UID` for BPMN message types
