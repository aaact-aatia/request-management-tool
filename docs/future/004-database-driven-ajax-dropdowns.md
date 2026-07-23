# Database-Driven Intake Dropdowns

**Status**: Completed
**Completed**: 2026-07-22

## Result

The public intake reads active catalogue data directly from MySQL and renders it with WET Field Flow:

1. `openrequest.php` loads the active catalogue, service, and subservice hierarchy in one query.
2. Field Flow appends the related service and optional subservice controls as selections change.
3. Field Flow `query` actions submit numeric IDs for each selected level.
4. `openrequest2.php` validates the submitted parent-child relationships before accepting the selection.

The former synthetic colon-delimited values and all four public intake AJAX endpoints were removed. Localized names and prompts are rendered on the initial page response.

## Data Management

Administrators can add or deactivate catalogues, services, and subservices through catalogue management without changing intake PHP. Fresh databases load hierarchy data from the profile selected by `RMT_SEED_PROFILE`; administrators may extend it afterward.

Existing databases must apply `database/migrations/014-clean-catalogue-hierarchy.sql` before relying on the new constraints. See `docs/migrations/014-clean-catalogue-hierarchy.md`.

## Separate Internal Flow

`addrequest-ajax1.php` and `addrequest-ajax2.php` remain in use by internal add/search workflows. They are separate from the public intake and were not removed by this work.