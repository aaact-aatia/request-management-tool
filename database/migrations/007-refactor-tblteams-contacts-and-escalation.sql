-- Refactor tblteams to use team_lead_user_id only for contact.
-- Remove contactname, contactemail, escalationcontactname, escalationcontactemail columns.
-- Managers are assigned via tblusers.team field (atype=3 users).
-- Idempotent for MySQL 5.7.

-- Remove contactname column if it exists
SET @has_contactname := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'tblteams'
    AND COLUMN_NAME = 'contactname'
);

SET @ddl := IF(
  @has_contactname > 0,
  'ALTER TABLE `tblteams` DROP COLUMN `contactname`',
  'SELECT 1'
);

PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Remove contactemail column if it exists
SET @has_contactemail := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'tblteams'
    AND COLUMN_NAME = 'contactemail'
);

SET @ddl := IF(
  @has_contactemail > 0,
  'ALTER TABLE `tblteams` DROP COLUMN `contactemail`',
  'SELECT 1'
);

PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Remove escalationcontactname column if it exists
SET @has_escalationcontactname := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'tblteams'
    AND COLUMN_NAME = 'escalationcontactname'
);

SET @ddl := IF(
  @has_escalationcontactname > 0,
  'ALTER TABLE `tblteams` DROP COLUMN `escalationcontactname`',
  'SELECT 1'
);

PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Remove escalationcontactemail column if it exists
SET @has_escalationcontactemail := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'tblteams'
    AND COLUMN_NAME = 'escalationcontactemail'
);

SET @ddl := IF(
  @has_escalationcontactemail > 0,
  'ALTER TABLE `tblteams` DROP COLUMN `escalationcontactemail`',
  'SELECT 1'
);

PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Remove manager_user_id column if it exists (not needed - managers assigned via tblusers.team)
SET @has_manager_user_id := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'tblteams'
    AND COLUMN_NAME = 'manager_user_id'
);

SET @ddl := IF(
  @has_manager_user_id > 0,
  'ALTER TABLE `tblteams` DROP COLUMN `manager_user_id`',
  'SELECT 1'
);

PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
