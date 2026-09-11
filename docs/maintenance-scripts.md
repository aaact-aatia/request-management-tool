# Maintenance & Utility Scripts

One-off scripts that perform bulk data operations. These are **not accessible from the UI** (menu links are commented out) and should be run deliberately with care, as they make irreversible database changes.

---

## `batch-ace-info.php`

**Purpose:** Bulk anonymizes client information on ACE (Accessibility, Accommodation and Adaptive Computer Technology) triage records.

**What it does:**

1. Queries all records in `tbltriage` belonging to catalogue IDs 1–4 (ACE service categories)
2. Skips records for service ID 46 (WS CoE services)
3. For each remaining record:
   - Overwrites `clientlname`, `clientfname`, `clientemail`, `clientphone` in `tbltriage` with generic AAACT contact details
   - Replaces the original description in `tblcommlog` with a generic placeholder string (`batch_ace_no_details` from the lang file)
4. Redirects to the index page with `?status=batchsuccess`

**When to use:** Before sharing or archiving a dataset — strips personally identifiable client information from a bulk set of ACE requests.

**Future plan:** This script will be replaced by a configurable superadmin UI tool. See [docs/future/008-superadmin-bulk-anonymize.md](future/008-superadmin-bulk-anonymize.md).

**Caution:**
- **Irreversible** — there is no undo. Back up the database first.
- The menu link is intentionally commented out in `appmenu.php` and `template/menu.php` to prevent accidental execution.
- Contains a **SQL injection vulnerability** (`$requestid` is interpolated directly into UPDATE queries). Do not expose this endpoint publicly. See [docs/future/005-code-quality-refactoring.md](future/005-code-quality-refactoring.md) for the remediation plan.

**How to run (deliberately):**

Temporarily uncomment the menu link in `appmenu.php`, or navigate directly:

```
https://<your-domain>/batch-ace-info.php?lang=en
```

---

## `scripts/sync-notification-templates.php`

**Purpose:** Validates and syncs app-wide default GC Notify message templates from [docs/notification-templates.md](notification-templates.md) to `tblnotificationtemplates` or generates SQL migration files.

**What it does:**

1. Parses `docs/notification-templates.md` into 12 expected audience/event/language templates.
2. Cleans Markdown backticks wrapping `{{placeholder}}` tokens for clean plaintext email delivery.
3. Validates placeholders against the application's supported placeholder catalog.
4. Supports four operational modes:
   - `--validate`: Validates syntax and placeholders without touching the database.
   - `--diff`: Compares Markdown file against database defaults (`team_id=0, service_id=0, subservice_id=0`) and displays differences.
   - `--generate-sql[=<file>]`: Outputs SQL migration script with `ON DUPLICATE KEY UPDATE` to file or stdout.
   - `--apply`: Updates app-wide default templates directly in the connected database.

**When to use:** Whenever default notification message wording is updated in `docs/notification-templates.md` and needs to be verified, synced to local DB, or converted into a deployment migration.

**How to run:**

```bash
# Validate markdown templates
docker compose exec -T web php /var/www/scripts/sync-notification-templates.php --validate

# View differences against current database defaults
docker compose exec -T web php /var/www/scripts/sync-notification-templates.php --diff

# Generate a numbered SQL migration file
docker compose exec -T web php /var/www/scripts/sync-notification-templates.php --generate-sql=/var/www/database/migrations/030-sync-default-notification-templates.sql

# Apply directly to local/dev database
docker compose exec -T web php /var/www/scripts/sync-notification-templates.php --apply
```

