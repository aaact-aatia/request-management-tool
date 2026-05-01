# Future Plan 002: WET4 to GC Design System Migration

**Status**: Planned — Future Work  
**Date Planned**: 2026-04-08  
**Estimated Effort**: 11–18 days  
**Reference**: [GC Design System Documentation](https://design-system.canada.ca/en/)

## Overview

Replace the Web Experience Toolkit 4 (WET4) framework with the GC Design System (GCDS) to modernize the RMT UI while preserving all existing functionality.

## Current State (WET4)

- **Framework**: Web Experience Toolkit v4 (GCWeb theme)
- **Delivery**: CDN from `canada.ca/etc/designs/canada/cdts/gcweb/`
- **Layout builders**: `wet.builder.appTop()`, `wet.builder.appFooter()`, etc.
- **Critical dependencies**:
  - Lightbox modals (`wb-lbx`): 46 instances across admin functions
  - Glyphicons: 30+ instances for visual indicators
  - Utility classes: `mrgn-*`, `pull-*`, `form-control`, `btn`, `alert`
- **Bilingual**: Separate `appTop.php` / `appTop-fr.php` with identical structure

## Target State (GCDS)

- **Framework**: GC Design System v1.1.0+ (Web Components)
- **Delivery**: CDN from `design-system.canada.ca`
- **Layout**: HTML Web Components (`<gcds-header>`, `<gcds-footer>`, etc.)
- **Available components**: Header, Footer, Breadcrumbs, Language Toggle, Grid, Button, Input, Select, Textarea, Checkboxes, Radios, Error Message, Notice, Icon, File Uploader, Date
- **Missing component**: Modal/Dialog → use native HTML `<dialog>` as temporary fallback

## Modal Fallback Strategy

GCDS does not yet provide a modal component. RMT has 46 lightbox instances for critical functionality (delete confirmations, edit forms, add forms).

**Solution**: Native HTML `<dialog>` element
- Excellent built-in accessibility (focus trap, ESC to close, `::backdrop`)
- No external dependencies
- Easy swap to `<gcds-modal>` when GCDS releases it — a single `openModal()` helper function makes the migration trivial

## Migration Phases

### Phase 1 — Foundation (1–2 days)
- Replace WET4 CDN in `refTop.php` with GCDS CDN (pinned version)
- Create `includes/modal-helper.js` — `openModal(url)` / `closeModal()` helpers
- Create `includes/modal-styles.css` — `<dialog>` styling
- Create `includes/icon-fallbacks.css` — Unicode replacements for unavailable GCDS icons

### Phase 2 — Layout Conversion (2–3 days)
- Replace `wet.builder.appTop()` with `<gcds-header>` in `appTop.php` and `appTop-fr.php`
- Replace `wet.builder.appFooter()` with `<gcds-footer>` in `appFooter.php`
- Add `<dialog id="app-modal">` to `appFooter.php`
- Replace `wet.builder.preFooter()` with `<gcds-date-modified>` in `preFooter.php`

### Phase 3 — CSS Class Migration (3–5 days)
Replace WET4 utility classes with GCDS equivalents across all pages:

| WET4 | GCDS Replacement |
|------|-----------------|
| `<input class="form-control">` | `<gcds-input>` |
| `<select class="form-control">` | `<gcds-select>` |
| `<button class="btn btn-primary">` | `<gcds-button variant="primary">` |
| `<div class="alert alert-danger">` | `<gcds-notice notice-type="danger">` |
| `class="mrgn-tp-md"` | `class="mt-300"` |
| `class="pull-right"` | `class="text-align-right"` |

### Phase 4 — Modal Migration (2–3 days)
- Replace all 46 `class="wb-lbx"` triggers with `onclick="openModal(this.href); return false;"`
- Add close button + cancel button to all `includes/delete-*.php`, `includes/edit-*.php`, `includes/add-*.php`

### Phase 5 — Icon Replacement (1–2 days)
Replace Glyphicons with GCDS icons or Unicode fallbacks:

| Glyphicon | Replacement |
|-----------|------------|
| `glyphicon-envelope` | `<gcds-icon name="mail">` |
| `glyphicon-trash` | `<span class="icon-trash" aria-hidden="true">` |
| `glyphicon-eye-open` | `<span class="icon-view" aria-hidden="true">` |
| `glyphicon-new-window` | `<gcds-icon name="external-link">` |
| `glyphicon-ok` | `<gcds-icon name="check">` |

### Phase 6 — Testing & Cleanup (2–3 days)
- Cross-browser testing (Chrome, Edge, Firefox, Safari)
- Accessibility audit (axe DevTools, keyboard nav, screen reader)
- Functional testing (all 46 modals, all forms, both languages)
- Remove all WET4 remnants (`wb-`, `glyphicon`, `canada.ca/etc/designs` CDN references)
- Update README

## Files Affected

**Modified (38 total)**: Layout includes (6), admin pages (9), request pages (5), index/search pages (4), modal content files (~15)  
**New files (3)**: `modal-helper.js`, `modal-styles.css`, `icon-fallbacks.css`

## Rollback

- **Immediate** (< 5 min): Revert `refTop.php` to restore WET4 CDN — restores functionality
- **Full** (< 15 min): `git revert <commit-range>` for all phase commits, redeploy

## Future: When GCDS Modal Component is Released

Replace `<dialog>` with `<gcds-modal>`, update `modal-helper.js`, remove `modal-styles.css`. Estimated effort: 1–2 hours.

## Success Criteria

1. All 46 modals function correctly in both languages
2. All forms submit and validate properly
3. Zero JavaScript console errors
4. Zero WET4 references remain in codebase
5. Accessibility audit passes (WCAG 2.1 AA)
6. Cross-browser testing passes
7. Bilingual functionality verified
