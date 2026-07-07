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

## Remaining Enforcement Gaps

Most role-hardening work is now implemented for Director, Manager, Team Lead, and Employee flows. The remaining high-priority gaps are:

1. Non-authenticated/public route hardening is still incomplete for all endpoints (especially AJAX/public dependencies).
2. Guest linked-view field allowlist/denylist enforcement still needs explicit verification against implementation.
3. Some pages still use direct session conditionals instead of helper-only checks.
4. Employee report access is not fully assignment-scoped when reached directly by URL (menu is scoped, route guard remains broad).
5. Automated permission tests are still missing; validation is currently lint + manual smoke testing.

## Rollout Plan

### Phase 1: Documentation and Source-of-Truth

- Completed: publish and maintain permissions model and route/access inventory.
- Remaining: add architecture decision record for permission centralization.

### Phase 2: Helper Consolidation

- Completed in large part: helper-based role checks and scoped test-mode helpers are in place.
- Remaining: remove residual inline conditionals and normalize all account-type checks to strict integer comparisons.

### Phase 3: Guard Enforcement

- Completed in large part for internal list/detail/edit/report scope behavior.
- Remaining: finish explicit auth and public-data boundary checks for AJAX/public routes.

### Phase 4: Verification

- Completed repeatedly: Docker PHP lint for changed files and role-based manual smoke checks.
- Remaining: add and run automated permission tests to match this matrix.

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
