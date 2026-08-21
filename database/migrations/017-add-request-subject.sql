-- Add configurable request-subject wording to the catalogue hierarchy and store the submitted value.

SET @schema_name = DATABASE();

SET @sql = IF(
  EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'tblcatalogue' AND COLUMN_NAME = 'request_subject_type'),
  'SELECT 1',
  'ALTER TABLE `tblcatalogue` ADD COLUMN `request_subject_type` ENUM(''system'', ''document'', ''subject'') DEFAULT NULL AFTER `contactid`'
);
PREPARE statement FROM @sql;
EXECUTE statement;
DEALLOCATE PREPARE statement;

SET @sql = IF(
  EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'tblservices' AND COLUMN_NAME = 'request_subject_type'),
  'SELECT 1',
  'ALTER TABLE `tblservices` ADD COLUMN `request_subject_type` ENUM(''system'', ''document'', ''subject'') DEFAULT NULL AFTER `contactid`'
);
PREPARE statement FROM @sql;
EXECUTE statement;
DEALLOCATE PREPARE statement;

SET @sql = IF(
  EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'tblsubservices' AND COLUMN_NAME = 'request_subject_type'),
  'SELECT 1',
  'ALTER TABLE `tblsubservices` ADD COLUMN `request_subject_type` ENUM(''system'', ''document'', ''subject'') DEFAULT NULL AFTER `contactid`'
);
PREPARE statement FROM @sql;
EXECUTE statement;
DEALLOCATE PREPARE statement;

SET @sql = IF(
  EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'tbltriage' AND COLUMN_NAME = 'request_subject'),
  'SELECT 1',
  'ALTER TABLE `tbltriage` ADD COLUMN `request_subject` VARCHAR(500) DEFAULT NULL AFTER `title`'
);
PREPARE statement FROM @sql;
EXECUTE statement;
DEALLOCATE PREPARE statement;