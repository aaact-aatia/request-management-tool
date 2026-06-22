-- Add admin-configurable resolved status flag (idempotent for MySQL 5.7).
SET @has_is_resolved := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'tblstatus'
    AND COLUMN_NAME = 'is_resolved'
);

SET @ddl := IF(
  @has_is_resolved = 0,
  'ALTER TABLE `tblstatus` ADD COLUMN `is_resolved` TINYINT(1) DEFAULT 0 AFTER `namefr`',
  'SELECT 1'
);

PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Backfill existing status rows.
UPDATE `tblstatus`
SET `is_resolved` = CASE
  WHEN LOWER(TRIM(`nameen`)) = 'resolved' OR LOWER(TRIM(`namefr`)) IN ('résolu', 'resolu') THEN 1
  ELSE 0
END;
