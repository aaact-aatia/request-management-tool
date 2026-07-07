# Target Permissions Policy

## Purpose

This document defines the target-state permission policy that implementation should enforce. It is the contract used by engineering, QA, and reviewers when hardening role behavior.

This policy assumes:
- Current role model remains in place (`atype`, `is_superuser`, `is_admin`, `primary_atype`)
- Non-authenticated users are a first-class access class
- Route-level and field-level rules are both required

## Permission Catalog

Use the following logical permissions as the policy vocabulary.

### Authentication and session

- `auth.login_required`: user must have valid session (`pid`)
- `auth.guest_access`: route is intentionally available without login
- `auth.superadmin_test_mode`: superadmin role testing allowed only in approved environment

### Request lifecycle

- `request.create_public`: submit new request through guest intake
- `request.create_internal`: create request as authenticated internal user
- `request.view_linked_limited`: guest view via client link with limited fields
- `request.view_internal_assigned`: internal view for assigned/team scope
- `request.view_internal_all`: internal view across all requests
- `request.edit.client_fields`: edit client-facing/requester-provided fields
- `request.edit.workflow_fields`: edit workflow and delivery-management fields
- `request.edit.internal_fields`: edit privileged internal fields
- `request.delete`: delete request

### Workflow and operations

- `workflow.manage_sla`: set/adjust SLA-related fields and workflows
- `workflow.manage_assignments`: assign/reassign internal owner/team
- `workflow.internal_comms_read`: view staff communications
- `workflow.internal_comms_write`: add staff communications

### Administrative configuration

- `admin.catalogue.manage`
- `admin.service.manage`
- `admin.subservice.manage`
- `admin.source.manage`
- `admin.status.manage`
- `admin.team.manage`
- `admin.user.manage`
- `admin.holiday.manage`
- `admin.csv.import_export`
- `admin.request.delete`

### Reports and survey administration

- `report.view`
- `survey.manage_links`
- `survey.view_results`

## Target Role-to-Permission Matrix

| Permission | Guest | Employee (5) | Team Lead (4) | Manager (3) | Director (6) | Admin | Superadmin |
|---|---|---|---|---|---|---|---|
| `auth.login_required` | No | Yes | Yes | Yes | Yes | Yes | Yes |
| `auth.guest_access` | Yes (public routes only) | No | No | No | No | No | No |
| `request.create_public` | Yes | No | No | No | No | No | No |
| `request.create_internal` | No | Yes | Yes | Yes | No | Yes | Yes |
| `request.view_linked_limited` | Yes (link only) | Optional via link | Optional via link | Optional via link | Optional via link | Optional via link | Optional via link |
| `request.view_internal_assigned` | No | Yes | Yes | Yes | Yes | Yes | Yes |
| `request.view_internal_all` | No | No | No | No | Yes | Yes | Yes |
| `request.edit.client_fields` | No | Yes | Yes | Yes | No | Yes | Yes |
| `request.edit.workflow_fields` | No | No | Yes | Yes | No | Yes | Yes |
| `request.edit.internal_fields` | No | No | No | No | No | Yes | Yes |
| `request.delete` | No | No | No | No | No | Yes | Yes |
| `workflow.manage_sla` | No | No | No | Yes | No | Yes | Yes |
| `workflow.manage_assignments` | No | Yes | Yes | Yes | No | Yes | Yes |
| `workflow.internal_comms_read` | No | Scoped | Scoped | Scoped | Scoped/read-only | Yes | Yes |
| `workflow.internal_comms_write` | No | Scoped | Scoped | Scoped | No | Yes | Yes |
| `admin.*` | No | No | No | No | No | Yes | Yes |
| `report.view` | No | Yes (scoped) | Yes (scoped) | Yes (scoped) | Yes (all) | Yes (all) | Yes (all) |
| `survey.manage_links` | No | No | Yes | Yes | No | Yes | Yes |
| `survey.view_results` | No | No | Yes | Yes | Yes | Yes | Yes |

Notes:
- "Scoped" means assignment/team scope unless a role explicitly has all-request visibility.
- Director remains read-only for request editing and SLA operations.
- Admin and superadmin are both all-request roles; superadmin also has test-mode capability.

### Director enforcement details

The Director role (`atype=6`) is explicitly read-only in internal request workflows.

- List and search views: Director must not see Edit/Delete actions.
- Card footer behavior: if no allowed actions exist, do not render an action footer.
- Request detail view: Director must not see outbound workflow actions (for example, the resolved/survey send button).
- Backend handlers must reject action posts requiring edit rights when initiated without edit permission.

## Role-Specific Edit Policy (Request Form)

This policy defines edit behavior for request pages such as `app/editrequest.php`.

### Edit tier definitions

- Client fields:
  - Intended for requester-facing content and baseline request metadata.
  - Examples: title, client contact details, client-provided description, requester language, requester-facing dates.

- Workflow fields:
  - Intended for delivery operations.
  - Examples: SLA due inputs, status transitions, assignment/owner, triage/workflow routing, completion flags.

- Internal fields:
  - Privileged operational and governance data.
  - Examples: internal notes, escalation metadata, internal-only admin controls, destructive operations.

### Edit matrix by role

| Role | Client fields | Workflow fields | Internal fields | Notes |
|---|---|---|---|---|
| Guest | No | No | No | No request edit access. |
| Employee (atype 5) | Limited | Limited | Limited | Only approved exceptions: status, assignee, and communications log add/delete. |
| Team Lead (atype 4) | Limited | Limited | No | Limited to approved workflow exceptions; no broad field edits. |
| Manager (atype 3) | Limited | Limited | No | Limited to approved workflow exceptions; no broad field edits. |
| Director (atype 6) | No | No | No | Read-only role. |
| Admin (`is_admin=1`) | Yes | Yes | Yes | Full edit across all tiers. |
| Superadmin (`is_superuser=1`) | Yes | Yes | Yes | Full edit across all tiers, except while testing another role. |

### Superadmin test-mode rule

When superadmin is in role-testing mode (`atype` differs from `primary_atype`), edit permissions must match the selected test role exactly. Superadmin global override is suspended until test mode is exited.

### Editrequest field inventory and proposed tier mapping

Source page and handlers:
- `app/editrequest.php`
- `app/includes/editrequest-processing.php`
- `app/includes/editrequest-staff-section.php`
- `app/includes/editrequest-communications-section.php`

The following mapping is the proposed implementation contract for role-based field gating.

| Form field / area | Proposed tier | Employee | Team Lead | Manager | Admin | Superadmin | Notes |
|---|---|---|---|---|---|---|---|
| `requesttitle` | Client | No | Edit | Edit | Edit | Edit | Request title is requester-facing content. |
| `clientlname`, `clientfname` | Client | No | No | No | Edit | Edit | Client identity fields. |
| `clientemail`, `clientphone` | Client | No | No | No | Edit | Edit | Client contact fields. |
| `departmentagency` | Client | No | No | No | Edit | Edit | Stored in communications note prefix; keep client-tier. |
| `attach1`, `attach2`, `attach3` (URL attachments) | Client | Edit | Edit | Edit | Edit | Edit | Deferred for now in implementation scope. |
| File uploads (`fileToUpload`) | Workflow | No | No | No | No | No | Disabled in edit flow until storage is implemented. |
| File delete actions | Internal | No | No | No | Edit | Edit | Edit-flow file deletion unavailable while storage migration is pending. |
| `catalogueid`, `serviceid`, `subserviceid` | Workflow | No | No | No | Edit | Edit | Routing and service ownership impact. |
| `statusid` | Workflow | Edit | Edit | Edit | Edit | Edit | Workflow/state transition field. |
| `datereceived`, `dateupdated`, `daterequired`, `dateresolved` | Workflow | No | No | No | Edit | Edit | Operational timeline fields. |
| `sourceid` | Workflow | No | No | No | Edit | Edit | Intake classification field. |
| `audience` | Workflow | No | No | No | Edit | Edit | Operational classification field. |
| Sprint fields (`firstsprintstartdate`, `firstsprintenddate`, `sprintschedule`, `sprintdefects`) | Workflow | No | No | No | Edit | Edit | Delivery planning fields. |
| `workerid` (assignee) | Workflow | Edit | Edit | Edit | Edit | Edit | Assignment control. |
| `slatimer` | Workflow | No | No | Edit | Edit | Edit | Manager can update SLA timer; Team Lead cannot. |
| Communications log update (`commlog1`, `commlog2`) | Internal | Edit | Edit | Edit | Edit | Edit | Employee, Team Lead, and Manager can update existing communication logs. |
| Communications log add (`adminnotes`) | Internal | Edit | Edit | Edit | Edit | Edit | Employee, Team Lead, and Manager can add logs. |
| Communications log delete | Internal | No | No | No | Edit | Edit | Request-detail comm-log delete controls currently follow request-delete permissions. |
| `requestid` | Internal | No | No | No | Edit | Edit | Identifier mutation should be privileged only. |
| `newrequest` | Internal | No | No | No | Edit | Edit | Legacy field currently shown when session first name equals "Admin"; candidate for removal. |

Implementation notes from current code:
- Team Lead and Employee scoping is enforced in overview, resolved, detail, and edit flows using effective test-mode identities where applicable.
- Edit processing now enforces role-tier restrictions for Employee, Team Lead, and Manager workflow fields.
- Communications log add/update is enabled for Employee, Team Lead, and Manager in edit flow.
- Edit-flow file uploads are intentionally disabled until storage implementation is complete.
- Request-detail communication delete actions still follow request-delete permission boundaries.

Communications log implementation status:
- Add/update behavior for Employee, Team Lead, and Manager is implemented in edit flow.
- Delete behavior remains aligned to request-delete controls and may be revisited in a dedicated follow-up change.

Role-test scoping notes:
- Team Lead testing may select a test team scope.
- Employee testing may select a test employee scope; assignment-based pages and edit guards use that effective employee identity.
- Header testing banner displays the effective employee identity when Employee test mode is active.

## Guest Policy (Required)

Guest access is deny-by-default except for explicitly public routes:
- Public intake: submit a new request
- Link-based limited request view: read only client-safe fields
- Client survey submission route (if business confirms this remains public)

Guest users must never access:
- Internal request lists
- Internal staff communication logs
- Assignment and SLA controls
- Any admin page, CRUD endpoint, CSV import/export, or user/team management

## Field-Level Policy for Guest Linked View

The linked guest request page must enforce a safe subset:

Allowlist examples (client-safe):
- Public request ID and title
- Client-provided metadata
- High-level status text suitable for client communication
- Client-facing dates and language metadata

Denylist examples (internal-only):
- Staff communications and notes
- Internal assignment fields and team ownership
- Escalation/internal workflow metadata
- Admin controls (edit/delete buttons)
- Internal file-management controls

## Environment Controls

Superadmin role-testing must be environment-gated:
- Enabled: local/dev/test environments
- Disabled: production

The environment gate must apply to both:
- test-mode UI entry points
- backend test-mode action endpoint

## Implementation Priorities

1. Convert policy into helper-level authorization checks.
2. Classify all routes and endpoints in route access map.
3. Enforce guest-limited view allowlist on linked request page.
4. Remove direct session conditional duplication where possible.
5. Add tests by role and by route class.
6. Enforce field-tier gates in edit forms and backend update handlers.

## Decision Log Inputs Needed

The following business confirmations should be captured before code hardening:

1. Whether client survey submission remains public.
2. Exact guest linked-view field allowlist.
3. Whether employee role can view reports beyond own/team scope.
4. Whether director should view survey results or only request records.
5. Final field-group mapping for `app/editrequest.php` and related includes (client/workflow/internal).
