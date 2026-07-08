# Change Control SOP

## Purpose

Provide a lightweight, auditable process for planning, approving, deploying, and validating configuration-related changes.

## Change Types

| Type | Description | Approval |
|---|---|---|
| Standard | Low-risk, repeatable, pre-approved pattern | One technical approver |
| Normal | Planned change with moderate/high impact potential | Technical approver + service owner |
| Emergency | Time-critical incident or outage response | On-call lead post-approval record within 24h |

## Required Inputs Before Approval

Every change must include:
1. Scope and objective
2. Affected CI IDs
3. Dependency impact summary
4. Risk assessment
5. Rollback plan
6. Validation plan
7. Change window

## Approval Matrix

| Environment | Standard | Normal | Emergency |
|---|---|---|---|
| local/dev | Developer or tech lead | Tech lead | On-call lead |
| test | Tech lead | Tech lead + service owner | On-call lead |
| prod | Tech lead | Tech lead + service owner + release owner | On-call lead, then formal review |

## Execution Procedure

1. Create a change record using change-record-template.md.
2. Confirm impacted CIs are present in ci-inventory-standard.md inventory.
3. Obtain required approvals.
4. Execute in approved window.
5. Run validation checks.
6. If validation fails, execute rollback plan.
7. Record final outcome and evidence links.

## Minimum Validation Evidence

- Relevant lint/test output
- Deployment or pipeline run evidence
- Functional smoke verification
- Any manual verification notes

## Post-Implementation Review

For normal and emergency changes:
- Complete a short PIR within 2 business days
- Capture what changed, what worked, what to improve
- Add improvement actions to backlog if needed

## Emergency Change Exception Rules

Emergency changes may bypass pre-approval only when service restoration is at risk.

Still required after deployment:
- Full change record completion
- Formal approver acknowledgement
- Root cause and prevention actions
