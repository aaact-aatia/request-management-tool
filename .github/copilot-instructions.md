# Request Management Tool - AI Coding Agent Instructions

## Project Overview

**Tech Stack**: PHP 8.2, MySQL 5.7, WET4 (Web Experience Toolkit), jQuery, Docker  
**Purpose**: Multi-page accessibility request management system for the IT Accessibility Office

This is a bilingual (English/French) government web application managing the lifecycle of accessibility service requests. Language is handled via a session variable and a language file system — pages are single files, not bilingual pairs.

## Architecture & Critical Patterns

### Database Connection Flow
**Always use this pattern** - never create new connection methods:
```php
require('sql.php');  // Handles session management, CORS, timezone, and includes db.php
// $link is now available for mysqli queries
```

- [sql.php](../app/sql.php) sets up global session state, includes CORS, and loads [db.php](../app/db.php)
- [db.php](../app/db.php) uses `vlucas/phpdotenv` to load `.env` credentials (`DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME`)
- Connection uses `mysqli` procedurally (not OOP) with `mysqli_real_escape_string()` for escaping
- Environment variables from `.env` include: `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME`, `MYSQL_ROOT_PASSWORD`, `PORT`, `TZ`, `CORS_ALLOWED_ORIGINS`, `GCNOTIFY_API_KEY`, `GCNOTIFY_TEMPLATE_ID`, `GCNOTIFY_TEST_EMAIL`
- Copy `.env.example` to `.env` to get started — all required keys are documented there

### Language System
Language is detected once per page load using `detectLanguage()` from [includes/helpers.php](../app/includes/helpers.php), stored in `$_SESSION['lang']` (`'en'` or `'fr'`), and used to load the appropriate string array:

```php
$lang = detectLanguage();           // sets $_SESSION['lang'], returns 'en' or 'fr'
$t = require("lang/{$lang}.php");   // $t['key'] for all UI strings
```

- [app/lang/en.php](../app/lang/en.php) and [app/lang/fr.php](../app/lang/fr.php) each return an associative array of 312 translation keys
- Pages are **single files** (not `-en.php`/`-fr.php` pairs) — all bilingual logic is handled through `$t[...]`
- Always add new UI strings to **both** lang files with matching keys

### Session Management Pattern
Sessions are initialized in [sql.php](../app/sql.php). Key session variables:
```php
$_SESSION['pid']        // User ID
$_SESSION['atype']      // Account type (1 = admin)
$_SESSION['email']      // User email
$_SESSION['firstname']  // User first name
$_SESSION['team']       // User team
$_SESSION['lang']       // 'en' or 'fr'
```

Check authentication with: `require('includes/loggedincheck.php')`

### Multi-Tier AJAX Request Flow
The "New Request" workflow uses cascading AJAX calls to populate dependent dropdowns:

**4-Tier System** (see [openrequest.php](../app/openrequest.php)):
1. User selects **Catalogue** (topic/product) → triggers `ajax1(catalogueid)`
2. `ajax1()` calls [addrequest2-ajax1.php](../app/addrequest2-ajax1.php) → populates Service dropdown
3. User selects **Service** → triggers `ajax2(serviceid)`
4. `ajax2()` calls [addrequest2-ajax2.php](../app/addrequest2-ajax2.php) → populates Subservice dropdown
5. Pattern continues with `ajax3()` and `ajax4()` for additional tiers

**Critical**: AJAX endpoints use hardcoded logic per catalogue ID (e.g., `if ($catalogueid==1)`) rather than database-driven lookups. See [docs/future/004-database-driven-ajax-dropdowns.md](../docs/future/004-database-driven-ajax-dropdowns.md) for the planned fix.

### File Storage
File upload and download use `AzureBlobStorageManager` (defined in [app/BlobStorage.php](../app/BlobStorage.php)). Azure Blob Storage has been removed — the current class is a **stub** that silently no-ops uploads and returns empty URLs. File functionality is pending replacement with local filesystem storage. See [docs/future/007-local-file-storage.md](../docs/future/007-local-file-storage.md).

### File Organization

```
app/
├── *.php                        # Single-file bilingual pages
├── db.php, sql.php              # Database connection (sql.php is the entry point)
├── BlobStorage.php              # File storage stub (Azure removed — see future/007)
├── emailController.php          # GC Notify email integration
├── composer.json                # Dependencies: phpdotenv, sendgrid
├── lang/
│   ├── en.php                   # English translation strings
│   └── fr.php                   # French translation strings
└── includes/
    ├── add-*.php, edit-*.php    # Admin CRUD operations
    ├── delete-*.php             # Delete operations (lightbox modals)
    ├── helpers.php              # Shared utility functions (detectLanguage, isAdmin, etc.)
    ├── appTop.php, appTop-fr.php, appFooter.php, refTop.php, preFooter.php  # Layout components
    ├── template/                # Page layout partials (head, header, footer, menu, scripts)
    ├── loggedincheck.php        # Authentication guard
    ├── httpscheck.php           # HTTPS redirect enforcement
    ├── calculate-bdays.php      # Business days calculator (SLA calculations)
    ├── sla-calculator.php       # Service Level Agreement logic
    └── dev-account-switcher.php # Superadmin dev tool for testing account types
```

## Data Model (12 Tables)

- `tblcatalogue` → Populates 1st dropdown (topics/products) in "New Request"
- `tblservices` → Populates 2nd dropdown (services)
- `tblsubservices` → Populates 3rd dropdown (sub-services)
- `tblsources` → Sub-services/sources (3rd tier alternative)
- `tblstatus` → Request status values
- `tblusers` → User accounts
- `tblaccounttype` → Account types (1 = admin)
- `tbladminlog` → Admin activity audit trail
- `tblcommlog` → Communication logs
- `tblcontacts` → Team contacts and escalation info
- `tblcss` → Customer satisfaction feedback
- `tbltriage` → Request triage/prioritization
- `tblfiles` → Uploaded file metadata (code, name, type, size, requestid)

## Developer Workflows

### Local Development Setup
```bash
# Copy environment file
cp .env.example .env
# Edit .env with your local credentials

# Start Docker environment
docker compose up -d

# Install PHP dependencies (happens automatically via entrypoint.sh)
# Access: http://localhost:${PORT} (from .env)
```

The database is initialized automatically on first start using the split bootstrap files: [database/schema.sql](../database/schema.sql), [database/reference.sql](../database/reference.sql), and [database/sample-dev.sql](../database/sample-dev.sql).

### Docker-First PHP Validation (Required)
Agents must use Docker for PHP validation and must not assume PHP is installed on the host machine.

Use these commands from the repository root:

```bash
# Ensure containers are running
docker compose up -d

# Lint a single PHP file in app/
docker compose exec web php -l /var/www/html/index.php

# Lint multiple changed files
docker compose exec web php -l /var/www/html/openrequest.php
docker compose exec web php -l /var/www/html/signin.php

# Lint all PHP files under app/
docker compose exec web sh -lc "find /var/www/html -name '*.php' -print0 | xargs -0 -n1 php -l"
```

When reporting validation, include the command used and whether syntax errors were detected.

### Deployment
CI/CD via GitHub Actions is planned but not yet configured. See README for current deployment approach.

## Project-Specific Conventions

### Bilingual Pages
Pages are **single files** — language is controlled by `$_SESSION['lang']`. When adding UI text:
1. Add the key to **both** [app/lang/en.php](../app/lang/en.php) and [app/lang/fr.php](../app/lang/fr.php)
2. Use `$t['your_key']` in the page template
3. Always `htmlspecialchars()` output: `<?= htmlspecialchars($t['your_key']) ?>`

### WET4 Framework Integration
All pages use Government of Canada's **Web Experience Toolkit v4**:
```html
<!-- Web Experience Toolkit (WET) / Boîte à outils de l'expérience Web (BOEW) -->
```
- Uses WET4 CSS classes: `form-control`, `btn btn-primary`, `alert alert-danger`
- Includes shared templates via `includes/template/head.php`, `includes/template/header.php`, `includes/template/page-details.php`, `includes/template/footer.php`, `includes/template/scripts.php`
- Do not use Bootstrap standalone classes - use WET4 equivalents

### Helper Functions
[includes/helpers.php](../app/includes/helpers.php) provides shared utilities — use these instead of reinventing:
- `detectLanguage()` — detects and sets `$_SESSION['lang']`
- `isAdmin()` — checks `$_SESSION['atype'] == 1`
- `canEditRequests()` — checks allowed account types
- `hasValue($val)` — non-empty, non-zero check
- `getPostValue($key)` — escaped `$_POST` value
- `getGetValue($key)` — escaped `$_GET` value
- `getDropdownOptions($link, $table, $lang)` — query for `<select>` options

### Security Patterns
```php
// HTTPS enforcement (in includes/httpscheck.php)
if ($https !== 'on' && $proto !== 'https') {
    header("Location: https://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);
}

// SQL escaping (always use before queries)
$var = mysqli_real_escape_string($link, $_GET['param']);

// Authentication (at top of protected pages)
require('includes/loggedincheck.php');

// Admin-only check
if ($_SESSION['atype'] != 1) { header("location:/index.php"); exit(); }
```

### SLA & Business Days
[includes/calculate-bdays.php](../app/includes/calculate-bdays.php) calculates business days excluding weekends/holidays. Used for estimating request completion dates. Timezone is always `America/New_York` (set in [sql.php](../app/sql.php)).

## Known Technical Debt

Detailed plans for all items are in [docs/future/](../docs/future/):

1. **Hardcoded AJAX logic** (`future/004`) — [addrequest2-ajax1.php](../app/addrequest2-ajax1.php) uses cascading if/else blocks instead of database-driven dropdowns
2. **File storage stub** (`future/007`) — `BlobStorage.php` is a no-op stub; file upload/download needs local filesystem replacement
3. **No prepared statements** (`future/005`) — queries use `mysqli_real_escape_string()` instead of parameterised statements
4. **PriorityUpdates.php incomplete** (`future/005`) — batch priority scorer has unfinished SLA logic, missing auth guard, and SQL injection vulnerability
5. **GC Notify not configured** (`future/006`) — email integration requires API key and template setup
6. **WET4 → GC Design System** (`future/002`) — long-term UI framework migration

## Common Tasks

### Adding a New Request Type (Catalogue)
1. Update `tblcatalogue` in database
2. Add hardcoded logic in [addrequest2-ajax1.php](../app/addrequest2-ajax1.php)
3. Add corresponding option in [openrequest.php](../app/openrequest.php) dropdown
4. Add translation strings to both lang files

### Creating Admin Pages
Follow pattern in `includes/`:
- `add-*.php` for create forms
- `edit-*.php` for update forms
- `delete-*.php` for lightbox delete confirmations
- Always check `$_SESSION['atype'] == 1` for admin access

### Modifying Existing Pages
1. Read [sql.php](../app/sql.php) usage first to understand session/DB state
2. Use `detectLanguage()` and `$t = require("lang/{$lang}.php")` for any new UI strings
3. Check for AJAX endpoints (search for `ajax*.php` references)
4. Test with Docker environment before deployment
