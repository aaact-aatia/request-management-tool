<?php
/**
 * Intake Flow Runtime Controller — Phase 2A (rev 5)
 *
 * Dual-mode handler:
 *   AJAX mode  (_ajax=1 in POST body) — returns JSON with HTML fragments.
 *   Non-AJAX                          — POST/redirect fallback for no-JS.
 *
 * Session shutdown: rmt_intake_clean_exit($link) is always called before
 * output; it calls session_write_close() first so the MySQL session handler
 * never tries to write to a closed connection.
 *
 * AJAX actions:
 *   start       POST catalogueid, serviceid?, form_csrf
 *               → {success, run_token, csrf, fragment, node_type, revision}
 *   step        POST run_token, csrf_token, node_id, option_id, client_revision
 *               → {success, run_token, answered_node_id, fragment, node_type, revision}
 *               client_revision must equal the run's current revision counter;
 *               a mismatch is rejected with error=stale_revision. This guards
 *               against overlapping/out-of-order session writes even when a
 *               client aborts an older fetch() — aborting a fetch does not
 *               stop PHP from finishing the request it already started.
 *   reconstruct POST run_token, csrf_token, lang?
 *               → {success, run_token, csrf, fragments, node_type, revision}
 *               (rebuilds the full #intake-workflow path in the requested
 *               language; available for programmatic use, but the client
 *               language switch in openrequest.js now performs a full page
 *               navigation to openrequest.php?lang=..&run=.. instead of
 *               calling this action)
 *   restart     POST run_token, csrf_token
 *               → {success: true}
 *
 * GET ?run=TOKEN         → forward to openrequest.php?run=TOKEN (no-JS rendering)
 * GET ?run=T&lang=xx     → language switch, redirect to openrequest.php?lang=xx&run=T
 *
 * @package RMT
 * @since 2.1.0  (Phase 2A)
 */

require_once __DIR__ . '/includes/session_start.php';
require('includes/httpscheck.php');
require('sql.php');
/** @var mysqli $link */
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/intake-flow-helpers.php';
require_once __DIR__ . '/vendor/autoload.php';

use League\CommonMark\CommonMarkConverter;

require('includes/loggedincheck.php');

$mdConverter = new CommonMarkConverter(['html_input' => 'strip', 'allow_unsafe_links' => false]);
$lang        = detectLanguage();
$isFr        = ($lang === 'fr');
$isAjax      = (($_POST['_ajax'] ?? '') === '1');

// Load translations once — used for error messages and fragment rendering
$t = require("lang/{$lang}.php");

// ============================================================================
// Local helpers
// ============================================================================

function rmt_intake_redirect_error(string $lang, string $reason = 'invalid'): void
{
    header("Location: /openrequest.php?lang={$lang}&intake_error=" . urlencode($reason));
    exit;
}

function rmt_ajax_error(mysqli $link, string $error, string $msgEn, string $msgFr = ''): void
{
    global $isFr;
    $msg = ($isFr && $msgFr !== '') ? $msgFr : $msgEn;
    rmt_intake_clean_exit($link);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'error' => $error, 'message' => $msg]);
    exit;
}

function rmt_ajax_ok(mysqli $link, array $data): void
{
    rmt_intake_clean_exit($link);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

// ============================================================================
// Language switch on GET
// ============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'GET'
    && isset($_GET['lang'])
    && in_array($_GET['lang'], ['en', 'fr'], true)
    && isset($_GET['run'])
) {
    $token = (string) $_GET['run'];
    if (!preg_match('/^[0-9a-f]{32}$/i', $token)) {
        rmt_intake_clean_exit($link);
        header("Location: /openrequest.php?lang={$lang}");
        exit;
    }
    $token = strtolower($token);
    $run   = rmt_intake_get_run($token);
    if ($run) {
        $run['lang'] = $_GET['lang'];
        rmt_intake_save_run($token, $run);
        $_SESSION['lang'] = $_GET['lang']; // set BEFORE write_close
    }
    rmt_intake_clean_exit($link);
    header("Location: /openrequest.php?lang=" . urlencode($_GET['lang']) . "&run=" . urlencode($token));
    exit;
}

// GET ?run=TOKEN — forward to openrequest.php for no-JS rendering
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['run'])) {
    $token = (string) $_GET['run'];
    $valid = preg_match('/^[0-9a-f]{32}$/i', $token) ? strtolower($token) : '';
    rmt_intake_clean_exit($link);
    header("Location: /openrequest.php?lang={$lang}" . ($valid
        ? '&run=' . urlencode($valid)
        : '&intake_error=invalid'));
    exit;
}

// ============================================================================
// POST dispatch
// ============================================================================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    rmt_intake_clean_exit($link);
    header("Location: /openrequest.php?lang={$lang}");
    exit;
}

$action = trim((string) ($_POST['action'] ?? ''));

// ----------------------------------------------------------------------------
// action=start
// ----------------------------------------------------------------------------
if ($action === 'start') {
    $formCsrf = (string) ($_POST['form_csrf'] ?? '');
    $expected = (string) ($_SESSION['openrequest_csrf'] ?? '');
    if ($expected === '' || !hash_equals($expected, $formCsrf)) {
        if ($isAjax) { rmt_ajax_error($link, 'csrf',
            $t['intake_error_csrf'] ?? 'The request was not valid.', ''); }
        rmt_intake_clean_exit($link);
        rmt_intake_redirect_error($lang, 'csrf');
    }

    $catalogueid  = (int) ($_POST['catalogueid'] ?? 0);
    $serviceid    = rmt_optional_positive_int($_POST['serviceid']    ?? null);
    $subserviceid = rmt_optional_positive_int($_POST['subserviceid'] ?? null);

    if ($catalogueid <= 0) {
        $mInvalid = $t['intake_error_invalid'] ?? 'An error occurred. Please start over.';
        if ($isAjax) { rmt_ajax_error($link, 'invalid', $mInvalid); }
        rmt_intake_clean_exit($link);
        rmt_intake_redirect_error($lang, 'invalid');
    }

    $resolution = rmt_intake_resolve_flow($link, $catalogueid, $serviceid, $subserviceid);

    if ($resolution['result'] === RMT_INTAKE_RESOLVE_INVALID) {
        $m = $t['intake_error_flow_invalid'] ?? 'This service is currently unavailable.';
        if ($isAjax) { rmt_ajax_error($link, 'flow_invalid', $m); }
        rmt_intake_clean_exit($link);
        rmt_intake_redirect_error($lang, 'flow_invalid');
    }
    if ($resolution['result'] !== RMT_INTAKE_RESOLVE_OK) {
        $mNoFlow = $t['intake_error_invalid'] ?? 'An error occurred. Please start over.';
        if ($isAjax) { rmt_ajax_error($link, 'no_flow', $mNoFlow); }
        rmt_intake_clean_exit($link);
        rmt_intake_redirect_error($lang, 'no_flow');
    }

    $token     = rmt_intake_create_run($resolution['flow'], $lang);
    $run       = rmt_intake_get_run($token);
    $firstNode = rmt_intake_load_node($link, (int) $run['flow_id'], (int) $run['start_node_id']);

    if (!$firstNode) {
        $mMissing = $t['intake_error_missing_node'] ?? 'This step could not be completed. Please start over.';
        if ($isAjax) { rmt_ajax_error($link, 'invalid', $mMissing); }
        rmt_intake_clean_exit($link);
        rmt_intake_redirect_error($lang, 'invalid');
    }

    if ($isAjax) {
        $fragment = rmt_intake_render_node_fragment($link, $firstNode, $run, $token, $lang, $mdConverter, $t);
        rmt_ajax_ok($link, [
            'success'   => true,
            'run_token' => $token,
            'csrf'      => rmt_intake_csrf_token($run),
            'node_type' => $firstNode['node_type'],
            'fragment'  => $fragment,
            'revision'  => (int) ($run['revision'] ?? 0),
        ]);
    }

    // redirect fallback (no-JS)
    rmt_intake_clean_exit($link);
        header("Location: /openrequest.php?lang=" . urlencode($lang) . "&run=" . urlencode($token)
            . '#' . urlencode(rmt_intake_node_focus_id($firstNode)));
    exit;
}

// All other actions require a validated run token + per-run CSRF
$rawToken = trim((string) ($_POST['run_token'] ?? ''));
if ($rawToken === '' || !preg_match('/^[0-9a-f]{32}$/i', $rawToken)) {
    $mBadToken = $t['intake_error_invalid'] ?? 'An error occurred. Please start over.';
    if ($isAjax) { rmt_ajax_error($link, 'invalid', $mBadToken); }
    rmt_intake_clean_exit($link);
    rmt_intake_redirect_error($lang, 'invalid');
}
$token = strtolower($rawToken);

$run = rmt_intake_get_run($token);
if (!$run) {
    $mExp = $t['intake_error_expired'] ?? 'Session expired.';
    if ($isAjax) { rmt_ajax_error($link, 'expired', $mExp, $mExp); }
    rmt_intake_clean_exit($link);
    rmt_intake_redirect_error($lang, 'expired');
}

$runLang       = $run['lang'];
$csrfSubmitted = $_POST['csrf_token'] ?? null;

if (!rmt_intake_verify_csrf($run, $csrfSubmitted)) {
    $mCsrf = $t['intake_error_csrf'] ?? 'Invalid request.';
    if ($isAjax) { rmt_ajax_error($link, 'csrf', $mCsrf, $mCsrf); }
    rmt_intake_clean_exit($link);
    rmt_intake_redirect_error($runLang, 'csrf');
}

// ----------------------------------------------------------------------------
// action=restart
// ----------------------------------------------------------------------------
if ($action === 'restart') {
    rmt_intake_discard_run($token);
    if ($isAjax) { rmt_ajax_ok($link, ['success' => true]); }
    rmt_intake_clean_exit($link);
    header("Location: /openrequest.php?lang={$runLang}");
    exit;
}

// ----------------------------------------------------------------------------
// action=reconstruct — rebuild the visible path in the requested language
// Used by the JS language switcher; returns all fragments as one HTML string.
// ----------------------------------------------------------------------------
if ($action === 'reconstruct') {
    // Optionally update the run's language
    $newLang = trim((string) ($_POST['lang'] ?? $runLang));
    if (in_array($newLang, ['en', 'fr'], true) && $newLang !== $runLang) {
        $run['lang'] = $newLang;
        rmt_intake_save_run($token, $run);
        $runLang = $newLang;
    }

    // Load translations for the target language
    $tRecon = require("lang/{$runLang}.php");

    $currentNodeId = (int) end($run['history']);
    $currentNode   = rmt_intake_load_node($link, (int) $run['flow_id'], $currentNodeId);
    $nodeType      = $currentNode ? $currentNode['node_type'] : 'question';

    $fragments = rmt_intake_render_full_path(
        $link, $run, $token, $runLang, $mdConverter, $tRecon, false
    );

    rmt_ajax_ok($link, [
        'success'   => true,
        'run_token' => $token,
        'csrf'      => rmt_intake_csrf_token($run),
        'node_type' => $nodeType,
        'fragments' => $fragments,
        'lang'      => $runLang,
        'revision'  => (int) ($run['revision'] ?? 0),
    ]);
}

// ----------------------------------------------------------------------------
// action=step — process an answer (new or changed)
// ----------------------------------------------------------------------------
if ($action === 'step') {
    $postedNodeId   = (int) ($_POST['node_id']   ?? 0);
    $postedOptionId = (int) ($_POST['option_id'] ?? 0);

    // Load translations for the run language
    $tRun = require("lang/{$runLang}.php");

    // Validate posted node is in the run history
    if ($postedNodeId <= 0 || !in_array($postedNodeId, $run['history'], true)) {
        $mInv = $tRun['intake_error_invalid'] ?? 'Invalid node.';
        if ($isAjax) { rmt_ajax_error($link, 'invalid', $mInv, $mInv); }
        rmt_intake_clean_exit($link);
        rmt_intake_redirect_error($runLang, 'invalid');
    }

    $node = rmt_intake_load_node($link, (int) $run['flow_id'], $postedNodeId);
    if (!$node || $node['node_type'] !== 'question') {
        $mInv = $tRun['intake_error_invalid'] ?? 'Invalid node.';
        if ($isAjax) { rmt_ajax_error($link, 'invalid', $mInv, $mInv); }
        rmt_intake_clean_exit($link);
        rmt_intake_redirect_error($runLang, 'invalid');
    }

    // Optimistic-concurrency guard: the client must echo back the revision it
    // last observed. A mismatch means either a stale request (an older,
    // client-aborted fetch whose PHP execution nonetheless kept running to
    // completion — aborting fetch() client-side does not stop server-side
    // processing) or a genuinely out-of-date page. Either way the write is
    // rejected rather than risking an overlapping/out-of-order session write.
    $clientRevision = isset($_POST['client_revision']) ? (int) $_POST['client_revision'] : -1;
    if (!rmt_intake_revision_is_current($run, $clientRevision)) {
        $mStale = $tRun['intake_error_stale'] ?? 'This page is out of date. Please refresh and try again.';
        if ($isAjax) { rmt_ajax_error($link, 'stale_revision', $mStale, $mStale); }
        rmt_intake_clean_exit($link);
        rmt_intake_redirect_error($runLang, 'invalid');
    }

    // Validate option belongs to this node and is active
    $matchedOption = null;
    foreach ($node['options'] as $opt) {
        if ((int) $opt['id'] === $postedOptionId && (int) $opt['status'] === 1) {
            $matchedOption = $opt;
            break;
        }
    }
    if (!$matchedOption) {
        $mOpt = $tRun['intake_error_option_required'] ?? 'Please select an option.';
        if ($isAjax) { rmt_ajax_error($link, 'invalid_option', $mOpt, $mOpt); }
        rmt_intake_clean_exit($link);
        rmt_intake_redirect_error($runLang, 'invalid');
    }

    // Freeform (for the selected option only; length-bound server-side)
    $ffField = 'freeform_opt_' . $postedOptionId;
    $postedFreeformRaw = isset($_POST[$ffField])
        ? mb_substr((string) $_POST[$ffField], 0, RMT_INTAKE_FREEFORM_MAX_LEN)
        : null;
    $postedFreeform = $postedFreeformRaw !== null ? trim($postedFreeformRaw) : null;

    if (!empty($matchedOption['freeform_required'])
        && ($postedFreeform === null || $postedFreeform === '')) {
        $mFf = $tRun['intake_error_freeform_required'] ?? 'Please enter text in this field.';
        if ($isAjax) { rmt_ajax_error($link, 'freeform_required', $mFf, $mFf); }

        // Preserve the active run and the visitor's attempted values for the
        // no-JS validation round trip. The renderer uses this state to select
        // the submitted option, retain its text, and produce an accessible
        // error summary linked to the invalid textarea. The committed answer
        // and downstream history are intentionally left unchanged.
        $fieldId = 'intake-ff-' . $postedNodeId . '-' . $postedOptionId . '-text';
        $run['_validation_node_id']  = $postedNodeId;
        $run['_validation_option_id'] = $postedOptionId;
        $run['_validation_freeform'] = $postedFreeformRaw ?? '';
        $run['_validation_errors']   = [$fieldId => $mFf];
        rmt_intake_save_run($token, $run);

        rmt_intake_clean_exit($link);
        header('Location: /openrequest.php?lang=' . urlencode($runLang)
               . '&run=' . urlencode($token)
               . '#intake-validation-summary-' . $postedNodeId);
        exit;
    }

    $nextNodeId = (int) $matchedOption['next_node_id'];
    if ($nextNodeId <= 0) {
        $mMissing = $tRun['intake_error_missing_node'] ?? 'This step could not be completed. Please start over.';
        if ($isAjax) { rmt_ajax_error($link, 'invalid', $mMissing, $mMissing); }
        rmt_intake_clean_exit($link);
        rmt_intake_redirect_error($runLang, 'invalid');
    }

    $nextNode = rmt_intake_load_node($link, (int) $run['flow_id'], $nextNodeId);
    if (!$nextNode) {
        $mMissing = $tRun['intake_error_missing_node'] ?? 'This step could not be completed. Please start over.';
        if ($isAjax) { rmt_ajax_error($link, 'invalid', $mMissing, $mMissing); }
        rmt_intake_clean_exit($link);
        rmt_intake_redirect_error($runLang, 'invalid');
    }

    $freeformToStore = (!empty($matchedOption['allow_freeform'])
                       && $postedFreeform !== null && $postedFreeform !== '')
        ? $postedFreeform : null;

    // rmt_intake_answer_node handles re-answering with automatic downstream truncation
    rmt_intake_answer_node($run, $postedNodeId, $postedOptionId, $nextNodeId, $freeformToStore);
    rmt_intake_save_run($token, $run);

    if ($isAjax) {
        $fragment = rmt_intake_render_node_fragment($link, $nextNode, $run, $token, $runLang, $mdConverter, $tRun);
        rmt_ajax_ok($link, [
            'success'          => true,
            'run_token'        => $token,
            'answered_node_id' => $postedNodeId,
            'node_type'        => $nextNode['node_type'],
            'fragment'         => $fragment,
            'revision'         => (int) ($run['revision'] ?? 0),
        ]);
    }

    // redirect fallback (no-JS)
    rmt_intake_clean_exit($link);
        header("Location: /openrequest.php?lang=" . urlencode($runLang) . "&run=" . urlencode($token)
            . '#' . urlencode(rmt_intake_node_focus_id($nextNode)));
    exit;
}

// Unknown action
$mInv = $t['intake_error_invalid'] ?? 'An error occurred.';
if ($isAjax) { rmt_ajax_error($link, 'invalid', $mInv, $mInv); }
rmt_intake_clean_exit($link);
rmt_intake_redirect_error($lang, 'invalid');
