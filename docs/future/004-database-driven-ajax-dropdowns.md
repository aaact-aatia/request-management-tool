# Future Plan 004: Database-Driven AJAX Dropdowns

**Status**: Planned — Future Work  
**Date Planned**: 2026-05-01  
**Estimated Effort**: 2–3 days  

## Overview

Replace the hardcoded if/else blocks in the AJAX dropdown files with dynamic database queries. This eliminates ~1000+ lines of duplicated code and makes it possible to add or change catalogue/service/subservice options without touching PHP files.

## Current Problem

Files like `app/addrequest2-ajax1.php` contain cascading hardcoded blocks:

```php
if ($catalogueid == 1) {
    // 20 lines of hardcoded HTML
} elseif ($catalogueid == 2) {
    // 20 more lines
} // ... repeated for every catalogue ID
```

This pattern is repeated across `addrequest2-ajax1.php` through `addrequest2-ajax4.php` for the new request flow, and `addrequest-ajax1.php` / `addrequest-ajax2.php` for the search/triage flow.

## Affected Files

- `app/addrequest2-ajax1.php` — catalogue → service dropdown
- `app/addrequest2-ajax2.php` — service → subservice dropdown
- `app/addrequest2-ajax3.php` — subservice → source dropdown
- `app/addrequest2-ajax4.php` — source → final selection
- `app/addrequest-ajax1.php` — search/triage catalogue → service
- `app/addrequest-ajax2.php` — search/triage service → subservice

## Proposed Approach

Replace hardcoded logic with a single parameterised query per file:

```php
// addrequest2-ajax1.php — catalogue → services
$stmt = $link->prepare(
    "SELECT id, nameen, namefr FROM tblservices
     WHERE catalogueid = ? AND status = '1'
     ORDER BY nameen ASC"
);
$stmt->bind_param("i", $catalogueid);
$stmt->execute();
$result = $stmt->get_result();

$lang = $_SESSION['lang'] ?? 'en';
$nameCol = $lang === 'fr' ? 'namefr' : 'nameen';

echo '<label for="serviceid">...</label>';
echo '<select class="form-control" id="serviceid" name="serviceid" onchange="ajax2(this.value)" required>';
echo '<option value="">Make your selection</option>';
while ($row = $result->fetch_assoc()) {
    echo '<option value="' . htmlspecialchars($row['id']) . '">'
       . htmlspecialchars($row[$nameCol]) . '</option>';
}
echo '</select>';
```

## Implementation Steps

1. Audit each AJAX file and map which table/columns it queries
2. Verify `tblservices`, `tblsubservices`, and `tblsources` have the correct data for all current catalogue IDs
3. Replace one file at a time, testing the new request flow after each change
4. Remove the hardcoded blocks once the dynamic version is confirmed working

## Impact

- Eliminates ~1000+ lines of duplicated hardcoded HTML/PHP
- Adding a new catalogue type requires only a database insert — no PHP changes
- Bilingual support (EN/FR) is handled automatically via the `nameen`/`namefr` columns already in the schema

## Notes

- The `tblcatalogue`, `tblservices`, `tblsubservices`, and `tblsources` tables already exist with the correct structure — this change connects the existing data to the dropdown logic
- This is referenced in the README as known technical debt
