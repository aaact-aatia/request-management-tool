-- Add extra role flags for superuser/admin privileges independent of primary account type.
-- Idempotent for MySQL 5.7.

SET @has_is_superuser := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'tblusers'
    AND COLUMN_NAME = 'is_superuser'
);

SET @ddl := IF(
  @has_is_superuser = 0,
  'ALTER TABLE `tblusers` ADD COLUMN `is_superuser` TINYINT(1) DEFAULT 0 AFTER `atype`',
  'SELECT 1'
);

PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_is_admin := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'tblusers'
    AND COLUMN_NAME = 'is_admin'
);

SET @ddl := IF(
  @has_is_admin = 0,
  'ALTER TABLE `tblusers` ADD COLUMN `is_admin` TINYINT(1) DEFAULT 0 AFTER `is_superuser`',
  'SELECT 1'
);

PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Backfill existing users from legacy atype-only model.
UPDATE `tblusers`
SET `is_superuser` = 1,
    `is_admin` = 1,
    `atype` = 3,
    `team` = ''
WHERE `atype` = 1;

UPDATE `tblusers`
SET `is_admin` = 1,
    `atype` = 3,
    `team` = ''
WHERE `atype` = 2;
