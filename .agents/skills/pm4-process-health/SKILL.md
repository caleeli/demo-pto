---
name: pm4-process-health
description: >-
  Reviews an existing ProcessMaker 4 process and linked assets for performance,
  maintainability, UX, security, technical debt, and simplification. Use when
  the user asks for a health check, optimization review, process audit, or
  structured findings on BPMN, screens, scripts, connectors, decision tables,
  PM Blocks, variables, or process configuration. Do not use to create or edit
  assets (use the asset skill), plan a new solution (pm4-overview), or start
  requests unless the user asked.
---

# PM4 process health check

Read-only review of an **existing** process and its linked assets. Produce a structured report; do not change assets unless the user asks to fix something.

References: [checklist.md](checklist.md) (checks by asset), [report-template.md](report-template.md) (output format), [lookup.md](lookup.md) (API to gather assets).

Apply rules from linked asset skills when judging findings: `pm4-process`, `pm4-screen`, `pm4-script`, `pm4-data-connector`, `pm4-decision-table`, `pm4-pm-block`, `pm4-sub-process`, `pm4-expressions`, `pm-user-task`, `pm4-signal`, `pm4-flow-genie`, `pm4-send-email`, `pm4-action-by-email`, `pm4-collection`.

## Not this skill

| User wants | Use instead |
|------------|-------------|
| Create or edit BPMN, a screen, script, connector, etc. | That asset's `pm4-*` skill |
| Design a new solution — which assets to build and in what order | `pm4-overview` |
| Fix one specific gateway, task, or screen after you already know the change | That asset's skill |
| Start a request or complete tasks to test runtime | Only if the user asked |
| Edit ProcessMaker platform source | `laravel-pm4-patterns`, `vue2-pm4-patterns` |

## Before reviewing

Ask only if missing:

1. **Process** — name or id on this instance.
2. **Scope** — full review (default) or one area (e.g. security only, BPMN only).
3. **Depth** — static design review (default) or include runtime metrics (open request counts, failed jobs) when the user provides access.

Then resolve the process id ([lookup.md](lookup.md)). **Never invent asset ids.**

## Workflow

```
- [ ] 1. Resolve process id and read metadata (status, category, manager, permissions)
- [ ] 2. Load BPMN: GET /api/1.0/processes/{id}/bpmn
- [ ] 3. Inventory linked assets from BPMN refs and pm:config (screens, scripts, tables, connectors, children, genies, blocks)
- [ ] 4. Optional: POST /api/1.0/processes/{id}/export for bundled assets (PM Blocks, nested processes)
- [ ] 5. Fetch each linked asset the BPMN references (screen config, script code, decision table rules, connector auth)
- [ ] 6. Run [checklist.md](checklist.md) per asset; note category and severity for each finding
- [ ] 7. Score and write the report using [report-template.md](report-template.md)
- [ ] 8. List quick wins (low effort, high impact) and long-term recommendations separately
```

Static review is enough for most tickets. Do not start requests unless the user asked.

## What to review

| Area | Ticket term | Source |
|------|-------------|--------|
| BPMN flow | BPMN flow | Process XML: tasks, gateways, flows, events, subprocesses |
| Screens | Screens | `pm:screenRef`, nested screens, collection schema screens |
| Scripts | Scripts | `pm:scriptRef`, screen watchers, computed scripts |
| Data Connectors | Data Connectors | Service tasks with `dataSource` in `pm:config` |
| Decision Tables | Decision Tables | Decision tasks, `pm:config` on decision service tasks |
| PM Blocks | PM Blocks | Call activities with `process_package_key` / block config |
| Variables | Variables | `data.*` usage across BPMN, screens, scripts; env vars |
| Process configuration | Process configuration | Status, start permissions, manager, notifications, launchpad, assignments |

Also note linked **sub-processes**, **signals**, **FlowGenies**, and **email** nodes when present — they affect health even when not in the ticket list.

## Finding categories

Every finding must map to one category:

- **Performance** — slow paths, redundant API/script work, heavy watchers, serial bottlenecks
- **Maintainability** — unclear names, duplication, missing docs, fragile expressions
- **UX** — missing validations, confusing task order, DISPLAY for input, accessibility gaps
- **Security** — hardcoded secrets, over-broad permissions, PII exposure, missing obfuscation
- **Technical debt** — deprecated patterns, orphan refs, draft assets on live process, legacy inline genie config
- **Simplification** — mergeable tasks, replace script with decision table, unused screens or variables

## Severity

| Level | When |
|-------|------|
| **Critical** | Process cannot run reliably, data loss, exposed secret, broken assignment |
| **High** | Frequent failures, security weakness, major maintainability blocker |
| **Medium** | Correct but fragile, suboptimal UX or performance |
| **Low** | Style, naming, minor redundancy |
| **Info** | Observation, optional improvement |

## After the report

If the user asks to **fix** a finding, switch to the asset skill for that change. Keep the health report as context; do not re-run the full review unless asked.

## Common mistakes

- Reviewing only BPMN without fetching linked screens and scripts
- Treating a design review as permission to publish or start requests
- Confusing `pm4-overview` (new solution) with this skill (existing process audit)
- Flagging `${screen:title}` git placeholders as broken refs without checking the instance
- Listing findings without severity, category, or a concrete recommendation
