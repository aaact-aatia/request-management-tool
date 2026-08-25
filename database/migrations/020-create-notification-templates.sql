-- Create per-team GC Notify message templates (subject + body) for client and employee audiences.
-- team_id = 0 represents the global default template, editable by admin/superadmin only.
-- Content is resolved at send time: team override -> global default (team_id = 0) -> built-in fallback text.

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

CREATE TABLE IF NOT EXISTS `tblnotificationtemplates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `team_id` int(11) NOT NULL DEFAULT 0 COMMENT '0 = global default, matches tblteams.id otherwise',
  `audience` enum('client','employee') NOT NULL,
  `event` varchar(50) NOT NULL,
  `language` enum('en','fr') NOT NULL,
  `subject` varchar(500) NOT NULL DEFAULT '',
  `body` text NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `dateupdated` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updatedby` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_notification_template` (`team_id`, `audience`, `event`, `language`),
  KEY `idx_notification_template_team` (`team_id`),
  KEY `idx_notification_template_updatedby` (`updatedby`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
