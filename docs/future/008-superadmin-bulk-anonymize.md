# Future Plan 008: Superadmin Configurable Bulk Anonymization

**Status**: Implemented  
**Date Planned**: 2026-06-16  
**Estimated Effort**: 1–2 days  

## Overview

Replace the one-off `batch-ace-info.php` script with a proper superadmin tool that lets an authorized user configure and run bulk anonymization from the admin panel safely.

The script itself is documented in [docs/maintenance-scripts.md](../maintenance-scripts.md).

## Delivered State

- `app/bulk-anonymize.php` provides a superadmin-only configurable workflow and is intentionally hidden from routine navigation
- Catalogue IDs and excluded service IDs are selected at runtime
- Preview mode performs a count only; execution requires a fresh preview token and explicit confirmation
- All dynamic database values use prepared statements and execution is transactional
- Each run is recorded in `tbladminlog`

## Goals

1. Expose the feature as a superadmin-only page in the admin control panel
2. Let the admin choose which catalogue(s) to include and which service IDs to exclude — no hardcoded IDs
3. Add a dry-run preview (shows count of records that would be affected) before committing
4. Require explicit confirmation before executing
5. Log the operation to `tbladminlog`
6. Fix the SQL injection by using prepared statements

## Proposed UI Flow

```
Admin Panel → Data Tools → Bulk Anonymize Requests

[ ] Catalogue 1 — <name>
[ ] Catalogue 2 — <name>
[ ] Catalogue 3 — <name>
[ ] Catalogue 4 — <name>

Exclude service IDs (comma-separated): [46        ]

[ Preview affected records ]

  → "23 records will be anonymized. 4 skipped (excluded service)."

[ Run anonymization ]  ← requires confirmation dialog
```

## Implementation Steps

### 1. New admin page: `app/bulk-anonymize.php`

- Superadmin-only (`isSuperAdmin()`)
- Loads all catalogues from `tblcatalogue` for the checkbox list
- On `GET`: renders the form
- On `POST` with `action=preview`: runs a `SELECT COUNT(*)` and returns the affected count (no writes)
- On `POST` with `action=run`: validates input, runs anonymization using prepared statements, logs to `tbladminlog`, redirects with success message

### 2. Move anonymization logic to `app/includes/bulk-anonymize-processing.php`

Keeps the page file clean. Handles:
- Input validation (array of catalogue IDs, array of excluded service IDs)
- Prepared `SELECT` to fetch matching `tbltriage` IDs
- Prepared `UPDATE` on `tbltriage` (client fields)
- Prepared `UPDATE` on `tblcommlog` (notes field)
- Audit log entry in `tbladminlog`

### 3. Add lang strings to both `lang/en.php` and `lang/fr.php`

Keys needed (approximate):
- `bulk_anon_title`
- `bulk_anon_intro`
- `bulk_anon_select_catalogues`
- `bulk_anon_exclude_services`
- `bulk_anon_preview_btn`
- `bulk_anon_preview_result` — e.g. "X records will be anonymized."
- `bulk_anon_run_btn`
- `bulk_anon_confirm`
- `bulk_anon_success`

### 4. Keep the tool out of routine navigation

Completed: the direct route remains protected by `isSuperAdmin()`, but there is no menu link. Authorized operators use the documented direct URL when needed.

### 5. Deprecate / delete `batch-ace-info.php`

Completed: the legacy script was removed after the new UI was added, and [docs/maintenance-scripts.md](../maintenance-scripts.md) now documents the controlled workflow.

## Security Considerations

- All DB writes must use prepared statements (see [future/005](005-code-quality-refactoring.md))
- Superadmin session check at top of page and processing include
- Confirmation step prevents accidental mass updates
- Operation logged to `tbladminlog` for audit trail
