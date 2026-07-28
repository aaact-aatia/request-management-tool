# SSC Intake Flow Seeds

This directory contains intake-flow seeds specific to the SSC (Shared Services Canada)
IT Accessibility Office deployment.

## Files

| File | Description |
|---|---|
| `website-testing-v1.sql` | Website-testing intake flow, published version 1 |

## Classification mapping

The website-testing flow routes to these reference records:

| Purpose | Catalogue | Service | Subservice |
|---|---|---|---|
| Flow attached to | 8 (Accessibility audit) | 28 (Websites / web applications) | — |
| First assessment destination | 8 | 28 | 96 (Audit of representative sample) |
| Reassessment destination | 8 | 28 | 212 (Re-audit) |

## Versioning

`website-testing-v1.sql` is immutable once published.  To revise the flow:

1. Create `website-testing-v2.sql` with `version_number = 2`.
2. Register it in `scripts/load-intake-seed.sh`.
3. Never modify this file after it has been applied to any environment.

## Organization neutrality

The SSC-specific wording, URLs, and IDs in this directory belong only here.  The schema,
migration, and loader contain no SSC-specific knowledge.
