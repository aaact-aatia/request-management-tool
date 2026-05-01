# RMT (Request Management Tool) - AI Coding Agent Instructions

## Project Overview

**Tech Stack**: PHP 8.2, MySQL 5.7, WET4 (Web Experience Toolkit), jQuery, Docker  
**Purpose**: Multi-page accessibility request management system for the IT Accessibility Office

This is a bilingual (English/French) government web application managing the lifecycle of accessibility service requests. Each page typically exists in both `-en.php` and `-fr.php` variants.

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
- Environment variables from `.env` include: `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME`, `MYSQL_ROOT_PASSWORD`, `PORT`, `TZ`

### Session Management Pattern
Sessions are initialized in [sql.php](../app/sql.php). Key session variables:
```php
$_SESSION['pid']        // User ID
$_SESSION['atype']      // Account type (1 = admin)
$_SESSION['email']      // User email
$_SESSION['firstname']  // User first name
$_SESSION['team']       // User team
```

Check authentication with: `require('includes/loggedincheck-en.php')` (or `-fr.php`)

### Multi-Tier AJAX Request Flow
The "New Request" workflow uses cascading AJAX calls to populate dependent dropdowns:

**4-Tier System** (see [openrequest-en.php](../app/openrequest-en.php)):
1. User selects **Catalogue** (topic/product) → triggers `ajax1(catalogueid)`
2. `ajax1()` calls [addrequest2-ajax1-en.php](../app/addrequest2-ajax1-en.php) → populates Service dropdown
3. User selects **Service** → triggers `ajax2(serviceid)`  
4. `ajax2()` calls [addrequest2-ajax2-en.php](../app/addrequest2-ajax2-en.php) → populates Subservice dropdown
5. Pattern continues with `ajax3()` and `ajax4()` for additional tiers

**Critical**: AJAX endpoints use hardcoded logic per catalogue ID (e.g., `if ($catalogueid==1)`) rather than database-driven lookups. This is a known technical debt item.

### File Organization

```
app/
├── *-en.php, *-fr.php          # Bilingual page pairs
├── db.php, sql.php             # Database connection (sql.php is the entry point)
├── composer.json               # Dependencies: phpdotenv, sendgrid
├── includes/
│   ├── add-*.php, edit-*.php   # Admin CRUD operations
│   ├── delete-*.php            # Delete operations (lightbox modals)
│   ├── appTop.php, appFooter.php, refTop.php, preFooter.php  # Layout components
│   ├── loggedincheck-*.php     # Authentication guards
│   ├── httpscheck.php          # HTTPS redirect enforcement
│   ├── calculate-bdays.php     # Business days calculator (SLA calculations)
│   └── sla-calculator.php      # Service Level Agreement logic
```

## Data Model (12 Tables)

- `tblcatalogue` → Populates 1st dropdown (topics/products) in "New Request"
- `tblservices` → Populates 2nd dropdown (services)
- `tblsubservices` → Populates 3rd dropdown (sub-services)
- `tblsources` → Sub-services/sources (3rd tier alternative)
- `tblstatus` → Request status values
- `tblusers` → User accounts (has `environment` field for prod/dev switching)
- `tblaccounttype` → Account types (1 = admin)
- `tbladminlog` → Admin activity audit trail
- `tblcommlog` → Communication logs
- `tblcontacts` → Team contacts and escalation info
- `tblcss` → Customer satisfaction feedback
- `tbltriage` → Request triage/prioritization

## Developer Workflows

### Local Development Setup
```bash
# Start Docker environment (uses docker-compose.yml)
docker-compose up -d

# Install PHP dependencies (happens automatically via entrypoint.sh)
composer install --no-dev

# Access: http://localhost:${PORT} (from .env)
```

**Required `.env` file** (no example exists in repo):
```env
DB_HOST=aaact-rmt-db
DB_USER=aaactuser
DB_PASS=secret
DB_NAME=aaact
MYSQL_ROOT_PASSWORD=rootpass
PORT=8080
TZ=America/New_York
```

### Deployment (Azure)
Per README.md (section 5):
1. Commit to main via GitHub Desktop
2. Open Azure Portal → Run Command
3. Execute provided bash script (RunShellScript)

Azure Pipelines ([azure-pipelines.yml](../azure-pipelines.yml)) runs `composer install`, archives files, and deploys to VM `ITAORMTVM`.

## Project-Specific Conventions

### Bilingual File Pairs
**Every user-facing page** has `-en.php` and `-fr.php` variants with identical logic but different language strings. When modifying functionality:
1. Always update BOTH language variants
2. Keep logic identical, only translate UI text
3. Watch for includes: `includes/refTop.php` vs `includes/refTop-fr.php`

### WET4 Framework Integration
All pages use Government of Canada's **Web Experience Toolkit v4**:
```html
<!-- Standard in all page headers -->
<!-- Web Experience Toolkit (WET) / Boîte à outils de l'expérience Web (BOEW) -->
```
- Uses WET4 CSS classes: `form-control`, `btn btn-primary`, `alert alert-danger`
- Includes standard GC templates via `includes/refTop.php`, `includes/appTop.php`
- Do not use Bootstrap standalone classes - use WET4 equivalents

### Security Patterns
```php
// HTTPS enforcement (in includes/httpscheck.php)
if ($https !== 'on' && $proto !== 'https') {
    header("Location: https://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);
}

// SQL escaping (always use before queries)
$var = mysqli_real_escape_string($link, $_GET['param']);

// Authentication (at top of protected pages)
require('includes/loggedincheck-en.php');
```

### SLA & Business Days
[includes/calculate-bdays.php](../app/includes/calculate-bdays.php) calculates business days excluding weekends/holidays. Used for estimating request completion dates. Timezone is always `America/New_York` (set in [sql.php](../app/sql.php)).

## Known Technical Debt (from README)

1. **Hardcoded AJAX logic**: [addrequest2-ajax1-en.php](../app/addrequest2-ajax1-en.php) uses cascading if/else blocks instead of database-driven dropdowns
2. **No staged environments**: Recommended to add dev/test/staging tiers before production
3. **Git practices**: No branch strategy documented; direct commits to main
4. **Framework evaluation**: README suggests migrating to Laravel for better maintainability

## Common Tasks

### Adding a New Request Type (Catalogue)
1. Update `tblcatalogue` in database
2. Add hardcoded logic in [addrequest2-ajax1-en.php](../app/addrequest2-ajax1-en.php) and `-fr.php`
3. Add corresponding option in [openrequest-en.php](../app/openrequest-en.php) dropdown (lines 66-79)
4. Repeat for French variant

### Creating Admin Pages
Follow pattern in `includes/`:
- `add-*.php` for create forms
- `edit-*.php` for update forms
- `delete-*.php` for lightbox delete confirmations
- Always check `$_SESSION['atype'] == 1` for admin access

### Modifying Existing Pages
1. Read [sql.php](../app/sql.php) usage first to understand session/DB state
2. Locate both `-en.php` and `-fr.php` variants
3. Check for AJAX endpoints (search for `ajax*.php` references)
4. Test with Docker environment before deployment
