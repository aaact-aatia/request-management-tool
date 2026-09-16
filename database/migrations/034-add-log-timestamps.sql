-- Preserve timestamps for new request communications and staff log entries.
-- Existing date-only rows remain valid and use dateadded as a fallback.

SET @sql = (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE `tblcommlog` ADD COLUMN `timeadded` DATETIME NULL AFTER `dateadded`',
        'SELECT 1'
    )
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'tblcommlog'
      AND COLUMN_NAME = 'timeadded'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE `tbladminlog` ADD COLUMN `timeadded` DATETIME NULL AFTER `dateadded`',
        'SELECT 1'
    )
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'tbladminlog'
      AND COLUMN_NAME = 'timeadded'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
