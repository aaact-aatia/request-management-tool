-- Add assignment change fields to StatusHistory (idempotent for MySQL 5.7).

SET @has_change_type := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'StatusHistory'
    AND COLUMN_NAME = 'changeType'
);

SET @ddl_change_type := IF(
  @has_change_type = 0,
  'ALTER TABLE `StatusHistory` ADD COLUMN `changeType` VARCHAR(50) DEFAULT NULL AFTER `actorUserID`',
  'SELECT 1'
);

PREPARE stmt FROM @ddl_change_type;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_previous_worker := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'StatusHistory'
    AND COLUMN_NAME = 'previousWorkerID'
);

SET @ddl_previous_worker := IF(
  @has_previous_worker = 0,
  'ALTER TABLE `StatusHistory` ADD COLUMN `previousWorkerID` INT(11) DEFAULT NULL AFTER `changeType`',
  'SELECT 1'
);

PREPARE stmt FROM @ddl_previous_worker;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_new_worker := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'StatusHistory'
    AND COLUMN_NAME = 'newWorkerID'
);

SET @ddl_new_worker := IF(
  @has_new_worker = 0,
  'ALTER TABLE `StatusHistory` ADD COLUMN `newWorkerID` INT(11) DEFAULT NULL AFTER `previousWorkerID`',
  'SELECT 1'
);

PREPARE stmt FROM @ddl_new_worker;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
