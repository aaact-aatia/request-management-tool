-- Add an optional GC Notify reply-to address ID per team.
-- Empty values fall back to the global GCNOTIFY_EMAIL_REPLY_TO_ID setting.

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

SET @has_reply_to_id := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'tblteams'
    AND COLUMN_NAME = 'reply_to_id'
);
SET @sql := IF(
  @has_reply_to_id = 0,
  'ALTER TABLE `tblteams` ADD COLUMN `reply_to_id` varchar(64) DEFAULT NULL AFTER `email`',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
