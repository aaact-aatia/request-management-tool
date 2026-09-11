-- Add the department signature lines to untouched app-wide notification defaults.
-- Guarded by the existing team email placeholder so custom edits are preserved.

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

UPDATE `tblnotificationtemplates`
SET `body` = CONCAT(
  `body`,
  CASE WHEN `language` = 'fr'
    THEN CONCAT(CHAR(10), 'Accessibilité, adaptation et technologie informatique adaptée (AATIA)', CHAR(10), 'Transformation numérique Canada')
    ELSE CONCAT(CHAR(10), 'Accessibility, Accommodation and Adaptive Computer Technology (AAACT)', CHAR(10), 'Digital Transformation Canada')
  END
)
WHERE `team_id` = 0 AND `service_id` = 0 AND `subservice_id` = 0
  AND `status` = 1
  AND `body` LIKE '%{{teamemail}}%'
  AND `body` NOT LIKE '%Digital Transformation Canada%'
  AND `body` NOT LIKE '%Transformation numérique Canada%';
