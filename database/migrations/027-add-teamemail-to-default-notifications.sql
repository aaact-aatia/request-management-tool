-- Add the responsible team's email address to untouched app-wide notification signatures.
-- The placeholder resolves from tblteams.email for initial notifications.

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

UPDATE `tblnotificationtemplates`
SET `body` = REPLACE(`body`, CONCAT(CHAR(10), '{{teamname}}'), CONCAT(CHAR(10), '{{teamname}}', CHAR(10), '{{teamemail}}'))
WHERE `team_id` = 0 AND `service_id` = 0 AND `subservice_id` = 0
  AND `status` = 1
  AND `body` NOT LIKE '%{{teamemail}}%'
  AND `body` LIKE '%{{teamname}}';
