# Permissions Model (Current State and Target Policy)

## Purpose

This document defines how permissions currently work in the Request Management Tool, where enforcement is inconsistent, and what policy changes are approved for the next implementation phase.

Scope for this document:
- Current runtime role model
- Non-authenticated (guest) access model
- Current capability matrix
- Known enforcement gaps
- Target policy decisions
- Implementation rollout phases

Out of scope for this phase:
- Full RBAC schema redesign (`tblroles`, `tblpermissions`)
- Large UI redesign

## Current Runtime Role Model

The current model combines legacy account type values (`atype`) with two role flags:

- `atype`: account type (legacy and functional role signal)
- `is_superuser`: superadmin privilege flag
- `is_admin`: admin privilege flag
- `primary_atype`: original account type from login; used for superadmin role testing mode

Session initialization and login mapping:
- `app/sql.php` initializes role-related session fields.
- `app/signin.php` sets `is_superuser`, `is_admin`, `primary_atype`, and `atype`.

Non-authenticated users (no session `pid`) are not represented by `atype` and should be treated as a separate access class.

## Role Semantics

The app currently uses these account types:

- `1`: Super Admin (legacy)
- `2`: Admin (legacy)
- `3`: Manager
- `4`: Team Lead
- `5`: Employee
- `6`: Director

Primary helper functions currently used for role logic:
- `isSuperAdmin()`
- `canEditRequests()`
- `canManageSLA()`
- `isReadOnly()`
- `canViewAllRequests()`

Defined in:
- `app/includes/helpers.php`

## Current Capability Matrix (Documented Baseline)

The matrix below reflects currently intended behavior for this documentation effort.

| Role / Account Type | Edit Requests | Manage SLA | View All Requests | Read-Only | Admin Config/CRUD |
|---|---|---|---|---|---|
| Non-authenticated user (guest) | No | No | No | Yes | No |
| Superadmin (`is_superuser=1`) | Yes | Yes | Yes | No (except while testing another role) | Yes |
| Admin (`is_admin=1`) | Yes | Yes | Yes | No | Yes |
| Manager (`atype=3`) | Yes | Yes | No | No | No |
| Team Lead (`atype=4`) | Yes | No | No | No | No |
| Employee (`atype=5`) | Yes | No | No | No | No |
| Director (`atype=6`) | No | No | Yes | Yes | No |

Employee UI policy clarifications (approved):
- Employee overview navigation is limited to assigned requests and assigned closed/resolved requests.
- Employee request cards and detail view may show Edit, but only for requests assigned to the effective employee scope.
- Employee edit permissions are limited to status, assignment, and communications/log updates; no SLA timer changes.

### Non-Authenticated Access Policy

Current observed state:
- Some pages and AJAX endpoints are accessible without login due to missing guard checks.
- This is treated as an enforcement gap, not intended role behavior.

Target policy for non-authenticated users:
- Deny access to all request data, admin features, and role-dependent actions.
- Allow only explicitly designated public routes (if any), documented in the route access map.
- Require all non-public pages and AJAX endpoints to enforce login/session checks.

### Page View and Route Access Scope (Policy)

Yes, this should be documented in this file. Route/page visibility is part of the permission model and should be explicit for implementation and QA.

Non-authenticated users are allowed to:
- Submit a new request through the public intake flow.
- View a previously submitted request only through a client link, with limited details.

#### Target route scope by access class

| Access Class | Allowed Route Scope | Notes |
|---|---|---|
| Non-authenticated user (guest) | Public intake flow (`openrequest.php` -> `openrequest2.php` -> `openrequest3.php`) | Must remain submit-focused only; no admin/staff controls. |
| Non-authenticated user (guest) | Limited request view via client link (`viewrequest.php`) | Must require a secure client access mechanism and show limited details only. |
| Authenticated users (all roles) | Standard internal request pages | Scope still constrained by role helpers and data visibility rules. |
| Admin/Superadmin | Administrative configuration pages and CRUD includes | Must stay role-restricted and session-guarded. |

#### Limited-details requirement for guest request view

For non-authenticated request viewing, the page must be limited to client-safe fields. It must exclude internal-only data such as staff communications, internal notes, assignment metadata, privileged workflow controls, and destructive actions.

The detailed field allowlist/denylist should be captured in `docs/permissions-route-access-map.md`.

## Approved Policy Decisions

Decisions confirmed for this workstream:

1. Keep the current 6 account types plus `is_admin` and `is_superuser` flags.
2. Keep Director (`atype=6`) as view-only.
3. Keep superadmin role-testing (impersonation), but restrict it to approved environments.
4. Treat non-authenticated users as a distinct access class with explicit deny-by-default policy.
5. Prioritize documentation first, then implementation hardening.

Director UI policy clarifications (approved):
- Directors must not see Edit or Delete request actions on overview/list/search cards.
- Request-card footers should be hidden when no permitted actions remain.
- Directors must not see request-detail actions that trigger outbound workflow (for example, send resolved/survey email actions).

## Known Enforcement Gaps (To Be Addressed)

Current code discovery identified these high-priority inconsistencies:

1. Route intent is not fully documented as public vs authenticated vs role-restricted.
2. Some endpoints/pages do not enforce an explicit policy boundary (public intake, limited guest view, or authenticated-only).
3. AJAX endpoints have inconsistent authentication expectations.
4. Mixed direct session checks and helper-based checks.
5. Duplicated role logic in multiple files.
6. Magic-number account-type checks (`3`, `4`, `5`, `6`) repeated across pages.

## Rollout Plan

### Phase 1: Documentation and Source-of-Truth

- Publish this permissions model document.
- Add architecture decision record for permission centralization.
- Add a route/access inventory for sensitive pages and endpoints.

### Phase 2: Helper Consolidation

- Centralize role checks in helper functions.
- Remove duplicated inline role conditionals.
- Normalize account-type comparisons to strict integer checks.

### Phase 3: Guard Enforcement

- Add/verify login checks for sensitive pages.
- Add explicit auth guards to AJAX endpoints.
- Ensure role-based action checks call centralized helpers.

### Phase 4: Verification

- Update permission unit tests to match this matrix.
- Run Docker PHP lint on changed files.
- Run role-based manual smoke checks (superadmin/admin/manager/team lead/employee/director).

## Test and Validation Strategy

Required validation for each permissions change:

1. Docker lint for every changed PHP file.
2. Unit tests for helper permission behavior.
3. Manual verification by role on key pages:
   - request list and detail pages
   - request edit actions
   - admin configuration pages
   - reports and survey administration pages
4. Unauthenticated access checks for hardened pages and AJAX endpoints.
5. Non-authenticated flow checks:
   - Guest can submit new request via intake flow.
   - Guest can access only limited request details via client link.
   - Guest is denied internal/admin pages and internal-only request details.

## Next Documentation Artifacts

Planned follow-up docs:

- `docs/adr/002-permissions-source-of-truth.md`
- `docs/migrations/002-permissions-hardening.md`
- `docs/permissions-route-access-map.md`
