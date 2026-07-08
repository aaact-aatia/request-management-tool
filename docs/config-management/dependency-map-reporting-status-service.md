# Dependency Map: Reporting and Status Service (Initial Baseline)

## Purpose

Document configuration-item dependencies for reporting and status capabilities to support impact analysis and change planning.

## Service Profile

- Service name: RMT Reporting and Status
- Business owner: IT Accessibility Office
- Technical owner: RMT Application Team
- Service description: Internal reporting, request status views, and resolved-request visibility for operational planning.
- Criticality: high

## Core CI Set

| CI ID | CI Name | Role in Service | Environment |
|---|---|---|---|
| RMT-APP-004 | Internal Reporting and Status Views | Primary reporting UI and status presentation | local/dev/prod |
| RMT-APP-001 | RMT Core PHP Application | Shared runtime and query orchestration | local/dev/prod |
| RMT-DB-001 | MySQL Primary Data Store | Source data for report and status queries | local/dev/prod |
| RMT-DOC-001 | Permissions Governance Documentation | Access boundary reference for report visibility and role behavior | dev/prod |
| RMT-PIPE-001 | Container Build and Publish Workflow | Deployment path for report-related changes | dev/prod |

## Upstream Dependencies

| Dependency CI ID | Dependency Name | Type | Failure Impact | Fallback |
|---|---|---|---|---|
| RMT-INFRA-002 | Azure App Service Container Host | Runtime | Reporting pages become unavailable or stale | Roll back to prior image and restart host |
| RMT-DB-001 | MySQL Primary Data Store | Data source | Reports fail or show incomplete status data | Read-only communication to stakeholders and DB recovery workflow |
| RMT-APP-003 | Session Bootstrap and Runtime Wiring | Authentication/session | Authorized users cannot access reporting paths reliably | Session diagnostics and restart procedure |

## Downstream Dependencies

| Dependent CI ID | Dependent Name | Type | Impact if this service fails |
|---|---|---|---|
| RMT-DOC-002 | Configuration Management Documentation Pack | Governance dependency | KPI and audit updates lose reliable report inputs |
| RMT-APP-005 | Client Survey and Feedback Workflow | Operational dependency | Survey-result visibility and follow-up analytics are delayed |

## Data and Integration Points

| Interface | Direction | Auth Type | Data Classification | Notes |
|---|---|---|---|---|
| MySQL request/status queries | inbound | DB credentials | protected | Primary source for report tables and status rollups |
| Session + role checks | inbound | App session | protected | Enforces report visibility by account type |
| Deployment pipeline artifact path | inbound | GitHub token and Azure context | internal | Governs release of reporting changes |

## Change Impact Notes

- High-risk dependencies:
  - RMT-DB-001 data integrity and query compatibility.
  - RMT-APP-003 session continuity for authorized report access.
- Coordinated change windows:
  - Report-query updates with schema changes touching request/status entities.
- Limited fallback areas:
  - Manual report extraction is not yet standardized for prolonged outages.

## Relationship Matrix (Optional)

Use H for hard dependency, S for soft dependency, blank for none.

| CI | RMT-APP-004 | RMT-APP-001 | RMT-DB-001 | RMT-APP-003 | RMT-INFRA-002 |
|---|---|---|---|---|---|
| RMT-APP-004 | - | H | H | H | H |
| RMT-APP-001 | H | - | H | H | H |
| RMT-DB-001 | S | S | - |  | S |
| RMT-APP-003 | S | S |  | - | S |
| RMT-INFRA-002 | H | H | H | H | - |

## Review Notes

- Initial baseline created: 2026-07-08
- Next review trigger: next release containing reporting query or permissions changes
- Owner action: add owner-confirmed recovery procedure for reporting outage scenario
