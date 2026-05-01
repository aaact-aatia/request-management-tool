# Future Plan 005: Code Quality Refactoring

**Status**: Planned — Future Work  
**Date Planned**: 2026-05-01  
**Estimated Effort**: 3–5 days  

## Overview

Two related code quality improvements to the data layer:

1. **Separate concerns in `sql.php`** — split the monolithic bootstrap file into focused single-responsibility files
2. **Replace manual escaping with prepared statements** — improve security and consistency across all database queries

---

## Part A: Separate Database Connection from Session/CORS/Timezone

### Current Problem

`app/sql.php` handles five unrelated concerns in one file:
- CORS headers (via `cors.php`)
- Session configuration and lifetime
- Timezone setup
- Session variable initialization
- Database connection (via `db.php`)

This makes it harder to test, debug, and reason about each concern independently.

### Proposed Structure

```
app/
  config/
    database.php   — mysqli connection only
    session.php    — session start, defaults, timezone
  middleware/
    cors.php       — CORS headers (already exists)
  bootstrap.php    — replaces sql.php, requires all three
```

```php
// config/database.php
require_once __DIR__ . '/../../vendor/autoload.php';
Dotenv\Dotenv::createImmutable(__DIR__ . '/../..')->safeLoad();
$link = mysqli_connect($_ENV['DB_HOST'], $_ENV['DB_USER'], $_ENV['DB_PASS'], $_ENV['DB_NAME']);
$link->set_charset("utf8mb4");

// config/session.php
if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.gc_maxlifetime', 86400);
    session_set_cookie_params(86400);
    date_default_timezone_set($_ENV['TZ'] ?? 'America/New_York');
    session_start();
}
$_SESSION['pid'] ??= null;
$_SESSION['atype'] ??= null;
$_SESSION['email'] ??= null;
$_SESSION['firstname'] ??= null;
$_SESSION['team'] ??= null;

// bootstrap.php (replaces sql.php)
require_once 'middleware/cors.php';
require_once 'config/session.php';
require_once 'config/database.php';
```

All existing `require('sql.php')` calls across the app would change to `require('bootstrap.php')` — a straightforward find-and-replace.

---

## Part B: Use Prepared Statements Consistently

### Current Problem

Most queries use manual escaping, which is error-prone:

```php
$var = mysqli_real_escape_string($link, $_GET['param']);
$sql = "SELECT * FROM tbl WHERE id='$var'";
$result = mysqli_query($link, $sql);
```

### Proposed Approach

Replace with parameterised prepared statements:

```php
$stmt = $link->prepare("SELECT * FROM tbl WHERE id = ?");
$stmt->bind_param("i", $_GET['param']);
$stmt->execute();
$result = $stmt->get_result();
```

### Migration Strategy

Add query helper functions to `app/includes/helpers.php` (or a new `app/includes/db-helpers.php`):

```php
function fetchOne($link, $sql, $types = '', $params = []) {
    $stmt = $link->prepare($sql);
    if ($params) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function fetchAll($link, $sql, $types = '', $params = []) {
    $stmt = $link->prepare($sql);
    if ($params) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Usage
$user     = fetchOne($link, "SELECT * FROM tblusers WHERE id = ?", 'i', [$_SESSION['pid']]);
$services = fetchAll($link, "SELECT * FROM tblservices WHERE catalogueid = ?", 'i', [$catalogueid]);
```

Migrate one file at a time, prioritising pages that accept user input via GET/POST parameters.

---

---

## Part C: Complete `PriorityUpdates.php`

### Current State

`app/PriorityUpdates.php` is an unfinished batch priority-score calculator. It reads all active triage requests, scores them across six dimensions, and writes the result back to `tbltriage.priority_score`. It was intentionally kept (not removed) because the scoring model is real and partially correct.

**Known issues to fix before this can be used:**

1. **Incomplete SLA scoring block** — lines ~197–202 contain `$row[""]` (empty key); the logic for comparing `date_recieved + slatimer` against `date_required` was never written
2. **SQL injection** — the `UPDATE` query builds the string directly from PHP variables with no escaping or prepared statements:
   ```php
   $sql2 = "UPDATE `tbltriage` SET `priority_score` = '$prioScore' WHERE `id` = '$request_id'";
   ```
   Note: the query is also never executed (missing `mysqli_query` call)
3. **No authentication check** — any unauthenticated user can trigger a mass priority recalculation by visiting the URL; add `require('includes/loggedincheck.php')` and an admin-only guard (`$_SESSION['atype'] == 1`)
4. **Stale redirect** — footer redirects to `index-en.php` which no longer exists; update to `index.php`
5. **First query result unused** — the initial `SELECT` on line 14 is run but `$result` is never iterated; only the second query (line 51) is used

### Proposed Fix

- Add auth guard at top
- Finish the SLA comparison: calculate `business_days_between($datereceived, today)` vs `$slatimer`, apply `$withinSLA`, `$underSLA`, or `$underWithSLA` score accordingly
- Execute the `UPDATE` using a prepared statement
- Update redirect to `index.php`
- Remove the dead first query

---

## Implementation Order

1. Part B helpers first (additive, no breaking changes)
2. Migrate highest-risk pages to prepared statements (search, edit, add request flows)
3. Complete `PriorityUpdates.php` (Part C) — finish SLA logic, add auth, fix SQL
4. Part A refactor (requires updating all `require('sql.php')` references — do last to minimise churn)
