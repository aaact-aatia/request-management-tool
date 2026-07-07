# Permissions Route Access Map

## Purpose

This document classifies routes and endpoints by required access class and expected permission behavior.

Access classes:
- `PUBLIC_GUEST`: no login required by policy
- `GUEST_LINK_LIMITED`: no login, but requires secure client link and limited field response
- `AUTH_REQUIRED`: logged-in user required
- `ROLE_RESTRICTED`: logged-in user plus role permission required

## Core request flow

| Route | Access Class | Target Policy | Notes |
|---|---|---|---|
| `app/openrequest.php` | `PUBLIC_GUEST` | Public intake entry | Guest allowed to start request submission flow. |
| `app/openrequest2.php` | `PUBLIC_GUEST` | Public intake step | Must remain intake-only (no internal controls). |
| `app/openrequest3.php` | `PUBLIC_GUEST` | Public intake submit | Must only support create flow and safe redirects. |
| `app/viewrequest.php` | `GUEST_LINK_LIMITED` and `AUTH_REQUIRED` (dual mode) | Guest link-limited view + richer internal view for authenticated roles | Requires explicit mode boundary and field-level restrictions; Director remains read-only (no edit/delete/send-resolved-email actions). Employee edit action appears only on assigned requests. |
| `app/editrequest.php` | `ROLE_RESTRICTED` | `request.edit.client_fields` / `request.edit.workflow_fields` / `request.edit.internal_fields` | No guest or director edit access. Team Lead is team-scoped; Employee is assignment-scoped; edit fields must be role-tier gated. |
| `app/clonerequest.php` | `ROLE_RESTRICTED` | Internal only | Scoped by role; no guest access. |

## Internal request lists and dashboards

| Route | Access Class | Target Policy | Notes |
|---|---|---|---|
| `app/index.php` | `AUTH_REQUIRED` | Internal landing/list view | Scope by role (all vs assigned/team). |
| `app/indexonly.php` | `AUTH_REQUIRED` | Internal list view | Scope by role. Employee is restricted to assigned requests. |
| `app/indexresolved.php` | `AUTH_REQUIRED` | Internal resolved list | Scope by role. Team Lead is restricted to team-related requests; Employee is restricted to assigned closed/resolved requests. |
| `app/asearch.php` | `AUTH_REQUIRED` | Internal search | Scope by role and data visibility rules. Team Lead defaults to team-related scope and can explicitly choose all-requests search. |

## Reports and survey pages

| Route | Access Class | Target Policy | Notes |
|---|---|---|---|
| `app/reports.php` | `AUTH_REQUIRED` | `report.view` | No guest access. |
| `app/report-status.php` | `AUTH_REQUIRED` | `report.view` | Role scope applies. Team Lead report data is restricted to team-related requests. |
| `app/client-survey.php` | `PUBLIC_GUEST` (if confirmed) | Public client survey submission | Confirm business intent and keep write-only client fields. |
| `app/client-survey-thank-you.php` | `PUBLIC_GUEST` | Survey completion page | Public confirmation route. |
| `app/client-survey-link.php` | `ROLE_RESTRICTED` | `survey.manage_links` | Internal link management. |
| `app/client-survey-pending.php` | `ROLE_RESTRICTED` | `survey.manage_links`/`survey.view_results` | Internal pending queue. |
| `app/client-survey-results.php` | `ROLE_RESTRICTED` | `survey.view_results` | Internal analytics/results page. |

## Administration pages

| Route | Access Class | Target Policy | Notes |
|---|---|---|---|
| `app/catalogue.php` | `ROLE_RESTRICTED` | `admin.catalogue.manage` | Admin/superadmin only. |
| `app/catalogue-mgmt.php` | `ROLE_RESTRICTED` | `admin.catalogue.manage` | Admin/superadmin only. |
| `app/catalogue-sub-mgmt.php` | `ROLE_RESTRICTED` | `admin.catalogue.manage` | Admin/superadmin only. |
| `app/products.php` | `ROLE_RESTRICTED` | `admin.service.manage` | Admin/superadmin only. |
| `app/sources.php` | `ROLE_RESTRICTED` | `admin.source.manage` | Admin/superadmin only. |
| `app/status.php` | `ROLE_RESTRICTED` | `admin.status.manage` | Admin/superadmin only. |
| `app/teams.php` | `ROLE_RESTRICTED` | `admin.team.manage` (or delegated policy) | Team-edit delegation to manager/team lead can be allowed by explicit policy. |
| `app/team-details.php` | `ROLE_RESTRICTED` | Team detail visibility/edit policy | Must align with final team delegation decision. |
| `app/users.php` | `ROLE_RESTRICTED` | `admin.user.manage` | Admin/superadmin only. |
| `app/holidays-mgmt.php` | `ROLE_RESTRICTED` | `admin.holiday.manage` | Admin/superadmin only. |
| `app/settings.php` | `AUTH_REQUIRED` / `ROLE_RESTRICTED` partial | User settings for all; privileged controls by role | Superadmin test-mode controls must be environment-gated. |
| `app/gcnotify-settings.php` | `ROLE_RESTRICTED` | Admin settings | Admin/superadmin only. |

## AJAX endpoints

| Endpoint | Access Class | Target Policy | Notes |
|---|---|---|---|
| `app/addrequest-ajax1.php` | `PUBLIC_GUEST` (if intake dependency) | Public intake dependency only | Validate no internal data leakage. |
| `app/addrequest-ajax2.php` | `PUBLIC_GUEST` (if intake dependency) | Public intake dependency only | Validate no internal data leakage. |
| `app/addrequest2-ajax1.php` | `PUBLIC_GUEST` (if intake dependency) | Public intake dependency only | Validate only public dropdown metadata. |
| `app/addrequest2-ajax2.php` | `PUBLIC_GUEST` (if intake dependency) | Public intake dependency only | Validate only public dropdown metadata. |
| `app/addrequest2-ajax3.php` | `PUBLIC_GUEST` (if intake dependency) | Public intake dependency only | Validate only public dropdown metadata. |
| `app/addrequest2-ajax4.php` | `PUBLIC_GUEST` (if intake dependency) | Public intake dependency only | Validate only public dropdown metadata. |

## Include endpoints (write actions)

All write endpoints under `app/includes/add-*.php`, `app/includes/edit-*.php`, and `app/includes/delete-*.php` are `ROLE_RESTRICTED` and must be denied to guests.

For request update handlers specifically, backend validation must enforce the same field-tier policy as the UI:
- Employee: scoped workflow updates only (status, assignment, communications log)
- Team lead and manager: scoped workflow updates; Team Lead cannot manage SLA
- Admin and superadmin: client + workflow + internal fields
- Superadmin in test mode: selected role rules only

## Guest-linked limited view allowlist/denylist placeholder

This section is intentionally explicit and should be finalized before guard refactoring.

Allowlist (initial placeholder):
- Request number (public format)
- Title
- Client-facing status
- Client-facing dates
- Language

Denylist (initial placeholder):
- Staff communication logs
- Internal comments/notes
- Assignment and ownership internals
- Internal escalation metadata
- Edit/delete controls
- Internal file administration actions

## Validation checklist

1. Every route in this map has an explicit access class.
2. Every `PUBLIC_GUEST` route has a documented public-data scope.
3. Every `GUEST_LINK_LIMITED` route has field-level allowlist/denylist enforced.
4. Every `ROLE_RESTRICTED` route has helper-based permission checks.
5. Unauthenticated requests to non-public routes are denied.
