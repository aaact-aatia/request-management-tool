-- =============================================================================
-- Migration 015: Service "has_other_option" flag
--
-- Adds has_other_option to tblservices so an "Other" option can be injected
-- at the bottom of a subservice dropdown without a dedicated DB row.
-- Idempotent for MySQL 5.7.
-- =============================================================================

SET @has_col := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME   = 'tblservices'
    AND COLUMN_NAME  = 'has_other_option'
);
SET @sql := IF(@has_col = 0,
  'ALTER TABLE `tblservices` ADD COLUMN `has_other_option` TINYINT(1) NOT NULL DEFAULT 0 COMMENT ''Append an Other/Autre option to the subservice dropdown''',
  'SELECT 1'
);
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
