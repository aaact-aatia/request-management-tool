-- Remove the retired audit/re-audit intake choice and scoring flag.

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

UPDATE `tblsubservices`
SET `status` = 0
WHERE LOWER(TRIM(`nameen`)) IN ('audit', 're-audit', 'reaudit')
   OR LOWER(TRIM(`namefr`)) IN ('audit', 'vérification', 'vérification de suivi');

SET @has_isreaudit_column := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'tbltriage'
    AND COLUMN_NAME = 'isreaudit'
);
SET @drop_isreaudit_sql := IF(
  @has_isreaudit_column > 0,
  'ALTER TABLE `tbltriage` DROP COLUMN `isreaudit`',
  'SELECT 1'
);
PREPARE drop_isreaudit_stmt FROM @drop_isreaudit_sql;
EXECUTE drop_isreaudit_stmt;
DEALLOCATE PREPARE drop_isreaudit_stmt;