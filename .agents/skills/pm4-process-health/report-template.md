# Health review report template

Copy this structure into the response. Replace placeholders with real values from the review.

## Scoring

Start at **100**. Subtract per finding:

| Severity | Points |
|----------|--------|
| Critical | −15 |
| High | −10 |
| Medium | −5 |
| Low | −2 |
| Info | 0 |

Floor at **0**. Round to integer. Add a one-line rationale after the score (e.g. "Two High security findings on scripts drove the score down.").

Optional band:

| Score | Band |
|-------|------|
| 85–100 | Healthy |
| 70–84 | Needs attention |
| 50–69 | At risk |
| 0–49 | Critical |

---

```markdown
# Process health review: {ProcessName}

**Process id:** {id}  
**Reviewed:** {date}  
**Scope:** {full | area}  
**Overall health score:** {score}/100 ({band})

## Executive summary

{2–4 sentences: overall posture, top risks, best quick win.}

## Findings by asset

### Process configuration

| ID | Severity | Category | Finding | Recommendation |
|----|----------|----------|---------|----------------|
| P-1 | {level} | {category} | {what} | {action} |

### BPMN flow

| ID | Severity | Category | Finding | Recommendation |
|----|----------|----------|---------|----------------|
| B-1 | … | … | … | … |

### Screens

| ID | Screen | Severity | Category | Finding | Recommendation |
|----|--------|----------|----------|---------|----------------|
| S-1 | {title or id} | … | … | … | … |

### Scripts

| ID | Script | Severity | Category | Finding | Recommendation |
|----|--------|----------|----------|---------|----------------|
| X-1 | {name or id} | … | … | … | … |

### Data Connectors

| ID | Connector | Severity | Category | Finding | Recommendation |
|----|-----------|----------|----------|---------|----------------|
| D-1 | … | … | … | … | … |

### Decision Tables

| ID | Table | Severity | Category | Finding | Recommendation |
|----|-------|----------|----------|---------|----------------|
| T-1 | … | … | … | … | … |

### PM Blocks

| ID | Block | Severity | Category | Finding | Recommendation |
|----|-------|----------|----------|---------|----------------|
| K-1 | … | … | … | … | … |

### Variables

| ID | Severity | Category | Finding | Recommendation |
|----|----------|----------|---------|----------------|
| V-1 | … | … | … | … |

{Omit empty sections. Add Sub-processes, Signals, FlowGenie, or Email subsections when relevant.}

## Quick wins

Ordered by impact vs effort. Each item: **{finding id}** — {one sentence action}.

1. …
2. …

## Long-term recommendations

Structural or cross-cutting improvements (architecture, block extraction, collection migration, env var program).

1. …
2. …

## Assets reviewed

| Type | Count | Notes |
|------|-------|-------|
| Form tasks | n | |
| Script tasks | n | |
| Screens | n | |
| Decision tables | n | |
| Data connectors | n | |
| PM Blocks / call activities | n | |
| Sub-processes | n | |

## Out of scope

{What was not reviewed — runtime load test, unrelated processes, packages not installed, etc.}
```

---

## Writing rules

- Every finding needs **evidence** (node id, screen control, script line, config key).
- **Recommendations** must be actionable and name the skill if the user later asks you to implement (`pm4-screen`, `pm4-script`, …).
- Do not invent findings when data was not loaded — say what was missing instead.
- Separate **quick wins** (hours) from **long-term** (days/weeks).
