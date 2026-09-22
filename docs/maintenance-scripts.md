# Maintenance & Utility Scripts

Maintenance operations that perform bulk data changes should be run deliberately with care, as they make irreversible database changes.

---

## Bulk anonymization

**Purpose:** Bulk anonymizes client information on selected triage records.

The controlled UI is intentionally hidden from routine navigation. Superadministrators can access it directly at `/bulk-anonymize.php` when an approved anonymization operation is required.

**What it does:**

1. Lets the superadministrator choose catalogue IDs and excluded service IDs
2. Previews the number of matching records without writing changes
3. Requires an explicit confirmation before execution
4. For each matching record:
   - Overwrites `clientlname`, `clientfname`, `clientemail`, `clientphone` in `tbltriage` with generic AAACT contact details
   - Replaces the original description in `tblcommlog` with a generic placeholder string from the language file
5. Records the criteria, actor, language, and number of affected records in `tbladminlog`

**When to use:** Before sharing or archiving an approved dataset when personally identifiable client information must be removed from a selected set of requests.

**Caution:**
- **Irreversible** — there is no undo. Back up the database first.
- Access is restricted to superadministrators and all reads and writes use prepared statements.
- Verify the preview criteria carefully before confirming the operation.

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

