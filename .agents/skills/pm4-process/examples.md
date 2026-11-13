# Gateway and event example scenarios

Use with [gateways.md](gateways.md), [events.md](events.md), and [static verification](verify.md). These are complete BPMN documents with diagram shapes/waypoints, not `process_package` exports. Use their XML through the process create/edit workflow; do not wrap it in an invented export package.

## Setup and safety

- **Do not deploy, publish, or run these examples just to read them.** Runtime scenarios below require an authorized disposable environment. Scheduled starts can create requests automatically when activated; future example dates are not an isolation mechanism.
- Resolve a real category and any required assets with [lookup.md](lookup.md). `FORM_SCREEN_ID` means an existing **FORM** screen ID on the target instance, not a DISPLAY screen. Use [pm4-form-manual-task](../pm4-form-manual-task/SKILL.md) to configure the requester assignment and screen; a simple FORM with a Submit control is sufficient. Reusing it does not mean screen submission may erase the initialized business inputs.
- Replace **every** `FORM_SCREEN_ID` occurrence in a file. `CHILD_PROCESS_ID` is an unquoted numeric placeholder inside `pm:config`: replace every occurrence with the actual child database ID **before** decoding/using that config. Never send placeholders or invented fixture IDs to a real API.
- Preserve all internal BPMN IDs. Each XML document owns its process element IDs; these are not database IDs. Keep references, declarations, mapping assignments, boundary attachments, and DI geometry together.
- Initialize the input keys and types stated below before the relevant decision. Examples use root request keys, not a `data` wrapper, following [pm4-expressions](../pm4-expressions/SKILL.md).
- Use INACTIVE configuration until activation is intended. Existing request versions are not updated by editing a diagram. Keep the original request/task IDs when observing outcomes, and do not force unrelated work forward.

Expected outcomes below describe the source-backed intent to verify. **No workflow outcome is claimed as executed or passing.** They are not authorization to create requests, deliver events, advance clocks, complete tasks, or activate a scheduler.

## 1. Exclusive condition and fallback

File: [exclusive-fallback.bpmn.xml](examples/exclusive-fallback.bpmn.xml). Replace `FORM_SCREEN_ID`; entry is `exclusive_start` in `ExclusiveProcess`.

| Initial payload | Expected active task after entry | Expected continuation |
|---|---|---|
| `{"approved":true}` | `exclusive_approve` only | Complete it → `exclusive_end`; rejection task never activated |
| `{"approved":false}` | `exclusive_reject` only | Default `exclusive_fallback`; complete task → `exclusive_end` |
| `{"approved":"true"}` | `exclusive_reject` only | Strict Boolean condition does not accept a string |

The fallback flow has no condition; `exclusive_choice@default` selects it when no non-default condition matches. Missing input is an error case, not a guaranteed fallback. To test a genuine no-match/no-default variation later, remove the gateway's default **and** make all outgoing conditions explicitly false; removing default alone makes its otherwise unconditioned flow an unconditional candidate in the reviewed engine.

## 2. Inclusive subset and multiple selected branches

File: [inclusive-split-join.bpmn.xml](examples/inclusive-split-join.bpmn.xml). Replace `FORM_SCREEN_ID`; entry is `inclusive_start` in `InclusiveProcess`.

| Initial payload | Expected selected work | Join/outcome to verify |
|---|---|---|
| `{"needsFinance":true,"needsLegal":false}` | `inclusive_finance` only | Its completion should release the subset join to `inclusive_end`, without waiting for an unselected Legal branch |
| `{"needsFinance":false,"needsLegal":true}` | `inclusive_legal` only | Symmetric subset case |
| `{"needsFinance":true,"needsLegal":true}` | Both tasks | Complete either first; the other remains required. After both complete, expect one continuation to `inclusive_end` |
| `{"needsFinance":false,"needsLegal":false}` | No review tasks | Default goes directly to `inclusive_skipped`; it does not join unselected branches |

This is a simple acyclic split/join, not proof of complex-loop or nested Inclusive synchronization. Preserve separate Diverging/Converging gateways.

## 3. Parallel fork/join

File: [parallel-split-join.bpmn.xml](examples/parallel-split-join.bpmn.xml). Replace `FORM_SCREEN_ID`; entry is `parallel_start` in `ParallelProcess`. No business input is required.

Expect `parallel_a` and `parallel_b` active. Completing one alone must not reach `parallel_end`; completing both should release `parallel_join` once. There are no conditions or defaults. The distinction from example 2 is intentional: every incoming connection is required here.

## 4. Message-versus-timer race

File: [message-timer-race.bpmn.xml](examples/message-timer-race.bpmn.xml). Replace `FORM_SCREEN_ID`; entry is `race_start` in `RaceProcess`. The receiver declares `Message_PaymentReceived`, has two direct competing catches, and a fixed `PT24H` timeout.

- **Message wins:** while `race_message` is active, deliver the intended message through a verified sender/correlation path; expect `race_receipt` only, with the losing timer catch inactive/closed. The document does not include a sender. Example 7 provides a separate modeled-message pattern, not an automatically correlated sender for this receiver.
- **Direct completion variant:** if specifically authorized, use the source-backed [catch-completion API](events.md#source-backed-api-catch-completion) with the real request ID and node ID `race_message`, body `{"payment_id":"P123"}`. The definition's `pm:variableName="payment"` places that supplied object under `payment` on this direct-completion path. This tests direct catch activation, not modeled-message transport.
- **Timer wins:** allow/advance time only in the approved test environment; expect `race_followup` only and the message catch no longer active.
- **Late loser:** after one outcome, a late losing delivery must not activate the other outcome. Inspect token history and scheduled rows separately. Scheduled-job cleanup and simultaneous-delivery behavior remain unverified.

Complete only the winning task to reach the ordinary `race_end`. The outcome merge does not synchronize two winners. Do not shorten or trigger a live timer as an unrequested diagnostic.

## 5. Scheduled start followed by a timer delay

File: [timer-start-delay.bpmn.xml](examples/timer-start-delay.bpmn.xml). No screen/script asset is required. `TimerProcess` contains `timer_start` with cycle `R/2030-01-15T09:00:00Z/P1D`, then `timer_delay` with duration `PT30M`.

The **date is illustrative**. Choose an authorized test-clock window or future schedule before activation; update the label too if changing the time. Expect one request per due cycle, first waiting at `timer_delay`, then completing at `timer_end` after the delay. Inspect scheduler precision and timezone rather than assuming exact wall-clock seconds. No request should start merely because XML was parsed.

Dynamic-duration variation: in a separate authorized test, set the delay body to `{{interval}}` and arrange an upstream request value such as `interval: "PT8M"`. Do not assume the timed start automatically supplies that request data. Keep the shipped fixture's fixed delay for its baseline check.

## 6. Boundary deadline versus additional reminder branch

Files: [boundary-timer-interrupting.bpmn.xml](examples/boundary-timer-interrupting.bpmn.xml) and [boundary-timer-noninterrupting.bpmn.xml](examples/boundary-timer-noninterrupting.bpmn.xml). Replace `FORM_SCREEN_ID` in both. Their starts are `interrupt_start` / `reminder_start`; no business input is required.

| Scenario | Interrupting fixture | Non-interrupting fixture |
|---|---|---|
| Review completes before `PT30M` | `interrupt_reviewed`; no overdue task should activate later | `reminder_reviewed`; no follow-up should activate later |
| Timer expires while review remains active | Review is interrupted; `interrupt_escalate` activates; normal reviewed end must not be reached from the interrupted work | `reminder_followup` activates **and** `reminder_review` remains active |
| Alternate task completes | `interrupt_escalated` ends diverted work | `reminder_contacted` ends only the reminder branch; original review still needs completion |

Both boundary events attach to a Form Task and have no incoming sequence flow. `cancelActivity` is explicitly true/false. These are duration timers, not repeating cycles. Runtime checks must include completion-first cleanup and active/closed task states; disappearance of a task does not by itself prove the schedule row was removed.

## 7. Cross-pool message start, catch, and mapped payload

File: [message-collaboration.bpmn.xml](examples/message-collaboration.bpmn.xml). Replace `FORM_SCREEN_ID` twice. This is **one process asset BPMN document** with two process elements/pools and a collaboration plane; do not split it into unrelated assets or start both pools manually.

Authorized sequence to verify:

1. Start only `payment_sender_start` in `PaymentSender` with root payload `{"paymentId":"P123","privateSenderNote":"not mapped"}`.
2. `payment_open` throws `Message_OpenPayment`; expect a separate receiver request beginning at `payment_receiver_start` and waiting at `payment_receive`. Sender pauses at `payment_send_gate`, allowing observation of the waiting receiver before the next delivery. Verify assignment/receiver availability on the target; do not assume a message-created request inherits every sender UI setting.
3. Complete sender `payment_send_gate` without removing `paymentId`. Sender `payment_sender_end` throws `Message_DeliverPayment`. Its declared `payment_payload` input and association assign root `paymentId` to receiver key `payment_id`.
4. Expect receiver `payment_check` active with `payment_id: "P123"`; `privateSenderNote` must not be copied merely because it exists in sender data. Complete receiver work to reach its end. Sender and receiver have distinct request payloads.

This exercises **BPMN data-input association delivery**, not direct catch-completion `pm:variableName`, and not full-data broadcast. Check concurrent-request/correlation isolation separately. Message IDs, start/catch refs, mapping expressions, both pool geometries, and collaboration Message Flow edges must survive serialization together.

## 8. Normal versus Terminate End; child Error End

### Ordinary branch end versus closing pending work

File: [normal-terminate-end.bpmn.xml](examples/normal-terminate-end.bpmn.xml). Replace `FORM_SCREEN_ID`; entry is `ending_start` in `EndingProcess`. Use an isolated request and complete `ending_finish_task` **before** `ending_pending_task`.

- Initial `{"terminateRemaining":false}`: finishing the first task takes `ending_normal`; `ending_pending_task` should remain active. Only its later completion reaches `ending_pending_end` and permits ordinary completion.
- Initial `{"terminateRemaining":true}`: finishing the first task reaches `ending_terminate`; pending work should be closed, not require human completion. Record actual request/token states and drain only approved queues as needed.

This does not establish termination scope across concurrent requests or child processes. Never demonstrate it against unrelated live work.

### Error propagation to caller Boundary Error

Files: [error-end-child.bpmn.xml](examples/error-end-child.bpmn.xml) and [error-boundary-parent.bpmn.xml](examples/error-boundary-parent.bpmn.xml). Use [pm4-sub-process](../pm4-sub-process/SKILL.md) for the external child reference contract.

1. Create/configure the child asset from `error-end-child.bpmn.xml` only in the approved environment. Its internal owner process ID is `ErrorChild`, start ID `error_child_start`. It has no screen/script dependency.
2. Resolve that child's **real numeric database ID**. Substitute every `CHILD_PROCESS_ID` in the parent; the result must have matching `calledElement="ErrorChild-{id}"`, numeric `pm:config.processId`, the same config `calledElement`, and `startEvent="error_child_start"`. Before substitution the template's config contains a numeric placeholder and is not usable JSON.
3. Replace parent's `FORM_SCREEN_ID`. Preserve `error_parent_catch` attachment to `error_parent_call`, `cancelActivity="true"`, and the catch-all error definition (no `errorRef`). This deliberate catch-all avoids inventing a cross-asset declaration reference. The child still declares and references its own `Error_BusinessRejected`.
4. Start `error_parent_start` in `ErrorParent`. The child immediately reaches Error End; expect caller recovery task `error_parent_handle`, not normal-success end `error_parent_success`. Completing recovery should reach `error_parent_end`.

Capture child/parent request links and caller state; this scenario does not imply every uncaught error or non-interrupting Boundary Error behaves identically. No embedded `bpmn:subProcess` is used.

## Validation record and limits

On **2026-10-19**, the 11 new XML documents passed XML parsing, unique-ID/internal-reference and reciprocal-flow checks, full node/pool DI shape and flow/message-edge coverage checks, and warning-free installed moddle parse → serialize → reparse checks after **synthetic, local-only** asset substitution. Descriptor-based snapshots matched before/after serialization, including model properties, references, and diagram data. The substituted child-call JSON was also checked for numeric process ID and consistent calledElement/startEvent fields. No fixture was sent to an instance.

Installed validation stack: `bpmn-moddle 6.0.7` with PM schema `0.16.1`. Synthetic screen/child numbers used for parsing are not real asset lookups or a deployment recipe. These checks are not XSD validation, do not execute expressions/mappings, and do not exercise Modeler UI watchers or publication. Browser Modeler load/save and workflow execution remain **NOT RUN**; locked schema `0.16.3` and alternate engine compatibility are unverified. Structural regression assertions are extended in the evaluation step, separately from authorized platform behavior tests.
