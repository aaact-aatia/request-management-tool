# Migration 001: Language File Consolidation

**Status**: In Progress  
**Base Branch**: `dockerfy`  
**Feature Branch**: `feature/language-file-consolidation`  
**Date Started**: 2025-12-12  
**Related ADR**: [ADR-001: Language File System](../adr/001-language-file-system.md)  
**Workflow**: Commits during steps, PR to `dockerfy` when complete

## Overview

This migration consolidates duplicate bilingual page files (`*-en.php` and `*-fr.php`) into single files that use a centralized language array system. This reduces file count from ~270 to ~135 and makes translations easier to maintain.

## Objectives

- Eliminate duplicate logic across language-specific page pairs
- Centralize all translation strings in `app/lang/en.php` and `app/lang/fr.php`
- Enable easy addition of new languages (e.g., Spanish, Inuktitut)
- Improve maintainability by keeping logic in one place

## Prerequisites

- Docker environment running
- Access to test both English and French versions
- Understanding of session management in PHP

## Implementation Steps

### Step 1: Create Language File Structure ✅

**Files Created:**
- `app/lang/en.php` - English translation strings
- `app/lang/fr.php` - French translation strings

**Structure:**
```php
<?php
return [
    'page_title' => 'New request - Request Management Tool',
    'main_heading' => 'Need help? Create a new request',
    // ... more strings
];
```

### Step 2: Create Language Switcher ✅

**File Created:**
- `app/switch-lang.php` - Handles language switching via session

**Usage:**
```html
<a href="/switch-lang.php?lang=fr&return=<?= urlencode($_SERVER['REQUEST_URI']) ?>">Français</a>
<a href="/switch-lang.php?lang=en&return=<?= urlencode($_SERVER['REQUEST_URI']) ?>">English</a>
```

### Step 3: Convert First Page (Proof of Concept) ✅

**Converted:**
- `app/openrequest-en.php` + `app/openrequest-fr.php` → `app/openrequest.php`

**Key Changes:**
- Load language file: `$lang = require("lang/{$_SESSION['lang']}.php");`
- Use language strings: `<?= htmlspecialchars($lang['page_title']) ?>`
- Set HTML lang attribute: `lang="<?= $_SESSION['lang'] ?>"`
- Dynamic AJAX endpoints based on session language

### Step 4: Testing

**Test Cases:**

1. **Default Language (English)**
   - [ ] Access `/openrequest.php` without session
   - [ ] Verify English text displays
   - [ ] Verify AJAX calls use `-en.php` endpoints

2. **French Language**
   - [ ] Use language switcher to set French
   - [ ] Access `/openrequest.php`
   - [ ] Verify French text displays
   - [ ] Verify AJAX calls use `-fr.php` endpoints
   - [ ] Verify correct catalogue ordering (French alphabetical)

3. **Form Functionality**
   - [ ] Select catalogue from dropdown
   - [ ] Verify AJAX populates service dropdown
   - [ ] Test cascading dropdowns (subservice, etc.)
   - [ ] Submit form and verify redirect works

4. **Language Persistence**
   - [ ] Set language to French
   - [ ] Navigate to another page
   - [ ] Return to `/openrequest.php`
   - [ ] Verify language remains French

5. **Security**
   - [ ] Verify all `$lang` outputs use `htmlspecialchars()`
   - [ ] Test invalid language codes (should default to 'en')
   - [ ] Test open redirect prevention in switcher

### Step 5: Next Pages to Convert

**Priority Order:**
1. `index-en.php` / `index-fr.php` - Main dashboard (most used)
2. `editrequest-en.php` / `editrequest-fr.php` - Edit request form
3. AJAX endpoints (requires database-driven approach - see Migration 002)

### Step 6: Cleanup (When Confident)

Once new system is tested and proven:
1. Create backup of old files
2. Remove old `-en.php` and `-fr.php` files
3. Update all navigation links
4. Update `.htaccess` if needed for redirects

## Testing Checklist

- [ ] Page loads in English by default
- [ ] Language switcher changes to French
- [ ] All text translates correctly
- [ ] Form submission works in both languages
- [ ] AJAX dropdowns populate correctly
- [ ] Session persists language choice
- [ ] No console errors in browser
- [ ] No PHP errors in logs

## Rollback Instructions

If issues are encountered:

1. **Immediate rollback:**
   ```bash
   git checkout main
   ```

2. **Partial rollback (keep new files, use old pages):**
   - Update navigation to point back to `-en.php` and `-fr.php` files
   - New files remain for reference but aren't used

3. **Complete removal:**
   ```bash
   git branch -D feature/language-file-consolidation
   rm -rf app/lang/
   rm app/openrequest.php app/switch-lang.php
   ```

## Performance Impact

**Expected:**
- Minimal (one additional `require()` per page load)
- Session check is negligible
- No database impact

**Monitoring:**
- Watch for increased session storage
- Monitor page load times

## Known Issues / Limitations

1. **AJAX endpoints still use language-specific files**
   - Currently: AJAX calls `-en.php` or `-fr.php` dynamically
   - Future: Convert to single API endpoint (Migration 002)

2. **Includes files still language-specific**
   - `refTop.php` vs `refTop-fr.php`
   - `loggedincheck-en.php` vs `loggedincheck-fr.php`
   - These will need conversion in future migration

3. **Catalogue ordering differs by language**
   - Preserved from original (alphabetical per language)
   - Future: Could be database-driven

## Resources

- [ADR-001: Language File System](../adr/001-language-file-system.md)
- [IMPROVEMENTS.md Section 2](../../IMPROVEMENTS.md#2-consolidate-bilingual-files-with-language-arrays)
- [PHP Internationalization](https://www.php.net/manual/en/book.intl.php)

## Questions / Decisions Needed

- [ ] Should we add more languages beyond EN/FR?
- [ ] Should we use `gettext` instead of simple arrays for future scalability?
- [ ] What's the timeline for converting remaining pages?

## Progress Log

### 2025-12-12
- Created language file structure
- Implemented language switcher
- Converted `openrequest.php` as proof of concept
- Ready for testing
