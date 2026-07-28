-- Enforce catalogue hierarchy integrity without deleting lookup data.
-- Numeric IDs are not stable row identities and may be reused after a reset.

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

DROP PROCEDURE IF EXISTS rmt_validate_catalogue_hierarchy;
DELIMITER $$
CREATE PROCEDURE rmt_validate_catalogue_hierarchy()
BEGIN
  IF EXISTS (
    SELECT 1
    FROM tblservices s
    LEFT JOIN tblcatalogue c ON c.id = s.catalogueid
    WHERE c.id IS NULL
  ) THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Migration 014 stopped: orphan services require manual review';
  END IF;

  IF EXISTS (
    SELECT 1
    FROM tblsubservices ss
    LEFT JOIN tblservices s ON s.id = ss.serviceid
    WHERE s.id IS NULL
  ) THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Migration 014 stopped: orphan subservices require manual review';
  END IF;
END$$
DELIMITER ;

CALL rmt_validate_catalogue_hierarchy();
DROP PROCEDURE rmt_validate_catalogue_hierarchy;

SET @has_services_fk := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND CONSTRAINT_NAME = 'fk_tblservices_catalogue'
);
SET @services_fk_sql := IF(
  @has_services_fk = 0,
  'ALTER TABLE `tblservices` ADD CONSTRAINT `fk_tblservices_catalogue` FOREIGN KEY (`catalogueid`) REFERENCES `tblcatalogue` (`id`) ON UPDATE RESTRICT ON DELETE RESTRICT',
  'SELECT 1'
);
PREPARE services_fk_stmt FROM @services_fk_sql;
EXECUTE services_fk_stmt;
DEALLOCATE PREPARE services_fk_stmt;

SET @has_subservices_fk := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND CONSTRAINT_NAME = 'fk_tblsubservices_service'
);
SET @subservices_fk_sql := IF(
  @has_subservices_fk = 0,
  'ALTER TABLE `tblsubservices` ADD CONSTRAINT `fk_tblsubservices_service` FOREIGN KEY (`serviceid`) REFERENCES `tblservices` (`id`) ON UPDATE RESTRICT ON DELETE RESTRICT',
  'SELECT 1'
);
PREPARE subservices_fk_stmt FROM @subservices_fk_sql;
EXECUTE subservices_fk_stmt;
DEALLOCATE PREPARE subservices_fk_stmt;

SELECT COUNT(*) AS orphan_services
FROM tblservices s
LEFT JOIN tblcatalogue c ON c.id = s.catalogueid
WHERE c.id IS NULL;

SELECT COUNT(*) AS orphan_subservices
FROM tblsubservices ss
LEFT JOIN tblservices s ON s.id = ss.serviceid
WHERE s.id IS NULL;