# SLA Calculation and Setup

## Purpose

This document explains how SLA timing is configured and calculated in the Request Management Tool, including:
- where SLA inputs come from
- how business days are computed
- how pause statuses affect elapsed SLA time
- how SLA snapshots are logged on status changes

## Runtime Components

SLA behavior is implemented primarily in:
- [app/includes/sla-calculator.php](app/includes/sla-calculator.php)
- [app/includes/calculate-bdays.php](app/includes/calculate-bdays.php)
- [app/includes/editrequest-processing.php](app/includes/editrequest-processing.php)
- [app/viewrequest.php](app/viewrequest.php)
- [app/includes/helpers.php](app/includes/helpers.php)

## Required Setup

1. Timezone
- Application timezone is set in [app/sql.php](app/sql.php) and should remain aligned with environment settings.
- In Docker/local configuration, `TZ` is expected in `.env` (for example `America/New_York`).

2. Holiday data
- Business day calculations skip weekends and active holidays from `tblholidays`.
- Seed/reference holidays are loaded from:
- [database/reference.sql](database/reference.sql)

3. SLA source fields
- SLA target days come from `sds` on `tblservices` and `tblsubservices`.
- Request-level SLA clock uses:
- `tbltriage.slatimer` as override start date when present
- otherwise `tbltriage.datereceived`

## Core Calculation Logic

Main function:
- `calculateSLA($link, $requestId, $dateCreated, $dateResolved = null)` in [app/includes/sla-calculator.php](app/includes/sla-calculator.php)

How elapsed SLA days are computed:
1. Read status timeline from `StatusHistory` for the request up to end date.
2. Split timeline into intervals between status changes.
3. Count business days in each interval.
4. Add days only for statuses that count toward SLA.
5. Return total elapsed business days (non-negative).

Business day rules:
- Weekends are excluded.
- Active holidays in `tblholidays` are excluded.

## Pause Behavior (On Hold / Pending)

SLA pause logic is centralized in `shouldCountStatusForSla(...)` in [app/includes/sla-calculator.php](app/includes/sla-calculator.php).

Current behavior:
- Legacy excluded status IDs are not counted.
- Historical On Hold ID fallback is supported.
- Status labels are also checked (English/French), so hold-like labels pause SLA even when IDs differ by environment.

This means statuses like On Hold or Pending are treated as paused intervals, and elapsed SLA does not increase during those periods.

## SLA Due Date Derivation

Due date is calculated when status changes are logged using:
- `rmt_get_sla_days_required_for_request(...)` in [app/includes/helpers.php](app/includes/helpers.php)
- `addBusinessDays(...)` in [app/includes/calculate-bdays.php](app/includes/calculate-bdays.php)

Selection order for SLA days required:
1. Subservice `sds` (if present and greater than 0)
2. Service `sds`
3. Legacy special-case service IDs 21-24 use 15 days

Clock start date used for due date:
1. `slatimer` when set and valid
2. otherwise `datereceived`

## Status Change Logging with SLA Snapshot

On each status transition, [app/includes/editrequest-processing.php](app/includes/editrequest-processing.php) writes to `StatusHistory`.

Audit fields captured:
- previous status ID
- new status ID
- change timestamp
- actor user ID
- change type (status change, assignment change, or both)
- previous assigned team member ID
- new assigned team member ID
- SLA clock start date snapshot
- SLA due date snapshot
- SLA elapsed business days snapshot

The write path is schema-aware and will only write optional fields when columns exist.

## Management Visibility

Status change log is shown on request details in:
- [app/viewrequest.php](app/viewrequest.php)

Log split model in request details:

- Workflow log table: SLA, status, and assignment transitions from `StatusHistory`
- Other changes log table: employee-scope communication edits (plus admin-performed equivalents) from `RequestFieldHistory`

Visibility is restricted to:
- super admin
- admin
- manager
- team lead

Employees and read-only roles do not see this section.

## Database Schema and Migration

Fresh schema includes StatusHistory SLA/audit columns in:
- [database/schema.sql](database/schema.sql)

Existing databases should run migration:
- [database/migrations/011-add-statushistory-audit-sla-columns.sql](database/migrations/011-add-statushistory-audit-sla-columns.sql)
- [database/migrations/012-add-statushistory-assignment-change-columns.sql](database/migrations/012-add-statushistory-assignment-change-columns.sql)
- [database/migrations/013-add-request-field-history-table.sql](database/migrations/013-add-request-field-history-table.sql)

The migration is idempotent and safe to run multiple times.

## Operational Verification Checklist

1. Confirm `tblholidays` has active rows for current years.
2. Confirm `sds` values are set for relevant services/subservices.
3. Run migrations 011, 012, and 013 in the target database.
4. Change a request status and verify new `StatusHistory` row includes SLA snapshot fields.
5. Open request details as manager/admin and verify the Status Change Log table appears.
6. Verify On Hold/Pending intervals do not increase elapsed SLA in calculations.

## Docker Validation Commands

Use Docker-first validation for PHP files:

```bash
docker compose up -d
docker compose exec web php -l /var/www/html/includes/sla-calculator.php
docker compose exec web php -l /var/www/html/includes/editrequest-processing.php
docker compose exec web php -l /var/www/html/viewrequest.php
```

Apply migration 011 in local Docker DB:

```bash
cat database/migrations/011-add-statushistory-audit-sla-columns.sql | docker compose exec -T db sh -lc 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" -D aaact'
```
