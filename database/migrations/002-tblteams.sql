-- Migration 002: Add tblteams
-- Creates the tblteams table and seeds it from existing tblcontacts rows.
-- tblteams.id intentionally mirrors tblcontacts.id so that the existing
-- tblusers.team comma-separated ID values continue to work for request
-- visibility logic without any data changes.
--
-- Safe to run multiple times (IF NOT EXISTS / INSERT IGNORE).

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

CREATE TABLE IF NOT EXISTS `tblteams` (
  `id` int(11) NOT NULL COMMENT 'Matches tblcontacts.id for request/team permission compatibility',
  `nameen` varchar(100) NOT NULL,
  `namefr` varchar(100) NOT NULL,
  `status` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed from existing active contacts so no team IDs go missing
INSERT IGNORE INTO `tblteams` (`id`, `nameen`, `namefr`, `status`)
SELECT `id`, `teamnameen`, `teamnamefr`, `status`
FROM `tblcontacts`
WHERE `status` = 1;
