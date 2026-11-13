# Verify a Sub Process

Static checks only unless the user asked for a runtime exercise.

## Embedded / Ad-hoc / Transaction

- [ ] Parent uses `bpmn:subProcess` (or `adHocSubProcess` / `transaction`) to group a phase, not a flat fan of sibling tasks that belong together
- [ ] Phase `name` is business language (not default “Sub Process”)
- [ ] Non-event embedded subprocess has at least one inner none `startEvent`
- [ ] All inner tasks, gateways, ends, and sequence flows are **nested inside** the container element
- [ ] No sequence flow crosses the subprocess boundary (inner ↔ parent)
- [ ] Nested subprocesses are acyclic
- [ ] Event subprocess has `triggeredByEvent="true"` and no incoming sequence flow
- [ ] Ad-hoc: `ordering` is `Parallel` or `Sequential`; completion condition expressions match [pm4-expressions](../pm4-expressions/SKILL.md)
- [ ] Transaction: cancel/compensation only when that semantics is required; otherwise prefer plain embedded
- [ ] Top-level parent graph is a short phase story (see [design.md](design.md))

## Call Activity

- [ ] Element is `bpmn:callActivity` because a **separate** child process/request is required (reuse, PM Block, shared asset)
- [ ] `calledElement` is `{ownerProcessId}-{numericId}` and that process exists, is not archived, and is not the parent
- [ ] `pm:config` `calledElement`, `processId`, and `startEvent` agree with `calledElement`
- [ ] `startEvent` is a plain Start Event: no event definition, no Web Entry
- [ ] The suffix of `calledElement` is numeric (a Data Connector call is out of scope)
- [ ] Name describes the child process, not the default "Sub Process"

Core warns on save when the Call Activity start event is missing, is not a start event, has an event definition, or is a Web Entry (`CallActivityTest`). Treat those warnings as a failed check.

## Runtime (only if the user asked)

### Embedded

1. Start the parent request.
2. Confirm Inbox tasks are the **inner** Form Tasks (same `process_request_id`).
3. Complete the phase; parent continues to the next top-level node without a second request row.

### Call Activity

```
GET /api/1.0/requests/{parentRequestId}/tokens?element_id={callActivityId}
```

- Parent request stays `ACTIVE` while the child is open. Default Inbox `GET /tasks` is empty for the Call Activity. With `all_tasks=true`, the `callActivity` token is `ACTIVE` and `subprocess_request_id` is the child.
- Child row has `parent_request_id`.
- After the child completes, parent data includes merged new/changed keys; `_parent` is not present on `include=data`.
