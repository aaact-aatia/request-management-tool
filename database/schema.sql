-- RMT database schema.
-- Catalogue, service, and subservice relationships drive the public intake.

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
  `request_subject_type` enum('system','document','subject') DEFAULT NULL,
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
  `request_subject_type` enum('system','document','subject') DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `catalogueid` (`catalogueid`),
  CONSTRAINT `fk_tblservices_catalogue`
    FOREIGN KEY (`catalogueid`) REFERENCES `tblcatalogue` (`id`)
    ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tblsubservices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `serviceid` int(11) NOT NULL,
  `nameen` varchar(255) NOT NULL,
  `namefr` varchar(255) NOT NULL,
  `sds` int(11) DEFAULT NULL COMMENT 'Service Delivery Standard in business days',
  `contactid` int(11) DEFAULT NULL,
  `request_subject_type` enum('system','document','subject') DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `serviceid` (`serviceid`),
  CONSTRAINT `fk_tblsubservices_service`
    FOREIGN KEY (`serviceid`) REFERENCES `tblservices` (`id`)
    ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tblorganizations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nameen` varchar(255) NOT NULL,
  `namefr` varchar(255) NOT NULL,
  `abbreviationen` varchar(50) DEFAULT NULL,
  `abbreviationfr` varchar(50) DEFAULT NULL,
  `source_part` tinyint(1) NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tblorganizations_nameen` (`nameen`),
  UNIQUE KEY `uq_tblorganizations_namefr` (`namefr`),
  KEY `idx_tblorganizations_status_nameen` (`status`, `nameen`),
  KEY `idx_tblorganizations_status_namefr` (`status`, `namefr`)
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
  `request_subject` varchar(500) DEFAULT NULL,
  `additionalinfo` text DEFAULT NULL,
  `clientlname` varchar(100) DEFAULT NULL,
  `clientfname` varchar(100) DEFAULT NULL,
  `clientemail` varchar(255) DEFAULT NULL,
  `clientphone` varchar(50) DEFAULT NULL,
  `requestlang` varchar(2) NOT NULL DEFAULT 'en',
  `datereceived` date DEFAULT NULL,
  `dateupdated` date DEFAULT NULL,
  `daterequired` date DEFAULT NULL,
  `dateresolved` date DEFAULT NULL,
  `slatimer` date DEFAULT NULL,
  `statusid` int(11) DEFAULT NULL,
  `bdm` varchar(100) DEFAULT NULL,
  `catalogueid` int(11) DEFAULT NULL,
  `cataloguenameen` varchar(255) DEFAULT NULL,
  `cataloguenamefr` varchar(255) DEFAULT NULL,
  `serviceid` int(11) DEFAULT NULL,
  `servicenameen` varchar(255) DEFAULT NULL,
  `servicenamefr` varchar(255) DEFAULT NULL,
  `subserviceid` int(11) DEFAULT NULL,
  `subservicenameen` varchar(255) DEFAULT NULL,
  `subservicenamefr` varchar(255) DEFAULT NULL,
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
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_tblcss_requestid` (`requestid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tblnotificationtemplates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `team_id` int(11) NOT NULL DEFAULT 0 COMMENT '0 = global default, matches tblteams.id otherwise',
  `service_id` int(11) NOT NULL DEFAULT 0 COMMENT '0 = not scoped to a specific service',
  `subservice_id` int(11) NOT NULL DEFAULT 0 COMMENT '0 = not scoped to a specific subservice',
  `audience` enum('client','employee') NOT NULL,
  `event` varchar(50) NOT NULL,
  `language` enum('en','fr') NOT NULL,
  `subject` varchar(500) NOT NULL DEFAULT '',
  `body` text NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `dateupdated` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updatedby` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_notification_template_scope` (`team_id`, `service_id`, `subservice_id`, `audience`, `event`, `language`),
  KEY `idx_notification_template_team` (`team_id`),
  KEY `idx_notification_template_service` (`service_id`),
  KEY `idx_notification_template_subservice` (`subservice_id`),
  KEY `idx_notification_template_updatedby` (`updatedby`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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

CREATE TABLE IF NOT EXISTS `StatusHistory` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `requestID` varchar(50) NOT NULL,
  `previousStatusID` int(11) DEFAULT NULL,
  `statusID` int(11) NOT NULL,
  `actorUserID` int(11) DEFAULT NULL,
  `changeType` varchar(50) DEFAULT NULL,
  `previousWorkerID` int(11) DEFAULT NULL,
  `newWorkerID` int(11) DEFAULT NULL,
  `changeTimeStamp` varchar(50) DEFAULT NULL,
  `slaClockStartDate` date DEFAULT NULL,
  `slaDueDate` date DEFAULT NULL,
  `slaElapsedBusinessDays` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `RequestFieldHistory` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `requestID` varchar(50) NOT NULL,
  `fieldName` varchar(100) NOT NULL,
  `oldValue` text,
  `newValue` text,
  `actorUserID` int(11) DEFAULT NULL,
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
  `uploadedby` int(11) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_tblfiles_uploadedby` (`uploadedby`)
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

