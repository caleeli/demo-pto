# Final migration report contract

Used by [pm3-pm4-migration](SKILL.md)
after its validated, explicitly approved local conversion. This is an
agent-authored Markdown report, not a new CLI, requirements schema, inventory
coverage checker, or parity runner.

## Evidence to read

| Evidence | What it establishes | What it does not establish |
| --- | --- | --- |
| Last complete passing `check-requirements.js --format json` result, with overall exit 0 | `summary.byDisposition`, `summary.nonAuto`, well-formed catalogs | Completeness, correct conversion, approval, or a migration score |
| The matching catalogs and companion MD | Source/target plans, tags, rationale, `decidedBy`, family-wide vs individual scope, follow-up notes | What the converter actually emitted |
| `pm-analyze report` inventory and source refs for the same XML `.pmx`/`.pmx2` | Counts, analyzer-auto classifications, unweighted rows, actual source identifiers | Catalog dispositions or successful migration |
| Approval of the unchanged validated plan | Authorization for the stated input/output/version/skip set | Import/publish, deployment, manual rebuilds, or executed tests |
| Convert exit/result and inspected output JSON | Whether the command succeeded and which assets are present | Importability, runtime behavior, satisfied requirements, or parity |

Do not combine an old package with a new catalog summary. If the evidence does
not match the approved conversion, identify the mismatch and stop the success
claim. Do not alter catalogs just to make their counts agree with output.
Preserve unresolved requirement IDs and explicitly state missing context rather
than dropping them from the report.

Inspect `type`, `version`, `root`, and `export`. The current package is
`process_package` v2, with a Process root. Inspect Process `attributes.bpmn`,
Screen names/types/configuration, and screen dependents as needed to describe
what was written. Separate Process/Screen assets from category scaffolding.
Count output exports independently of requirements. Package inspection is
read-only; it does not authorize repairing the package, importing it, or running
it on a PM4 instance.

The CLI's `✓ wrote` and printed skip list alone are insufficient to claim
particular assets were migrated. A non-zero convert exit is a failed conversion,
even if an earlier file still exists at the output path. If the command succeeds
but the artifact cannot be read or has an unexpected shape, disclose that
failure/uncertainty rather than substituting an assumed package summary.

## Required distinctions

- **`auto` disposition:** expected handling, not a verified success count.
- **`manual` / `hitl`:** list every open requirement/decision, rationale, and
  the relevant follow-up skills. A generated skeleton does not close it.
- **`excluded`:** list every requirement with rationale and `decidedBy`, not
  just a family total. State whether it was omitted via a family skip, is absent
  because the converter does not implement that family, or is an individual
  exclusion the CLI cannot enforce. Disclose any skeleton that is still present.
- **Analyzer-auto but not emitted:** independently list applicable gaps. Known
  cases include process-variable definitions, short/empty triggers not converted
  into scripts, input-document definitions, and subprocess call activities.
  A FORM binding named after a variable, generic task, or fallback `FormInput`
  does not establish that those capabilities were converted.
- **Unconfirmed mapping:** the package has no complete PM3 source-ID output
  trace. Names/counts alone are not proof that a particular requirement or asset
  was migrated. Do not manufacture a mapping or a coverage percentage.

These lists may overlap. Do not sum requirement counts, analyzer counts, and
output asset counts into a “converted total.” Rows outside the heuristic remain
inventory; they are not automatically excluded from migration scope.
Also retain any `UnmodeledTables`/`EmbeddedFiles` limitations: table records not
separately modeled and inventoried file metadata are not evidence that those
behaviors or file contents were converted.

## Report shape

Use actual evidence in place of placeholders. Omit no non-auto requirement; an
empty section should explicitly say “none,” not imply a missing check passed.
This shape can be presented in the conversation or saved at an agreed Markdown
path outside the catalogs.

```text
# Migration conversion report — <project>

## Conversion result
- Input: <actual XML .pmx or .pmx2 path>
- Output/version: <approved path and version>
- Convert result: <actual exit/result and inspection outcome>
- Effective family skips: <IDs, or none>

## Package contents (inspected)
- Process/BPMN: <actual export names/counts; partial structure, not runtime proof>
- FORM screens: <actual export names/counts and relevant limitations>
- Categories: <separately identified scaffolding>
- Source-to-output mappings not established: <uncertainties, or none>

## Catalog dispositions (last passing gate; requirement counts)
- Catalogs/requirements: <summary counts>
- auto: <n>; manual: <n>; hitl: <n>; excluded: <n>
- Structural validation only; not completeness, correctness, parity, a score, or approval.

## Excluded requirements
- <specId#ID>: <source/context>; decidedBy: <user|analysis>; <rationale>;
  <family skip/unsupported-family absence/individual-exclusion artifact evidence>

## Open manual/hitl requirements
- <specId#ID> (<disposition>): <source/context>; <rationale and remaining work>;
  follow-up: <named skills>

## Analyzer-auto items not emitted
- Process variables: <actual analyzer count>; no process-variable definitions emitted.
- <other applicable family/items and output evidence; identify any overlapping exclusions>

## Remaining work and authorization
- <open gaps and uncertainties; exclusions remain listed>
- <separate requested next step; no automatic import/publish or rebuild>
- <accurate package-generation outcome, not a completed-migration claim>
```

Never mark requirements `Verified` or present `traceability.testCases` as
executed tests. Even an empty manual/hitl list is not proof of completeness or
parity; the inventory completeness check and conformance runner do not exist.
