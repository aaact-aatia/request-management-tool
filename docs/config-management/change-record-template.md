# Change Record Template

## Example Record: CR-2026-001

## Record Metadata

- Change ID: CR-2026-001
- Title: Add dev App Service restart step to container publish workflow
- Change type: normal
- Requested by: Release owner
- Implemented by: DevOps owner
- Date and window: 2026-Q2 release cycle
- Environment(s): dev

## Scope

- Objective: Ensure the dev App Service pulls the newest dev container image after workflow publish.
- In-scope components: publish workflow, Azure login step, Azure CLI restart step
- Out-of-scope components: production restart automation, runtime application code changes

## Affected Configuration Items

| CI ID | CI Name | Expected Impact |
|---|---|---|
| RMT-PIPE-001 | Container Build and Publish Workflow | Adds dev-only restart action after publish |
| RMT-INFRA-002 | Azure App Service (Container Host) | Pulls latest image on restart |
| RMT-EXT-002 | GitHub Container Registry (GHCR) | Continues as image source for restart pull |

## Dependency Impact

- Upstream impact: Azure authentication secrets must be available and valid for workflow login.
- Downstream impact: Dev deployment freshness improves because runtime restart occurs on push.
- Coordination required: Cloud operations awareness for automated restarts in dev.

## Risk and Rollback

- Risk level: medium
- Key risks: restart step fails or credential drift blocks Azure login.
- Rollback trigger: repeated workflow failure or unintended dev outage due to restart behavior.
- Rollback steps:
	1. Remove or disable the restart step in publish workflow.
	2. Re-run workflow without restart step.
	3. Perform manual restart only when needed.

## Validation Plan

- Pre-change checks: Confirm workflow syntax and required secrets are present.
- Post-change checks: Verify workflow success and that dev reflects latest pushed image.
- Success criteria: push to dev completes with successful build, publish, Azure login, and restart.

## Approvals

| Role | Name | Decision | Timestamp |
|---|---|---|---|
| Technical approver | TBD | approved | TBD |
| Service owner | TBD | approved | TBD |

## Execution Log

- Start time: Recorded in workflow run history
- End time: Recorded in workflow run history
- Steps executed: Build image, push tags, Azure login (dev only), restart dev App Service
- Deviations from plan: None documented in baseline record

## Outcome and Evidence

- Final outcome: success
- Evidence links:
	- .github/workflows/publish-container.yml
- Incident linkage (if any): none
- Follow-up actions:
	1. Add periodic secret validity verification for Azure login credentials.

---

## Example Record: CR-2026-002

## Record Metadata

- Change ID: CR-2026-002
- Title: Disable edit-time file uploads until replacement storage path is implemented
- Change type: normal
- Requested by: Service owner
- Implemented by: Application team
- Date and window: 2026-Q2 access and storage hardening window
- Environment(s): dev, prod

## Scope

- Objective: Prevent misleading upload behavior and reduce data-handling risk while Azure blob integration remains a stub.
- In-scope components: edit request upload controls, storage behavior guardrails, operator guidance
- Out-of-scope components: full local file storage implementation, metadata backfill for historical file rows

## Affected Configuration Items

| CI ID | CI Name | Expected Impact |
|---|---|---|
| RMT-APP-001 | RMT Core PHP Application | Upload path restricted during edit flow until storage replacement is ready |
| RMT-DOC-001 | Permissions Governance Documentation | Clarifies expected operator behavior while storage is constrained |

## Dependency Impact

- Upstream impact: Existing storage integration constraints remain unchanged.
- Downstream impact: Users cannot upload replacement files through edit flow until storage strategy is finalized.
- Coordination required: Application and release owners must communicate temporary behavior to support staff.

## Risk and Rollback

- Risk level: medium
- Key risks: user confusion if expected upload controls are unavailable.
- Rollback trigger: replacement storage implementation validated and approved.
- Rollback steps:
	1. Re-enable edit upload path in application flow.
	2. Validate upload and retrieval behavior in dev.
	3. Deploy with updated operator notes.

## Validation Plan

- Pre-change checks: Confirm upload functionality is currently non-functional for durable storage.
- Post-change checks: Verify edit upload controls are disabled and no false-success path remains.
- Success criteria: no edit-path upload attempts can create unrecoverable or misleading file state.

## Approvals

| Role | Name | Decision | Timestamp |
|---|---|---|---|
| Technical approver | TBD | approved | TBD |
| Service owner | TBD | approved | TBD |

## Execution Log

- Start time: Recorded in commit history
- End time: Recorded in commit history
- Steps executed: Disable edit upload capability, update related behavior documentation
- Deviations from plan: None documented

## Outcome and Evidence

- Final outcome: success
- Evidence links:
	- app/editrequest.php
	- app/BlobStorage.php
	- Commit: 962d5ea
- Incident linkage (if any): none
- Follow-up actions:
	1. Implement and validate local file storage replacement.
	2. Add a dedicated change record when uploads are re-enabled.

---

## Example Record: CR-2026-003

## Record Metadata

- Change ID: CR-2026-003
- Title: Enforce manager and team-lead authorization boundaries in request workflow
- Change type: normal
- Requested by: Product owner
- Implemented by: Application team
- Date and window: 2026-Q2 permissions hardening window
- Environment(s): dev, prod

## Scope

- Objective: Align workflow operations with target permissions model for manager and team-lead roles.
- In-scope components: role checks, workflow state transitions, request card action visibility, scoped behavior docs
- Out-of-scope components: account model redesign, identity provider changes

## Affected Configuration Items

| CI ID | CI Name | Expected Impact |
|---|---|---|
| RMT-APP-001 | RMT Core PHP Application | Authorization logic updated for role-constrained actions |
| RMT-DOC-001 | Permissions Governance Documentation | Permission policy and route behavior baselines updated |

## Dependency Impact

- Upstream impact: Session account-type and team context must remain accurate.
- Downstream impact: Role-restricted users see reduced action set and tighter workflow boundaries.
- Coordination required: Product owner and support teams aligned on role behavior expectations.

## Risk and Rollback

- Risk level: high
- Key risks: unintended over-restriction or under-restriction of request operations.
- Rollback trigger: confirmed workflow blockage for authorized users or policy regression.
- Rollback steps:
	1. Revert policy-enforcement commit set.
	2. Restore previous role action map while triaging regression.
	3. Re-test with targeted role scenarios before re-release.

## Validation Plan

- Pre-change checks: Review permissions-target-policy and route access map.
- Post-change checks: Validate manager and team-lead scenarios against expected actions.
- Success criteria: role behavior aligns with documented policy for read, edit, and workflow actions.

## Approvals

| Role | Name | Decision | Timestamp |
|---|---|---|---|
| Technical approver | TBD | approved | TBD |
| Service owner | TBD | approved | TBD |

## Execution Log

- Start time: Recorded in commit history
- End time: Recorded in commit history
- Steps executed: Add policy checks, refine role-scoped workflow behavior, update docs
- Deviations from plan: None documented

## Outcome and Evidence

- Final outcome: success
- Evidence links:
	- docs/permissions-target-policy.md
	- docs/permissions-route-access-map.md
	- Commits: dce8c79, 07d6bc4, 84bc553
- Incident linkage (if any): none
- Follow-up actions:
	1. Add quarterly access-policy drift review against runtime behavior.
	2. Capture formal approver names for the baseline record set.

---

## Example Record: CR-2026-004

## Record Metadata

- Change ID: CR-2026-004
- Title: Add and harden admin markdown documentation browser in Help area
- Change type: standard
- Requested by: Product owner
- Implemented by: Application team
- Date and window: 2026-Q3 documentation usability release
- Environment(s): dev, prod

## Scope

- Objective: Improve discoverability of markdown documentation from within the admin Help experience.
- In-scope components: help page markdown rendering flow, path and content hardening, docs availability in runtime image
- Out-of-scope components: document authoring workflow changes, permissions model redesign

## Affected Configuration Items

| CI ID | CI Name | Expected Impact |
|---|---|---|
| RMT-APP-001 | RMT Core PHP Application | Help section now serves selected markdown documentation to admins |
| RMT-INFRA-001 | Docker Compose Local Runtime | Local image now includes docs needed for in-app markdown browsing |
| RMT-DOC-002 | Configuration Management Documentation Pack | Pack becomes directly discoverable via in-app Help paths |

## Dependency Impact

- Upstream impact: markdown files must remain present and readable in runtime context.
- Downstream impact: admin users access docs through Help UI without leaving the app.
- Coordination required: docs maintainers and application team coordinate on supported markdown paths.

## Risk and Rollback

- Risk level: medium
- Key risks: unsafe path resolution or rendering behavior if hardening regresses.
- Rollback trigger: detected security concern or broken Help-page rendering.
- Rollback steps:
	1. Disable markdown browser code path in Help page.
	2. Revert to static Help behavior.
	3. Re-introduce browser with validated hardening fixes.

## Validation Plan

- Pre-change checks: Confirm docs path strategy and admin-only access intent.
- Post-change checks: Verify approved docs render in Help and path traversal protections hold.
- Success criteria: admins can browse approved markdown docs; unsupported paths are blocked.

## Approvals

| Role | Name | Decision | Timestamp |
|---|---|---|---|
| Technical approver | TBD | approved | TBD |
| Service owner | TBD | approved | TBD |

## Execution Log

- Start time: Recorded in commit history
- End time: Recorded in commit history
- Steps executed: Add markdown docs browser, include docs in runtime context, apply hardening updates
- Deviations from plan: None documented

## Outcome and Evidence

- Final outcome: success
- Evidence links:
	- app/help.php
	- docker-compose.yml
	- Commits: 26656c7, 231057b, d3ba3e7
- Incident linkage (if any): none
- Follow-up actions:
	1. Add regression tests for allowed markdown path set.
	2. Record approved-document allowlist policy in Help documentation.

---

## Record Metadata

- Change ID:
- Title:
- Change type: standard or normal or emergency
- Requested by:
- Implemented by:
- Date and window:
- Environment(s):

## Scope

- Objective:
- In-scope components:
- Out-of-scope components:

## Affected Configuration Items

| CI ID | CI Name | Expected Impact |
|---|---|---|
| RMT-APP-001 | Example app CI | Brief impact summary |

## Dependency Impact

- Upstream impact:
- Downstream impact:
- Coordination required:

## Risk and Rollback

- Risk level: low or medium or high
- Key risks:
- Rollback trigger:
- Rollback steps:

## Validation Plan

- Pre-change checks:
- Post-change checks:
- Success criteria:

## Approvals

| Role | Name | Decision | Timestamp |
|---|---|---|---|
| Technical approver |  | approved or rejected |  |
| Service owner |  | approved or rejected |  |

## Execution Log

- Start time:
- End time:
- Steps executed:
- Deviations from plan:

## Outcome and Evidence

- Final outcome: success or rolled back or partial
- Evidence links:
- Incident linkage (if any):
- Follow-up actions:
