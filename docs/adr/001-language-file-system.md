# ADR-001: Language File System for Bilingual Support

**Status**: Accepted  
**Date**: 2025-12-12  
**Decision Makers**: Development Team  
**Base Branch**: `dockerfy`  
**Related Migration**: [Migration 001: Language File Consolidation](../migrations/001-language-files.md)

## Context and Problem Statement

The RMT application is fully bilingual (English/French), currently implemented by maintaining duplicate files for each page (e.g., `openrequest-en.php` and `openrequest-fr.php`). This approach has several issues:

1. **Code Duplication**: Logic changes must be made in two places, increasing maintenance burden and risk of inconsistencies
2. **File Proliferation**: ~270 files instead of ~135, making the codebase harder to navigate
3. **Scalability**: Adding a third language would require creating another ~135 files
4. **Translation Management**: No central location to manage/update translations
5. **Logic Drift**: Over time, `-en.php` and `-fr.php` files can diverge unintentionally

## Decision Drivers

- Reduce maintenance burden on developers
- Improve code quality and consistency
- Enable easier addition of new languages
- Follow modern PHP best practices
- Maintain backward compatibility during transition

## Considered Options

### Option 1: Continue with Duplicate Files (Status Quo)
Maintain separate `-en.php` and `-fr.php` files as currently implemented.

**Pros:**
- No migration effort required
- Familiar to current developers
- Simple to understand

**Cons:**
- High maintenance burden
- Logic drift between language versions
- Difficult to add new languages
- Not scalable

### Option 2: gettext with .po/.mo Files
Use PHP's built-in `gettext` extension with translation files.

**Pros:**
- Industry standard for i18n
- Translation tools available (Poedit, etc.)
- Professional translation workflows
- Handles pluralization, context, etc.

**Cons:**
- Requires PHP extension (may not be available)
- More complex setup
- Compilation step for .mo files
- Steeper learning curve
- Overkill for 2-language system

### Option 3: Language Array Files (CHOSEN)
Create `lang/en.php` and `lang/fr.php` files that return associative arrays.

**Pros:**
- Simple to implement and understand
- No external dependencies
- Easy to add new languages
- Centralized translation management
- Can migrate incrementally
- Version controlled directly
- PHP native (no compilation needed)

**Cons:**
- Less sophisticated than gettext
- No built-in pluralization handling
- Manual translation workflow
- Not ideal for 10+ languages

### Option 4: Database-Driven Translations
Store all translations in database tables.

**Pros:**
- Runtime translation updates (no deployment)
- Can build translation UI for admins
- Centralized management

**Cons:**
- Database overhead on every page load
- More complex architecture
- Caching required for performance
- Overkill for static translations
- Harder to version control

## Decision Outcome

**Chosen option**: **Option 3 - Language Array Files**

We will implement a language file system using simple PHP arrays because:

1. **Right-sized solution**: Perfect for 2-language system, can scale to 5-6 languages easily
2. **Low complexity**: Team can adopt immediately without training
3. **Incremental migration**: Can convert pages one at a time
4. **Version control friendly**: All translations in Git
5. **No dependencies**: Pure PHP, no extensions required
6. **Performance**: Zero overhead (just one `require()`)

## Implementation

### File Structure
```
app/
├── lang/
│   ├── en.php    # English translations
│   └── fr.php    # French translations
├── openrequest.php          # Single bilingual page
└── switch-lang.php          # Language switcher
```

### Language File Format
```php
<?php
// lang/en.php
return [
    'page_title' => 'New request - Request Management Tool',
    'main_heading' => 'Need help? Create a new request',
    'make_selection' => 'Make your selection...',
    // ... more strings
];
```

### Usage Pattern
```php
<?php
// At top of page
session_start();
$_SESSION['lang'] = $_SESSION['lang'] ?? 'en';
$lang = require("lang/{$_SESSION['lang']}.php");

// In HTML
<h1><?= htmlspecialchars($lang['main_heading']) ?></h1>
```

### Naming Conventions
- Keys use `snake_case`
- Group related keys with prefixes (e.g., `alert_failed_title`, `alert_failed_message`)
- Keep keys descriptive (`catalogue_label` not `cl`)

## Consequences

### Positive

- **Reduced File Count**: 270 files → 135 files (-50%)
- **Single Source of Truth**: Logic exists in one place
- **Easier Maintenance**: Update logic once, translations once
- **Better Organization**: All translations centralized
- **Incremental Adoption**: Can migrate page-by-page
- **Easy Testing**: Switch languages via session variable
- **Future-Proof**: Adding Spanish/Inuktitut is just one new file

### Negative

- **Migration Effort**: ~135 page pairs need conversion
- **Learning Curve**: Team needs to learn new pattern (minimal)
- **Temporary Duplication**: During migration, old and new files coexist
- **Include Files**: Still need language-specific includes (`refTop.php` vs `refTop-fr.php`)

### Neutral

- **Session Dependency**: Relies on `$_SESSION['lang']` (already exists)
- **Manual Translation**: Still requires human translation (same as before)

## Validation

Success will be measured by:

1. **File Count Reduction**: Achieve ~50% reduction in PHP files
2. **Zero Logic Drift**: No more divergence between language versions
3. **Translation Completeness**: All strings in both language files
4. **Performance**: No measurable impact on page load times
5. **Developer Satisfaction**: Easier to maintain reported by team

## Future Considerations

### Potential Enhancements

1. **Validation Script**: Check for missing translation keys
2. **Fallback Mechanism**: Default to English if French translation missing
3. **Translation Helper**: Script to extract strings from old files
4. **Export/Import**: Generate CSV for external translation

### Migration to gettext (if needed)

If we grow to 10+ languages, we can migrate to gettext:
- Keep same `$lang` array syntax in code
- Create adapter that loads from .po files instead
- Minimal code changes required

## Related Documents

- [Migration 001: Language File Consolidation](../migrations/001-language-files.md)
- [IMPROVEMENTS.md Section 2](../../IMPROVEMENTS.md#2-consolidate-bilingual-files-with-language-arrays)
- [README.md](../../README.md)

## Revision History

- **2025-12-12**: Initial decision, accepted by development team
