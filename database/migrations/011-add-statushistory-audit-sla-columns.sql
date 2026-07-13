-- Add audit and SLA snapshot fields to StatusHistory (idempotent for MySQL 5.7).

SET @has_previous_status := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'StatusHistory'
    AND COLUMN_NAME = 'previousStatusID'
);

SET @ddl_previous_status := IF(
  @has_previous_status = 0,
  'ALTER TABLE `StatusHistory` ADD COLUMN `previousStatusID` INT(11) DEFAULT NULL AFTER `requestID`',
  'SELECT 1'
);

PREPARE stmt FROM @ddl_previous_status;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_actor_user := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'StatusHistory'
    AND COLUMN_NAME = 'actorUserID'
);

SET @ddl_actor_user := IF(
  @has_actor_user = 0,
  'ALTER TABLE `StatusHistory` ADD COLUMN `actorUserID` INT(11) DEFAULT NULL AFTER `statusID`',
  'SELECT 1'
);

PREPARE stmt FROM @ddl_actor_user;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_sla_clock_start := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'StatusHistory'
    AND COLUMN_NAME = 'slaClockStartDate'
);

SET @ddl_sla_clock_start := IF(
  @has_sla_clock_start = 0,
  'ALTER TABLE `StatusHistory` ADD COLUMN `slaClockStartDate` DATE DEFAULT NULL AFTER `changeTimeStamp`',
  'SELECT 1'
);

PREPARE stmt FROM @ddl_sla_clock_start;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_sla_due := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'StatusHistory'
    AND COLUMN_NAME = 'slaDueDate'
);

SET @ddl_sla_due := IF(
  @has_sla_due = 0,
  'ALTER TABLE `StatusHistory` ADD COLUMN `slaDueDate` DATE DEFAULT NULL AFTER `slaClockStartDate`',
  'SELECT 1'
);

PREPARE stmt FROM @ddl_sla_due;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_sla_elapsed := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'StatusHistory'
    AND COLUMN_NAME = 'slaElapsedBusinessDays'
);

SET @ddl_sla_elapsed := IF(
  @has_sla_elapsed = 0,
  'ALTER TABLE `StatusHistory` ADD COLUMN `slaElapsedBusinessDays` INT(11) DEFAULT NULL AFTER `slaDueDate`',
  'SELECT 1'
);

PREPARE stmt FROM @ddl_sla_elapsed;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
