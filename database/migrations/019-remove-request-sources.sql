-- Remove the retired request source field, lookup data, and audit history.

SET @schema_name = DATABASE();

SET @has_request_source_history := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.TABLES
  WHERE TABLE_SCHEMA = @schema_name
    AND TABLE_NAME = 'RequestFieldHistory'
);
SET @delete_request_source_history_sql := IF(
  @has_request_source_history > 0,
  'DELETE FROM `RequestFieldHistory` WHERE `fieldName` = ''request_source''',
  'SELECT 1'
);
PREPARE delete_request_source_history_stmt FROM @delete_request_source_history_sql;
EXECUTE delete_request_source_history_stmt;
DEALLOCATE PREPARE delete_request_source_history_stmt;

SET @has_sourceid_column := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @schema_name
    AND TABLE_NAME = 'tbltriage'
    AND COLUMN_NAME = 'sourceid'
);
SET @drop_sourceid_column_sql := IF(
  @has_sourceid_column > 0,
  'ALTER TABLE `tbltriage` DROP COLUMN `sourceid`',
  'SELECT 1'
);
PREPARE drop_sourceid_column_stmt FROM @drop_sourceid_column_sql;
EXECUTE drop_sourceid_column_stmt;
DEALLOCATE PREPARE drop_sourceid_column_stmt;

SET @has_sources_table := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.TABLES
  WHERE TABLE_SCHEMA = @schema_name
    AND TABLE_NAME = 'tblsources'
);
SET @drop_sources_table_sql := IF(
  @has_sources_table > 0,
  'DROP TABLE `tblsources`',
  'SELECT 1'
);
PREPARE drop_sources_table_stmt FROM @drop_sources_table_sql;
EXECUTE drop_sources_table_stmt;
DEALLOCATE PREPARE drop_sources_table_stmt;