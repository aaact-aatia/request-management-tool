# Migration 014: Enforce Catalogue Hierarchy

Migration `database/migrations/014-clean-catalogue-hierarchy.sql` adds foreign keys between catalogue, service, and subservice lookup tables.

It does not delete or reclassify data. Catalogue hierarchy IDs can be reused after a database reset, so an automated cleanup based only on numeric IDs could delete unrelated administrator-created records.

## Before Applying

1. Back up the database.
2. Check for orphan services and subservices.
3. Resolve each orphan after reviewing its names and intended parent.

The migration stops without changing the schema if either orphan query returns rows.

```sql
SELECT s.*
FROM tblservices s
LEFT JOIN tblcatalogue c ON c.id = s.catalogueid
WHERE c.id IS NULL;

SELECT ss.*
FROM tblsubservices ss
LEFT JOIN tblservices s ON s.id = ss.serviceid
WHERE s.id IS NULL;
```

## Apply Locally

```bash
docker compose exec -T db sh -lc \
  'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"' \
  < database/migrations/014-clean-catalogue-hierarchy.sql
```

The migration is idempotent. Existing constraints with the expected names are left unchanged.

## Verify

The final result sets must report `0` orphan services and `0` orphan subservices. Also verify these constraints exist in `INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS`:

- `fk_tblservices_catalogue`
- `fk_tblsubservices_service`

Retiring historical catalogue data is a separate, environment-specific operation. Identify rows by reviewed IDs and names, preserve any request classification history required for reporting, and do not reuse this migration for destructive cleanup.