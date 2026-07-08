# Dependency Map: Client Survey Lifecycle Service (Initial Baseline)

## Purpose

Document configuration-item dependencies for client survey and feedback capabilities used after request lifecycle milestones.

## Service Profile

- Service name: RMT Client Survey Lifecycle
- Business owner: IT Accessibility Office
- Technical owner: RMT Application Team
- Service description: Survey link generation, client survey submission, pending survey tracking, and survey results reporting.
- Criticality: medium

## Core CI Set

| CI ID | CI Name | Role in Service | Environment |
|---|---|---|---|
| RMT-APP-005 | Client Survey and Feedback Workflow | Primary survey lifecycle pages and business logic | local/dev/prod |
| RMT-APP-001 | RMT Core PHP Application | Shared runtime and workflow integration | local/dev/prod |
| RMT-DB-001 | MySQL Primary Data Store | Survey persistence and request linkage | local/dev/prod |
| RMT-EXT-001 | GC Notify API Integration | Survey-related communication and client notifications | dev/prod |
| RMT-APP-004 | Internal Reporting and Status Views | Survey results consumption for internal analysis | local/dev/prod |

## Upstream Dependencies

| Dependency CI ID | Dependency Name | Type | Failure Impact | Fallback |
|---|---|---|---|---|
| RMT-EXT-001 | GC Notify API Integration | API | Survey notifications delayed or not delivered | Manual communication and deferred resend |
| RMT-DB-001 | MySQL Primary Data Store | Data source | Survey responses cannot be persisted or linked to requests | Temporary intake pause and DB recovery workflow |
| RMT-INFRA-002 | Azure App Service Container Host | Runtime | Survey pages unavailable to clients | Roll back image and restore service availability |

## Downstream Dependencies

| Dependent CI ID | Dependent Name | Type | Impact if this service fails |
|---|---|---|---|
| RMT-APP-004 | Internal Reporting and Status Views | Data consumer | Survey trend reporting and follow-up insights become incomplete |
| RMT-DOC-002 | Configuration Management Documentation Pack | Governance dependency | KPI interpretation for client feedback metrics becomes weaker |

## Data and Integration Points

| Interface | Direction | Auth Type | Data Classification | Notes |
|---|---|---|---|---|
| Survey form submit and persistence path | bidirectional | App/session + DB credentials | protected | Writes client responses and links to requests |
| GC Notify outbound notification path | outbound | API key | protected | Sends survey links and status communications |
| Survey results read path | outbound | App/session | protected | Consumed by internal reporting views |

## Change Impact Notes

- High-risk dependencies:
  - RMT-EXT-001 message delivery reliability.
  - RMT-DB-001 integrity for survey-request association.
- Coordinated change windows:
  - Survey template/message updates coordinated with notification integration owners.
  - Schema changes impacting survey tables coordinated with reporting consumers.
- Limited fallback areas:
  - Extended GC Notify outage handling remains partially manual.

## Relationship Matrix (Optional)

Use H for hard dependency, S for soft dependency, blank for none.

| CI | RMT-APP-005 | RMT-APP-001 | RMT-DB-001 | RMT-EXT-001 | RMT-INFRA-002 |
|---|---|---|---|---|---|
| RMT-APP-005 | - | H | H | H | H |
| RMT-APP-001 | H | - | H | S | H |
| RMT-DB-001 | S | S | - |  | S |
| RMT-EXT-001 | S | S |  | - |  |
| RMT-INFRA-002 | H | H | H | S | - |

## Review Notes

- Initial baseline created: 2026-07-08
- Next review trigger: next release containing survey flow, notification, or schema changes
- Owner action: formalize outage communication playbook for survey-notification failures
