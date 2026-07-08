# Dependency Map: Request Intake and Triage Service (Initial Baseline)

## Purpose

Provide a standard way to document service-to-CI relationships for impact analysis and change planning.

## How To Use

1. Create one map per service capability.
2. Keep it updated during major change work.
3. Reference CI IDs from the CI inventory.

## Service Profile

- Service name: RMT Request Intake and Triage
- Business owner: IT Accessibility Office
- Technical owner: RMT Application Team
- Service description: Public and internal workflow for submitting, triaging, assigning, and tracking accessibility requests.
- Criticality: critical

## Core CI Set

| CI ID | CI Name | Role in Service | Environment |
|---|---|---|---|
| RMT-APP-001 | RMT Core PHP Application | Primary application runtime and business logic | local/dev/prod |
| RMT-APP-002 | Public Intake Flow | Public request creation entry points | local/dev/prod |
| RMT-DB-001 | MySQL Primary Data Store | Persistent data for requests and admin entities | local/dev/prod |
| RMT-DB-002 | MySQL Session Storage Table | Shared session state | dev/prod |
| RMT-PIPE-001 | Container Build and Publish Workflow | Delivery automation | dev/prod |

## Upstream Dependencies

| Dependency CI ID | Dependency Name | Type | Failure Impact | Fallback |
|---|---|---|---|---|
| RMT-INFRA-001 | Docker Compose Local Runtime | Runtime | Local dev and validation unavailable | Local troubleshooting and container restart |
| RMT-INFRA-002 | Azure App Service Container Host | Runtime | Hosted app unavailable or stale deployment | Manual restart and rollback image tag |
| RMT-EXT-001 | GC Notify API Integration | API | Email notifications unavailable | Operate without email and retry when restored |
| RMT-EXT-002 | GitHub Container Registry | Artifact registry | New deployments cannot pull latest image | Use previous known good image tag |

## Downstream Dependencies

| Dependent CI ID | Dependent Name | Type | Impact if this service fails |
|---|---|---|---|
| RMT-APP-004 | Internal Reporting Pages | Data consumer | Operational reporting unavailable or stale |
| RMT-APP-005 | Client Survey Workflows | Workflow dependency | Survey and feedback lifecycle disrupted |
| RMT-DOC-001 | Permissions Governance Docs | Governance dependency | Policy-to-runtime validation becomes harder |

## Data and Integration Points

| Interface | Direction | Auth Type | Data Classification | Notes |
|---|---|---|---|---|
| MySQL data access via sql.php/bootstrap chain | bidirectional | DB credentials | protected | Core request, admin, and status data path |
| GC Notify REST API | outbound | API key | protected | Email notifications and communication events |
| GHCR image pull/push path | bidirectional | GitHub token and Azure pull context | internal | Build and deployment artifact channel |

## Change Impact Notes

- High-risk dependencies:
	- RMT-DB-001 and RMT-DB-002 (schema/session changes can block authentication and workflows)
	- RMT-INFRA-002 (runtime availability directly affects service availability)
	- RMT-EXT-001 (notification reliability affects client communication obligations)
- Dependencies requiring coordinated change windows:
	- RMT-PIPE-001 with RMT-INFRA-002 for release and restart sequencing
	- Any DB migration touching triage/status/session tables with RMT-APP-001 compatibility
- Dependencies with limited tested fallback:
	- GC Notify integration behavior during prolonged outage
	- Drift detection between intended runtime config and deployed env values

## Relationship Matrix (Optional)

Use H for hard dependency, S for soft dependency, blank for none.

| CI | RMT-APP-001 | RMT-APP-002 | RMT-DB-001 | RMT-DB-002 | RMT-EXT-001 | RMT-INFRA-002 |
|---|---|---|---|---|---|---|
| RMT-APP-001 | - | H | H | H | S | H |
| RMT-APP-002 | H | - | H | S |  | H |
| RMT-DB-001 | S | S | - |  |  | S |
| RMT-DB-002 | S |  |  | - |  | S |
| RMT-EXT-001 | S |  |  |  | - |  |
| RMT-INFRA-002 | H | H | H | H | S | - |

## Next Expansion Targets

1. Add a dedicated dependency map for reports and survey lifecycle flows.
2. Add owner-confirmed recovery procedures per high-risk dependency.
3. Validate soft vs hard dependency ratings during the next release cycle.
