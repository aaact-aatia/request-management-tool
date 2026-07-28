-- RMT Database Seed File
-- Defines the complete post-migration table structure for a fresh installation.
-- Run this file followed by database/reference.sql to get a clean working database.
--
-- Existing databases apply numbered migrations (database/migrations/) instead.
-- This file must stay in sync with the highest-numbered applied migration.

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
  `id`                  int(11)      NOT NULL AUTO_INCREMENT,
  `nameen`              varchar(255) NOT NULL,
  `namefr`              varchar(255) NOT NULL,
  `contactid`           int(11)      DEFAULT 1,
  `survey`              tinyint(1)   DEFAULT 1,
  `status`              tinyint(1)   DEFAULT 1,
  -- open-request routing flags (from migration 014)
  `show_in_openrequest` tinyint(1)   NOT NULL DEFAULT 0
    COMMENT 'Show in open request service-type dropdown',
  `openrequest_order`   int          NOT NULL DEFAULT 99
    COMMENT 'Display order (lower = first)',
  `is_guidance_only`    tinyint(1)   NOT NULL DEFAULT 0
    COMMENT 'Show guidance panel instead of request form',
  `guidance_text_en`    text         DEFAULT NULL,
  `guidance_text_fr`    text         DEFAULT NULL,
  `guidance_url_en`     varchar(500) DEFAULT NULL,
  `guidance_url_fr`     varchar(500) DEFAULT NULL,
  -- NOTE: requires_ssc_check was added in the original migration 014 and
  -- exists as a dormant unused column on databases upgraded from that version.
  -- It is intentionally omitted here for clean installations.
  -- configurable intake flow (from migration 016)
  `intake_flow_id`      int(11)      DEFAULT NULL
    COMMENT 'Custom intake flow; overrides default cascade when set',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tblservices` (
  `id`                int(11)      NOT NULL AUTO_INCREMENT,
  `catalogueid`       int(11)      NOT NULL,
  `nameen`            varchar(255) NOT NULL,
  `namefr`            varchar(255) NOT NULL,
  `sds`               int(11)      DEFAULT NULL COMMENT 'Service Delivery Standard in business days',
  `contactid`         int(11)      DEFAULT NULL,
  `status`            tinyint(1)   DEFAULT 1,
  -- open-request routing flags (from migration 014)
  `is_guidance_only`  tinyint(1)   NOT NULL DEFAULT 0,
  `guidance_text_en`  text         DEFAULT NULL,
  `guidance_text_fr`  text         DEFAULT NULL,
  `guidance_url_en`   varchar(500) DEFAULT NULL,
  `guidance_url_fr`   varchar(500) DEFAULT NULL,
  `alert_text_en`     text         DEFAULT NULL
    COMMENT 'Informational panel shown above form (Markdown; does not block submission)',
  `alert_text_fr`     text         DEFAULT NULL,
  `needs_checklist`   tinyint(1)   NOT NULL DEFAULT 0
    COMMENT 'Show checklist yes/no gate (for services with no subservices)',
  `checklist_name_en` varchar(255) DEFAULT NULL,
  `checklist_name_fr` varchar(255) DEFAULT NULL,
  `checklist_url_en`  varchar(500) DEFAULT NULL,
  `checklist_url_fr`  varchar(500) DEFAULT NULL,
  -- Other option flag (from migration 015)
  `has_other_option`  tinyint(1)   NOT NULL DEFAULT 0
    COMMENT 'Append an Other/Autre option to the subservice dropdown',
  -- configurable intake flow (from migration 016)
  `intake_flow_id`    int(11)      DEFAULT NULL
    COMMENT 'Custom intake flow; overrides default cascade when set',
  PRIMARY KEY (`id`),
  KEY `catalogueid` (`catalogueid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tblsubservices` (
  `id`                  int(11)      NOT NULL AUTO_INCREMENT,
  `serviceid`           int(11)      NOT NULL,
  `nameen`              varchar(255) NOT NULL,
  `namefr`              varchar(255) NOT NULL,
  `sds`                 int(11)      DEFAULT NULL COMMENT 'Service Delivery Standard in business days',
  `contactid`           int(11)      DEFAULT NULL,
  `status`              tinyint(1)   DEFAULT 1,
  -- open-request routing flags (from migration 014)
  `is_guidance_only`    tinyint(1)   NOT NULL DEFAULT 0,
  `guidance_text_en`    text         DEFAULT NULL,
  `guidance_text_fr`    text         DEFAULT NULL,
  `guidance_url_en`     varchar(500) DEFAULT NULL,
  `guidance_url_fr`     varchar(500) DEFAULT NULL,
  `alert_text_en`       text         DEFAULT NULL,
  `alert_text_fr`       text         DEFAULT NULL,
  `needs_checklist`     tinyint(1)   NOT NULL DEFAULT 0,
  `checklist_name_en`   varchar(255) DEFAULT NULL,
  `checklist_name_fr`   varchar(255) DEFAULT NULL,
  `checklist_url_en`    varchar(500) DEFAULT NULL,
  `checklist_url_fr`    varchar(500) DEFAULT NULL,
  `needs_sprint_fields` tinyint(1)   NOT NULL DEFAULT 0
    COMMENT 'Show sprint date fields in the request form (dormant — see docs/sprint-spot-check-fields.md)',
  -- configurable intake flow (from migration 016)
  `intake_flow_id`      int(11)      DEFAULT NULL
    COMMENT 'Custom intake flow; overrides default cascade when set',
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
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_tblcss_requestid` (`requestid`)
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

-- ---------------------------------------------------------------------------
-- Configurable Intake Flows (from migration 016)
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `tblintakeflows` (
  `id`                  int(11)      NOT NULL AUTO_INCREMENT,
  `nameen`              varchar(255) NOT NULL,
  `namefr`              varchar(255) NOT NULL,
  `flow_family_key`     varchar(100) NOT NULL
    COMMENT 'Stable slug shared by all versions of this flow',
  `version_number`      int(11)      NOT NULL DEFAULT 1
    COMMENT 'Monotonically increasing within a flow_family_key',
  `previous_version_id` int(11)      DEFAULT NULL
    COMMENT 'FK to tblintakeflows.id of the flow this was cloned from',
  `start_node_id`       int(11)      DEFAULT NULL
    COMMENT 'FK to tblintakenodes.id; set after first node is created',
  `status`              tinyint(1)   NOT NULL DEFAULT 0
    COMMENT '0=draft, 1=published, 2=archived',
  `created_at`          timestamp    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`          timestamp    NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `updated_by`          int(11)      DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_family_version` (`flow_family_key`, `version_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tblintakenodes` (
  `id`                  int(11)      NOT NULL AUTO_INCREMENT,
  `flow_id`             int(11)      NOT NULL,
  `node_type`           enum('question','guidance','destination') NOT NULL,
  `sort_order`          int(11)      NOT NULL DEFAULT 0,
  `prompt_en`           text         DEFAULT NULL,
  `prompt_fr`           text         DEFAULT NULL,
  `intro_en`            text         DEFAULT NULL,
  `intro_fr`            text         DEFAULT NULL,
  `presentation`        enum('radio','select') DEFAULT 'radio',
  `heading_en`          varchar(500) DEFAULT NULL,
  `heading_fr`          varchar(500) DEFAULT NULL,
  `body_en`             text         DEFAULT NULL,
  `body_fr`             text         DEFAULT NULL,
  `target_catalogueid`  int(11)      DEFAULT NULL,
  `target_serviceid`    int(11)      DEFAULT NULL,
  `target_subserviceid` int(11)      DEFAULT NULL,
  `outcome_code`        varchar(100) DEFAULT NULL
    COMMENT 'Stable internal outcome identifier (e.g. first_assessment)',
  `status`              tinyint(1)   NOT NULL DEFAULT 1,
  `created_at`          timestamp    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_flow_id` (`flow_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tblintakeoptions` (
  `id`                int(11)      NOT NULL AUTO_INCREMENT,
  `node_id`           int(11)      NOT NULL,
  `labelen`           varchar(500) NOT NULL,
  `labelfr`           varchar(500) NOT NULL,
  `next_node_id`      int(11)      DEFAULT NULL
    COMMENT 'NULL permitted only in incomplete drafts. Every branch in a published flow must point to an explicit guidance or destination node.',
  `allow_freeform`    tinyint(1)   NOT NULL DEFAULT 0
    COMMENT '1 = selecting this option reveals a labelled free-form text field',
  `freeform_required` tinyint(1)   NOT NULL DEFAULT 0
    COMMENT '1 = free-form field required when this option is selected',
  `freeform_label_en` varchar(500) DEFAULT NULL,
  `freeform_label_fr` varchar(500) DEFAULT NULL,
  `sort_order`        int(11)      NOT NULL DEFAULT 0,
  `status`            tinyint(1)   NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_node_id` (`node_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tblintakeresources` (
  `id`         int(11)       NOT NULL AUTO_INCREMENT,
  `node_id`    int(11)       NOT NULL,
  `titleen`    varchar(500)  NOT NULL,
  `titlefr`    varchar(500)  NOT NULL,
  `url_en`     varchar(1000) NOT NULL
    COMMENT 'English URL (https:// or mailto: only)',
  `url_fr`     varchar(1000) DEFAULT NULL
    COMMENT 'French URL; falls back to url_en when NULL',
  `sort_order` int(11)       NOT NULL DEFAULT 0,
  `status`     tinyint(1)    NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_node_id` (`node_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tblintakeresponses` (
  `id`                 int(11)     NOT NULL AUTO_INCREMENT,
  `requestid`          varchar(50) NOT NULL COMMENT 'tbltriage.requestid',
  `flow_version_id`    int(11)     NOT NULL
    COMMENT 'FK to tblintakeflows.id — exact published version used at intake time',
  `node_id`            int(11)     NOT NULL,
  `option_id`          int(11)     DEFAULT NULL,
  `lang`               char(2)     NOT NULL DEFAULT 'en'
    COMMENT 'Interface language at time of submission',
  `prompt_snapshot_en` text        DEFAULT NULL,
  `prompt_snapshot_fr` text        DEFAULT NULL,
  `answer_snapshot_en` text        DEFAULT NULL,
  `answer_snapshot_fr` text        DEFAULT NULL,
  `freeform_text`      text        DEFAULT NULL,
  `created_at`         timestamp   NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_requestid` (`requestid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

