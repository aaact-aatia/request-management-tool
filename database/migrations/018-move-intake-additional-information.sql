-- Store intake Additional information as request details instead of a client communication.

SET @schema_name = DATABASE();

SET @sql = IF(
  EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'tbltriage' AND COLUMN_NAME = 'additionalinfo'),
  'SELECT 1',
  'ALTER TABLE `tbltriage` ADD COLUMN `additionalinfo` TEXT DEFAULT NULL AFTER `request_subject`'
);
PREPARE statement FROM @sql;
EXECUTE statement;
DEALLOCATE PREPARE statement;

DROP TEMPORARY TABLE IF EXISTS `rmt_additionalinfo_migration`;
CREATE TEMPORARY TABLE `rmt_additionalinfo_migration` (
  `triageid` INT NOT NULL PRIMARY KEY,
  `commlogid` INT NOT NULL UNIQUE,
  `notes` TEXT NOT NULL
);

INSERT INTO `rmt_additionalinfo_migration` (`triageid`, `commlogid`, `notes`)
SELECT t.id, additional.id, additional.notes
FROM tbltriage t
JOIN tblcommlog department
  ON department.triageid = t.id
 AND department.status = 1
 AND department.notes REGEXP '^(Department/agency|Ministère/organisme):[[:space:]]*'
JOIN tblcommlog additional
  ON additional.id = (
    SELECT MIN(candidate.id)
    FROM tblcommlog candidate
    WHERE candidate.triageid = t.id
      AND candidate.status = 1
      AND candidate.id > department.id
      AND candidate.dateadded = department.dateadded
      AND candidate.creatorid = department.creatorid
  )
WHERE (t.additionalinfo IS NULL OR TRIM(t.additionalinfo) = '')
  AND additional.notes NOT REGEXP '^(Department/agency|Ministère/organisme):[[:space:]]*';

UPDATE tbltriage t
JOIN rmt_additionalinfo_migration migrated ON migrated.triageid = t.id
SET t.additionalinfo = migrated.notes;

DELETE communication
FROM tblcommlog communication
JOIN rmt_additionalinfo_migration migrated ON migrated.commlogid = communication.id;

DROP TEMPORARY TABLE `rmt_additionalinfo_migration`;