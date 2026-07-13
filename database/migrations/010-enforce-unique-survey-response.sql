-- Enforce one survey response per request (first response wins).
-- Idempotent for MySQL 5.7.

-- Remove duplicate survey rows while preserving the earliest response per requestid.
DELETE newer
FROM tblcss AS older
JOIN tblcss AS newer
  ON older.requestid = newer.requestid
 AND older.id < newer.id;

-- Add a unique index on requestid when missing.
SET @has_unique_requestid := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'tblcss'
    AND INDEX_NAME = 'uniq_tblcss_requestid'
);

SET @ddl := IF(
  @has_unique_requestid = 0,
  'ALTER TABLE `tblcss` ADD UNIQUE KEY `uniq_tblcss_requestid` (`requestid`)',
  'SELECT 1'
);

PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
