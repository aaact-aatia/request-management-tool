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
	1. Create equivalent production record when prod automation is introduced.
	2. Add periodic secret validity verification for Azure login credentials.

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
