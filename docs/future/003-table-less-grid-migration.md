# Future Plan 003: Table-Less Card Grid Migration

**Status**: Planned — Future Work  
**Date Planned**: 2026-05-01  
**Reference Branch**: `table-less` (proof of concept)  
**Estimated Effort**: 3–5 days  

## Overview

Replace the WET4 datatable layout on list pages (primarily `index.php`) with a card-based grid using the WET4 `wb-tagfilter` component. This improves readability, mobile responsiveness, and accessibility by removing reliance on complex `<table>` markup for list presentation.

## What the POC Demonstrated

The `table-less` branch implemented a full proof of concept on `index.php` with the following changes:

| Change | Detail |
|--------|--------|
| Layout | Replaced WET4 DataTables (`<table>`) with `wb-tagfilter` card grid |
| Filtering | Added text search using WET4 `wb-filter` with input-group label |
| Status colours | Mapped status IDs to Bootstrap label colour classes |
| SLA alerts | Removed redundant glyphicon warning icons from SLA rows |
| Styling | Added global app CSS (`app.css`) with panel heading contrast fix |
| Layout hook | Added `extraStyles` hook to `head.php` for page-level CSS injection |

### Key commits on `table-less` branch

```
7a00913 feat: convert index.php from datatable to wb-tagfilter card grid
371ed50 feat: add extraStyles hook to head.php; fix card button layout
c3017b4 feat(index): map status IDs to Bootstrap label colours
ee15346 fix(index): remove redundant glyphicon warning icons from SLA alerts
e8e2388 feat(index): add wb-filter text search with input-group addon label
cc9ed85 style: add global app CSS with panel heading contrast fix
68bcfd4 fix(index): rename 'actions' translation key to 'edit' with EN/FR labels
```

## Scope for Full Migration

The following list pages use `<table>` layouts and are candidates for the same card grid treatment:

- `app/index.php` ✅ Done in POC
- `app/indexonly.php`
- `app/indexresolved.php`
- `app/asearch.php`
- `app/css-pending.php`
- `app/css-results.php`
- Admin management pages: `catalogue.php`, `contacts.php`, `users.php`, `products.php`, `sources.php`, `status.php`, `holidays-mgmt.php`

## Implementation Notes

- The `wb-tagfilter` card grid pattern from the POC can be directly reused across all list pages
- `app.css` with the panel heading contrast fix should be applied globally (already done in POC)
- The `extraStyles` hook in `head.php` allows page-level CSS without modifying the shared header
- Each card should display the same data columns as the existing table — no data is removed, just represented as cards
- Mobile responsiveness is automatically improved as cards reflow naturally

## Dependencies

- No new libraries required — uses existing WET4 `wb-tagfilter` and Bootstrap label classes
- Requires `app/includes/head.php` to have the `extraStyles` hook (done in POC)
- Requires `app/app.css` to exist (created in POC)

## Rollback

Since this is a visual/layout change only, rollback is straightforward — revert the affected `.php` files to their table-based versions. No database changes required.
