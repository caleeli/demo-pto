# Lookup instance ids (do not invent)

Form Task and Manual Task XML store **numeric** screen, user, and group ids. Resolve them from the running instance. Guessing `1` or copying ids from another environment will attach the wrong screen or assign nobody.

Auth: the authorized session cookie or bearer token; never print or commit credentials. Confirm the instance host, permissions, and target version. Query contracts: [pm-api-contracts](../pm-api-contracts/SKILL.md). API/permission ownership: [asset-map.md](asset-map.md).

## Existing process and node

```
GET /api/1.0/processes?filter=Invoice&per_page=15
GET /api/1.0/processes/{processId}
GET /api/1.0/processes/{processId}/bpmn
```

Confirm exact process name/ID and the intended BPMN node ID/type. Paginate if the result is ambiguous. A normal process response can omit BPMN; `/bpmn` returns raw XML. Inspect draft/published state before a PUT so another pending draft is not accidentally published.

Keep BPMN node IDs unique across the model, including the process ID, and retain them for ordinary edits. Runtime task IDs identify tokens, not these nodes. If a task label matches several nodes, ask rather than guessing.

Read and preserve the existing process `description`, `manager_id`, `task_notifications`, and metadata. Lookups do not authorize creating a process or running a request.

## Screen For Input

Select the screen type for the intended task; `status` is typically `ACTIVE`:

| Task | Screen type |
|------|-------------|
| Form Task | `FORM`, or `CONVERSATIONAL` with `package-conversational-forms` installed wherever edited/run |
| Manual Task | `DISPLAY` instructions/context; no input or Submit controls |

```
GET /api/1.0/screens?filter=Approve%20Invoice&per_page=15
GET /api/1.0/screens?filter=Warehouse%20Instructions&type=DISPLAY&per_page=15
```

Pick the row whose **title** matches and verify its `type`; read `GET /api/1.0/screens/{id}` if needed. Use that `id` as `pm:screenRef`. Finding a screen of a package-backed type is not proof its renderer is installed; check package availability ([pm4-screen](../pm4-screen/screen-types.md#conversational)).

If nothing matches or the type is wrong, ask for an existing correctly typed screen, or create one only if asked through [pm4-screen](../pm4-screen/SKILL.md). The [create-and-import.md](create-and-import.md) helper covers the existing Form example. Do not attach a DISPLAY screen to a Form Task or an interactive screen to a Manual Task.

Category for `POST /screens`:

```
GET /api/1.0/screen_categories?per_page=15
```

Only needed when screen creation was explicitly requested. Use an existing `ACTIVE` category ID; do not assume `1`. Screen construction/drafts belong to [pm4-screen](../pm4-screen/SKILL.md).

## Users and groups

`user_group` assignment:

```
GET /api/1.0/users?filter=jane&per_page=15
GET /api/1.0/groups?filter=approvers&per_page=15
```

`pm:assignedUsers` / `pm:assignedGroups` are **comma-separated ids** for `user_group`.

Verify exact user/group identity and eligible members, not just that the group row exists. Do not create users/groups merely to configure a task. If the group is missing, ask; create it only with explicit authorization and use the returned ID.

An empty Self Service pool is unclaimable. Other empty/invalid candidate selections can fall back to Process Managers or fail; neither outcome validates the intended pool. A seeded empty group is sufficient for an API-edit assertion, not a runtime assignment/claim test.

For `process_variable`, those attributes are **FEEL data expressions**, not literal lookup lists or Mustache templates. Confirm the actual context and that the value exists before activation; it must resolve to real user/group IDs (a scalar or array). For top-level `reviewer_ids`, use `reviewer_ids`, not an invented `data.reviewers` wrapper ([expression contract](bpmn-attrs.md#process-variable-expressions)).

For `rule_expression`, look up each rule/default assignee and preserve ordered inspector-generated entries. A default with a null expression belongs last; empty expressions evaluate true. Verify matching rules and the no-match path without assuming Process Manager fallback is the requested default.

## Process managers

`process_manager` assignment needs an existing Process Manager list. Look up the intended users and use the process API's `manager_id` **array**, with actual numeric IDs, only when changing managers was requested. Preserve other managers and metadata. The inspected API normalizes positive numeric IDs and permits at most ten; it does not replace the need to look up real users.

The getter exposes an array (including normalized legacy scalar values); storage is in process properties. Assignment reads the managers on the request's process version and cycles when several exist. A manager edit on the current process does not prove an old request uses the new list.

This is **not** the requester's or assignee's `users.manager_id`. Automatic/assignee-triggered user-manager escalation requires the documented [integration capability](bpmn-attrs.md#pmconfig-json-camelcase-keys); do not set `assignToManager` and assume bare core will resolve a boss.

## Packages and optional references

- Check CONVERSATIONAL support wherever edited/run; stored type alone is not proof of installation.
- DISPLAY, task destination, and interstitial screen IDs must refer to the intended screens. If a dashboard/external destination was requested, use the current inspector's value/JSON representation, not a guessed asset ID or URL field.
- Shared event notifications reference the BPMN node ID and process metadata; custom EMAIL screens and Web Entry/Actions by Email settings have separate capability contracts.
- Stop on unavailable assets or 403 responses. Do not switch actor or fabricate replacement assets to bypass access restrictions.

## Process category (new process)

```
GET /api/1.0/process_categories?per_page=15
```

Use an `ACTIVE` `id` on `POST /api/1.0/processes`.

## Portable git refs

Some repos write `pm:screenRef="${screen:title}"` and resolve title → id at deploy time. That is a **repo convention**, not core ProcessMaker XML. On the instance after save, the attribute is numeric. When editing such a repo, keep the named ref in git; still verify the titled screen exists on the instance with the correct type for the task.
