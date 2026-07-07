-- RMT Database Seed File
-- Creates schema and inserts dummy data for local development
--
-- NOTE: Catalogue/Services dropdowns are HARDCODED in the PHP files (addrequest2-ajax*.php)
-- not database-driven. Those tables exist but aren't used by the dropdown logic.
-- This seed file focuses on tables actually needed for the app to function.

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- Create tables
CREATE TABLE IF NOT EXISTS `tblaccounttype` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nameen` varchar(100) NOT NULL,
  `namefr` varchar(100) NOT NULL,
  `status` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tblusers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `firstname` varchar(100) NOT NULL,
  `lastname` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `atype` int(11) NOT NULL,
  `is_superuser` tinyint(1) DEFAULT 0,
  `is_admin` tinyint(1) DEFAULT 0,
  `manager_id` int(11) DEFAULT NULL,
  `team` varchar(100) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `atype` (`atype`),
  KEY `manager_id` (`manager_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tblcatalogue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nameen` varchar(255) NOT NULL,
  `namefr` varchar(255) NOT NULL,
  `contactid` int(11) DEFAULT 1,
  `survey` tinyint(1) DEFAULT 1,
  `status` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tblservices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `catalogueid` int(11) NOT NULL,
  `nameen` varchar(255) NOT NULL,
  `namefr` varchar(255) NOT NULL,
  `sds` int(11) DEFAULT NULL COMMENT 'Service Delivery Standard in business days',
  `contactid` int(11) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `catalogueid` (`catalogueid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tblsubservices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `serviceid` int(11) NOT NULL,
  `nameen` varchar(255) NOT NULL,
  `namefr` varchar(255) NOT NULL,
  `sds` int(11) DEFAULT NULL COMMENT 'Service Delivery Standard in business days',
  `contactid` int(11) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `serviceid` (`serviceid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tblsources` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nameen` varchar(255) NOT NULL,
  `namefr` varchar(255) NOT NULL,
  `status` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tblstatus` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nameen` varchar(100) NOT NULL,
  `namefr` varchar(100) NOT NULL,
  `is_resolved` tinyint(1) DEFAULT 0,
  `status` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tblproducts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nameen` varchar(255) NOT NULL,
  `namefr` varchar(255) NOT NULL,
  `status` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbltriage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `requestid` varchar(50) DEFAULT NULL,
  `title` varchar(500) DEFAULT NULL,
  `clientlname` varchar(100) DEFAULT NULL,
  `clientfname` varchar(100) DEFAULT NULL,
  `clientemail` varchar(255) DEFAULT NULL,
  `clientphone` varchar(50) DEFAULT NULL,
  `requestlang` varchar(2) NOT NULL DEFAULT 'en',
  `sourceid` int(11) DEFAULT NULL,
  `datereceived` date DEFAULT NULL,
  `dateupdated` date DEFAULT NULL,
  `daterequired` date DEFAULT NULL,
  `dateresolved` date DEFAULT NULL,
  `slatimer` date DEFAULT NULL,
  `statusid` int(11) DEFAULT NULL,
  `bdm` varchar(100) DEFAULT NULL,
  `catalogueid` int(11) DEFAULT NULL,
  `serviceid` int(11) DEFAULT NULL,
  `subserviceid` int(11) DEFAULT NULL,
  `attach1` varchar(255) DEFAULT NULL,
  `attach2` varchar(255) DEFAULT NULL,
  `attach3` varchar(255) DEFAULT NULL,
  `creatorid` int(11) DEFAULT NULL,
  `updaterid` int(11) DEFAULT NULL,
  `workerid` int(11) DEFAULT NULL,
  `closesla` tinyint(1) DEFAULT 0,
  `pastsla` tinyint(1) DEFAULT 0,
  `cssurvey` tinyint(1) DEFAULT 0,
  `project_id` int(11) DEFAULT NULL,
  `audience_id` int(11) DEFAULT NULL,
  `triage_population` int(11) DEFAULT NULL,
  `conformance_id` int(11) DEFAULT NULL,
  `triage_maturity` int(11) DEFAULT NULL,
  `triage_management` int(11) DEFAULT NULL,
  `tech_id` int(11) DEFAULT NULL,
  `priority_score` int(11) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  `isreaudit` tinyint(1) DEFAULT 0,
  `ipaddress` varchar(50) DEFAULT NULL,
  `exactTime` varchar(50) DEFAULT NULL,
  `firstsprintenddate` date DEFAULT NULL,
  `firstsprintstartdate` date DEFAULT NULL,
  `sprintschedule` varchar(255) DEFAULT NULL,
  `sprintdefects` varchar(255) DEFAULT NULL,
  `audienceid` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `catalogueid` (`catalogueid`),
  KEY `serviceid` (`serviceid`),
  KEY `statusid` (`statusid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tblcommlog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `triageid` int(11) NOT NULL,
  `dateadded` date NOT NULL,
  `notes` text,
  `creatorid` int(11) NOT NULL,
  `status` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `triageid` (`triageid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbladminlog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `triageid` int(11) NOT NULL,
  `dateadded` date NOT NULL,
  `notes` text,
  `creatorid` int(11) NOT NULL,
  `status` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `triageid` (`triageid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tblcontacts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `teamnameen` varchar(100) NOT NULL,
  `teamnamefr` varchar(100) NOT NULL,
  `teamemail` varchar(255) NOT NULL,
  `contactname` varchar(200) NOT NULL,
  `contactemail` varchar(255) NOT NULL,
  `escalationcontactname` varchar(200) DEFAULT NULL,
  `escalationcontactemail` varchar(255) DEFAULT NULL,
  `dateupdated` timestamp NULL DEFAULT NULL,
  `updatedby` int(11) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tblteams` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Matches tblcontacts.id for request/team permission compatibility',
  `nameen` varchar(100) NOT NULL,
  `namefr` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `team_lead_user_id` int(11) DEFAULT NULL,
  `dateadded` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `dateupdated` timestamp NULL DEFAULT NULL,
  `updatedby` int(11) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `team_lead_user_id` (`team_lead_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tblcss` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `requestid` varchar(50) NOT NULL,
  `overall` int(11) DEFAULT NULL,
  `response` int(11) DEFAULT NULL,
  `comments` text,
  `status` tinyint(1) DEFAULT 1,
  `dateadded` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `StatusHistory` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `requestID` varchar(50) NOT NULL,
  `statusID` int(11) NOT NULL,
  `changeTimeStamp` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tblfiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `requestid` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(100) NOT NULL,
  `type` varchar(50) DEFAULT NULL,
  `size` int(11) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tblholidays` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `holiday_date` date NOT NULL,
  `name_en` varchar(100) DEFAULT NULL,
  `name_fr` varchar(100) DEFAULT NULL,
  `recurring` tinyint(1) DEFAULT 0 COMMENT '1=annual recurring (same month/day), 0=specific date only',
  `status` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `holiday_date` (`holiday_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

