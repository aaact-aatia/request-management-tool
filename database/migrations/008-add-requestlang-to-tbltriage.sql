-- Add request language to tbltriage so notification logic can follow the site's language at submission time.
-- Idempotent for MySQL 5.7.

SET @has_requestlang := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'tbltriage'
    AND COLUMN_NAME = 'requestlang'
);

SET @ddl := IF(
  @has_requestlang = 0,
  'ALTER TABLE `tbltriage` ADD COLUMN `requestlang` VARCHAR(2) NOT NULL DEFAULT ''en'' AFTER `clientphone`',
  'SELECT 1'
);

PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE `tbltriage`
SET `requestlang` = 'en'
WHERE `requestlang` IS NULL OR `requestlang` = '';
