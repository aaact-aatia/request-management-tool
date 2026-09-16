-- Store the language of staff communication log entries for WCAG language-of-parts support.

SET @sql = (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE `tbladminlog` ADD COLUMN `language_code` VARCHAR(2) NULL AFTER `notes`',
        'SELECT 1'
    )
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'tbladminlog'
      AND COLUMN_NAME = 'language_code'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
