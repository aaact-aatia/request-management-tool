-- Add hierarchy relationship fields for manager and team lead assignments (idempotent for MySQL 5.7).

SET @has_manager_id := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'tblusers'
    AND COLUMN_NAME = 'manager_id'
);

SET @ddl := IF(
  @has_manager_id = 0,
  'ALTER TABLE `tblusers` ADD COLUMN `manager_id` INT(11) DEFAULT NULL AFTER `atype`',
  'SELECT 1'
);

PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_manager_idx := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'tblusers'
    AND INDEX_NAME = 'manager_id'
);

SET @ddl := IF(
  @has_manager_idx = 0,
  'ALTER TABLE `tblusers` ADD KEY `manager_id` (`manager_id`)',
  'SELECT 1'
);

PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_team_lead_user_id := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'tblteams'
    AND COLUMN_NAME = 'team_lead_user_id'
);

SET @ddl := IF(
  @has_team_lead_user_id = 0,
  'ALTER TABLE `tblteams` ADD COLUMN `team_lead_user_id` INT(11) DEFAULT NULL AFTER `escalationcontactemail`',
  'SELECT 1'
);

PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_team_lead_idx := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'tblteams'
    AND INDEX_NAME = 'team_lead_user_id'
);

SET @ddl := IF(
  @has_team_lead_idx = 0,
  'ALTER TABLE `tblteams` ADD KEY `team_lead_user_id` (`team_lead_user_id`)',
  'SELECT 1'
);

PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;