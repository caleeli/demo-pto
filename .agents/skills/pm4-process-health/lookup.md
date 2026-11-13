# Gather assets for a health review

Read-only lookups. Auth: session cookie or `Authorization: Bearer <token>`. Query contracts: [pm-api-contracts](../pm-api-contracts/SKILL.md).

## Process

```
GET /api/1.0/processes?filter=<name>&include=events,category&filter_without_assignments=true&per_page=15
GET /api/1.0/processes/{id}?include=category,events
GET /api/1.0/processes/{id}/bpmn
POST /api/1.0/processes/{id}/export
GET /api/1.0/process_launchpad/{id}
```

`GET /processes/{id}` returns `status`, `manager_id`, `warnings`, and notifications. `include=events` adds parsed start events (permissions live in each event's assignment config). `include=category` adds the category row. There is no `include=startEvents` or `include=participants` on this endpoint.

`export` returns a `process_package` JSON with bundled screens, scripts, data connectors, and nested processes when the export includes them. Use it when PM Blocks or deep subprocess trees make BPMN-only inventory incomplete.

## Inventory from BPMN

Parse the BPMN XML (or `export` package) for linked ids:

| Attribute / `pm:config` key | Asset type |
|-----------------------------|------------|
| `pm:screenRef` on `bpmn:task` | Form screen |
| `pm:scriptRef` on `bpmn:scriptTask` | Script |
| `pm:config.dataSource` on connector service task | Data connector |
| `implementation="package-decision-engine/decision-table"` + `pm:config` | Decision table |
| `pm:config.flow_genie_id` on FlowGenie service task | Flow Genie |
| `pm:config.processKey` / `process_package_key` on call activity | PM Block |
| `calledElement` numeric suffix on call activity | Child process ([pm4-sub-process](../pm4-sub-process/SKILL.md)) |

Screen watchers and nested `FormNestedScreen` controls require reading each screen's `config` JSON ([pm4-screen](../pm4-screen/SKILL.md)).

## Linked assets (from BPMN ids)

```
GET /api/1.0/screens/{id}
GET /api/1.0/scripts/{id}
GET /api/1.0/decision_tables/{id}
GET /api/1.0/data_sources/{id}
GET /api/1.0/package-ai/flow_genies/{id}
GET /api/1.0/processes/{childId}/bpmn
```

`GET /screens/{id}` and `GET /scripts/{id}` return the current published config/code. Optional `include=versions` loads version history when you need draft vs published comparison.

Resolve ids from BPMN attributes and `pm:config` JSON before calling. Screen list fallback:

```
GET /api/1.0/screens?filter=<title>&per_page=15
```

## Variables and configuration

- **Request variables** — collect every `data.*`, `{{var}}`, and FEEL path from BPMN, screen config, watchers, and script code; diff for orphans and typos ([pm4-expressions](../pm4-expressions/SKILL.md)).
- **Environment variables** — flag literals that look like secrets; confirm `{{env.NAME}}` / `getenv()` usage ([pm4-script](../pm4-script/SKILL.md)).
- **Process config** — `status`, `warnings` (non-empty means not valid for execution), start event assignment, `manager_id`, notification settings, launchpad (`process_launchpad`), unpublished draft (`PUT /processes/{id}/draft` exists when Designer has unsaved changes).

## Optional runtime signals (only if user asked)

```
GET /api/1.0/requests?pmql=process_id=<id> AND status="ACTIVE"&per_page=5
GET /api/1.0/requests/{requestId}/tokens?element_id=<nodeId>
```

There is no `GET /processes/{id}/tokens`. Tokens are per **request**. Use PMQL to sample active cases, then inspect tokens on a specific request when checking stuck Call Activities or parallel flows.
