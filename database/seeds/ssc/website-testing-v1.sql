-- =============================================================================
-- SSC Website-Testing Intake Flow — Version 1
--
-- flow_family_key : ssc.website-testing  |  version_number : 1
--
-- Graph: 9 nodes, 8 options, 2 resources
-- Node sort_orders: Q1=1, Q2=2, Q3=3, Q4=4, DEST_FIRST=5,
--                  GUID_CK1=6, GUID_FIX=7, DEST_REAU=8, GUID_CK2=9
--
-- Expected edges (src_sort, opt_sort, dst_sort):
--   (1,1,2) Q1-Yes->Q2     (1,2,3) Q1-No->Q3
--   (2,1,5) Q2-Yes->DEST1  (2,2,6) Q2-No->GUID_CK1
--   (3,1,7) Q3-No->GUID_FIX (3,2,4) Q3-Yes->Q4
--   (4,1,8) Q4-Yes->DEST2  (4,2,9) Q4-No->GUID_CK2
--
-- Both paths (new flow + existing published) go through the same full
-- graph validation before any success is reported or committed.
--
-- Future changes: create website-testing-v2.sql. Do not modify this file.
-- =============================================================================

SET NAMES utf8mb4;

-- Classification constants
SET @target_catalogueid   = 8;
SET @target_serviceid     = 28;
SET @dest_first_subid     = 96;
SET @dest_reaudit_subid   = 212;

-- Flow identity
SET @flow_family_key      = 'ssc.website-testing';
SET @flow_version         = 1;
SET @flow_name_en         = 'SSC Website Testing Intake';
SET @flow_name_fr         = 'Demande d''évaluation de sites Web de SPC';

-- Outcome codes
SET @outcome_first        = 'first_assessment';
SET @outcome_reassessment = 'reassessment';

-- Easy Checks resource
SET @ck_title_en = 'Easy Checks for Web Accessibility';
SET @ck_title_fr = 'Vérifications faciles pour l''accessibilité Web';
SET @ck_url_en   = 'https://bati-itao.github.io/resources/a11ycheck-en.html';
SET @ck_url_fr   = 'https://bati-itao.github.io/resources/a11ycheck-fr.html';

-- =============================================================================
-- Shared graph-validation procedure.
-- Called by both the new-flow path (draft) and the existing-flow path.
-- SIGNALS on any mismatch; caller's EXIT HANDLER rolls back on SIGNAL.
-- Uses session variables @flow_name_en/fr, @target_*, @outcome_*, @ck_* etc.
-- =============================================================================
DROP PROCEDURE IF EXISTS _rmt_validate_ssc_wt_graph;

DELIMITER $$
CREATE PROCEDURE _rmt_validate_ssc_wt_graph(IN p_flow_id INT, IN p_ctx VARCHAR(30))
validate_proc: BEGIN

  DECLARE v_count INT DEFAULT 0;
  DECLARE v_msg   VARCHAR(500) DEFAULT '';

  -- helper: build a prefixed error message
  -- (SIGNAL SET MESSAGE_TEXT accepts a variable in MySQL 5.7)

  -- ----------------------------------------------------------------
  -- 1. Flow names EN and FR
  -- ----------------------------------------------------------------
  SET v_count = (SELECT COUNT(*) FROM tblintakeflows
                 WHERE id=p_flow_id AND nameen=@flow_name_en AND namefr=@flow_name_fr);
  IF v_count != 1 THEN
    SET v_msg = CONCAT('VALIDATION FAILED (', p_ctx, '): Flow names do not match expected EN/FR values.');
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_msg;
  END IF;

  -- ----------------------------------------------------------------
  -- 2. Start node: active question at sort_order=1
  -- ----------------------------------------------------------------
  SET v_count = (
    SELECT COUNT(*) FROM tblintakeflows f
    JOIN tblintakenodes n ON f.start_node_id=n.id
    WHERE f.id=p_flow_id AND n.flow_id=p_flow_id
      AND n.status=1 AND n.node_type='question' AND n.sort_order=1
  );
  IF v_count != 1 THEN
    SET v_msg = CONCAT('VALIDATION FAILED (', p_ctx, '): start_node_id must point to the active question at sort_order=1.');
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_msg;
  END IF;

  -- ----------------------------------------------------------------
  -- 3. Exactly 9 active nodes: 4 questions, 3 guidance, 2 destinations
  -- ----------------------------------------------------------------
  SET v_count = (SELECT COUNT(*) FROM tblintakenodes WHERE flow_id=p_flow_id AND status=1);
  IF v_count != 9 THEN
    SET v_msg = CONCAT('VALIDATION FAILED (', p_ctx, '): Expected 9 active nodes, found ', v_count, '.');
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_msg;
  END IF;

  SET v_count = (SELECT COUNT(*) FROM tblintakenodes WHERE flow_id=p_flow_id AND status=1 AND node_type='question');
  IF v_count != 4 THEN
    SET v_msg = CONCAT('VALIDATION FAILED (', p_ctx, '): Expected 4 active question nodes, found ', v_count, '.');
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_msg;
  END IF;

  SET v_count = (SELECT COUNT(*) FROM tblintakenodes WHERE flow_id=p_flow_id AND status=1 AND node_type='guidance');
  IF v_count != 3 THEN
    SET v_msg = CONCAT('VALIDATION FAILED (', p_ctx, '): Expected 3 active guidance nodes, found ', v_count, '.');
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_msg;
  END IF;

  SET v_count = (SELECT COUNT(*) FROM tblintakenodes WHERE flow_id=p_flow_id AND status=1 AND node_type='destination');
  IF v_count != 2 THEN
    SET v_msg = CONCAT('VALIDATION FAILED (', p_ctx, '): Expected 2 active destination nodes, found ', v_count, '.');
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_msg;
  END IF;

  -- ----------------------------------------------------------------
  -- 4. Each active question has exactly 2 active options
  -- ----------------------------------------------------------------
  SET v_count = (
    SELECT COUNT(*) FROM (
      SELECT n.id, COUNT(o.id) AS cnt
      FROM tblintakenodes n
      LEFT JOIN tblintakeoptions o ON o.node_id=n.id AND o.status=1
      WHERE n.flow_id=p_flow_id AND n.status=1 AND n.node_type='question'
      GROUP BY n.id
      HAVING cnt != 2
    ) AS bad_q
  );
  IF v_count != 0 THEN
    SET v_msg = CONCAT('VALIDATION FAILED (', p_ctx, '): Each active question must have exactly 2 active options.');
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_msg;
  END IF;

  -- ----------------------------------------------------------------
  -- 5. Exactly 8 active options; no NULL targets; no cross-flow targets
  -- ----------------------------------------------------------------
  SET v_count = (
    SELECT COUNT(*) FROM tblintakeoptions o
    JOIN tblintakenodes n ON o.node_id=n.id AND n.status=1
    WHERE n.flow_id=p_flow_id AND o.status=1
  );
  IF v_count != 8 THEN
    SET v_msg = CONCAT('VALIDATION FAILED (', p_ctx, '): Expected 8 active options, found ', v_count, '.');
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_msg;
  END IF;

  SET v_count = (
    SELECT COUNT(*) FROM tblintakeoptions o
    JOIN tblintakenodes n ON o.node_id=n.id AND n.status=1
    WHERE n.flow_id=p_flow_id AND o.status=1 AND o.next_node_id IS NULL
  );
  IF v_count != 0 THEN
    SET v_msg = CONCAT('VALIDATION FAILED (', p_ctx, '): ', v_count, ' active option(s) have NULL targets.');
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_msg;
  END IF;

  SET v_count = (
    SELECT COUNT(*) FROM tblintakeoptions o
    JOIN tblintakenodes src ON o.node_id=src.id AND src.status=1
    LEFT JOIN tblintakenodes dst ON o.next_node_id=dst.id AND dst.status=1
    WHERE src.flow_id=p_flow_id AND o.status=1
      AND (dst.id IS NULL OR dst.flow_id != p_flow_id)
  );
  IF v_count != 0 THEN
    SET v_msg = CONCAT('VALIDATION FAILED (', p_ctx, '): ', v_count, ' active option(s) point outside this flow.');
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_msg;
  END IF;

  -- ----------------------------------------------------------------
  -- 6. Eight expected edges with status=1 on option, source, and target
  --    Edge table (src_sort, opt_sort, dst_sort)
  -- ----------------------------------------------------------------
  SET v_count = (
    SELECT COUNT(*) FROM tblintakeoptions o
    JOIN tblintakenodes src ON o.node_id=src.id AND src.flow_id=p_flow_id AND src.status=1
    JOIN tblintakenodes dst ON o.next_node_id=dst.id AND dst.flow_id=p_flow_id AND dst.status=1
    WHERE o.status=1
      AND (src.sort_order, o.sort_order, dst.sort_order) IN (
        (1,1,2),(1,2,3),(2,1,5),(2,2,6),(3,1,7),(3,2,4),(4,1,8),(4,2,9)
      )
  );
  IF v_count != 8 THEN
    SET v_msg = CONCAT('VALIDATION FAILED (', p_ctx, '): Expected 8 edges matching the graph structure, found ', v_count, '.');
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_msg;
  END IF;

  -- ----------------------------------------------------------------
  -- 7. Bilingual question prompts (by sort_order)
  -- ----------------------------------------------------------------
  SET v_count = (SELECT COUNT(*) FROM tblintakenodes WHERE flow_id=p_flow_id AND status=1
    AND sort_order=1 AND node_type='question'
    AND prompt_en='Is this a first-time assessment?'
    AND prompt_fr='S''agit-il d''une première évaluation?');
  IF v_count != 1 THEN
    SET v_msg = CONCAT('VALIDATION FAILED (', p_ctx, '): Q1 (sort_order=1) prompt EN/FR mismatch.');
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_msg;
  END IF;

  SET v_count = (SELECT COUNT(*) FROM tblintakenodes WHERE flow_id=p_flow_id AND status=1
    AND sort_order=2 AND node_type='question'
    AND prompt_en='Have you completed the Easy Checks for Web Accessibility self-checklist?'
    AND prompt_fr='Avez-vous rempli la liste d''autoévaluation Vérifications faciles pour l''accessibilité Web?');
  IF v_count != 1 THEN
    SET v_msg = CONCAT('VALIDATION FAILED (', p_ctx, '): Q2 (sort_order=2) prompt EN/FR mismatch.');
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_msg;
  END IF;

  SET v_count = (SELECT COUNT(*) FROM tblintakenodes WHERE flow_id=p_flow_id AND status=1
    AND sort_order=3 AND node_type='question'
    AND prompt_en='Have you fixed all the issues found during the first assessment?'
    AND prompt_fr='Avez-vous corrigé tous les problèmes relevés lors de la première évaluation?');
  IF v_count != 1 THEN
    SET v_msg = CONCAT('VALIDATION FAILED (', p_ctx, '): Q3 (sort_order=3) prompt EN/FR mismatch.');
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_msg;
  END IF;

  SET v_count = (SELECT COUNT(*) FROM tblintakenodes WHERE flow_id=p_flow_id AND status=1
    AND sort_order=4 AND node_type='question'
    AND prompt_en='Have you completed the Easy Checks for Web Accessibility self-checklist?'
    AND prompt_fr='Avez-vous rempli la liste d''autoévaluation Vérifications faciles pour l''accessibilité Web?');
  IF v_count != 1 THEN
    SET v_msg = CONCAT('VALIDATION FAILED (', p_ctx, '): Q4 (sort_order=4) prompt EN/FR mismatch.');
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_msg;
  END IF;

  -- ----------------------------------------------------------------
  -- 8. Bilingual guidance headings (by sort_order)
  -- ----------------------------------------------------------------
  SET v_count = (SELECT COUNT(*) FROM tblintakenodes WHERE flow_id=p_flow_id AND status=1
    AND sort_order=6 AND node_type='guidance'
    AND heading_en='Please complete the self-checklist first'
    AND heading_fr='Veuillez d''abord remplir la liste d''autoévaluation');
  IF v_count != 1 THEN
    SET v_msg = CONCAT('VALIDATION FAILED (', p_ctx, '): GUID_CK1 (sort_order=6) heading EN/FR mismatch.');
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_msg;
  END IF;

  SET v_count = (SELECT COUNT(*) FROM tblintakenodes WHERE flow_id=p_flow_id AND status=1
    AND sort_order=7 AND node_type='guidance'
    AND heading_en='Please fix the issues from the first assessment'
    AND heading_fr='Veuillez corriger les problèmes relevés lors de la première évaluation');
  IF v_count != 1 THEN
    SET v_msg = CONCAT('VALIDATION FAILED (', p_ctx, '): GUID_FIX (sort_order=7) heading EN/FR mismatch.');
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_msg;
  END IF;

  SET v_count = (SELECT COUNT(*) FROM tblintakenodes WHERE flow_id=p_flow_id AND status=1
    AND sort_order=9 AND node_type='guidance'
    AND heading_en='Please complete the self-checklist first'
    AND heading_fr='Veuillez d''abord remplir la liste d''autoévaluation');
  IF v_count != 1 THEN
    SET v_msg = CONCAT('VALIDATION FAILED (', p_ctx, '): GUID_CK2 (sort_order=9) heading EN/FR mismatch.');
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_msg;
  END IF;

  -- ----------------------------------------------------------------
  -- 9. Guidance body content (exact match — v1 text is immutable)
  -- ----------------------------------------------------------------
  SET v_count = (SELECT COUNT(*) FROM tblintakenodes WHERE flow_id=p_flow_id AND status=1
    AND sort_order=6 AND node_type='guidance'
    AND body_en = 'Before submitting a website accessibility assessment request, please complete the Easy Checks for Web Accessibility self-checklist. Once you have worked through the checklist, return to this form and answer "Yes" to continue.'
    AND body_fr = 'Avant de soumettre une demande d''évaluation de l''accessibilité Web, veuillez remplir la liste d''autoévaluation Vérifications faciles pour l''accessibilité Web. Une fois la liste complétée, revenez à ce formulaire et répondez « Oui » pour continuer.');
  IF v_count != 1 THEN
    SET v_msg = CONCAT('VALIDATION FAILED (', p_ctx, '): GUID_CK1 (sort_order=6) body EN/FR mismatch.');
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_msg;
  END IF;

  SET v_count = (SELECT COUNT(*) FROM tblintakenodes WHERE flow_id=p_flow_id AND status=1
    AND sort_order=7 AND node_type='guidance'
    AND body_en = 'A reassessment request requires that all issues identified during the first assessment have been resolved. Please correct the reported issues, then return to this form and begin again.'
    AND body_fr = 'Une demande de réévaluation exige que tous les problèmes identifiés lors de la première évaluation aient été corrigés. Veuillez corriger les problèmes signalés, puis revenez à ce formulaire pour recommencer.');
  IF v_count != 1 THEN
    SET v_msg = CONCAT('VALIDATION FAILED (', p_ctx, '): GUID_FIX (sort_order=7) body EN/FR mismatch.');
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_msg;
  END IF;

  SET v_count = (SELECT COUNT(*) FROM tblintakenodes WHERE flow_id=p_flow_id AND status=1
    AND sort_order=9 AND node_type='guidance'
    AND body_en = 'Before submitting a website accessibility reassessment request, please complete the Easy Checks for Web Accessibility self-checklist. Once you have worked through the checklist, return to this form and answer "Yes" to continue.'
    AND body_fr = 'Avant de soumettre une demande de réévaluation de l''accessibilité Web, veuillez remplir la liste d''autoévaluation Vérifications faciles pour l''accessibilité Web. Une fois la liste complétée, revenez à ce formulaire et répondez « Oui » pour continuer.');
  IF v_count != 1 THEN
    SET v_msg = CONCAT('VALIDATION FAILED (', p_ctx, '): GUID_CK2 (sort_order=9) body EN/FR mismatch.');
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_msg;
  END IF;

  -- ----------------------------------------------------------------
  -- 10. Option labels: Yes/Oui and No/Non with correct sort orders
  --     Q1(sort=1): opt_sort=1 Yes/Oui, opt_sort=2 No/Non
  --     Q2(sort=2): opt_sort=1 Yes/Oui, opt_sort=2 No/Non
  --     Q3(sort=3): opt_sort=1 No/Non,  opt_sort=2 Yes/Oui  (reversed)
  --     Q4(sort=4): opt_sort=1 Yes/Oui, opt_sort=2 No/Non
  -- ----------------------------------------------------------------
  SET v_count = (
    SELECT COUNT(*) FROM tblintakeoptions o
    JOIN tblintakenodes n ON o.node_id=n.id AND n.flow_id=p_flow_id AND n.status=1
    WHERE o.status=1
      AND (
        (n.sort_order=1 AND o.sort_order=1 AND o.labelen='Yes' AND o.labelfr='Oui') OR
        (n.sort_order=1 AND o.sort_order=2 AND o.labelen='No'  AND o.labelfr='Non') OR
        (n.sort_order=2 AND o.sort_order=1 AND o.labelen='Yes' AND o.labelfr='Oui') OR
        (n.sort_order=2 AND o.sort_order=2 AND o.labelen='No'  AND o.labelfr='Non') OR
        (n.sort_order=3 AND o.sort_order=1 AND o.labelen='No'  AND o.labelfr='Non') OR
        (n.sort_order=3 AND o.sort_order=2 AND o.labelen='Yes' AND o.labelfr='Oui') OR
        (n.sort_order=4 AND o.sort_order=1 AND o.labelen='Yes' AND o.labelfr='Oui') OR
        (n.sort_order=4 AND o.sort_order=2 AND o.labelen='No'  AND o.labelfr='Non')
      )
  );
  IF v_count != 8 THEN
    SET v_msg = CONCAT('VALIDATION FAILED (', p_ctx, '): Option labels EN/FR or sort orders mismatch (expected 8 matching, got ', v_count, ').');
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_msg;
  END IF;

  -- ----------------------------------------------------------------
  -- 11. Exactly 2 active resources: exactly one on sort_order=6, one on sort_order=9
  -- ----------------------------------------------------------------
  SET v_count = (
    SELECT COUNT(*) FROM tblintakeresources r
    JOIN tblintakenodes n ON r.node_id=n.id AND n.flow_id=p_flow_id AND n.status=1
    WHERE r.status=1
  );
  IF v_count != 2 THEN
    SET v_msg = CONCAT('VALIDATION FAILED (', p_ctx, '): Expected 2 active resources, found ', v_count, '.');
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_msg;
  END IF;

  SET v_count = (
    SELECT COUNT(*) FROM tblintakeresources r
    JOIN tblintakenodes n ON r.node_id=n.id AND n.flow_id=p_flow_id AND n.status=1
    WHERE r.status=1 AND n.sort_order=6 AND n.node_type='guidance'
  );
  IF v_count != 1 THEN
    SET v_msg = CONCAT('VALIDATION FAILED (', p_ctx, '): Expected exactly 1 active resource on guidance node sort_order=6 (GUID_CK1).');
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_msg;
  END IF;

  SET v_count = (
    SELECT COUNT(*) FROM tblintakeresources r
    JOIN tblintakenodes n ON r.node_id=n.id AND n.flow_id=p_flow_id AND n.status=1
    WHERE r.status=1 AND n.sort_order=9 AND n.node_type='guidance'
  );
  IF v_count != 1 THEN
    SET v_msg = CONCAT('VALIDATION FAILED (', p_ctx, '): Expected exactly 1 active resource on guidance node sort_order=9 (GUID_CK2).');
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_msg;
  END IF;

  -- ----------------------------------------------------------------
  -- 12. Resource EN/FR titles and URLs
  -- ----------------------------------------------------------------
  SET v_count = (
    SELECT COUNT(*) FROM tblintakeresources r
    JOIN tblintakenodes n ON r.node_id=n.id AND n.flow_id=p_flow_id AND n.status=1
    WHERE r.status=1
      AND r.titleen=@ck_title_en AND r.titlefr=@ck_title_fr
      AND r.url_en=@ck_url_en    AND r.url_fr=@ck_url_fr
  );
  IF v_count != 2 THEN
    SET v_msg = CONCAT('VALIDATION FAILED (', p_ctx, '): Resource titles or URLs do not match expected Easy Checks values (matched ', v_count, '/2).');
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_msg;
  END IF;

  -- ----------------------------------------------------------------
  -- 13. Destination: first_assessment — exact cat, svc, sub, outcome
  -- ----------------------------------------------------------------
  SET v_count = (SELECT COUNT(*) FROM tblintakenodes
    WHERE flow_id=p_flow_id AND status=1 AND node_type='destination'
      AND outcome_code=@outcome_first
      AND target_catalogueid=@target_catalogueid
      AND target_serviceid=@target_serviceid
      AND target_subserviceid=@dest_first_subid);
  IF v_count != 1 THEN
    SET v_msg = CONCAT('VALIDATION FAILED (', p_ctx, '): first_assessment destination has wrong catalogue/service/subservice/outcome.');
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_msg;
  END IF;

  -- ----------------------------------------------------------------
  -- 14. Destination: reassessment — exact cat, svc, sub, outcome
  -- ----------------------------------------------------------------
  SET v_count = (SELECT COUNT(*) FROM tblintakenodes
    WHERE flow_id=p_flow_id AND status=1 AND node_type='destination'
      AND outcome_code=@outcome_reassessment
      AND target_catalogueid=@target_catalogueid
      AND target_serviceid=@target_serviceid
      AND target_subserviceid=@dest_reaudit_subid);
  IF v_count != 1 THEN
    SET v_msg = CONCAT('VALIDATION FAILED (', p_ctx, '): reassessment destination has wrong catalogue/service/subservice/outcome.');
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_msg;
  END IF;

  -- ----------------------------------------------------------------
  -- 15. Reference classifications still active and correctly related
  -- ----------------------------------------------------------------
  SET v_count = (SELECT COUNT(*) FROM tblcatalogue WHERE id=@target_catalogueid AND status=1);
  IF v_count != 1 THEN
    SET v_msg = CONCAT('VALIDATION FAILED (', p_ctx, '): Catalogue 8 is inactive or missing.');
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_msg;
  END IF;

  SET v_count = (SELECT COUNT(*) FROM tblservices
    WHERE id=@target_serviceid AND catalogueid=@target_catalogueid AND status=1);
  IF v_count != 1 THEN
    SET v_msg = CONCAT('VALIDATION FAILED (', p_ctx, '): Service 28 is inactive, missing, or wrong catalogue.');
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_msg;
  END IF;

  SET v_count = (SELECT COUNT(*) FROM tblsubservices
    WHERE id=@dest_first_subid AND serviceid=@target_serviceid AND status=1);
  IF v_count != 1 THEN
    SET v_msg = CONCAT('VALIDATION FAILED (', p_ctx, '): Subservice 96 (first_assessment) inactive or wrong service.');
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_msg;
  END IF;

  SET v_count = (SELECT COUNT(*) FROM tblsubservices
    WHERE id=@dest_reaudit_subid AND serviceid=@target_serviceid AND status=1);
  IF v_count != 1 THEN
    SET v_msg = CONCAT('VALIDATION FAILED (', p_ctx, '): Subservice 212 (reassessment) inactive or wrong service.');
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_msg;
  END IF;

END validate_proc$$

-- =============================================================================
-- Main seed procedure
-- =============================================================================
DROP PROCEDURE IF EXISTS _rmt_seed_ssc_website_testing_v1$$

CREATE PROCEDURE _rmt_seed_ssc_website_testing_v1()
proc: BEGIN

  DECLARE v_flow_id          INT DEFAULT NULL;
  DECLARE v_node_q1          INT DEFAULT NULL;
  DECLARE v_node_q2          INT DEFAULT NULL;
  DECLARE v_node_q3          INT DEFAULT NULL;
  DECLARE v_node_q4          INT DEFAULT NULL;
  DECLARE v_node_dest_first  INT DEFAULT NULL;
  DECLARE v_node_guid_ck1    INT DEFAULT NULL;
  DECLARE v_node_guid_fix    INT DEFAULT NULL;
  DECLARE v_node_dest_reau   INT DEFAULT NULL;
  DECLARE v_node_guid_ck2    INT DEFAULT NULL;
  DECLARE v_node_count       INT DEFAULT 0;
  DECLARE v_opt_count        INT DEFAULT 0;
  DECLARE v_res_count        INT DEFAULT 0;
  DECLARE v_existing_svc_fid INT DEFAULT NULL;
  DECLARE v_check_fid        INT DEFAULT NULL;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    RESIGNAL;
  END;

  -- ================================================================
  -- Step 1: Verify migration 016 is applied
  -- ================================================================
  IF (SELECT COUNT(*) FROM information_schema.TABLES
      WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='tblintakeflows') = 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'ERROR: tblintakeflows does not exist. Apply migration 016-intake-flows.sql first.';
  END IF;

  -- ================================================================
  -- Step 2: Pre-validate reference data before any insert
  -- ================================================================
  IF (SELECT COUNT(*) FROM tblcatalogue WHERE id=@target_catalogueid AND status=1) = 0 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ERROR: Catalogue 8 not found or inactive.';
  END IF;
  IF (SELECT COUNT(*) FROM tblservices
      WHERE id=@target_serviceid AND catalogueid=@target_catalogueid AND status=1) = 0 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ERROR: Service 28 not found, inactive, or wrong catalogue.';
  END IF;
  IF (SELECT COUNT(*) FROM tblsubservices
      WHERE id=@dest_first_subid AND serviceid=@target_serviceid AND status=1) = 0 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ERROR: Subservice 96 not found, inactive, or wrong service.';
  END IF;
  IF (SELECT COUNT(*) FROM tblsubservices
      WHERE id=@dest_reaudit_subid AND serviceid=@target_serviceid AND status=1) = 0 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ERROR: Subservice 212 not found, inactive, or wrong service.';
  END IF;

  -- ================================================================
  -- Step 3: Idempotency check
  -- ================================================================
  SET v_flow_id = (
    SELECT id FROM tblintakeflows
    WHERE flow_family_key=@flow_family_key AND version_number=@flow_version
    LIMIT 1
  );

  IF v_flow_id IS NOT NULL THEN
    IF (SELECT status FROM tblintakeflows WHERE id=v_flow_id) != 1 THEN
      SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'ERROR: A draft/partial version of ssc.website-testing v1 exists. Inspect and clean up manually, then re-run.';
    END IF;

    -- Run the full common graph validation on the existing published flow.
    CALL _rmt_validate_ssc_wt_graph(v_flow_id, 'published-v1');

    -- Additionally: service 28 must point to THIS exact flow.
    SET v_check_fid = (SELECT intake_flow_id FROM tblservices WHERE id=@target_serviceid);
    IF v_check_fid IS NULL OR v_check_fid != v_flow_id THEN
      SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'VALIDATION FAILED (published-v1): Service 28 is not attached to this exact flow.';
    END IF;

    -- Compute counts for the success message
    SET v_node_count = (SELECT COUNT(*) FROM tblintakenodes WHERE flow_id=v_flow_id AND status=1);
    SET v_opt_count  = (SELECT COUNT(*) FROM tblintakeoptions o
                        JOIN tblintakenodes n ON o.node_id=n.id AND n.status=1
                        WHERE n.flow_id=v_flow_id AND o.status=1);
    SET v_res_count  = (SELECT COUNT(*) FROM tblintakeresources r
                        JOIN tblintakenodes n ON r.node_id=n.id AND n.status=1
                        WHERE n.flow_id=v_flow_id AND r.status=1);

    SELECT CONCAT(
      @flow_family_key, ' v', @flow_version,
      ' is already installed and published (flow_id=', v_flow_id,
      ', nodes=', v_node_count,
      ', options=', v_opt_count,
      ', resources=', v_res_count, '). No changes made.'
    ) AS message;
    LEAVE proc;
  END IF;

  -- ================================================================
  -- Step 4: Service 28 must not be attached to a different flow
  -- ================================================================
  SET v_existing_svc_fid = (SELECT intake_flow_id FROM tblservices WHERE id=@target_serviceid);
  IF v_existing_svc_fid IS NOT NULL THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'ERROR: Service 28 already has intake_flow_id set. Cannot overwrite without removing it first.';
  END IF;

  -- ================================================================
  -- Step 5: Build the draft graph inside a transaction
  -- ================================================================
  START TRANSACTION;

  INSERT INTO tblintakeflows (nameen, namefr, flow_family_key, version_number, status)
  VALUES (@flow_name_en, @flow_name_fr, @flow_family_key, @flow_version, 0);
  SET v_flow_id = LAST_INSERT_ID();

  -- Q1
  INSERT INTO tblintakenodes (flow_id, node_type, sort_order, presentation, prompt_en, prompt_fr)
  VALUES (v_flow_id, 'question', 1, 'radio',
    'Is this a first-time assessment?',
    'S''agit-il d''une première évaluation?');
  SET v_node_q1 = LAST_INSERT_ID();

  -- Q2
  INSERT INTO tblintakenodes (flow_id, node_type, sort_order, presentation,
    prompt_en, prompt_fr, intro_en, intro_fr)
  VALUES (v_flow_id, 'question', 2, 'radio',
    'Have you completed the Easy Checks for Web Accessibility self-checklist?',
    'Avez-vous rempli la liste d''autoévaluation Vérifications faciles pour l''accessibilité Web?',
    'Please review the checklist before submitting your request.',
    'Veuillez consulter la liste de vérification avant de soumettre votre demande.');
  SET v_node_q2 = LAST_INSERT_ID();

  -- Q3
  INSERT INTO tblintakenodes (flow_id, node_type, sort_order, presentation, prompt_en, prompt_fr)
  VALUES (v_flow_id, 'question', 3, 'radio',
    'Have you fixed all the issues found during the first assessment?',
    'Avez-vous corrigé tous les problèmes relevés lors de la première évaluation?');
  SET v_node_q3 = LAST_INSERT_ID();

  -- Q4
  INSERT INTO tblintakenodes (flow_id, node_type, sort_order, presentation,
    prompt_en, prompt_fr, intro_en, intro_fr)
  VALUES (v_flow_id, 'question', 4, 'radio',
    'Have you completed the Easy Checks for Web Accessibility self-checklist?',
    'Avez-vous rempli la liste d''autoévaluation Vérifications faciles pour l''accessibilité Web?',
    'Please review the checklist before submitting your reassessment request.',
    'Veuillez consulter la liste de vérification avant de soumettre votre demande de réévaluation.');
  SET v_node_q4 = LAST_INSERT_ID();

  -- DEST_FIRST (sort_order=5)
  INSERT INTO tblintakenodes (flow_id, node_type, sort_order,
    target_catalogueid, target_serviceid, target_subserviceid, outcome_code)
  VALUES (v_flow_id, 'destination', 5,
    @target_catalogueid, @target_serviceid, @dest_first_subid, @outcome_first);
  SET v_node_dest_first = LAST_INSERT_ID();

  -- GUID_CK1 (sort_order=6)
  INSERT INTO tblintakenodes (flow_id, node_type, sort_order,
    heading_en, heading_fr, body_en, body_fr)
  VALUES (v_flow_id, 'guidance', 6,
    'Please complete the self-checklist first',
    'Veuillez d''abord remplir la liste d''autoévaluation',
    'Before submitting a website accessibility assessment request, please complete the Easy Checks for Web Accessibility self-checklist. Once you have worked through the checklist, return to this form and answer "Yes" to continue.',
    'Avant de soumettre une demande d''évaluation de l''accessibilité Web, veuillez remplir la liste d''autoévaluation Vérifications faciles pour l''accessibilité Web. Une fois la liste complétée, revenez à ce formulaire et répondez « Oui » pour continuer.');
  SET v_node_guid_ck1 = LAST_INSERT_ID();

  -- GUID_FIX (sort_order=7)
  INSERT INTO tblintakenodes (flow_id, node_type, sort_order,
    heading_en, heading_fr, body_en, body_fr)
  VALUES (v_flow_id, 'guidance', 7,
    'Please fix the issues from the first assessment',
    'Veuillez corriger les problèmes relevés lors de la première évaluation',
    'A reassessment request requires that all issues identified during the first assessment have been resolved. Please correct the reported issues, then return to this form and begin again.',
    'Une demande de réévaluation exige que tous les problèmes identifiés lors de la première évaluation aient été corrigés. Veuillez corriger les problèmes signalés, puis revenez à ce formulaire pour recommencer.');
  SET v_node_guid_fix = LAST_INSERT_ID();

  -- DEST_REAU (sort_order=8)
  INSERT INTO tblintakenodes (flow_id, node_type, sort_order,
    target_catalogueid, target_serviceid, target_subserviceid, outcome_code)
  VALUES (v_flow_id, 'destination', 8,
    @target_catalogueid, @target_serviceid, @dest_reaudit_subid, @outcome_reassessment);
  SET v_node_dest_reau = LAST_INSERT_ID();

  -- GUID_CK2 (sort_order=9)
  INSERT INTO tblintakenodes (flow_id, node_type, sort_order,
    heading_en, heading_fr, body_en, body_fr)
  VALUES (v_flow_id, 'guidance', 9,
    'Please complete the self-checklist first',
    'Veuillez d''abord remplir la liste d''autoévaluation',
    'Before submitting a website accessibility reassessment request, please complete the Easy Checks for Web Accessibility self-checklist. Once you have worked through the checklist, return to this form and answer "Yes" to continue.',
    'Avant de soumettre une demande de réévaluation de l''accessibilité Web, veuillez remplir la liste d''autoévaluation Vérifications faciles pour l''accessibilité Web. Une fois la liste complétée, revenez à ce formulaire et répondez « Oui » pour continuer.');
  SET v_node_guid_ck2 = LAST_INSERT_ID();

  -- Set start node
  UPDATE tblintakeflows SET start_node_id=v_node_q1 WHERE id=v_flow_id;

  -- Insert options
  INSERT INTO tblintakeoptions (node_id, labelen, labelfr, next_node_id, sort_order)
  VALUES
    (v_node_q1, 'Yes', 'Oui', v_node_q2,         1),
    (v_node_q1, 'No',  'Non', v_node_q3,          2),
    (v_node_q2, 'Yes', 'Oui', v_node_dest_first,  1),
    (v_node_q2, 'No',  'Non', v_node_guid_ck1,    2),
    (v_node_q3, 'No',  'Non', v_node_guid_fix,    1),
    (v_node_q3, 'Yes', 'Oui', v_node_q4,          2),
    (v_node_q4, 'Yes', 'Oui', v_node_dest_reau,   1),
    (v_node_q4, 'No',  'Non', v_node_guid_ck2,    2);

  -- Insert resources
  INSERT INTO tblintakeresources (node_id, titleen, titlefr, url_en, url_fr, sort_order)
  VALUES
    (v_node_guid_ck1, @ck_title_en, @ck_title_fr, @ck_url_en, @ck_url_fr, 1),
    (v_node_guid_ck2, @ck_title_en, @ck_title_fr, @ck_url_en, @ck_url_fr, 1);

  -- ================================================================
  -- Step 6: Run complete graph validation on the DRAFT
  -- Any failure signals an exception which triggers ROLLBACK.
  -- ================================================================
  CALL _rmt_validate_ssc_wt_graph(v_flow_id, 'new-flow');

  -- ================================================================
  -- Step 7: Publish and attach
  -- ================================================================
  UPDATE tblintakeflows SET status=1 WHERE id=v_flow_id;
  UPDATE tblservices SET intake_flow_id=v_flow_id WHERE id=@target_serviceid;

  -- ================================================================
  -- Step 8: Verify final status and attachment inside transaction
  -- (catches any DB-level constraint or trigger that could block the UPDATEs)
  -- ================================================================
  IF (SELECT status FROM tblintakeflows WHERE id=v_flow_id) != 1 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'ERROR (new-flow): status UPDATE did not persist — unexpected DB state.';
  END IF;

  SET v_check_fid = (SELECT intake_flow_id FROM tblservices WHERE id=@target_serviceid);
  IF v_check_fid IS NULL OR v_check_fid != v_flow_id THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'ERROR (new-flow): service 28 attachment did not persist — unexpected DB state.';
  END IF;

  COMMIT;

  -- Compute final counts from the committed data
  SET v_node_count = (SELECT COUNT(*) FROM tblintakenodes WHERE flow_id=v_flow_id AND status=1);
  SET v_opt_count  = (SELECT COUNT(*) FROM tblintakeoptions o
                      JOIN tblintakenodes n ON o.node_id=n.id AND n.status=1
                      WHERE n.flow_id=v_flow_id AND o.status=1);
  SET v_res_count  = (SELECT COUNT(*) FROM tblintakeresources r
                      JOIN tblintakenodes n ON r.node_id=n.id AND n.status=1
                      WHERE n.flow_id=v_flow_id AND r.status=1);

  SELECT CONCAT(
    'SUCCESS: ', @flow_family_key, ' v', @flow_version,
    ' installed and published.',
    ' flow_id=', v_flow_id,
    ', start_node=', v_node_q1,
    ', nodes=', v_node_count,
    ', options=', v_opt_count,
    ', resources=', v_res_count
  ) AS message;

END proc$$
DELIMITER ;

CALL _rmt_seed_ssc_website_testing_v1();
DROP PROCEDURE IF EXISTS _rmt_seed_ssc_website_testing_v1;
DROP PROCEDURE IF EXISTS _rmt_validate_ssc_wt_graph;
