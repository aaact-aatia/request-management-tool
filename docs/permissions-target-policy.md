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
| `workflow.manage_sla` | No | No | Yes | Yes | No | Yes | Yes |
| `workflow.manage_assignments` | No | No | Yes | Yes | No | Yes | Yes |
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
| Employee (atype 5) | Yes | No | No | Can update only client-tier fields in permitted scope. |
| Team Lead (atype 4) | Yes | Yes | No | Can manage delivery operations in permitted scope. |
| Manager (atype 3) | Yes | Yes | No | Same edit tier as team lead unless later expanded. |
| Director (atype 6) | No | No | No | Read-only role. |
| Admin (`is_admin=1`) | Yes | Yes | Yes | Full edit across all tiers. |
| Superadmin (`is_superuser=1`) | Yes | Yes | Yes | Full edit across all tiers, except while testing another role. |

### Superadmin test-mode rule

When superadmin is in role-testing mode (`atype` differs from `primary_atype`), edit permissions must match the selected test role exactly. Superadmin global override is suspended until test mode is exited.

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
