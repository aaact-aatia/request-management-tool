-- Add catalogue-level contact ownership so team responsibility is defined at first-tier catalogue.
-- Idempotent for MySQL 5.7.

SET @has_contactid := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'tblcatalogue'
    AND COLUMN_NAME = 'contactid'
);

SET @ddl := IF(
  @has_contactid = 0,
  'ALTER TABLE `tblcatalogue` ADD COLUMN `contactid` INT(11) DEFAULT 1 AFTER `namefr`',
  'SELECT 1'
);

PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Backfill from first available service contact for each catalogue when present.
UPDATE `tblcatalogue` c
LEFT JOIN (
  SELECT s.catalogueid, MIN(s.id) AS first_service_id
  FROM tblservices s
  WHERE s.contactid IS NOT NULL AND s.contactid <> 0
  GROUP BY s.catalogueid
) pick ON pick.catalogueid = c.id
LEFT JOIN tblservices s1 ON s1.id = pick.first_service_id
SET c.contactid = s1.contactid
WHERE (c.contactid IS NULL OR c.contactid = 0)
  AND s1.contactid IS NOT NULL
  AND s1.contactid <> 0;

-- Ensure every catalogue has an owning team (default AAACT team id 1).
UPDATE `tblcatalogue`
SET `contactid` = 1
WHERE `contactid` IS NULL OR `contactid` = 0;
