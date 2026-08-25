-- Add per-team notification switches and a durable record for notifications skipped by a switch.
-- Missing setting rows are treated as enabled by the application.

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

CREATE TABLE IF NOT EXISTS `tblnotificationsettings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `team_id` int(11) NOT NULL,
  `audience` enum('client','employee') NOT NULL,
  `event` varchar(50) NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `dateupdated` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updatedby` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_notification_setting` (`team_id`, `audience`, `event`),
  KEY `idx_notification_setting_team` (`team_id`),
  KEY `idx_notification_setting_updatedby` (`updatedby`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tblnotificationlog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `triageid` int(11) DEFAULT NULL,
  `team_id` int(11) DEFAULT NULL,
  `audience` varchar(20) NOT NULL,
  `event` varchar(50) NOT NULL,
  `recipient` varchar(255) NOT NULL DEFAULT '',
  `result` varchar(50) NOT NULL,
  `createdby` int(11) DEFAULT NULL,
  `datecreated` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notification_log_triage` (`triageid`),
  KEY `idx_notification_log_team_event` (`team_id`, `event`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `tblnotificationsettings` (`team_id`, `audience`, `event`, `enabled`)
SELECT t.id, combinations.audience, combinations.event, 1
FROM tblteams t
JOIN (
  SELECT 'client' AS audience, 'request_created' AS event
  UNION ALL SELECT 'client', 'resolved'
  UNION ALL SELECT 'employee', 'request_created'
  UNION ALL SELECT 'employee', 'status_changed'
  UNION ALL SELECT 'employee', 'reassigned'
  UNION ALL SELECT 'employee', 'resolved'
) combinations
WHERE t.status = 1;