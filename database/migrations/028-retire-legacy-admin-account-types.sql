-- Retire legacy Super Administrator (1) and Administrator (2) account types.
-- Normalize any remaining users before removing the obsolete lookup rows.

START TRANSACTION;

UPDATE `tblusers`
SET `atype` = 3,
    `is_superuser` = 1,
    `is_admin` = 1
WHERE `atype` = 1;

UPDATE `tblusers`
SET `atype` = 3,
    `is_admin` = 1
WHERE `atype` = 2;

-- Remove the legacy lookup rows only after all user references have been migrated.
DELETE FROM `tblaccounttype`
WHERE `id` IN (1, 2)
  AND NOT EXISTS (
    SELECT 1
    FROM `tblusers`
    WHERE `tblusers`.`atype` IN (1, 2)
  );

COMMIT;

-- Verification: this must return zero rows after the migration.
SELECT `atype`, COUNT(*) AS `user_count`
FROM `tblusers`
WHERE `atype` IN (1, 2)
GROUP BY `atype`;

-- Verification: this must return zero rows after the migration.
SELECT `id`, `nameen`, `namefr`
FROM `tblaccounttype`
WHERE `id` IN (1, 2);
