-- Migration 016: Configurable Intake Flows
--
-- Creates the five intake flow tables and adds intake_flow_id to the three
-- existing hierarchy tables.
--
-- DESIGN NOTES
--   - Published flows are immutable. To modify a published flow, clone it
--     into a new draft version (flow_family_key + version_number + 1).
--     The published version remains unchanged; in-progress responses keep
--     referencing it via tblintakeresponses.flow_version_id.
--   - allow_freeform belongs to the answer option, not the question node.
--     One specific option (e.g. "Other") may reveal a labelled free-form
--     field while other options on the same question do not.
--   - Resource links are bilingual. url_fr falls back to url_en when NULL.
--   - Response snapshots capture both EN and FR text so records remain
--     readable after flow versions are edited or archived.
--
-- IDEMPOTENCY
--   All DDL is guarded:
--   - CREATE TABLE IF NOT EXISTS  — skipped when tables already exist.
--   - INFORMATION_SCHEMA guards   — ADD/CHANGE COLUMN and MODIFY COLUMN are
--                                   skipped when the target state already matches.
--   - INDEX guard                 — ADD UNIQUE KEY skipped when it exists.
--   On the first run against an old schema the migration intentionally renames
--   columns (e.g. url → url_en, flow_id → flow_version_id) and backfills legacy
--   flow rows with deterministic family keys (legacy-flow-<id>).  On subsequent
--   runs all guards skip and no data is changed or deleted.
--   No DROP TABLE, DROP COLUMN, TRUNCATE, or reset command appears anywhere in
--   this file.
--
-- No foreign keys are used (consistent with the rest of this project).
-- Where comments say "references" they describe the logical relationship only;
-- no actual SQL FOREIGN KEY constraint is created.

SET NAMES utf8mb4;

-- ===========================================================================
-- Step 1: Create tables when they do not yet exist
-- The full correct schema is specified here for fresh installations.
-- On databases where the tables already exist these statements are skipped.
-- ===========================================================================

CREATE TABLE IF NOT EXISTS `tblintakeflows` (
  `id`                  INT(11)      NOT NULL AUTO_INCREMENT,
  `nameen`              VARCHAR(255) NOT NULL,
  `namefr`              VARCHAR(255) NOT NULL,
  `flow_family_key`     VARCHAR(100) NOT NULL
    COMMENT 'Stable slug shared by all versions of this flow (e.g. website-testing)',
  `version_number`      INT(11)      NOT NULL DEFAULT 1
    COMMENT 'Monotonically increasing integer within a flow_family_key',
  `previous_version_id` INT(11)      DEFAULT NULL
    COMMENT 'References tblintakeflows.id of the flow version this was cloned from',
  `start_node_id`       INT(11)      DEFAULT NULL
    COMMENT 'References tblintakenodes.id; set after first node is created',
  `status`              TINYINT(1)   NOT NULL DEFAULT 0
    COMMENT '0=draft, 1=published, 2=archived',
  `created_at`          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`          TIMESTAMP    NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `updated_by`          INT(11)      DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_family_version` (`flow_family_key`, `version_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tblintakenodes` (
  `id`                  INT(11)      NOT NULL AUTO_INCREMENT,
  `flow_id`             INT(11)      NOT NULL,
  `node_type`           ENUM('question','guidance','destination') NOT NULL,
  `sort_order`          INT(11)      NOT NULL DEFAULT 0,
  `prompt_en`           TEXT         DEFAULT NULL,
  `prompt_fr`           TEXT         DEFAULT NULL,
  `intro_en`            TEXT         DEFAULT NULL
    COMMENT 'Optional introductory paragraph shown above the question (Markdown)',
  `intro_fr`            TEXT         DEFAULT NULL,
  `presentation`        ENUM('radio','select') DEFAULT 'radio',
  `heading_en`          VARCHAR(500) DEFAULT NULL,
  `heading_fr`          VARCHAR(500) DEFAULT NULL,
  `body_en`             TEXT         DEFAULT NULL
    COMMENT 'Markdown body for guidance nodes; rendered via CommonMarkConverter',
  `body_fr`             TEXT         DEFAULT NULL,
  `target_catalogueid`  INT(11)      DEFAULT NULL,
  `target_serviceid`    INT(11)      DEFAULT NULL,
  `target_subserviceid` INT(11)      DEFAULT NULL,
  `outcome_code`        VARCHAR(100) DEFAULT NULL
    COMMENT 'Stable internal code for destination nodes (e.g. first_assessment)',
  `status`              TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_flow_id` (`flow_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tblintakeoptions` (
  `id`                INT(11)      NOT NULL AUTO_INCREMENT,
  `node_id`           INT(11)      NOT NULL,
  `labelen`           VARCHAR(500) NOT NULL,
  `labelfr`           VARCHAR(500) NOT NULL,
  `next_node_id`      INT(11)      DEFAULT NULL
    COMMENT 'NULL permitted only in incomplete drafts; every active option in a published flow must point to another node.',
  `allow_freeform`    TINYINT(1)   NOT NULL DEFAULT 0
    COMMENT '1 = selecting this option reveals a labelled free-form text field',
  `freeform_required` TINYINT(1)   NOT NULL DEFAULT 0
    COMMENT '1 = text field is required when this option is selected',
  `freeform_label_en` VARCHAR(500) DEFAULT NULL
    COMMENT 'Accessible label for the free-form field (English)',
  `freeform_label_fr` VARCHAR(500) DEFAULT NULL
    COMMENT 'Accessible label for the free-form field (French)',
  `sort_order`        INT(11)      NOT NULL DEFAULT 0,
  `status`            TINYINT(1)   NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_node_id` (`node_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tblintakeresources` (
  `id`         INT(11)       NOT NULL AUTO_INCREMENT,
  `node_id`    INT(11)       NOT NULL,
  `titleen`    VARCHAR(500)  NOT NULL,
  `titlefr`    VARCHAR(500)  NOT NULL,
  `url_en`     VARCHAR(1000) NOT NULL
    COMMENT 'English resource URL (https:// or mailto: only)',
  `url_fr`     VARCHAR(1000) DEFAULT NULL
    COMMENT 'French resource URL; falls back to url_en when NULL',
  `sort_order` INT(11)       NOT NULL DEFAULT 0,
  `status`     TINYINT(1)    NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_node_id` (`node_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tblintakeresponses` (
  `id`                 INT(11)     NOT NULL AUTO_INCREMENT,
  `requestid`          VARCHAR(50) NOT NULL
    COMMENT 'tbltriage.requestid',
  `flow_version_id`    INT(11)     NOT NULL
    COMMENT 'References tblintakeflows.id — specific published version used at intake time',
  `node_id`            INT(11)     NOT NULL,
  `option_id`          INT(11)     DEFAULT NULL
    COMMENT 'NULL when the response was free-form only',
  `lang`               CHAR(2)     NOT NULL DEFAULT 'en'
    COMMENT 'Interface language in use at time of submission',
  `prompt_snapshot_en` TEXT        DEFAULT NULL
    COMMENT 'English prompt text captured at time of submission',
  `prompt_snapshot_fr` TEXT        DEFAULT NULL
    COMMENT 'French prompt text captured at time of submission',
  `answer_snapshot_en` TEXT        DEFAULT NULL
    COMMENT 'English answer label captured at time of submission',
  `answer_snapshot_fr` TEXT        DEFAULT NULL
    COMMENT 'French answer label captured at time of submission',
  `freeform_text`      TEXT        DEFAULT NULL
    COMMENT 'User-entered free-form text (not translated)',
  `created_at`         TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_requestid` (`requestid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===========================================================================
-- Step 2: tblintakeflows — add columns missing from the original schema
--
-- SAFE VERSIONING BACKFILL
-- flow_family_key is added as nullable first so existing rows are not forced
-- to the empty string. Legacy rows (null or empty family key) are then
-- assigned a deterministic value  legacy-flow-<id>  that preserves uniqueness
-- without guessing intent. Rows that already have a non-empty family key are
-- never renamed. The column is made NOT NULL only after the backfill, and the
-- unique index is added only after the data is verified clean.
-- ===========================================================================

-- 2a. Add flow_family_key as NULLABLE first (safe for non-empty tables)
SET @has_col := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='tblintakeflows'
  AND COLUMN_NAME='flow_family_key');
SET @sql := IF(@has_col = 0,
  'ALTER TABLE `tblintakeflows` ADD COLUMN `flow_family_key` VARCHAR(100) DEFAULT NULL',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- 2b. Add version_number with a safe DEFAULT so existing rows get 1
SET @has_col := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='tblintakeflows'
  AND COLUMN_NAME='version_number');
SET @sql := IF(@has_col = 0,
  'ALTER TABLE `tblintakeflows` ADD COLUMN `version_number` INT(11) NOT NULL DEFAULT 1',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- 2c. Backfill legacy rows that have no family key.
-- Rows with a non-empty key are untouched. The WHERE clause makes this
-- idempotent: once a row has 'legacy-flow-N', it no longer matches.
UPDATE `tblintakeflows`
  SET `flow_family_key` = CONCAT('legacy-flow-', CAST(`id` AS CHAR))
  WHERE `flow_family_key` IS NULL OR `flow_family_key` = '';

-- 2d. Repair version_number ≪ 0 (negative values or zero caused by data import
--     or manual editing; should not occur with the DEFAULT 1 added in step 2b)
UPDATE `tblintakeflows`
  SET `version_number` = 1
  WHERE `version_number` IS NULL OR `version_number` <= 0;

-- 2e. Make flow_family_key NOT NULL now that every row has a value
SET @col_nullable := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='tblintakeflows'
  AND COLUMN_NAME='flow_family_key' AND IS_NULLABLE='YES');
SET @sql := IF(@col_nullable = 1,
  'ALTER TABLE `tblintakeflows` MODIFY COLUMN `flow_family_key` VARCHAR(100) NOT NULL',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- previous_version_id
SET @has_col := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='tblintakeflows'
  AND COLUMN_NAME='previous_version_id');
SET @sql := IF(@has_col = 0,
  'ALTER TABLE `tblintakeflows` ADD COLUMN `previous_version_id` INT(11) DEFAULT NULL',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- updated_by
SET @has_col := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='tblintakeflows'
  AND COLUMN_NAME='updated_by');
SET @sql := IF(@has_col = 0,
  'ALTER TABLE `tblintakeflows` ADD COLUMN `updated_by` INT(11) DEFAULT NULL',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- UNIQUE KEY uniq_family_version
-- Added last, after the backfill above guarantees no duplicates among
-- legacy-flow-N assignments. If a user has intentionally configured two flows
-- with the same (family_key, version_number), this ALTER will fail with a
-- duplicate-key error -- which is the correct behaviour. Do not silently
-- rename user-configured flow families to resolve conflicts.
SET @has_idx := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='tblintakeflows'
  AND INDEX_NAME='uniq_family_version');
SET @sql := IF(@has_idx = 0,
  'ALTER TABLE `tblintakeflows` ADD UNIQUE KEY `uniq_family_version` (`flow_family_key`, `version_number`)',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- ===========================================================================
-- Step 3: tblintakenodes
--   Add outcome_code (missing from original schema).
--
--   DEPRECATED COLUMN: tblintakenodes.allow_freeform
--   The option-level allow_freeform (tblintakeoptions.allow_freeform) is the
--   supported design. The node-level column is deprecated for upgraded databases
--   and intentionally left in place. Its values must not be automatically moved
--   to an arbitrary option row. A future cleanup migration will remove the
--   column after any option-level data has been reviewed. Clean installations
--   using schema.sql never receive this column.
--   This migration does NOT drop tblintakenodes.allow_freeform.
-- ===========================================================================

SET @has_col := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='tblintakenodes'
  AND COLUMN_NAME='outcome_code');
SET @sql := IF(@has_col = 0,
  'ALTER TABLE `tblintakenodes` ADD COLUMN `outcome_code` VARCHAR(100) DEFAULT NULL',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- ===========================================================================
-- Step 4: tblintakeoptions — add option-level free-form fields
-- ===========================================================================

SET @has_col := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='tblintakeoptions'
  AND COLUMN_NAME='allow_freeform');
SET @sql := IF(@has_col = 0,
  'ALTER TABLE `tblintakeoptions` ADD COLUMN `allow_freeform` TINYINT(1) NOT NULL DEFAULT 0',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @has_col := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='tblintakeoptions'
  AND COLUMN_NAME='freeform_required');
SET @sql := IF(@has_col = 0,
  'ALTER TABLE `tblintakeoptions` ADD COLUMN `freeform_required` TINYINT(1) NOT NULL DEFAULT 0',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @has_col := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='tblintakeoptions'
  AND COLUMN_NAME='freeform_label_en');
SET @sql := IF(@has_col = 0,
  'ALTER TABLE `tblintakeoptions` ADD COLUMN `freeform_label_en` VARCHAR(500) DEFAULT NULL',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @has_col := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='tblintakeoptions'
  AND COLUMN_NAME='freeform_label_fr');
SET @sql := IF(@has_col = 0,
  'ALTER TABLE `tblintakeoptions` ADD COLUMN `freeform_label_fr` VARCHAR(500) DEFAULT NULL',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- ===========================================================================
-- Step 5: tblintakeresources
--   Rename `url` to `url_en` when the old column exists.
--   Add `url_fr` when missing.
-- ===========================================================================

-- Rename url → url_en (only when old column exists and new column does not)
SET @has_old := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='tblintakeresources'
  AND COLUMN_NAME='url');
SET @has_new := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='tblintakeresources'
  AND COLUMN_NAME='url_en');
SET @sql := IF(@has_old = 1 AND @has_new = 0,
  'ALTER TABLE `tblintakeresources` CHANGE COLUMN `url` `url_en` VARCHAR(1000) NOT NULL',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @has_col := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='tblintakeresources'
  AND COLUMN_NAME='url_fr');
SET @sql := IF(@has_col = 0,
  'ALTER TABLE `tblintakeresources` ADD COLUMN `url_fr` VARCHAR(1000) DEFAULT NULL',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- ===========================================================================
-- Step 6: tblintakeresponses
--   Rename flow_id → flow_version_id when the old column exists.
--   Add lang when missing.
--   Rename prompt_snapshot → prompt_snapshot_en when old column exists.
--   Rename answer_snapshot → answer_snapshot_en when old column exists.
--   Add prompt_snapshot_fr and answer_snapshot_fr when missing.
-- ===========================================================================

-- Rename flow_id → flow_version_id
SET @has_old := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='tblintakeresponses'
  AND COLUMN_NAME='flow_id');
SET @has_new := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='tblintakeresponses'
  AND COLUMN_NAME='flow_version_id');
SET @sql := IF(@has_old = 1 AND @has_new = 0,
  'ALTER TABLE `tblintakeresponses` CHANGE COLUMN `flow_id` `flow_version_id` INT(11) NOT NULL',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- Add lang
SET @has_col := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='tblintakeresponses'
  AND COLUMN_NAME='lang');
SET @sql := IF(@has_col = 0,
  CONCAT('ALTER TABLE `tblintakeresponses` ADD COLUMN `lang` CHAR(2) NOT NULL DEFAULT ', CHAR(39), 'en', CHAR(39)),
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- Rename prompt_snapshot → prompt_snapshot_en
SET @has_old := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='tblintakeresponses'
  AND COLUMN_NAME='prompt_snapshot');
SET @has_new := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='tblintakeresponses'
  AND COLUMN_NAME='prompt_snapshot_en');
SET @sql := IF(@has_old = 1 AND @has_new = 0,
  'ALTER TABLE `tblintakeresponses` CHANGE COLUMN `prompt_snapshot` `prompt_snapshot_en` TEXT DEFAULT NULL',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- Rename answer_snapshot → answer_snapshot_en
SET @has_old := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='tblintakeresponses'
  AND COLUMN_NAME='answer_snapshot');
SET @has_new := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='tblintakeresponses'
  AND COLUMN_NAME='answer_snapshot_en');
SET @sql := IF(@has_old = 1 AND @has_new = 0,
  'ALTER TABLE `tblintakeresponses` CHANGE COLUMN `answer_snapshot` `answer_snapshot_en` TEXT DEFAULT NULL',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- Add prompt_snapshot_fr
SET @has_col := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='tblintakeresponses'
  AND COLUMN_NAME='prompt_snapshot_fr');
SET @sql := IF(@has_col = 0,
  'ALTER TABLE `tblintakeresponses` ADD COLUMN `prompt_snapshot_fr` TEXT DEFAULT NULL',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- Add answer_snapshot_fr
SET @has_col := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='tblintakeresponses'
  AND COLUMN_NAME='answer_snapshot_fr');
SET @sql := IF(@has_col = 0,
  'ALTER TABLE `tblintakeresponses` ADD COLUMN `answer_snapshot_fr` TEXT DEFAULT NULL',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- ===========================================================================
-- Steps 7–9: Add intake_flow_id to existing hierarchy tables (idempotent)
-- ===========================================================================

SET @has_col := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='tblcatalogue'
  AND COLUMN_NAME='intake_flow_id');
SET @sql := IF(@has_col = 0,
  'ALTER TABLE `tblcatalogue` ADD COLUMN `intake_flow_id` INT(11) DEFAULT NULL',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @has_col := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='tblservices'
  AND COLUMN_NAME='intake_flow_id');
SET @sql := IF(@has_col = 0,
  'ALTER TABLE `tblservices` ADD COLUMN `intake_flow_id` INT(11) DEFAULT NULL',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @has_col := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='tblsubservices'
  AND COLUMN_NAME='intake_flow_id');
SET @sql := IF(@has_col = 0,
  'ALTER TABLE `tblsubservices` ADD COLUMN `intake_flow_id` INT(11) DEFAULT NULL',
  'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
