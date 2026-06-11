-- Migration 003: Rename tblcontacts → tblteams and strip redundant column name prefixes
-- MySQL 5.7: use CHANGE COLUMN (RENAME COLUMN not supported)
-- Also drops the lightweight tblteams stub created in migration 002.

-- Step 1: Rename columns in tblcontacts
ALTER TABLE `tblcontacts`
  CHANGE COLUMN `teamnameen` `nameen` varchar(100) NOT NULL,
  CHANGE COLUMN `teamnamefr` `namefr` varchar(100) NOT NULL,
  CHANGE COLUMN `teamemail`  `email`  varchar(255) NOT NULL;

-- Step 2: Drop lightweight tblteams stub (data lives in tblcontacts)
DROP TABLE IF EXISTS `tblteams`;

-- Step 3: Rename table
RENAME TABLE `tblcontacts` TO `tblteams`;
