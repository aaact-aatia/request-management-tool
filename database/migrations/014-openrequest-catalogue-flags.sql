-- =============================================================================
-- Migration 014: DB-Driven Open Request — Catalogue Behavioural Flags
--
-- Adds routing/behaviour columns to tblcatalogue, tblservices, tblsubservices
-- and populates them with data previously hardcoded in the PHP AJAX files.
-- Enables fully database-driven open-request cascade dropdowns.
--
-- Idempotent for MySQL 5.7 (uses INFORMATION_SCHEMA guards).
-- =============================================================================

SET NAMES utf8mb4;

-- =============================================================================
-- Step 1: Add columns to tblcatalogue
-- Guard: show_in_openrequest (sentinel for this migration block)
-- =============================================================================
SET @has_col := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME   = 'tblcatalogue'
    AND COLUMN_NAME  = 'show_in_openrequest'
);
SET @sql := IF(@has_col = 0,
  CONCAT('ALTER TABLE `tblcatalogue`',
    ' ADD COLUMN `show_in_openrequest` TINYINT(1) NOT NULL DEFAULT 0',
      ' COMMENT ''Show in open request service-type dropdown'',',
    ' ADD COLUMN `openrequest_order` INT NOT NULL DEFAULT 99',
      ' COMMENT ''Display order (lower = first)'',',
    ' ADD COLUMN `is_guidance_only` TINYINT(1) NOT NULL DEFAULT 0',
      ' COMMENT ''Show guidance panel instead of request form'',',
    ' ADD COLUMN `guidance_text_en` TEXT DEFAULT NULL',

    ' ADD COLUMN `guidance_text_fr` TEXT DEFAULT NULL,',
    ' ADD COLUMN `guidance_url_en` VARCHAR(500) DEFAULT NULL,',
    ' ADD COLUMN `guidance_url_fr` VARCHAR(500) DEFAULT NULL'
    -- NOTE: an earlier version of this migration also added `requires_ssc_check`
    -- here. That column is dormant and unused. Databases that applied the original
    -- version retain it as an unused column; clean installations via schema.sql do
    -- not include it. The column has been removed from this statement so that any
    -- future partial re-run does not re-add it.
  ),
  'SELECT 1'
);
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- =============================================================================
-- Step 2: Add columns to tblservices
-- Guard: is_guidance_only
-- =============================================================================
SET @has_col := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME   = 'tblservices'
    AND COLUMN_NAME  = 'is_guidance_only'
);
SET @sql := IF(@has_col = 0,
  CONCAT('ALTER TABLE `tblservices`',
    ' ADD COLUMN `is_guidance_only` TINYINT(1) NOT NULL DEFAULT 0,',
    ' ADD COLUMN `guidance_text_en` TEXT DEFAULT NULL,',
    ' ADD COLUMN `guidance_text_fr` TEXT DEFAULT NULL,',
    ' ADD COLUMN `guidance_url_en` VARCHAR(500) DEFAULT NULL,',
    ' ADD COLUMN `guidance_url_fr` VARCHAR(500) DEFAULT NULL,',
    ' ADD COLUMN `alert_text_en` TEXT DEFAULT NULL',
      ' COMMENT ''Informational panel shown above form (does not block submission)'',',
    ' ADD COLUMN `alert_text_fr` TEXT DEFAULT NULL,',
    ' ADD COLUMN `needs_checklist` TINYINT(1) NOT NULL DEFAULT 0',
      ' COMMENT ''Show checklist yes/no gate (for services with no subservices)'',',
    ' ADD COLUMN `checklist_name_en` VARCHAR(255) DEFAULT NULL,',
    ' ADD COLUMN `checklist_name_fr` VARCHAR(255) DEFAULT NULL,',
    ' ADD COLUMN `checklist_url_en` VARCHAR(500) DEFAULT NULL,',
    ' ADD COLUMN `checklist_url_fr` VARCHAR(500) DEFAULT NULL'
  ),
  'SELECT 1'
);
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- =============================================================================
-- Step 3: Add columns to tblsubservices
-- Guard: is_guidance_only
-- =============================================================================
SET @has_col := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME   = 'tblsubservices'
    AND COLUMN_NAME  = 'is_guidance_only'
);
SET @sql := IF(@has_col = 0,
  CONCAT('ALTER TABLE `tblsubservices`',
    ' ADD COLUMN `is_guidance_only` TINYINT(1) NOT NULL DEFAULT 0,',
    ' ADD COLUMN `guidance_text_en` TEXT DEFAULT NULL,',
    ' ADD COLUMN `guidance_text_fr` TEXT DEFAULT NULL,',
    ' ADD COLUMN `guidance_url_en` VARCHAR(500) DEFAULT NULL,',
    ' ADD COLUMN `guidance_url_fr` VARCHAR(500) DEFAULT NULL,',
    ' ADD COLUMN `alert_text_en` TEXT DEFAULT NULL,',
    ' ADD COLUMN `alert_text_fr` TEXT DEFAULT NULL,',
    ' ADD COLUMN `needs_checklist` TINYINT(1) NOT NULL DEFAULT 0,',
    ' ADD COLUMN `checklist_name_en` VARCHAR(255) DEFAULT NULL,',
    ' ADD COLUMN `checklist_name_fr` VARCHAR(255) DEFAULT NULL,',
    ' ADD COLUMN `checklist_url_en` VARCHAR(500) DEFAULT NULL,',
    ' ADD COLUMN `checklist_url_fr` VARCHAR(500) DEFAULT NULL,',
    ' ADD COLUMN `needs_sprint_fields` TINYINT(1) NOT NULL DEFAULT 0',
      ' COMMENT ''Show sprint date fields in the request form'''
  ),
  'SELECT 1'
);
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- =============================================================================
-- Step 4: Configure currently visible catalogues for open request
-- =============================================================================
-- Advice and recommendations
UPDATE `tblcatalogue` SET `show_in_openrequest` = 1, `openrequest_order` = 1 WHERE `id` = 3;

-- Accessibility audit
UPDATE `tblcatalogue` SET `show_in_openrequest` = 1, `openrequest_order` = 2 WHERE `id` = 8;

-- Document accessibility audits (store guidance text for non-SSC external users)
UPDATE `tblcatalogue`
SET
  `show_in_openrequest` = 1,
  `openrequest_order`   = 3,
  `guidance_text_en`    = '<p>This option is guidance-only for external (non-SSC) organizations. Please contact your communications branch for document accessibility support.</p><ul><li><a href="https://a11y.canada.ca/en/create-document/" target="_blank" rel="noopener noreferrer">Digital Accessibility Toolkit – Create document (opens in a new tab)</a></li><li><a href="https://www.csps-efpc.gc.ca/video/making-documents-accessible-eng.aspx" target="_blank" rel="noopener noreferrer">CSPS – Making Documents Accessible (opens in a new tab)</a></li><li><a href="mailto:AAACT-AATIA@ssc-spc.gc.ca">AAACT-AATIA@ssc-spc.gc.ca</a></li></ul>',
  `guidance_text_fr`    = '<p>Cette option est actuellement informative seulement pour les organisations externes (non SPC). Veuillez communiquer avec votre direction des communications pour le soutien en accessibilité documentaire.</p><ul><li><a href="https://a11y.canada.ca/fr/creer-un-document/index.html" target="_blank" rel="noopener noreferrer">Boîte à outils de l\'accessibilité numérique – Créer un document (s\'ouvre dans un nouvel onglet)</a></li><li><a href="https://www.csps-efpc.gc.ca/video/making-documents-accessible-fra.aspx" target="_blank" rel="noopener noreferrer">EFPC – Rendre les documents accessibles (s\'ouvre dans un nouvel onglet)</a></li><li><a href="mailto:AAACT-AATIA@ssc-spc.gc.ca">AAACT-AATIA@ssc-spc.gc.ca</a></li></ul>'
WHERE `id` = 6;

-- =============================================================================
-- Step 5: Add Workshops as a guidance-only catalogue
-- PENDING PRODUCT DECISION: Whether Workshops should remain guidance-only or
-- have sub-services has not been confirmed by the product owner. This step
-- restores the approved state from the request-catalogue branch: guidance-only
-- catalogue with EN/FR informational text. Hierarchy changes (services,
-- subservices under Workshops) must not be made here without explicit approval.
-- =============================================================================
INSERT INTO `tblcatalogue`
  (`nameen`, `namefr`, `contactid`, `survey`, `status`,
   `show_in_openrequest`, `openrequest_order`,
   `is_guidance_only`, `guidance_text_en`, `guidance_text_fr`)
SELECT
  'Workshops and learning sessions',
  'Ateliers et sessions d''apprentissage',
  1, 0, 1,
  1, 0,
  1,
  '<p>This path is guidance-only. Review our learning resources and contact us if you still need assistance.</p><ul><li><a href="https://www.gcpedia.gc.ca/wiki/GC_Accessibility_Training_and_Events_/_Formation_et_%C3%A9v%C3%A9nements_du_GC_sur_l%27accessibilit%C3%A9" target="_blank" rel="noopener noreferrer">GC Accessibility Training and Events (opens in a new tab)</a></li><li><a href="mailto:AAACT-AATIA@ssc-spc.gc.ca">AAACT-AATIA@ssc-spc.gc.ca</a></li></ul>',
  '<p>Ce parcours est informatif seulement. Consultez nos ressources de formation et communiquez avec nous si vous avez encore besoin d''aide.</p><ul><li><a href="https://www.gcpedia.gc.ca/wiki/GC_Accessibility_Training_and_Events_/_Formation_et_%C3%A9v%C3%A9nements_du_GC_sur_l%27accessibilit%C3%A9" target="_blank" rel="noopener noreferrer">Formation et événements du GC sur l''accessibilité (s''ouvre dans un nouvel onglet)</a></li><li><a href="mailto:AAACT-AATIA@ssc-spc.gc.ca">AAACT-AATIA@ssc-spc.gc.ca</a></li></ul>'
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM `tblcatalogue` WHERE `nameen` = 'Workshops and learning sessions'
);

-- =============================================================================
-- Step 6: Activate catalogue 3 services that were previously inactive
-- =============================================================================
-- Planning inclusive events (service ID=7): reactivate with alert text
UPDATE `tblservices`
SET
  `status`        = 1,
  `alert_text_en` = 'Please consult <a href="https://a11y.canada.ca/en/best-practices-for-accessible-virtual-events/">Best practices for accessible virtual events – Digital Accessibility Toolkit</a> before opening a new request – the answer you are seeking is probably there! If not, continue to submit a request.',
  `alert_text_fr` = 'Veuillez consulter les <a href="https://a11y.canada.ca/fr/bonnes-pratiques-pour-les-evenements-virtuels-accessibles/index.html">Bonnes pratiques pour les événements virtuels accessibles – Boîte à outils de l''accessibilité numérique</a> avant d''ouvrir une nouvelle demande. La réponse que vous cherchez s''y trouve probablement! Sinon, continuez pour soumettre une demande.'
WHERE `id` = 7;

-- Adaptive technologies advice (service ID=5): reactivate (just shows Continue, no alert)
UPDATE `tblservices` SET `status` = 1 WHERE `id` = 5;

-- =============================================================================
-- Step 7: Populate alert_text for advice subservices (IDs 104–110)
-- These currently show guidance in ajax3 after subservice selection
-- =============================================================================
UPDATE `tblsubservices` SET
  `alert_text_en` = 'Please consult <a href="https://a11y.canada.ca/en/create-forms/">Create forms – Digital Accessibility Toolkit</a> before opening a new request. If this does not answer your question, continue to submit a request.',
  `alert_text_fr` = 'Veuillez consulter <a href="https://a11y.canada.ca/fr/creer-un-formulaire/index.html">Créer un formulaire – Boîte à outils de l''accessibilité numérique</a> avant d''ouvrir une nouvelle demande. Si cela ne répond pas à votre question, continuez pour soumettre une demande.'
WHERE `id` = 104;

UPDATE `tblsubservices` SET
  `alert_text_en` = 'Please consult <a href="https://a11y.canada.ca/en/design-a-course/">Design a course – Digital Accessibility Toolkit</a> before opening a new request. If this does not answer your question, continue to submit a request.',
  `alert_text_fr` = 'Veuillez consulter <a href="https://a11y.canada.ca/fr/concevoir-un-cours/index.html">Concevoir un cours – Boîte à outils de l''accessibilité numérique</a> avant d''ouvrir une nouvelle demande. Si cela ne répond pas à votre question, continuez pour soumettre une demande.'
WHERE `id` = 105;

UPDATE `tblsubservices` SET
  `alert_text_en` = 'Please consult <a href="https://a11y.canada.ca/en/create-document/">Create document – Digital Accessibility Toolkit</a> before opening a new request. If this does not answer your question, continue to submit a request.',
  `alert_text_fr` = 'Veuillez consulter <a href="https://a11y.canada.ca/fr/creer-un-document/index.html">Créer un document – Boîte à outils de l''accessibilité numérique</a> avant d''ouvrir une nouvelle demande. Si cela ne répond pas à votre question, continuez pour soumettre une demande.'
WHERE `id` = 106;

UPDATE `tblsubservices` SET
  `alert_text_en` = 'Please consult the <a href="https://bati-itao.github.io/learning/esdc-self-paced-web-accessibility-course/index.html">ESDC Self-paced Web Accessibility Course</a> and <a href="https://a11y.canada.ca/en/create-web-content/">Create web content – Digital Accessibility Toolkit</a> before opening a new request. If this does not answer your question, continue to submit a request.',
  `alert_text_fr` = 'Veuillez consulter le <a href="https://bati-itao.github.io/learning/esdc-self-paced-web-accessibility-course/index-fr.html">EDSC – Cours en accessibilité web</a> et <a href="https://a11y.canada.ca/fr/creer-du-contenu-web/index.html">Créer du contenu Web – Boîte à outils de l''accessibilité numérique</a> avant d''ouvrir une nouvelle demande. Si cela ne répond pas à votre question, continuez pour soumettre une demande.'
WHERE `id` = 107;

UPDATE `tblsubservices` SET
  `alert_text_en` = 'Please consult <a href="https://a11y.canada.ca/en/designing-accessible-services/">Designing accessible services – Digital Accessibility Toolkit</a> before opening a new request. If this does not answer your question, continue to submit a request.',
  `alert_text_fr` = 'Veuillez consulter <a href="https://a11y.canada.ca/fr/principes-de-conception-pour-des-services-accessibles/index.html">Principes de conception pour des services accessibles – Boîte à outils de l''accessibilité numérique</a> avant d''ouvrir une nouvelle demande. Si cela ne répond pas à votre question, continuez pour soumettre une demande.'
WHERE `id` = 108;

UPDATE `tblsubservices` SET
  `alert_text_en` = 'Please consult <a href="https://a11y.canada.ca/en/test-your-products/">Test your products – Digital Accessibility Toolkit</a> before opening a new request. If this does not answer your question, continue to submit a request.',
  `alert_text_fr` = 'Veuillez consulter <a href="https://a11y.canada.ca/fr/testez-vos-produits/index.html">Testez vos produits – Boîte à outils de l''accessibilité numérique</a> avant d''ouvrir une nouvelle demande. Si cela ne répond pas à votre question, continuez pour soumettre une demande.'
WHERE `id` = 109;

UPDATE `tblsubservices` SET
  `alert_text_en` = 'Please consult <a href="https://a11y.canada.ca/en/making-accessible-emails/">Making Accessible Emails – Digital Accessibility Toolkit</a> before opening a new request. If this does not answer your question, continue to submit a request.',
  `alert_text_fr` = 'Veuillez consulter <a href="https://a11y.canada.ca/fr/rendre-vos-courriels-accessibles/index.html">Rendre vos courriels accessibles – Boîte à outils de l''accessibilité numérique</a> avant d''ouvrir une nouvelle demande. Si cela ne répond pas à votre question, continuez pour soumettre une demande.'
WHERE `id` = 110;

-- =============================================================================
-- Step 8: Insert Audit / Re-audit subservices for document audit paths
-- Catalogue 6 — services 25=Word, 61=Excel, 62=PowerPoint, 63=Email (MS checklist)
--              service 64=PDF (PDF checklist)
-- =============================================================================
INSERT INTO `tblsubservices`
  (`id`, `serviceid`, `nameen`, `namefr`, `sds`, `status`,
   `needs_checklist`, `checklist_name_en`, `checklist_name_fr`,
   `checklist_url_en`, `checklist_url_fr`)
SELECT 200, 25, 'Audit', 'Vérification', 5, 1,
  1, 'Microsoft document checklist', 'liste de vérification des documents Microsoft',
  'https://bati-itao.github.io/resources/ms-doc-compliance-checklist-en.html',
  'https://bati-itao.github.io/resources/ms-doc-compliance-checklist-fr.html'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `tblsubservices` WHERE `id` = 200);

INSERT INTO `tblsubservices`
  (`id`, `serviceid`, `nameen`, `namefr`, `sds`, `status`,
   `needs_checklist`, `checklist_name_en`, `checklist_name_fr`,
   `checklist_url_en`, `checklist_url_fr`)
SELECT 201, 25, 'Re-audit', 'Vérification de suivi', 5, 1,
  1, 'corrected all mentioned failures from the previous audit',
  'corrigé tous les échecs mentionnés lors de la vérification précédente', NULL, NULL
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `tblsubservices` WHERE `id` = 201);

INSERT INTO `tblsubservices`
  (`id`, `serviceid`, `nameen`, `namefr`, `sds`, `status`,
   `needs_checklist`, `checklist_name_en`, `checklist_name_fr`,
   `checklist_url_en`, `checklist_url_fr`)
SELECT 202, 61, 'Audit', 'Vérification', 5, 1,
  1, 'Microsoft document checklist', 'liste de vérification des documents Microsoft',
  'https://bati-itao.github.io/resources/ms-doc-compliance-checklist-en.html',
  'https://bati-itao.github.io/resources/ms-doc-compliance-checklist-fr.html'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `tblsubservices` WHERE `id` = 202);

INSERT INTO `tblsubservices`
  (`id`, `serviceid`, `nameen`, `namefr`, `sds`, `status`,
   `needs_checklist`, `checklist_name_en`, `checklist_name_fr`,
   `checklist_url_en`, `checklist_url_fr`)
SELECT 203, 61, 'Re-audit', 'Vérification de suivi', 5, 1,
  1, 'corrected all mentioned failures from the previous audit',
  'corrigé tous les échecs mentionnés lors de la vérification précédente', NULL, NULL
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `tblsubservices` WHERE `id` = 203);

INSERT INTO `tblsubservices`
  (`id`, `serviceid`, `nameen`, `namefr`, `sds`, `status`,
   `needs_checklist`, `checklist_name_en`, `checklist_name_fr`,
   `checklist_url_en`, `checklist_url_fr`)
SELECT 204, 62, 'Audit', 'Vérification', 5, 1,
  1, 'Microsoft document checklist', 'liste de vérification des documents Microsoft',
  'https://bati-itao.github.io/resources/ms-doc-compliance-checklist-en.html',
  'https://bati-itao.github.io/resources/ms-doc-compliance-checklist-fr.html'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `tblsubservices` WHERE `id` = 204);

INSERT INTO `tblsubservices`
  (`id`, `serviceid`, `nameen`, `namefr`, `sds`, `status`,
   `needs_checklist`, `checklist_name_en`, `checklist_name_fr`,
   `checklist_url_en`, `checklist_url_fr`)
SELECT 205, 62, 'Re-audit', 'Vérification de suivi', 5, 1,
  1, 'corrected all mentioned failures from the previous audit',
  'corrigé tous les échecs mentionnés lors de la vérification précédente', NULL, NULL
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `tblsubservices` WHERE `id` = 205);

INSERT INTO `tblsubservices`
  (`id`, `serviceid`, `nameen`, `namefr`, `sds`, `status`,
   `needs_checklist`, `checklist_name_en`, `checklist_name_fr`,
   `checklist_url_en`, `checklist_url_fr`)
SELECT 206, 63, 'Audit', 'Vérification', 5, 1,
  1, 'Microsoft document checklist', 'liste de vérification des documents Microsoft',
  'https://bati-itao.github.io/resources/ms-doc-compliance-checklist-en.html',
  'https://bati-itao.github.io/resources/ms-doc-compliance-checklist-fr.html'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `tblsubservices` WHERE `id` = 206);

INSERT INTO `tblsubservices`
  (`id`, `serviceid`, `nameen`, `namefr`, `sds`, `status`,
   `needs_checklist`, `checklist_name_en`, `checklist_name_fr`,
   `checklist_url_en`, `checklist_url_fr`)
SELECT 207, 63, 'Re-audit', 'Vérification de suivi', 5, 1,
  1, 'corrected all mentioned failures from the previous audit',
  'corrigé tous les échecs mentionnés lors de la vérification précédente', NULL, NULL
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `tblsubservices` WHERE `id` = 207);

-- PDF audit/re-audit (different checklist)
INSERT INTO `tblsubservices`
  (`id`, `serviceid`, `nameen`, `namefr`, `sds`, `status`,
   `needs_checklist`, `checklist_name_en`, `checklist_name_fr`,
   `checklist_url_en`, `checklist_url_fr`)
SELECT 208, 64, 'Audit', 'Vérification', 5, 1,
  1, 'PDF document checklist', 'Liste de vérification de l''accessibilité des documents PDF',
  'https://bati-itao.github.io/resources/pdf-accessibility-checklist-en.html',
  'https://bati-itao.github.io/resources/pdf-accessibility-checklist-fr.html'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `tblsubservices` WHERE `id` = 208);

INSERT INTO `tblsubservices`
  (`id`, `serviceid`, `nameen`, `namefr`, `sds`, `status`,
   `needs_checklist`, `checklist_name_en`, `checklist_name_fr`,
   `checklist_url_en`, `checklist_url_fr`)
SELECT 209, 64, 'Re-audit', 'Vérification de suivi', 5, 1,
  1, 'corrected all mentioned failures from the previous audit',
  'corrigé tous les échecs mentionnés lors de la vérification précédente', NULL, NULL
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `tblsubservices` WHERE `id` = 209);

-- =============================================================================
-- Step 9: Insert Audit / Re-audit subservices for Software audit (service 27)
-- =============================================================================
INSERT INTO `tblsubservices`
  (`id`, `serviceid`, `nameen`, `namefr`, `sds`, `status`,
   `needs_checklist`, `checklist_name_en`, `checklist_name_fr`,
   `checklist_url_en`, `checklist_url_fr`)
SELECT 210, 27, 'Audit', 'Vérification', 10, 1,
  1, 'software accessibility checklist',
  'Liste de contrôle des évaluations de la conformité de l''accessibilité (non Web / logiciel)',
  'https://bati-itao.github.io/resources/accessible-software-en.html',
  'https://bati-itao.github.io/resources/accessible-software-fr.html'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `tblsubservices` WHERE `id` = 210);

INSERT INTO `tblsubservices`
  (`id`, `serviceid`, `nameen`, `namefr`, `sds`, `status`,
   `needs_checklist`, `checklist_name_en`, `checklist_name_fr`,
   `checklist_url_en`, `checklist_url_fr`)
SELECT 211, 27, 'Re-audit', 'Vérification de suivi', 10, 1,
  1, 'corrected all mentioned failures from the previous audit',
  'corrigé tous les échecs mentionnés lors de la vérification précédente', NULL, NULL
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `tblsubservices` WHERE `id` = 211);

-- =============================================================================
-- Step 10: Update existing website audit subservices (95=Sprint, 96=Audit)
--          and add Re-audit for websites (service 28)
-- =============================================================================
-- Sprint spot-check: needs sprint form fields; no checklist gate
UPDATE `tblsubservices`
SET `needs_sprint_fields` = 1
WHERE `id` = 95;

-- Audit of representative sample: needs Easy Checks checklist + sprint fields
UPDATE `tblsubservices`
SET
  `needs_sprint_fields` = 1,
  `needs_checklist`     = 1,
  `checklist_name_en`   = 'Easy Checks for Web Accessibility',
  `checklist_name_fr`   = 'Vérifications faciles pour l''accessibilité web',
  `checklist_url_en`    = 'https://bati-itao.github.io/resources/a11ycheck-en.html',
  `checklist_url_fr`    = 'https://bati-itao.github.io/resources/a11ycheck-fr.html'
WHERE `id` = 96;

-- Re-audit for websites (new row)
INSERT INTO `tblsubservices`
  (`id`, `serviceid`, `nameen`, `namefr`, `sds`, `status`,
   `needs_checklist`, `checklist_name_en`, `checklist_name_fr`,
   `checklist_url_en`, `checklist_url_fr`)
SELECT 212, 28, 'Re-audit', 'Vérification de suivi', 10, 1,
  1, 'corrected all mentioned failures from the previous audit',
  'corrigé tous les échecs mentionnés lors de la vérification précédente', NULL, NULL
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `tblsubservices` WHERE `id` = 212);
