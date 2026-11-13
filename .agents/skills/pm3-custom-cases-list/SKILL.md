---
name: pm3-custom-cases-list
description: >-
  ProcessMaker 3 custom cases lists. Use for admin-defined inbox columns, filters,
  casesListSetup configuration, or custom case list builder.
---

# PM3 Custom Cases List

**Custom cases lists** let administrators define which columns and filters appear in user inboxes.

## When to use

- Customize inbox columns beyond defaults
- Add process-specific filters for case lists
- Enterprise custom case list builder feature

Configured in Setup → Custom Cases Lists (`casesListSetup`). Requires `PM_SETUP_CUSTOM_CASES_LIST` permission.

## Configuration

| Element | Purpose |
|---------|---------|
| Column definitions | Which case fields appear in list |
| Filters | Pre-defined search filters |
| Process scope | All processes or specific subset |
| Sort order | Default column ordering |

Uses case metadata and process variables available at list-query time.

## Rules

- Changes affect all users assigned to the custom list configuration
- Column data must be available in case list query (not all `@@` variables appear)
- Distinct from runtime saved searches (user-created filters)
- Enterprise may offer advanced list builder UI

## Links to other PM3 assets

| Asset | Relationship |
|-------|--------------|
| `pm3-variables` | Some columns map to case variables |
| `pm3-process` | Process-scoped column configuration |
| `pm3-report-table` | May source denormalized column data |

## Validation checklist

1. Columns display correct data in test inbox
2. Filters return expected case subsets
3. Performance acceptable with production case volume

## Common mistakes

- Column references variable not populated at list time
- Too many columns degrading inbox performance
- Custom list applied to wrong user groups
