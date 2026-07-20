-- =============================================================================
-- Intake Flow Seed Template
--
-- Copy to database/seeds/<org>/<flow-name>-v1.sql.
-- Fill in EVERY @@CONFIGURE section. Run with load-intake-seed.sh.
--
-- Example graph (5 nodes, 4 options, 1 resource):
--
--   Q1 ─ Yes ──► DEST_A (outcome_a)
--      └─ No  ──► Q2 ─ Yes ──► DEST_B (outcome_b)
--                    └─ No  ──► GUID_A (resource link)
--
--   Node sort_orders: Q1=1, Q2=2, DEST_A=3, DEST_B=4, GUID_A=5
--   Edge table (src_sort, opt_sort, dst_sort):
--     (1,1,3)(1,2,2)(2,1,4)(2,2,5)
--
-- When you change the graph, update @@CONFIGURE_COUNTS and every
-- section marked (update if graph changes).
-- =============================================================================

SET NAMES utf8mb4;

-- =============================================================================
-- @@CONFIGURE: Identity — change ALL four values
-- =============================================================================
SET @flow_family_key = 'CHANGE.ME.flow-name';       -- e.g. 'esdc.my-flow'
SET @flow_version    = 1;
SET @flow_name_en    = 'CHANGE ME — English name';
SET @flow_name_fr    = 'CHANGE ME — Nom français';

-- =============================================================================
-- @@CONFIGURE: Classification mapping
-- =============================================================================
SET @attach_catalogueid  = 0;    -- catalogue the flow belongs to
SET @attach_serviceid    = 0;    -- service to attach intake_flow_id
SET @dest_a_catalogueid  = 0;    -- destination A catalogue
SET @dest_a_serviceid    = 0;
SET @dest_a_subserviceid = 0;
SET @dest_b_catalogueid  = 0;    -- destination B catalogue
SET @dest_b_serviceid    = 0;
SET @dest_b_subserviceid = 0;
SET @outcome_a           = 'CHANGE_ME_A';   -- e.g. 'first_assessment'
SET @outcome_b           = 'CHANGE_ME_B';   -- e.g. 'reassessment'

-- =============================================================================
-- @@CONFIGURE: Bilingual node content (prompts, headings, bodies)
-- =============================================================================
-- Q1 (sort_order=1)
SET @q1_prompt_en = 'CHANGE ME — Q1 prompt EN';
SET @q1_prompt_fr = 'CHANGE ME — Q1 libellé FR';

-- Q2 (sort_order=2)
SET @q2_prompt_en = 'CHANGE ME — Q2 prompt EN';
SET @q2_prompt_fr = 'CHANGE ME — Q2 libellé FR';

-- GUID_A (sort_order=5)
SET @guid_a_heading_en = 'CHANGE ME — guidance heading EN';
SET @guid_a_heading_fr = 'CHANGE ME — titre d''orientation FR';
SET @guid_a_body_en    = 'CHANGE ME — guidance body EN (one full sentence or more)';
SET @guid_a_body_fr    = 'CHANGE ME — corps de l''orientation FR';

-- =============================================================================
-- @@CONFIGURE: Option labels (4 options)
-- If your graph uses different labels, change all four pairs.
-- =============================================================================
SET @q1_opt1_en = 'Yes'; SET @q1_opt1_fr = 'Oui';  -- Q1 opt_sort=1 → DEST_A
SET @q1_opt2_en = 'No';  SET @q1_opt2_fr = 'Non';  -- Q1 opt_sort=2 → Q2
SET @q2_opt1_en = 'Yes'; SET @q2_opt1_fr = 'Oui';  -- Q2 opt_sort=1 → DEST_B
SET @q2_opt2_en = 'No';  SET @q2_opt2_fr = 'Non';  -- Q2 opt_sort=2 → GUID_A

-- =============================================================================
-- @@CONFIGURE: Resource content (for GUID_A)
-- =============================================================================
SET @res_title_en = 'CHANGE ME — resource title EN';
SET @res_title_fr = 'CHANGE ME — titre de ressource FR';
SET @res_url_en   = 'https://CHANGE.ME/resource-en.html';
SET @res_url_fr   = 'https://CHANGE.ME/resource-fr.html';

-- =============================================================================
-- @@CONFIGURE_COUNTS: Update these when you change the graph
-- =============================================================================
SET @expected_nodes   = 5;   -- total active nodes
SET @expected_q       = 2;   -- question nodes
SET @expected_guid    = 1;   -- guidance nodes
SET @expected_dest    = 2;   -- destination nodes
SET @expected_options = 4;   -- total active options
SET @expected_res     = 1;   -- total active resources

-- =============================================================================
-- Shared graph-validation procedure (update if graph changes)
-- =============================================================================
DROP PROCEDURE IF EXISTS _rmt_tpl_validate_graph;

DELIMITER $$
CREATE PROCEDURE _rmt_tpl_validate_graph(IN p_flow_id INT, IN p_ctx VARCHAR(30))
tpl_val: BEGIN

  DECLARE v_count INT DEFAULT 0;
  DECLARE v_msg   VARCHAR(500) DEFAULT '';

  -- 1. Flow names EN and FR
  SET v_count = (SELECT COUNT(*) FROM tblintakeflows
                 WHERE id=p_flow_id AND nameen=@flow_name_en AND namefr=@flow_name_fr);
  IF v_count != 1 THEN
    SET v_msg = CONCAT('VALIDATION FAILED (', p_ctx, '): Flow names do not match expected EN/FR values.');
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_msg;
  END IF;

  -- 2. Start node: active question at sort_order=1
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

  -- 3. Active node counts (update @expected_* values to match your graph)
  SET v_count = (SELECT COUNT(*) FROM tblintakenodes WHERE flow_id=p_flow_id AND status=1);
  IF v_count != @expected_nodes THEN
    SET v_msg = CONCAT('VALIDATION FAILED (', p_ctx, '): Expected ', @expected_nodes, ' active nodes, found ', v_count, '.');
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_msg;
  END IF;

  SET v_count = (SELECT COUNT(*) FROM tblintakenodes WHERE flow_id=p_flow_id AND status=1 AND node_type='question');
  IF v_count != @expected_q THEN
    SET v_msg = CONCAT('VALIDATION FAILED (', p_ctx, '): Expected ', @expected_q, ' question nodes, found ', v_count, '.');
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_msg;
  END IF;

  SET v_count = (SELECT COUNT(*) FROM tblintakenodes WHERE flow_id=p_flow_id AND status=1 AND node_type='guidance');
  IF v_count != @expected_guid THEN
    SET v_msg = CONCAT('VALIDATION FAILED (', p_ctx, '): Expected ', @expected_guid, ' guidance node(s), found ', v_count, '.');
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_msg;
  END IF;

  SET v_count = (SELECT COUNT(*) FROM tblintakenodes WHERE flow_id=p_flow_id AND status=1 AND node_type='destination');
  IF v_count != @expected_dest THEN
    SET v_msg = CONCAT('VALIDATION FAILED (', p_ctx, '): Expected ', @expected_dest, ' destination nodes, found ', v_count, '.');
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_msg;
  END IF;

  -- 4. Each active question has exactly 2 active options
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

  -- 5. Option count, no NULL targets, no cross-flow targets
  SET v_count = (SELECT COUNT(*) FROM tblintakeoptions o
                 JOIN tblintakenodes n ON o.node_id=n.id AND n.status=1
                 WHERE n.flow_id=p_flow_id AND o.status=1);
  IF v_count != @expected_options THEN
    SET v_msg = CONCAT('VALIDATION FAILED (', p_ctx, '): Expected ', @expected_options, ' active options, found ', v_count, '.');
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_msg;
  END IF;

  SET v_count = (SELECT COUNT(*) FROM tblintakeoptions o
                 JOIN tblintakenodes n ON o.node_id=n.id AND n.status=1
                 WHERE n.flow_id=p_flow_id AND o.status=1 AND o.next_node_id IS NULL);
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

  -- 6. Expected edges with status=1 on option, source, and target
  --    @@CONFIGURE (update if graph changes): edge table (src_sort, opt_sort, dst_sort)
  SET v_count = (
    SELECT COUNT(*) FROM tblintakeoptions o
    JOIN tblintakenodes src ON o.node_id=src.id AND src.flow_id=p_flow_id AND src.status=1
    JOIN tblintakenodes dst ON o.next_node_id=dst.id AND dst.flow_id=p_flow_id AND dst.status=1
    WHERE o.status=1
      AND (src.sort_order, o.sort_order, dst.sort_order) IN (
        (1,1,3),(1,2,2),(2,1,4),(2,2,5)
      )
  );
  IF v_count != @expected_options THEN
    SET v_msg = CONCAT('VALIDATION FAILED (', p_ctx, '): Expected ', @expected_options, ' edges matching the graph structure, found ', v_count, '.');
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_msg;
  END IF;

  -- 7. Q1 prompt EN/FR
  SET v_count = (SELECT COUNT(*) FROM tblintakenodes WHERE flow_id=p_flow_id AND status=1
    AND sort_order=1 AND node_type='question'
    AND prompt_en=@q1_prompt_en AND prompt_fr=@q1_prompt_fr);
  IF v_count != 1 THEN
    SET v_msg = CONCAT('VALIDATION FAILED (', p_ctx, '): Q1 (sort_order=1) prompt EN/FR mismatch.');
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_msg;
  END IF;

  -- 8. Q2 prompt EN/FR
  SET v_count = (SELECT COUNT(*) FROM tblintakenodes WHERE flow_id=p_flow_id AND status=1
    AND sort_order=2 AND node_type='question'
    AND prompt_en=@q2_prompt_en AND prompt_fr=@q2_prompt_fr);
  IF v_count != 1 THEN
    SET v_msg = CONCAT('VALIDATION FAILED (', p_ctx, '): Q2 (sort_order=2) prompt EN/FR mismatch.');
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_msg;
  END IF;

  -- 9. GUID_A heading and body (sort_order=5)
  SET v_count = (SELECT COUNT(*) FROM tblintakenodes WHERE flow_id=p_flow_id AND status=1
    AND sort_order=5 AND node_type='guidance'
    AND heading_en=@guid_a_heading_en AND heading_fr=@guid_a_heading_fr
    AND body_en=@guid_a_body_en       AND body_fr=@guid_a_body_fr);
  IF v_count != 1 THEN
    SET v_msg = CONCAT('VALIDATION FAILED (', p_ctx, '): GUID_A (sort_order=5) heading or body EN/FR mismatch.');
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_msg;
  END IF;

  -- 10. Option labels (4 labels across 2 questions)
  SET v_count = (
    SELECT COUNT(*) FROM tblintakeoptions o
    JOIN tblintakenodes n ON o.node_id=n.id AND n.flow_id=p_flow_id AND n.status=1
    WHERE o.status=1
      AND (
        (n.sort_order=1 AND o.sort_order=1 AND o.labelen=@q1_opt1_en AND o.labelfr=@q1_opt1_fr) OR
        (n.sort_order=1 AND o.sort_order=2 AND o.labelen=@q1_opt2_en AND o.labelfr=@q1_opt2_fr) OR
        (n.sort_order=2 AND o.sort_order=1 AND o.labelen=@q2_opt1_en AND o.labelfr=@q2_opt1_fr) OR
        (n.sort_order=2 AND o.sort_order=2 AND o.labelen=@q2_opt2_en AND o.labelfr=@q2_opt2_fr)
      )
  );
  IF v_count != @expected_options THEN
    SET v_msg = CONCAT('VALIDATION FAILED (', p_ctx, '): Option labels EN/FR mismatch (expected ', @expected_options, ', got ', v_count, ').');
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_msg;
  END IF;

  -- 11. Active resource count and placement on GUID_A (sort_order=5)
  SET v_count = (SELECT COUNT(*) FROM tblintakeresources r
                 JOIN tblintakenodes n ON r.node_id=n.id AND n.flow_id=p_flow_id AND n.status=1
                 WHERE r.status=1);
  IF v_count != @expected_res THEN
    SET v_msg = CONCAT('VALIDATION FAILED (', p_ctx, '): Expected ', @expected_res, ' active resource(s), found ', v_count, '.');
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_msg;
  END IF;

  SET v_count = (SELECT COUNT(*) FROM tblintakeresources r
                 JOIN tblintakenodes n ON r.node_id=n.id AND n.flow_id=p_flow_id AND n.status=1
                 WHERE r.status=1 AND n.sort_order=5 AND n.node_type='guidance');
  IF v_count != 1 THEN
    SET v_msg = CONCAT('VALIDATION FAILED (', p_ctx, '): Expected resource on guidance node at sort_order=5 (GUID_A).');
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_msg;
  END IF;

  -- 12. Resource titles and URLs
  SET v_count = (SELECT COUNT(*) FROM tblintakeresources r
                 JOIN tblintakenodes n ON r.node_id=n.id AND n.flow_id=p_flow_id AND n.status=1
                 WHERE r.status=1
                   AND r.titleen=@res_title_en AND r.titlefr=@res_title_fr
                   AND r.url_en=@res_url_en    AND r.url_fr=@res_url_fr);
  IF v_count != @expected_res THEN
    SET v_msg = CONCAT('VALIDATION FAILED (', p_ctx, '): Resource titles or URLs do not match expected values.');
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_msg;
  END IF;

  -- 13. Destination A: exact cat, svc, sub, outcome
  SET v_count = (SELECT COUNT(*) FROM tblintakenodes WHERE flow_id=p_flow_id AND status=1
    AND node_type='destination' AND outcome_code=@outcome_a
    AND target_catalogueid=@dest_a_catalogueid
    AND target_serviceid=@dest_a_serviceid
    AND target_subserviceid=@dest_a_subserviceid);
  IF v_count != 1 THEN
    SET v_msg = CONCAT('VALIDATION FAILED (', p_ctx, '): Destination A wrong catalogue/service/subservice/outcome.');
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_msg;
  END IF;

  -- 14. Destination B: exact cat, svc, sub, outcome
  SET v_count = (SELECT COUNT(*) FROM tblintakenodes WHERE flow_id=p_flow_id AND status=1
    AND node_type='destination' AND outcome_code=@outcome_b
    AND target_catalogueid=@dest_b_catalogueid
    AND target_serviceid=@dest_b_serviceid
    AND target_subserviceid=@dest_b_subserviceid);
  IF v_count != 1 THEN
    SET v_msg = CONCAT('VALIDATION FAILED (', p_ctx, '): Destination B wrong catalogue/service/subservice/outcome.');
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_msg;
  END IF;

  -- 15. Reference classifications still active and correctly related (full hierarchy)
  SET v_count = (SELECT COUNT(*) FROM tblcatalogue WHERE id=@attach_catalogueid AND status=1);
  IF v_count != 1 THEN
    SET v_msg = CONCAT('VALIDATION FAILED (', p_ctx, '): attach_catalogueid inactive or missing.');
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_msg;
  END IF;
  SET v_count = (SELECT COUNT(*) FROM tblservices
    WHERE id=@attach_serviceid AND catalogueid=@attach_catalogueid AND status=1);
  IF v_count != 1 THEN
    SET v_msg = CONCAT('VALIDATION FAILED (', p_ctx, '): attach_serviceid inactive, missing, or wrong catalogue.');
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_msg;
  END IF;
  -- Destination A: catalogue → service → subservice
  SET v_count = (SELECT COUNT(*) FROM tblcatalogue WHERE id=@dest_a_catalogueid AND status=1);
  IF v_count != 1 THEN
    SET v_msg = CONCAT('VALIDATION FAILED (', p_ctx, '): dest_a_catalogueid inactive or missing.');
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_msg;
  END IF;
  SET v_count = (SELECT COUNT(*) FROM tblservices
    WHERE id=@dest_a_serviceid AND catalogueid=@dest_a_catalogueid AND status=1);
  IF v_count != 1 THEN
    SET v_msg = CONCAT('VALIDATION FAILED (', p_ctx, '): dest_a_serviceid inactive, missing, or wrong catalogue.');
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_msg;
  END IF;
  SET v_count = (SELECT COUNT(*) FROM tblsubservices
    WHERE id=@dest_a_subserviceid AND serviceid=@dest_a_serviceid AND status=1);
  IF v_count != 1 THEN
    SET v_msg = CONCAT('VALIDATION FAILED (', p_ctx, '): dest_a_subserviceid inactive or wrong service.');
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_msg;
  END IF;
  -- Destination B: catalogue → service → subservice
  SET v_count = (SELECT COUNT(*) FROM tblcatalogue WHERE id=@dest_b_catalogueid AND status=1);
  IF v_count != 1 THEN
    SET v_msg = CONCAT('VALIDATION FAILED (', p_ctx, '): dest_b_catalogueid inactive or missing.');
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_msg;
  END IF;
  SET v_count = (SELECT COUNT(*) FROM tblservices
    WHERE id=@dest_b_serviceid AND catalogueid=@dest_b_catalogueid AND status=1);
  IF v_count != 1 THEN
    SET v_msg = CONCAT('VALIDATION FAILED (', p_ctx, '): dest_b_serviceid inactive, missing, or wrong catalogue.');
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_msg;
  END IF;
  SET v_count = (SELECT COUNT(*) FROM tblsubservices
    WHERE id=@dest_b_subserviceid AND serviceid=@dest_b_serviceid AND status=1);
  IF v_count != 1 THEN
    SET v_msg = CONCAT('VALIDATION FAILED (', p_ctx, '): dest_b_subserviceid inactive or wrong service.');
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_msg;
  END IF;

END tpl_val$$

-- =============================================================================
-- Main install procedure
-- =============================================================================
DROP PROCEDURE IF EXISTS _rmt_seed_flow_install$$

CREATE PROCEDURE _rmt_seed_flow_install()
proc: BEGIN

  DECLARE v_flow_id     INT DEFAULT NULL;
  DECLARE v_node_q1     INT DEFAULT NULL;
  DECLARE v_node_q2     INT DEFAULT NULL;
  DECLARE v_node_dest_a INT DEFAULT NULL;
  DECLARE v_node_dest_b INT DEFAULT NULL;
  DECLARE v_node_guid_a INT DEFAULT NULL;
  DECLARE v_node_count  INT DEFAULT 0;
  DECLARE v_opt_count   INT DEFAULT 0;
  DECLARE v_res_count   INT DEFAULT 0;
  DECLARE v_svc_fid     INT DEFAULT NULL;
  DECLARE v_existing_fid INT DEFAULT NULL;

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    RESIGNAL;
  END;

  -- ================================================================
  -- Step 1: Reject every unconfigured placeholder value and blank
  -- ================================================================
  -- Identity
  IF @flow_family_key = 'CHANGE.ME.flow-name' OR TRIM(@flow_family_key) = '' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ERROR: flow_family_key must be configured and non-blank.';
  END IF;
  IF @flow_version < 1 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ERROR: flow_version must be >= 1.';
  END IF;
  IF @flow_name_en LIKE 'CHANGE ME%' OR TRIM(@flow_name_en) = '' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ERROR: flow_name_en must be configured and non-blank.';
  END IF;
  IF @flow_name_fr LIKE 'CHANGE ME%' OR TRIM(@flow_name_fr) = '' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ERROR: flow_name_fr must be configured and non-blank.';
  END IF;
  -- Classification IDs
  IF @attach_catalogueid = 0 OR @attach_serviceid = 0
    OR @dest_a_catalogueid = 0 OR @dest_a_serviceid = 0 OR @dest_a_subserviceid = 0
    OR @dest_b_catalogueid = 0 OR @dest_b_serviceid = 0 OR @dest_b_subserviceid = 0 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ERROR: All classification IDs must be non-zero.';
  END IF;
  -- Outcome codes
  IF @outcome_a LIKE 'CHANGE_ME%' OR TRIM(@outcome_a) = '' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ERROR: outcome_a must be configured and non-blank.';
  END IF;
  IF @outcome_b LIKE 'CHANGE_ME%' OR TRIM(@outcome_b) = '' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ERROR: outcome_b must be configured and non-blank.';
  END IF;
  -- Node prompts
  IF @q1_prompt_en LIKE 'CHANGE ME%' OR TRIM(@q1_prompt_en) = '' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ERROR: q1_prompt_en must be configured and non-blank.';
  END IF;
  IF @q1_prompt_fr LIKE 'CHANGE ME%' OR TRIM(@q1_prompt_fr) = '' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ERROR: q1_prompt_fr must be configured and non-blank.';
  END IF;
  IF @q2_prompt_en LIKE 'CHANGE ME%' OR TRIM(@q2_prompt_en) = '' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ERROR: q2_prompt_en must be configured and non-blank.';
  END IF;
  IF @q2_prompt_fr LIKE 'CHANGE ME%' OR TRIM(@q2_prompt_fr) = '' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ERROR: q2_prompt_fr must be configured and non-blank.';
  END IF;
  -- Guidance heading and body
  IF @guid_a_heading_en LIKE 'CHANGE ME%' OR TRIM(@guid_a_heading_en) = '' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ERROR: guid_a_heading_en must be configured and non-blank.';
  END IF;
  IF @guid_a_heading_fr LIKE 'CHANGE ME%' OR TRIM(@guid_a_heading_fr) = '' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ERROR: guid_a_heading_fr must be configured and non-blank.';
  END IF;
  IF @guid_a_body_en LIKE 'CHANGE ME%' OR TRIM(@guid_a_body_en) = '' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ERROR: guid_a_body_en must be configured and non-blank.';
  END IF;
  IF @guid_a_body_fr LIKE 'CHANGE ME%' OR TRIM(@guid_a_body_fr) = '' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ERROR: guid_a_body_fr must be configured and non-blank.';
  END IF;
  -- Resource titles
  IF @res_title_en LIKE 'CHANGE ME%' OR TRIM(@res_title_en) = '' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ERROR: res_title_en must be configured and non-blank.';
  END IF;
  IF @res_title_fr LIKE 'CHANGE ME%' OR TRIM(@res_title_fr) = '' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ERROR: res_title_fr must be configured and non-blank.';
  END IF;
  -- Resource URLs: EN required; FR optional (NULL/empty = fallback to EN)
  IF @res_url_en LIKE '%CHANGE.ME%' OR TRIM(@res_url_en) = '' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ERROR: res_url_en must be configured and non-blank.';
  END IF;
  IF @res_url_en NOT LIKE 'https://%' AND @res_url_en NOT LIKE 'mailto:%' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ERROR: res_url_en must begin with https:// or mailto:.';
  END IF;
  IF @res_url_fr LIKE '%CHANGE.ME%' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ERROR: res_url_fr placeholder must be replaced (use empty string for EN fallback).';
  END IF;
  IF @res_url_fr IS NOT NULL AND TRIM(@res_url_fr) != ''
     AND @res_url_fr NOT LIKE 'https://%' AND @res_url_fr NOT LIKE 'mailto:%' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ERROR: res_url_fr must begin with https:// or mailto: (or be empty/NULL for EN fallback).';
  END IF;
  -- Option labels (all 8 must be non-blank)
  IF TRIM(@q1_opt1_en) = '' OR TRIM(@q1_opt1_fr) = '' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ERROR: q1_opt1 labels must be non-blank.';
  END IF;
  IF TRIM(@q1_opt2_en) = '' OR TRIM(@q1_opt2_fr) = '' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ERROR: q1_opt2 labels must be non-blank.';
  END IF;
  IF TRIM(@q2_opt1_en) = '' OR TRIM(@q2_opt1_fr) = '' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ERROR: q2_opt1 labels must be non-blank.';
  END IF;
  IF TRIM(@q2_opt2_en) = '' OR TRIM(@q2_opt2_fr) = '' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ERROR: q2_opt2 labels must be non-blank.';
  END IF;

  -- ================================================================
  -- Step 2: Verify migration 016 is applied
  -- ================================================================
  IF (SELECT COUNT(*) FROM information_schema.TABLES
      WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='tblintakeflows') = 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'ERROR: tblintakeflows does not exist. Apply migration 016-intake-flows.sql first.';
  END IF;

  -- ================================================================
  -- Step 3: Pre-validate reference data (full hierarchy for each path)
  -- ================================================================
  -- Attachment path
  IF (SELECT COUNT(*) FROM tblcatalogue WHERE id=@attach_catalogueid AND status=1) = 0 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ERROR: attach_catalogueid not found or inactive.';
  END IF;
  IF (SELECT COUNT(*) FROM tblservices
      WHERE id=@attach_serviceid AND catalogueid=@attach_catalogueid AND status=1) = 0 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ERROR: attach_serviceid not found, inactive, or wrong catalogue.';
  END IF;
  -- Destination A: catalogue → service → subservice
  IF (SELECT COUNT(*) FROM tblcatalogue WHERE id=@dest_a_catalogueid AND status=1) = 0 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ERROR: dest_a_catalogueid not found or inactive.';
  END IF;
  IF (SELECT COUNT(*) FROM tblservices
      WHERE id=@dest_a_serviceid AND catalogueid=@dest_a_catalogueid AND status=1) = 0 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ERROR: dest_a_serviceid not found, inactive, or wrong catalogue.';
  END IF;
  IF (SELECT COUNT(*) FROM tblsubservices
      WHERE id=@dest_a_subserviceid AND serviceid=@dest_a_serviceid AND status=1) = 0 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ERROR: dest_a_subserviceid not found, inactive, or wrong service.';
  END IF;
  -- Destination B: catalogue → service → subservice
  IF (SELECT COUNT(*) FROM tblcatalogue WHERE id=@dest_b_catalogueid AND status=1) = 0 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ERROR: dest_b_catalogueid not found or inactive.';
  END IF;
  IF (SELECT COUNT(*) FROM tblservices
      WHERE id=@dest_b_serviceid AND catalogueid=@dest_b_catalogueid AND status=1) = 0 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ERROR: dest_b_serviceid not found, inactive, or wrong catalogue.';
  END IF;
  IF (SELECT COUNT(*) FROM tblsubservices
      WHERE id=@dest_b_subserviceid AND serviceid=@dest_b_serviceid AND status=1) = 0 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ERROR: dest_b_subserviceid not found, inactive, or wrong service.';
  END IF;

  -- ================================================================
  -- Step 4: Idempotency check
  -- ================================================================
  SET v_flow_id = (
    SELECT id FROM tblintakeflows
    WHERE flow_family_key=@flow_family_key AND version_number=@flow_version
    LIMIT 1
  );

  IF v_flow_id IS NOT NULL THEN
    IF (SELECT status FROM tblintakeflows WHERE id=v_flow_id) != 1 THEN
      SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'ERROR: A draft/partial version already exists. Clean up manually and re-run.';
    END IF;

    -- Run full graph validation on the existing published flow.
    CALL _rmt_tpl_validate_graph(v_flow_id, 'published');

    -- Service must point to THIS exact flow.
    SET v_svc_fid = (SELECT intake_flow_id FROM tblservices WHERE id=@attach_serviceid);
    IF v_svc_fid IS NULL OR v_svc_fid != v_flow_id THEN
      SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'VALIDATION FAILED (published): Service is not attached to this exact flow.';
    END IF;

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
  -- Step 5: Service must not already be attached
  -- ================================================================
  SET v_existing_fid = (SELECT intake_flow_id FROM tblservices WHERE id=@attach_serviceid);
  IF v_existing_fid IS NOT NULL THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'ERROR: Service already has intake_flow_id set. Cannot overwrite without removing it.';
  END IF;

  -- ================================================================
  -- Step 6: Build draft graph
  -- @@CONFIGURE (update if graph changes): nodes, options, resources
  -- ================================================================
  START TRANSACTION;

  INSERT INTO tblintakeflows (nameen, namefr, flow_family_key, version_number, status)
  VALUES (@flow_name_en, @flow_name_fr, @flow_family_key, @flow_version, 0);
  SET v_flow_id = LAST_INSERT_ID();

  -- Q1 (sort_order=1)
  INSERT INTO tblintakenodes (flow_id, node_type, sort_order, presentation, prompt_en, prompt_fr)
  VALUES (v_flow_id, 'question', 1, 'radio', @q1_prompt_en, @q1_prompt_fr);
  SET v_node_q1 = LAST_INSERT_ID();

  -- Q2 (sort_order=2)
  INSERT INTO tblintakenodes (flow_id, node_type, sort_order, presentation, prompt_en, prompt_fr)
  VALUES (v_flow_id, 'question', 2, 'radio', @q2_prompt_en, @q2_prompt_fr);
  SET v_node_q2 = LAST_INSERT_ID();

  -- DEST_A (sort_order=3)
  INSERT INTO tblintakenodes (flow_id, node_type, sort_order,
    target_catalogueid, target_serviceid, target_subserviceid, outcome_code)
  VALUES (v_flow_id, 'destination', 3,
    @dest_a_catalogueid, @dest_a_serviceid, @dest_a_subserviceid, @outcome_a);
  SET v_node_dest_a = LAST_INSERT_ID();

  -- DEST_B (sort_order=4)
  INSERT INTO tblintakenodes (flow_id, node_type, sort_order,
    target_catalogueid, target_serviceid, target_subserviceid, outcome_code)
  VALUES (v_flow_id, 'destination', 4,
    @dest_b_catalogueid, @dest_b_serviceid, @dest_b_subserviceid, @outcome_b);
  SET v_node_dest_b = LAST_INSERT_ID();

  -- GUID_A (sort_order=5)
  INSERT INTO tblintakenodes (flow_id, node_type, sort_order,
    heading_en, heading_fr, body_en, body_fr)
  VALUES (v_flow_id, 'guidance', 5,
    @guid_a_heading_en, @guid_a_heading_fr, @guid_a_body_en, @guid_a_body_fr);
  SET v_node_guid_a = LAST_INSERT_ID();

  -- Set start node
  UPDATE tblintakeflows SET start_node_id=v_node_q1 WHERE id=v_flow_id;

  -- Options (@@CONFIGURE: update if graph changes)
  INSERT INTO tblintakeoptions (node_id, labelen, labelfr, next_node_id, sort_order)
  VALUES
    (v_node_q1, @q1_opt1_en, @q1_opt1_fr, v_node_dest_a, 1),
    (v_node_q1, @q1_opt2_en, @q1_opt2_fr, v_node_q2,     2),
    (v_node_q2, @q2_opt1_en, @q2_opt1_fr, v_node_dest_b, 1),
    (v_node_q2, @q2_opt2_en, @q2_opt2_fr, v_node_guid_a, 2);

  -- Resource (@@CONFIGURE: update if graph changes)
  INSERT INTO tblintakeresources (node_id, titleen, titlefr, url_en, url_fr, sort_order)
  VALUES (v_node_guid_a, @res_title_en, @res_title_fr, @res_url_en, @res_url_fr, 1);

  -- ================================================================
  -- Step 7: Run full graph validation on the draft
  -- ================================================================
  CALL _rmt_tpl_validate_graph(v_flow_id, 'new-flow');

  -- ================================================================
  -- Step 8: Publish and attach; verify before COMMIT
  -- ================================================================
  UPDATE tblintakeflows SET status=1 WHERE id=v_flow_id;
  UPDATE tblservices SET intake_flow_id=v_flow_id WHERE id=@attach_serviceid;

  IF (SELECT status FROM tblintakeflows WHERE id=v_flow_id) != 1 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'ERROR (new-flow): status UPDATE did not persist.';
  END IF;
  SET v_svc_fid = (SELECT intake_flow_id FROM tblservices WHERE id=@attach_serviceid);
  IF v_svc_fid IS NULL OR v_svc_fid != v_flow_id THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'ERROR (new-flow): service attachment did not persist.';
  END IF;

  COMMIT;

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
    ', nodes=', v_node_count,
    ', options=', v_opt_count,
    ', resources=', v_res_count
  ) AS message;

END proc$$
DELIMITER ;

CALL _rmt_seed_flow_install();
DROP PROCEDURE IF EXISTS _rmt_seed_flow_install;
DROP PROCEDURE IF EXISTS _rmt_tpl_validate_graph;
