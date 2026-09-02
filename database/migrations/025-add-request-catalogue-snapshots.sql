-- Preserve bilingual catalogue labels on requests when catalogue rows are deleted.

SET @schema_name = DATABASE();

SET @sql = IF(
  EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'tbltriage' AND COLUMN_NAME = 'cataloguenameen'
  ),
  'SELECT 1',
  'ALTER TABLE `tbltriage` ADD COLUMN `cataloguenameen` varchar(255) DEFAULT NULL AFTER `catalogueid`'
);
PREPARE statement FROM @sql;
EXECUTE statement;
DEALLOCATE PREPARE statement;

SET @sql = IF(
  EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'tbltriage' AND COLUMN_NAME = 'cataloguenamefr'
  ),
  'SELECT 1',
  'ALTER TABLE `tbltriage` ADD COLUMN `cataloguenamefr` varchar(255) DEFAULT NULL AFTER `cataloguenameen`'
);
PREPARE statement FROM @sql;
EXECUTE statement;
DEALLOCATE PREPARE statement;

SET @sql = IF(
  EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'tbltriage' AND COLUMN_NAME = 'servicenameen'
  ),
  'SELECT 1',
  'ALTER TABLE `tbltriage` ADD COLUMN `servicenameen` varchar(255) DEFAULT NULL AFTER `serviceid`'
);
PREPARE statement FROM @sql;
EXECUTE statement;
DEALLOCATE PREPARE statement;

SET @sql = IF(
  EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'tbltriage' AND COLUMN_NAME = 'servicenamefr'
  ),
  'SELECT 1',
  'ALTER TABLE `tbltriage` ADD COLUMN `servicenamefr` varchar(255) DEFAULT NULL AFTER `servicenameen`'
);
PREPARE statement FROM @sql;
EXECUTE statement;
DEALLOCATE PREPARE statement;

SET @sql = IF(
  EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'tbltriage' AND COLUMN_NAME = 'subservicenameen'
  ),
  'SELECT 1',
  'ALTER TABLE `tbltriage` ADD COLUMN `subservicenameen` varchar(255) DEFAULT NULL AFTER `subserviceid`'
);
PREPARE statement FROM @sql;
EXECUTE statement;
DEALLOCATE PREPARE statement;

SET @sql = IF(
  EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'tbltriage' AND COLUMN_NAME = 'subservicenamefr'
  ),
  'SELECT 1',
  'ALTER TABLE `tbltriage` ADD COLUMN `subservicenamefr` varchar(255) DEFAULT NULL AFTER `subservicenameen`'
);
PREPARE statement FROM @sql;
EXECUTE statement;
DEALLOCATE PREPARE statement;

UPDATE tbltriage t
LEFT JOIN tblcatalogue c ON c.id = t.catalogueid
LEFT JOIN tblservices s ON s.id = t.serviceid
LEFT JOIN tblsubservices ss ON ss.id = t.subserviceid
SET
  t.cataloguenameen = COALESCE(t.cataloguenameen, c.nameen),
  t.cataloguenamefr = COALESCE(t.cataloguenamefr, c.namefr),
  t.servicenameen = COALESCE(t.servicenameen, s.nameen),
  t.servicenamefr = COALESCE(t.servicenamefr, s.namefr),
  t.subservicenameen = COALESCE(t.subservicenameen, ss.nameen),
  t.subservicenamefr = COALESCE(t.subservicenamefr, ss.namefr);