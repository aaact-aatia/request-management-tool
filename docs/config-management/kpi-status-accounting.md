# KPI and Status Accounting

## Purpose

Track a small set of configuration management metrics to measure integrity, traceability, and improvement over time.

## KPI Set

| KPI | Definition | Target | Owner | Frequency |
|---|---|---|---|---|
| CI completeness rate | Percent of CIs with all required fields populated | >= 95% | Config owner | Monthly |
| CI recency rate | Percent of critical prod CIs verified in last 90 days | >= 95% | Config owner | Monthly |
| Change traceability rate | Percent of prod changes with full change record and approvals | 100% | Release owner | Monthly |
| Audit findings (high) | Count of unresolved high severity audit findings | 0 | Service owner | Monthly |
| Drift correction time | Average days to close drift findings | <= 14 days | Service owner | Monthly |
| Config-related incident count | Incidents where misconfiguration is a confirmed contributor | Downward trend | Service owner | Monthly |

## Monthly Snapshot

| Month | CI completeness | CI recency | Traceability | High findings | Drift correction days | Config incidents |
|---|---|---|---|---|---|---|
| 2026-07 (baseline) | 100% (12 of 12 baseline CIs) | 100% (documentation baseline verification dated 2026-07-08) | 10% (1 baseline change record established; historical set pending) | 2 | N/A (first cycle) | N/A (not yet measured in this doc set) |

## Measurement Notes

- Baseline source: docs/config-management initial inventory and audit pass.
- Traceability is intentionally low for baseline month because historical records are not backfilled yet.
- Drift correction time and incident trend start once at least one full monthly cycle is complete.
- High findings count is aligned to unresolved high items in config-audit-checklist.md.

## Commentary Log

For each month, record:
- What improved
- What regressed
- Top 1 to 3 corrective actions

### Example Entry

- Month: 2026-07
- Improvement: Initial CI inventory and dependency map baseline established.
- Regression: Change traceability remains incomplete until historical records are backfilled.
- Actions:
  1. Backfill change records for production-impacting changes in active release scope.
  2. Add a recurring monthly drift check and record mean correction time.
  3. Add service-level incident tagging for confirmed config-related incidents.

## Reporting Rhythm

- Update this file monthly
- Review in release planning and retrospective
- Use trends to prioritize process improvements
