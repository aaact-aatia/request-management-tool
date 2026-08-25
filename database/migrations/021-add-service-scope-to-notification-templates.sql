-- Add service/sub-service scoped notification template overrides, layered above team/global.
-- A row is scoped to exactly one of: subservice (subservice_id > 0), service (service_id > 0),
-- team (team_id > 0), or global (all zero). Resolution checks subservice -> service -> team -> global.

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

SET @has_service_id := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'tblnotificationtemplates'
    AND COLUMN_NAME = 'service_id'
);
SET @sql := IF(
  @has_service_id = 0,
  'ALTER TABLE `tblnotificationtemplates` ADD COLUMN `service_id` int(11) NOT NULL DEFAULT 0 AFTER `team_id`',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_subservice_id := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'tblnotificationtemplates'
    AND COLUMN_NAME = 'subservice_id'
);
SET @sql := IF(
  @has_subservice_id = 0,
  'ALTER TABLE `tblnotificationtemplates` ADD COLUMN `subservice_id` int(11) NOT NULL DEFAULT 0 AFTER `service_id`',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Replace the team-only unique key with one that includes the new scope columns.
SET @has_old_key := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'tblnotificationtemplates'
    AND INDEX_NAME = 'uq_notification_template'
    AND SEQ_IN_INDEX = 1
    AND COLUMN_NAME = 'team_id'
);
SET @has_new_key := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'tblnotificationtemplates'
    AND INDEX_NAME = 'uq_notification_template_scope'
);
SET @sql := IF(
  @has_new_key = 0,
  CONCAT(
    'ALTER TABLE `tblnotificationtemplates` ',
    IF(@has_old_key > 0, 'DROP INDEX `uq_notification_template`, ', ''),
    'ADD UNIQUE KEY `uq_notification_template_scope` (`team_id`, `service_id`, `subservice_id`, `audience`, `event`, `language`)'
  ),
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_service_idx := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'tblnotificationtemplates'
    AND INDEX_NAME = 'idx_notification_template_service'
);
SET @sql := IF(
  @has_service_idx = 0,
  'ALTER TABLE `tblnotificationtemplates` ADD KEY `idx_notification_template_service` (`service_id`)',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_subservice_idx := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'tblnotificationtemplates'
    AND INDEX_NAME = 'idx_notification_template_subservice'
);
SET @sql := IF(
  @has_subservice_idx = 0,
  'ALTER TABLE `tblnotificationtemplates` ADD KEY `idx_notification_template_subservice` (`subservice_id`)',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
