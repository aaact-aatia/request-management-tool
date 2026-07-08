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
| 2026-07 (baseline) | 100% (14 of 14 baseline CIs) | 100% (documentation baseline verification dated 2026-07-08) | 40% (4 documented records in current baseline set; historical set still incomplete) | 0 | N/A (first cycle) | N/A (not yet measured in this doc set) |

## Measurement Notes

- Baseline source: docs/config-management initial inventory and audit pass.
- Traceability improved after initial backfill of three additional records, but full historical coverage remains in progress.
- Dependency mapping now covers intake, reporting/status, and survey lifecycle services.
- Secret-hygiene workflow is documented with baseline scan evidence, but monthly attestation cadence is still maturing.
- Drift correction time and incident trend start once at least one full monthly cycle is complete.
- High findings count is aligned to unresolved high items in config-audit-checklist.md.

## Commentary Log

For each month, record:
- What improved
- What regressed
- Top 1 to 3 corrective actions

### Example Entry

- Month: 2026-07
- Improvement: CI inventory expanded to include reporting and survey components, dependency map coverage expanded, and secret-hygiene workflow documented with baseline evidence.
- Regression: Change traceability is improved but still incomplete until remaining historical records are captured and approvals are formalized.
- Actions:
  1. Complete backfill for remaining production-impacting changes in earlier release windows.
  2. Operationalize monthly secret-hygiene owner attestation and approval capture.
  3. Add a recurring monthly drift check and record mean correction time.

## Reporting Rhythm

- Update this file monthly
- Review in release planning and retrospective
- Use trends to prioritize process improvements
