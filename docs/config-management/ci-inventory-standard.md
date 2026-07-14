# CI Inventory Standard

## Purpose

Define the minimum data required to maintain a reliable configuration item (CI) inventory for RMT.

## CI Classes

Use one class per CI:
- Application component
- Database component
- Runtime or host component
- Deployment pipeline component
- External integration
- Documentation or governance artifact

## Required Fields

Every CI entry must include:

| Field | Required | Description |
|---|---|---|
| CI ID | Yes | Stable unique identifier (example: RMT-APP-001) |
| CI Name | Yes | Human-readable name |
| CI Class | Yes | One of the approved CI classes |
| Environment | Yes | local, dev, test, prod |
| Owner | Yes | Team or role accountable |
| Criticality | Yes | low, medium, high, critical |
| Source of Truth | Yes | File, system, or location where this config is authoritative |
| Related Service | Yes | Business service supported |
| Dependencies | Yes | Upstream and downstream references |
| Last Verified Date | Yes | Date the CI details were validated |
| Verification Method | Yes | manual check, script, pipeline validation |
| Notes | No | Optional implementation context |

## Naming Convention

Use:
- Prefix: RMT
- Domain segment: APP, DB, PIPE, EXT, DOC, INFRA
- Sequence: 3 digits

Example IDs:
- RMT-APP-001
- RMT-DB-001
- RMT-EXT-003

## Inventory Quality Rules

1. No CI with an empty owner.
2. No production CI without dependencies documented.
3. Last verified date must be within 90 days for production CIs.
4. Deprecated CIs must be marked and linked to replacement CI.

## Starter Inventory Template

| CI ID | CI Name | CI Class | Env | Owner | Criticality | Source of Truth | Related Service | Dependencies | Last Verified | Verification Method |
|---|---|---|---|---|---|---|---|---|---|---|
| RMT-APP-001 | RMT PHP Web App | Application component | dev/prod | App Team | critical | app/ and Docker image | Request intake and management | RMT-DB-001, RMT-EXT-001 | YYYY-MM-DD | release smoke test |
| RMT-DB-001 | MySQL Database | Database component | dev/prod | Data Owner | critical | database/ schema and env vars | Request storage and workflow | RMT-APP-001 | YYYY-MM-DD | migration + query validation |
| RMT-PIPE-001 | Container Publish Workflow | Deployment pipeline component | dev/prod | DevOps Owner | high | .github/workflows/publish-container.yml | Release delivery | RMT-APP-001 | YYYY-MM-DD | workflow run verification |

## Initial Baseline Inventory (RMT)

This baseline is populated from repository documentation and source files and should be treated as the initial draft inventory.

| CI ID | CI Name | CI Class | Env | Owner | Criticality | Source of Truth | Related Service | Dependencies | Last Verified | Verification Method |
|---|---|---|---|---|---|---|---|---|---|---|
| RMT-APP-001 | RMT Core PHP Application | Application component | local/dev/prod | Application Team | critical | app/ | Request intake, triage, and request lifecycle | RMT-DB-001, RMT-INFRA-001, RMT-EXT-001 | 2026-07-08 | repository documentation review |
| RMT-APP-002 | Public Intake Flow (openrequest pages) | Application component | local/dev/prod | Application Team | critical | app/openrequest.php, app/openrequest2.php, app/openrequest3.php | Public request submission | RMT-APP-001, RMT-DB-001 | 2026-07-08 | repository documentation review |
| RMT-APP-003 | Session Bootstrap and Runtime Wiring | Application component | local/dev/prod | Application Team | high | app/sql.php, app/includes/session.php | Authentication continuity and session state | RMT-DB-002, RMT-INFRA-001 | 2026-07-08 | repository documentation review |
| RMT-APP-004 | Internal Reporting and Status Views | Application component | local/dev/prod | Application Team | high | app/reports.php, app/report-status.php, app/indexresolved.php | Operational reporting and request status visibility | RMT-APP-001, RMT-DB-001, RMT-DOC-001 | 2026-07-08 | repository documentation review |
| RMT-APP-005 | Client Survey and Feedback Workflow | Application component | local/dev/prod | Application Team | medium | app/client-survey.php, app/client-survey-link.php, app/client-survey-results.php | Client feedback capture and follow-up insights | RMT-APP-001, RMT-DB-001, RMT-EXT-001 | 2026-07-08 | repository documentation review |
| RMT-DB-001 | MySQL Primary Data Store | Database component | local/dev/prod | Data Owner | critical | database/schema.sql, database/reference.sql | Request and admin data persistence | RMT-APP-001, RMT-APP-002 | 2026-07-08 | schema and docs review |
| RMT-DB-002 | MySQL Session Storage Table | Database component | dev/prod | Data Owner | high | database/session_handler.sql, docs/MYSQL_SESSIONS.md | Shared session persistence | RMT-APP-003, RMT-INFRA-002 | 2026-07-08 | docs and SQL review |
| RMT-INFRA-001 | Docker Compose Local Runtime | Runtime or host component | local | DevOps Owner | high | docker-compose.yml, Dockerfile, entrypoint.sh | Local development and validation | RMT-APP-001, RMT-DB-001 | 2026-07-08 | config file review |
| RMT-INFRA-002 | Azure App Service (Container Host) | Runtime or host component | dev/prod | Cloud Operations | critical | .github/workflows/publish-container.yml | Hosted runtime for deployed application | RMT-EXT-002, RMT-PIPE-001 | 2026-07-08 | workflow and deployment docs review |
| RMT-PIPE-001 | Container Build and Publish Workflow | Deployment pipeline component | dev/prod | DevOps Owner | high | .github/workflows/publish-container.yml | Image build, publish, and branch-scoped App Service restart automation (dev and prod) | RMT-APP-001, RMT-EXT-002, RMT-INFRA-002 | 2026-07-08 | workflow file review |
| RMT-EXT-001 | GC Notify API Integration | External integration | dev/prod | Application Team | high | app/emailController.php, docs/future/006-gcnotify-integration.md | Outbound notification delivery | RMT-APP-001 | 2026-07-08 | docs and integration file review |
| RMT-EXT-002 | GitHub Container Registry (GHCR) | External integration | dev/prod | DevOps Owner | high | .github/workflows/publish-container.yml | Container image distribution | RMT-PIPE-001, RMT-INFRA-002 | 2026-07-08 | workflow file review |
| RMT-DOC-001 | Permissions Governance Documentation | Documentation or governance artifact | dev/prod | Product and App Team | medium | docs/permissions-model.md, docs/permissions-target-policy.md, docs/permissions-route-access-map.md | Access policy and authorization boundaries | RMT-APP-001 | 2026-07-08 | document review |
| RMT-DOC-002 | Configuration Management Documentation Pack | Documentation or governance artifact | dev/prod | Release Owner | medium | docs/config-management/ | CI governance, audit, and change control evidence | RMT-DOC-001, RMT-PIPE-001 | 2026-07-08 | document review |

## Update Triggers

Update inventory entries when:
- A new external integration is added
- Runtime, hosting, or deployment model changes
- Sensitive configuration handling changes
- Service ownership changes
