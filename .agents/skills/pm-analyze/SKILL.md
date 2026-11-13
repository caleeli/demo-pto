---
name: pm-analyze
description: >-
  Run pm-analyze/pm-migrate CLI: report/convert PM3 .pmx/.pmx2 to PM4 JSON,
  attach-scripts, or report-input/output-documents / report-email-notifications /
  report-web-entry. Not catalogs, PM4 import/publish, or migration scoping.
---

# pm-analyze

PM3 migration CLI skill in the skills monorepo.
CLI source: [tools/pm-migrate](../../tools/pm-migrate/README.md).

## When to use

- Coverage / inventory report for a PM3 export (`.pmx` or `.pmx2`).
- Convert export → PM4 `process_package` JSON (v2).
- After [pm3-pm4-triggers](../pm3-pm4-triggers/SKILL.md) Phase 3: `attach-scripts` on a convert package.
- After [pm3-pm4-input-documents](../pm3-pm4-input-documents/SKILL.md): `report-input-documents` → `input-doc-plan.json`.
- After [pm3-pm4-output-documents](../pm3-pm4-output-documents/SKILL.md): `report-output-documents` → `output-doc-plan.json`.
- After [pm3-pm4-email-notifications](../pm3-pm4-email-notifications/SKILL.md): `report-email-notifications` → `email-notification-plan.json`.
- After [pm3-pm4-web-entry](../pm3-pm4-web-entry/SKILL.md): `report-web-entry` → `web-entry-plan.json` (`WEB_ENTRY` + `webEntryEvent`).
- Print PM3/PM4 homologation links (`list` or `table`) after convert/report.

## Not this skill

| User wants | Use instead |
|------------|-------------|
| FR/NFR catalogs, dispositions | Internal [manage-requirements](../../.agents/skills/manage-requirements/SKILL.md) |
| Migration phases, gaps, post-import checklist | [pm3-pm4-migration](../pm3-pm4-migration/SKILL.md) |
| Import / publish package in PM4 | [pm4-process](../pm4-process/SKILL.md) |
| Change Go translators / architecture | [ARCHITECTURE.md](../../tools/pm-migrate/docs/ARCHITECTURE.md) |

## CLI binary

Prebuilt per OS/arch under `skills/pm-analyze/bin/`. Build or refresh from repo root:

```bash
make pm-analyze        # current OS only
make pm-analyze-all    # linux + darwin, all arch (CI / commit binaries)
```

Run the wrapper from anywhere (`report`, `convert`, `attach-scripts`):

```bash
<skills-checkout>/skills/pm-analyze/pm-analyze report --pmx2 path/to/export.pmx2
<skills-checkout>/skills/pm-analyze/pm-analyze attach-scripts --package process.json --plan plan.json --out out.json
```

## Working directory (source / tests)

For `go run`, tests, or translator work, use the pm-migrate project root:

```bash
cd <skills-checkout>/tools/pm-migrate
```

Requires Go (`brew install go` if missing). Prefer `make test` over ad-hoc flags.

## Workflow

1. Confirm input path (`.pmx` or `.pmx2`) and action: `report`, `convert`, `attach-scripts`, `report-input-documents`, `report-output-documents`, `report-email-notifications`, or `report-web-entry`.
2. A direct request to analyze, report, or migrate a named local export authorizes the read-only `report` command (and named read-only `report-*` plan commands). Ask before running only when the path, input type, or requested action is missing or ambiguous.
3. `convert` writes a package. Confirm `--out` and `--version` (default `1.0`), present the proposed write, then wait for a later explicit approval before running it.
4. `attach-scripts` writes a package. Confirm `--package`, `--plan`, and `--out` (plan from Phase 3 triggers), then wait for a later explicit approval before running it.
5. If homologation links are needed, confirm `--pm3-base`, `--pm4-base`, `--links-format`, and how the user will supply `--pm4-token` (env preferred).
6. Prefer the prebuilt wrapper `skills/pm-analyze/pm-analyze`; use `go run` from `tools/pm-migrate` only when hacking the CLI.
7. After `convert`, hand off **triggers** → [pm3-pm4-triggers](../pm3-pm4-triggers/SKILL.md), with mail peel-offs → [pm3-pm4-email-notifications](../pm3-pm4-email-notifications/SKILL.md); **subprocesses** → [pm3-pm4-subprocess](../pm3-pm4-subprocess/SKILL.md); **input documents** → [pm3-pm4-input-documents](../pm3-pm4-input-documents/SKILL.md) (`merge` FileUploads are in convert; `new_screen` stays manual); **output documents** → [pm3-pm4-output-documents](../pm3-pm4-output-documents/SKILL.md) (`email_body` EMAIL screen + Send Email are in convert; PDF file stays HITL; step `generate_pdf` in convert when eligible; trigger/JRXML manual); **web entry** → [pm3-pm4-web-entry](../pm3-pm4-web-entry/SKILL.md) when XML has `webEntryEvent` or public start; after `attach-scripts` or Modeler wiring, import → [pm4-process](../pm4-process/SKILL.md); gaps → [pm3-pm4-migration](../pm3-pm4-migration/SKILL.md).

### Report response data

For a user-facing analysis response, run `report` with `--json` and use its
parsed envelope as the source of truth. Do not scrape the box-drawn table or
terminal hyperlinks from the human output.

- Use `schema_version`, `process.name`, `process.uid`, and `report.rows` (each
  with `translator_id`, `disposition`, counts). Render `report.Rows` in returned
  order. Keep every row, including rows whose counts are zero.
- Use every `report.ManualDetail` entry exactly as returned. Preserve repeated
  findings and their full text. `report.hitl` and `report.manual_detail` carry
  the same manual work; prefer `hitl` for agent actions (`recommended_skills`).
- Use `parse.unmodeled_tables` for XML tables not mapped in `pkg/modelo`.
- Use `report.PM3` and `report.PM4` for homologation links, from
  `homologation.pm3_links`, `homologation.pm4_links`, and `homologation.pm4_id_map`.
- Use **`migration_ledger.elements`** for element-by-element status (`converted`, `partial`, `not_converted`, `pending`, `not_attempted`, `unsupported`) and **`properties`** (`mapped` / `unmapped`). After convert, use `convert --ledger-out` for post-convert state.
  An absent PM4 ID or URL remains unresolved; never infer a screen ID or construct a link.
  - **Grids:** a PM3 **`type: grid`** control may appear as **`partial`** with message **`grid requires convert context for Record List page`** in analyze-only inventory, while the aggregated **`report.rows`** entry `translator_id: grid` can still count as **auto**. Treat **`partial`** in the ledger as the per-control signal; confirm against **`convert`** output or package screens ([pm3-pm4-migration](../pm3-pm4-migration/SKILL.md)).
- Prefer `report --json --quiet` for machine-only stdout (errors on stderr).
- Use `validate` before/after convert; use `plan` for post-convert checklist JSON.
- Use `estimate` for migration **hours** (read-only; does not affect convert/import). Default human output is the PM3→PM4 table; agents use `--json --quiet` (`effort_summary`, `artifact_table`). Inputs: `--pmx2` + `--ledger` (from `convert --ledger-out`), or `convert --estimate` / `--estimate-json`. Calibrate `hours_per_story_point` in `--weights`. See [ESTIMATE.md](../../tools/pm-migrate/docs/ESTIMATE.md).
- The report action is analysis only. Its counts and links do not establish a
  conversion result, import/publish result, runtime behavior, or migration
  parity.
- When **`translator_id` is `subprocess`** and count &gt; 0, state in the user-facing reply: **import and publish the child process**, then **configure the Sub Process on the parent (main) process** in Modeler ([pm3-pm4-subprocess](../pm3-pm4-subprocess/SKILL.md)). `convert` alone does not finish that on PM4.

### Successful report response

After a successful report, respond in this exact order. Use GitHub-flavored
Markdown tables for structured data; do not paste the CLI's ASCII table or
replace the tables with prose.

1. **Analysis status** — command status, source export, detected process name,
   and action. State that the result is analysis only and that no conversion,
   import, or publish action was performed.
2. **PM3 inventory** — render the inventory table from `report.Rows` in returned
  order, including rows whose counts are zero.
3. **Manual / rebuild details** — render each `report.ManualDetail` entry exactly as returned. If there are none, write `None` below the
   heading instead of omitting it.
4. **Homologation links** — use `report.PM3` and `report.PM4` for homologation links; render separate PM3 and PM4 tables from the
   returned link data. If a link group has no entries, write `None` below that
   group instead of omitting it.
5. **Limitations and next actions** — retain the analysis-only limitation and
   list the remaining work as not started.

Use this shape, with returned data replacing the placeholders:

```markdown
## PM3 migration analysis

| Field | Value |
| --- | --- |
| Status | Report completed |
| Source | `<source export>` |
| Process | `<detected process name>` |
| Action | Analysis only; no conversion, import, or publish |

### PM3 inventory

| Asset type | Count | Auto-mapped | Manual / rebuild |
| --- | ---: | ---: | ---: |
| `<asset type>` | `<count>` | `<automated>` | `<manual>` |

### Manual / rebuild details

| Asset type | Finding |
| --- | --- |
| `<asset type>` | `<full finding text>` |

### Homologation links

#### PM3

| Asset | Link |
| --- | --- |
| `<asset>` | `<returned PM3 link>` |

#### PM4

| Asset | Link or status |
| --- | --- |
| `<asset>` | `<returned PM4 link or Unresolved>` |

### Limitations and next actions

| Priority | Action | Status |
| ---: | --- | --- |
| `<priority>` | `<follow-up action>` | Not started |
```

When rendering a manual-detail row, split only its leading asset-type label
from the finding when available; preserve the remainder verbatim. If a PM4
screen has no ID or builder URL, show `Unresolved` rather than making up a URL.

#### Next-action priority

Inspect the `Triggers` inventory row. When its count is greater than zero, make
trigger analysis the first row in the next-actions table:

| Priority | Action | Status |
| ---: | --- | --- |
| 1 | Analyze trigger and webbot mappings with [pm3-pm4-triggers](../pm3-pm4-triggers/SKILL.md) | Not started |

Then list, as separate later actions:

1. Convert only through the applicable conversion workflow and after its
   required confirmation.
2. Import or publish only through [pm4-process](../pm4-process/SKILL.md) on a
   separate request.
3. Review broader migration gaps through
   [pm3-pm4-migration](../pm3-pm4-migration/SKILL.md).

When the trigger count is zero, do not add a trigger-analysis row. When the
**Subprocesses** inventory row count is greater than zero, add a row (after
triggers if both apply):

| Priority | Action | Status |
| ---: | --- | --- |
| N | Import and **publish the child process**; import the parent and **configure Sub Process on the main process** in Modeler ([pm3-pm4-subprocess](../pm3-pm4-subprocess/SKILL.md)) | Not started |

Do not claim
that a report, a favorable count, or listing these next actions authorizes or
performs any of them.

### Report failure

If `pm-analyze report` exits non-zero, cannot parse its JSON output, or returns
an invalid/incomplete report envelope:

1. State that analysis failed and identify the source export.
2. Show the actionable command error or parsing error without inventing an
   inventory, manual detail, process name, or links.
3. Stop. Do not run `convert`, import or publish anything, start trigger
   migration, or render the successful-report tables from stale or partial
   output.

Missing files, invalid XML, unsupported input, and malformed JSON all follow
this failure path. Resolve the input or command error and rerun `report` before
continuing.

## Commands

### Tests

```bash
make test
# equivalent:
go test ./...
```

### Report

```bash
go run ./cmd/pm-analyze report --pmx2 <path.pmx2>
go run ./cmd/pm-analyze report --pmx <path.pmx>
go run ./cmd/pm-analyze report --pmx2 testdata/minimal.pmx2
go run ./cmd/pm-analyze report --pmx2 testdata/minimal.pmx2 --json --quiet
go run ./cmd/pm-analyze validate --pmx2 testdata/minimal.pmx2 --json
go run ./cmd/pm-analyze plan --pmx2 testdata/minimal.pmx2 --json
```

Optional: `--json`, `--quiet`, `--version 1.0`, link flags below.

### Migration directory (one per PM3 process)

See [MIGRATION.md](../../tools/pm-migrate/docs/MIGRATION.md). Multiple processes = multiple dirs; paths are **never** baked into the tool — use `--dir`, `PM_ANALYZE_ROOT`, or `PM_ANALYZE_MIGRATION_DIR` on each machine.

```bash
go run ./cmd/pm-analyze migration init --pmx2 pro1.pmx2 --dir /your/migrations/migration-pro1
go run ./cmd/pm-analyze migration use /your/migrations/migration-pro1   # remembers parent for list/use by name
go run ./cmd/pm-analyze migration list
go run ./cmd/pm-analyze migration use migration-pro3
go run ./cmd/pm-analyze migration status
```

Active dir: `--dir` → `PM_ANALYZE_MIGRATION_DIR` → `~/.pm-analyze/current`.

### Convert (offline / no PM4 API)

```bash
<skills-checkout>/skills/pm-analyze/pm-analyze convert --pmx2 <path.pmx2> --out <out.json> --version 1.0
# subprocess: place {child_pro_uid}.pmx2 beside parent, or repeat:
<skills-checkout>/skills/pm-analyze/pm-analyze convert --pmx2 parent.pmx2 --out out.json \
  --subprocess-pmx2 child.pmx2
```

After convert with subprocesses, load [pm3-pm4-subprocess](../pm3-pm4-subprocess/SKILL.md).

### Attach scripts (after convert + Phase 3 plan)

```bash
<skills-checkout>/skills/pm-analyze/pm-analyze attach-scripts \
  --package <process.json> \
  --plan <trigger-plan.json> \
  --out <process-with-scripts.json>
```

Plan example: [tools/pm-migrate/testdata/attach-scripts-plan.json](../../tools/pm-migrate/testdata/attach-scripts-plan.json). Does **not** generate the plan from PM3 — use [pm3-pm4-triggers](../pm3-pm4-triggers/SKILL.md) Phase 3.

### Input-document plan

```bash
<skills-checkout>/skills/pm-analyze/pm-analyze report-input-documents \
  --pmx2 <path.pmx2> --out input-doc-plan.json
```

Example: [tools/pm-migrate/testdata/input-doc-plan.json](../../tools/pm-migrate/testdata/input-doc-plan.json). `convert` emits FileUpload for `merge`. Review leftovers with [pm3-pm4-input-documents](../pm3-pm4-input-documents/SKILL.md).

### Output-document plan

```bash
<skills-checkout>/skills/pm-analyze/pm-analyze report-output-documents \
  --pmx2 <path.pmx2> --out output-doc-plan.json
```

Example: [tools/pm-migrate/testdata/output-doc-plan.json](../../tools/pm-migrate/testdata/output-doc-plan.json). `convert` emits `email_body` and eligible step `generate_pdf`. Review leftovers with [pm3-pm4-output-documents](../pm3-pm4-output-documents/SKILL.md).

### Email-notification plan

```bash
<skills-checkout>/skills/pm-analyze/pm-analyze report-email-notifications \
  --pmx2 <path.pmx2> --out email-notification-plan.json
```

Example: [tools/pm-migrate/testdata/email-notification-plan.json](../../tools/pm-migrate/testdata/email-notification-plan.json). `convert` emits safe task notifications, shared EMAIL screens, and safe Email Event Send Email tasks. Use `--notification-table notifications.csv|json` for sanitized external subject/body content, or `--skip emailnotif` to omit this family. Review HITL actions with [pm3-pm4-email-notifications](../pm3-pm4-email-notifications/SKILL.md).

### Web-entry plan

```bash
<skills-checkout>/skills/pm-analyze/pm-analyze report-web-entry \
  --pmx2 <path.pmx2> --out web-entry-plan.json
```

Example: [tools/pm-migrate/testdata/web-entry-plan.json](../../tools/pm-migrate/testdata/web-entry-plan.json). `convert` emits Start `web_entry` for canvas `html_form_task`; other patterns stay manual — [pm3-pm4-web-entry](../pm3-pm4-web-entry/SKILL.md).

### Convert with PM3/PM4 links

```bash
go run ./cmd/pm-analyze convert \
  --pmx2 <path.pmx2> \
  --out <out.json> \
  --version 1.0 \
  --pm3-base http://localhost:8088 \
  --pm4-base https://127.0.0.5:8092 \
  --pm4-token "$PM4_TOKEN" \
  --pm4-insecure \
  --links-format table
```

Same flags work with `--pmx` and with `--links-format list` (default).

## Flags

| Flag | Default | Notes |
|------|---------|-------|
| `--pmx2` / `--pmx` | required (one) | `.pmx2` granular or `.pmx` full; mutually exclusive if both set to different values |
| `--out` | required for convert / attach-scripts; optional for report plan commands | Output JSON path (stdout if omitted on `report-*`) |
| `--package` | required for attach-scripts | Input package from `convert` |
| `--plan` | required for attach-scripts | Trigger placement plan JSON |
| `--version` | `1.0` | Version label in PM4 screen titles; must match between report and convert when resolving IDs |
| `--pm3-base` | `http://localhost:8088` | PM3 origin; `/sysworkflow/en/neoclassic` is appended unless already present |
| `--pm4-base` | tool default | PM4 origin for screen-builder links |
| `--pm4-token` | empty | Bearer token for PM4 API screen-id resolve; **never hardcode** — use `$PM4_TOKEN` or ask the user |
| `--pm4-insecure` | false | Skip TLS verify (local dev) |
| `--links-format` | `list` | `list` or `table` |
| `--pm4-ids-file` | `<export>.pm4-ids.json` if present | JSON map `dyn_uid` → screen id |
| `--pm4-ids` | empty | Inline map: `dyn_uid=103,dyn_uid2=104` |
| `--skip` | empty | Convert only: comma-separated translator ids (e.g. `dbconnection,trigger`) |
| `--notification-table` | empty | Convert only: CSV/JSON email subject/body rows keyed by task UID/title and event |
| `--json` | false | Report only: emit JSON instead of table |

## Security

- Never embed Bearer tokens in this skill, committed notes, or PR text.
- Prefer `export PM4_TOKEN='…'` in the user shell; pass `"$PM4_TOKEN"` on the CLI.
- Do not log full tokens in chat responses.

## Related docs

- [tools/pm-migrate/README.md](../../tools/pm-migrate/README.md)
- [ARCHITECTURE.md](../../tools/pm-migrate/docs/ARCHITECTURE.md)
- [ROADMAP.md](../../tools/pm-migrate/docs/ROADMAP.md)
- [import-package-rules.md](../pm3-pm4-migration/import-package-rules.md)
