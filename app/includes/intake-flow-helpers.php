<?php
/**
 * Intake Flow Helpers — Phase 2A
 *
 * Read-only resolver, session management, security validation, and
 * rendering helpers for the configurable intake flow runtime.
 *
 * Conventions:
 *  - All DB reads use prepared statements.
 *  - Flow/node/option IDs are always resolved from the database;
 *    values from browser input are treated as untrusted references only.
 *  - Session state is scoped per opaque run token so concurrent browser
 *    tabs do not overwrite each other.
 *
 * @package RMT
 * @since 2.1.0  (Phase 2A)
 */

if (isset($_SERVER['SCRIPT_FILENAME']) && realpath(__FILE__) === realpath((string) $_SERVER['SCRIPT_FILENAME'])) {
    http_response_code(404);
    exit();
}

// ============================================================================
// Constants
// ============================================================================

/** Maximum concurrent intake runs stored per user session. */
define('RMT_INTAKE_MAX_RUNS', 5);

/** Session key under which all runs are stored. */
define('RMT_INTAKE_SESSION_KEY', 'intake_runs');

/** Maximum age of a run (seconds) before it is treated as expired. */
define('RMT_INTAKE_RUN_TTL', 7200); // 2 hours

/** Maximum characters stored for an option-level free-form answer. */
define('RMT_INTAKE_FREEFORM_MAX_LEN', 2000);

/**
 * Run-resolution result codes returned by rmt_intake_resolve_flow().
 * RMT_INTAKE_RESOLVE_OK      — resolved and returned in 'flow' key.
 * RMT_INTAKE_RESOLVE_NONE    — no flow attached at any applicable level.
 * RMT_INTAKE_RESOLVE_INVALID — a flow IS attached at the most-specific level
 *                              but it is draft, archived, missing, or structurally
 *                              invalid. Must NOT fall through to a less-specific level.
 */
define('RMT_INTAKE_RESOLVE_OK',      'ok');
define('RMT_INTAKE_RESOLVE_NONE',    'none');
define('RMT_INTAKE_RESOLVE_INVALID', 'invalid');


/**
 * Return whether a submitted client revision matches the current run state.
 */
function rmt_intake_revision_is_current(array $run, int $clientRevision): bool
{
    return $clientRevision === (int) ($run['revision'] ?? 0);
}

/**
 * Return the element ID that should receive focus when a no-JS node loads.
 */
function rmt_intake_node_focus_id(array $node): string
{
    $nodeId = (int) ($node['id'] ?? 0);
    if (($node['node_type'] ?? '') === 'question') {
        if (($node['presentation'] ?? 'select') === 'select') {
            return 'intake-q-' . $nodeId;
        }

        $firstOption = $node['options'][0] ?? null;
        if (is_array($firstOption)) {
            return 'intake-radio-' . $nodeId . '-' . (int) ($firstOption['id'] ?? 0);
        }
    }

    return 'intake-focus-' . $nodeId;
}


// ============================================================================
// Flow resolution
// ============================================================================

/**
 * Resolve the most-specific published intake flow for a classification triple.
 *
 * Resolution order (most specific first): subservice → service → catalogue.
 * If a flow IS attached at a level but is draft/archived/invalid, resolution
 * stops and returns RMT_INTAKE_RESOLVE_INVALID — it does NOT fall through.
 *
 * Full hierarchy is validated at each level:
 *   subservice → service (belongs to catalogueid) → catalogue
 *
 * @param mysqli   $link
 * @param int      $catalogueid
 * @param int|null $serviceid
 * @param int|null $subserviceid
 *
 * @return array  ['result' => RMT_INTAKE_RESOLVE_*,
 *                 'flow'   => array|null,   // set when result=OK
 *                 'level'  => string|null]  // level where attached/invalid
 */
function rmt_intake_resolve_flow(
    mysqli $link,
    int    $catalogueid,
    ?int   $serviceid,
    ?int   $subserviceid
): array {
    // First: require the posted catalogue to exist and be active.
    $st = $link->prepare('SELECT 1 FROM tblcatalogue WHERE id = ? AND status = 1 LIMIT 1');
    $st->bind_param('i', $catalogueid);
    $st->execute();
    $catExists = $st->get_result()->fetch_assoc();
    $st->close();
    if (!$catExists) {
        return ['result' => RMT_INTAKE_RESOLVE_INVALID, 'flow' => null, 'level' => 'catalogue'];
    }

    // subserviceid without serviceid is a broken hierarchy — fail closed.
    if ($subserviceid !== null && $serviceid === null) {
        return ['result' => RMT_INTAKE_RESOLVE_INVALID, 'flow' => null, 'level' => 'subservice'];
    }

    // Subservice-level: validate full chain (sub → svc → cat).
    // If the chain is broken (sub not found or not belonging to svc+cat), fail closed.
    // Do NOT fall through to a parent level after a broken hierarchy.
    if ($subserviceid !== null && $serviceid !== null) {
        $st = $link->prepare(
            'SELECT ss.intake_flow_id
               FROM tblsubservices ss
               JOIN tblservices    sv ON ss.serviceid = sv.id
                                     AND sv.catalogueid = ? AND sv.status = 1
              WHERE ss.id = ? AND ss.serviceid = ? AND ss.status = 1
              LIMIT 1'
        );
        $st->bind_param('iii', $catalogueid, $subserviceid, $serviceid);
        $st->execute();
        $row = $st->get_result()->fetch_assoc();
        $st->close();
        if ($row === null) {
            // Sub not found or doesn't belong to svc+cat — fail closed, no parent fallback.
            return ['result' => RMT_INTAKE_RESOLVE_INVALID, 'flow' => null, 'level' => 'subservice'];
        }
        if ($row['intake_flow_id']) {
            $flow = rmt_intake_load_flow($link, (int) $row['intake_flow_id']);
            if ($flow) {
                $flow['_attachment_level']        = 'subservice';
                $flow['_attachment_catalogueid']  = $catalogueid;
                $flow['_attachment_serviceid']    = $serviceid;
                $flow['_attachment_subserviceid'] = $subserviceid;
                return ['result' => RMT_INTAKE_RESOLVE_OK, 'flow' => $flow, 'level' => 'subservice'];
            }
            // Flow attached but invalid — fail closed; do not try parent levels.
            return ['result' => RMT_INTAKE_RESOLVE_INVALID, 'flow' => null, 'level' => 'subservice'];
        }
        // Sub is valid and has no flow; fall through to service check.
    }

    // Service-level: validate service belongs to catalogueid.
    // If service not found or wrong catalogue, fail closed.
    if ($serviceid !== null) {
        $st = $link->prepare(
            'SELECT s.intake_flow_id
               FROM tblservices s
              WHERE s.id = ? AND s.catalogueid = ? AND s.status = 1
              LIMIT 1'
        );
        $st->bind_param('ii', $serviceid, $catalogueid);
        $st->execute();
        $row = $st->get_result()->fetch_assoc();
        $st->close();
        if ($row === null) {
            // Service not found or doesn't belong to catalogue — fail closed.
            return ['result' => RMT_INTAKE_RESOLVE_INVALID, 'flow' => null, 'level' => 'service'];
        }
        if ($row['intake_flow_id']) {
            $flow = rmt_intake_load_flow($link, (int) $row['intake_flow_id']);
            if ($flow) {
                $flow['_attachment_level']        = 'service';
                $flow['_attachment_catalogueid']  = $catalogueid;
                $flow['_attachment_serviceid']    = $serviceid;
                $flow['_attachment_subserviceid'] = null;
                return ['result' => RMT_INTAKE_RESOLVE_OK, 'flow' => $flow, 'level' => 'service'];
            }
            return ['result' => RMT_INTAKE_RESOLVE_INVALID, 'flow' => null, 'level' => 'service'];
        }
        // Service is valid and has no flow; fall through to catalogue check.
    }

    // Catalogue-level
    $st = $link->prepare(
        'SELECT c.intake_flow_id FROM tblcatalogue c WHERE c.id = ? AND c.status = 1 LIMIT 1'
    );
    $st->bind_param('i', $catalogueid);
    $st->execute();
    $row = $st->get_result()->fetch_assoc();
    $st->close();
    if ($row !== null && $row['intake_flow_id']) {
        $flow = rmt_intake_load_flow($link, (int) $row['intake_flow_id']);
        if ($flow) {
            $flow['_attachment_level']        = 'catalogue';
            $flow['_attachment_catalogueid']  = $catalogueid;
            $flow['_attachment_serviceid']    = null;
            $flow['_attachment_subserviceid'] = null;
            return ['result' => RMT_INTAKE_RESOLVE_OK, 'flow' => $flow, 'level' => 'catalogue'];
        }
        return ['result' => RMT_INTAKE_RESOLVE_INVALID, 'flow' => null, 'level' => 'catalogue'];
    }

    return ['result' => RMT_INTAKE_RESOLVE_NONE, 'flow' => null, 'level' => null];
}

/**
 * Load and validate a published flow record.
 * Returns null when the flow does not exist, is not published (status=1),
 * or has an invalid start_node_id.
 *
 * @param mysqli $link
 * @param int    $flow_id  tblintakeflows.id
 *
 * @return array|null Flow row with start_node validated, or null.
 */
function rmt_intake_load_flow(mysqli $link, int $flow_id): ?array
{
    $st = $link->prepare(
        'SELECT f.id, f.nameen, f.namefr, f.flow_family_key,
                f.version_number, f.status, f.start_node_id
           FROM tblintakeflows f
          WHERE f.id = ? AND f.status = 1
          LIMIT 1'
    );
    $st->bind_param('i', $flow_id);
    $st->execute();
    $flow = $st->get_result()->fetch_assoc();
    $st->close();

    if (!$flow || !$flow['start_node_id']) {
        return null;
    }

    // Verify start_node belongs to this flow and is active
    $st2 = $link->prepare(
        'SELECT id FROM tblintakenodes
          WHERE id = ? AND flow_id = ? AND status = 1 AND node_type = \'question\'
          LIMIT 1'
    );
    $st2->bind_param('ii', $flow['start_node_id'], $flow_id);
    $st2->execute();
    $valid = $st2->get_result()->fetch_assoc();
    $st2->close();

    return $valid ? $flow : null;
}


// ============================================================================
// Node loading
// ============================================================================

/**
 * Load a node with its active options and resources.
 * Validates that the node belongs to the given flow and is active.
 *
 * @param mysqli $link
 * @param int    $flow_id  Exact flow version that must own this node.
 * @param int    $node_id
 *
 * @return array|null Node row with 'options' and 'resources' sub-arrays, or null.
 */
function rmt_intake_load_node(mysqli $link, int $flow_id, int $node_id): ?array
{
    $st = $link->prepare(
        'SELECT n.id, n.flow_id, n.node_type, n.sort_order,
                n.prompt_en, n.prompt_fr, n.intro_en, n.intro_fr,
                n.presentation,
                n.heading_en, n.heading_fr, n.body_en, n.body_fr,
                n.target_catalogueid, n.target_serviceid, n.target_subserviceid,
                n.outcome_code, n.status
           FROM tblintakenodes n
          WHERE n.id = ? AND n.flow_id = ? AND n.status = 1
          LIMIT 1'
    );
    $st->bind_param('ii', $node_id, $flow_id);
    $st->execute();
    $node = $st->get_result()->fetch_assoc();
    $st->close();

    if (!$node) {
        return null;
    }

    // Load active options (question nodes only)
    $node['options'] = [];
    if ($node['node_type'] === 'question') {
        $st2 = $link->prepare(
            'SELECT o.id, o.node_id, o.labelen, o.labelfr,
                    o.next_node_id, o.allow_freeform, o.freeform_required,
                    o.freeform_label_en, o.freeform_label_fr, o.sort_order, o.status
               FROM tblintakeoptions o
              WHERE o.node_id = ? AND o.status = 1
              ORDER BY o.sort_order ASC'
        );
        $st2->bind_param('i', $node_id);
        $st2->execute();
        $node['options'] = $st2->get_result()->fetch_all(MYSQLI_ASSOC);
        $st2->close();
    }

    // Load active resources (any node type)
    $st3 = $link->prepare(
        'SELECT r.id, r.node_id, r.titleen, r.titlefr,
                r.url_en, r.url_fr, r.sort_order, r.status
           FROM tblintakeresources r
          WHERE r.node_id = ? AND r.status = 1
          ORDER BY r.sort_order ASC'
    );
    $st3->bind_param('i', $node_id);
    $st3->execute();
    $node['resources'] = $st3->get_result()->fetch_all(MYSQLI_ASSOC);
    $st3->close();

    return $node;
}

/**
 * Re-validate a destination node's classification hierarchy against the DB.
 * Returns an array with keys 'catalogueid', 'serviceid', 'subserviceid', 'outcome_code'
 * or null if the hierarchy is invalid or inactive.
 *
 * @param mysqli $link
 * @param array  $node  Full node row from rmt_intake_load_node().
 *
 * @return array|null
 */
function rmt_intake_verify_destination(mysqli $link, array $node): ?array
{
    $catId = (int) ($node['target_catalogueid'] ?? 0);
    $svcId = $node['target_serviceid'] ? (int) $node['target_serviceid'] : null;
    $subId = $node['target_subserviceid'] ? (int) $node['target_subserviceid'] : null;

    if ($catId <= 0) {
        return null;
    }

    // Validate catalogue
    $st = $link->prepare('SELECT id FROM tblcatalogue WHERE id = ? AND status = 1 LIMIT 1');
    $st->bind_param('i', $catId);
    $st->execute();
    if (!$st->get_result()->fetch_assoc()) {
        $st->close();
        return null;
    }
    $st->close();

    // Validate service belongs to catalogue
    if ($svcId !== null) {
        $st = $link->prepare(
            'SELECT id FROM tblservices WHERE id = ? AND catalogueid = ? AND status = 1 LIMIT 1'
        );
        $st->bind_param('ii', $svcId, $catId);
        $st->execute();
        if (!$st->get_result()->fetch_assoc()) {
            $st->close();
            return null;
        }
        $st->close();
    }

    // Validate subservice belongs to service
    if ($subId !== null) {
        if ($svcId === null) {
            return null; // Can't have subservice without service
        }
        $st = $link->prepare(
            'SELECT id FROM tblsubservices WHERE id = ? AND serviceid = ? AND status = 1 LIMIT 1'
        );
        $st->bind_param('ii', $subId, $svcId);
        $st->execute();
        if (!$st->get_result()->fetch_assoc()) {
            $st->close();
            return null;
        }
        $st->close();
    }

    return [
        'catalogueid'  => $catId,
        'serviceid'    => $svcId,
        'subserviceid' => $subId,
        'outcome_code' => (string) ($node['outcome_code'] ?? ''),
    ];
}


// ============================================================================
// Session run management
// ============================================================================

/**
 * Create a new run entry in the session.
 *
 * Evicts the oldest run when RMT_INTAKE_MAX_RUNS is reached so that
 * concurrent browser tabs don't accumulate unboundedly.
 *
 * @param array    $flow            Full flow row (including _attachment_* keys).
 * @param string   $lang            'en' or 'fr'
 *
 * @return string  Opaque run token (hex).
 */
function rmt_intake_create_run(array $flow, string $lang): string
{
    if (!isset($_SESSION[RMT_INTAKE_SESSION_KEY]) ||
        !is_array($_SESSION[RMT_INTAKE_SESSION_KEY])) {
        $_SESSION[RMT_INTAKE_SESSION_KEY] = [];
    }

    $runs = &$_SESSION[RMT_INTAKE_SESSION_KEY];

    // Evict expired and oldest when at capacity
    $now = time();
    foreach ($runs as $tok => $r) {
        if (($now - (int) ($r['started_at'] ?? 0)) > RMT_INTAKE_RUN_TTL) {
            unset($runs[$tok]);
        }
    }
    while (count($runs) >= RMT_INTAKE_MAX_RUNS) {
        // Drop the oldest (first inserted)
        reset($runs);
        $oldest = key($runs);
        unset($runs[$oldest]);
    }

    $token = bin2hex(random_bytes(16));

    $runs[$token] = [
        'flow_id'              => (int) $flow['id'],
        'flow_family_key'      => (string) $flow['flow_family_key'],
        'version_number'       => (int) $flow['version_number'],
        'start_node_id'        => (int) $flow['start_node_id'],
        'current_node_id'      => (int) $flow['start_node_id'],
        'history'              => [(int) $flow['start_node_id']],
        'answers'              => [],
        'attachment_level'     => (string) ($flow['_attachment_level'] ?? 'catalogue'),
        'attachment_catalogueid'  => (int) $flow['_attachment_catalogueid'],
        'attachment_serviceid'    => $flow['_attachment_serviceid'] !== null
                                        ? (int) $flow['_attachment_serviceid'] : null,
        'attachment_subserviceid' => $flow['_attachment_subserviceid'] !== null
                                        ? (int) $flow['_attachment_subserviceid'] : null,
        'lang'                 => in_array($lang, ['en', 'fr']) ? $lang : 'en',
        'started_at'           => $now,
        'csrf'                 => bin2hex(random_bytes(16)),
        // Monotonic per-run counter. Bumped on every accepted answer change.
        // Clients must echo back the revision they last observed with each
        // action=step submission; a mismatch means a stale/overlapping
        // request is being rejected (aborting a fetch() client-side does not
        // stop PHP from finishing an earlier request, so this check is the
        // actual guard against overlapping writes, not the client abort).
        'revision'             => 0,
    ];

    return $token;
}

/**
 * Retrieve a run from the session by token.
 * Returns null for unknown, malformed, or expired tokens.
 *
 * @param string $token
 * @return array|null
 */
function rmt_intake_get_run(string $token): ?array
{
    if (!isset($_SESSION[RMT_INTAKE_SESSION_KEY]) ||
        !is_array($_SESSION[RMT_INTAKE_SESSION_KEY])) {
        return null;
    }

    $run = $_SESSION[RMT_INTAKE_SESSION_KEY][$token] ?? null;
    if (!is_array($run)) {
        return null;
    }

    // Expiry check
    if ((time() - (int) ($run['started_at'] ?? 0)) > RMT_INTAKE_RUN_TTL) {
        unset($_SESSION[RMT_INTAKE_SESSION_KEY][$token]);
        return null;
    }

    return $run;
}

/**
 * Persist an updated run back to the session.
 *
 * @param string $token
 * @param array  $run
 */
function rmt_intake_save_run(string $token, array $run): void
{
    if (!isset($_SESSION[RMT_INTAKE_SESSION_KEY]) ||
        !is_array($_SESSION[RMT_INTAKE_SESSION_KEY])) {
        $_SESSION[RMT_INTAKE_SESSION_KEY] = [];
    }
    $_SESSION[RMT_INTAKE_SESSION_KEY][$token] = $run;
}

/**
 * Remove a run from the session (Start over).
 *
 * @param string $token
 */
function rmt_intake_discard_run(string $token): void
{
    unset($_SESSION[RMT_INTAKE_SESSION_KEY][$token]);
}


// ============================================================================
// CSRF helpers
// ============================================================================

/**
 * Return the CSRF token for a run.
 * The token is already set at run-creation time; this just reads it.
 *
 * @param array $run  The run array (not the token string).
 * @return string
 */
function rmt_intake_csrf_token(array $run): string
{
    return (string) ($run['csrf'] ?? '');
}

/**
 * Verify a submitted CSRF token against the stored run token.
 * Uses a constant-time comparison to prevent timing attacks.
 *
 * @param array   $run       Full run array.
 * @param ?string $submitted Value from $_POST['csrf_token'].
 * @return bool
 */
function rmt_intake_verify_csrf(array $run, ?string $submitted): bool
{
    $expected = (string) ($run['csrf'] ?? '');
    if ($expected === '' || $submitted === null || $submitted === '') {
        return false;
    }
    return hash_equals($expected, $submitted);
}


// ============================================================================
// Navigation helpers
// ============================================================================

/**
 * Record an answer for the current node and advance to the next node.
 *
 * Truncates the history to the current node's position, pushes the next
 * node, and prunes any answers whose node_id is no longer in the history.
 * This correctly handles the case where the user went Back and is
 * re-answering a question with a different option.
 *
 * @param array  $run         Run array (passed by reference).
 * @param int    $node_id     The node being answered (must be current node).
 * @param int    $option_id   The chosen option's DB id.
 * @param int    $next_node_id The option's next_node_id (from DB, not user input).
 * @param string|null $freeform Free-form text if the option allows/requires it.
 */
function rmt_intake_answer_node(
    array   &$run,
    int     $node_id,
    int     $option_id,
    int     $next_node_id,
    ?string $freeform = null
): void {
    $key = (string) $node_id;

    // A successful answer supersedes any retained no-JS validation attempt.
    unset(
        $run['_validation_errors'],
        $run['_validation_node_id'],
        $run['_validation_option_id'],
        $run['_validation_freeform']
    );

    // Record the new answer
    $run['answers'][$key] = [
        'option_id' => $option_id,
        'freeform'  => $freeform,
    ];

    // Truncate history at the current node's position so that any
    // nodes visited via an old answer are removed from the path.
    $pos = array_search($node_id, $run['history'], true);
    if ($pos !== false) {
        $run['history'] = array_slice($run['history'], 0, (int) $pos + 1);
    }

    // Push next node and update current
    $run['history'][]       = $next_node_id;
    $run['current_node_id'] = $next_node_id;

    // Prune answers: any node that is no longer in the history path
    // (e.g. from a previous traversal via a different option) is removed.
    $historySet = array_flip($run['history']);
    foreach (array_keys($run['answers']) as $answeredKey) {
        if (!isset($historySet[(int) $answeredKey])) {
            unset($run['answers'][$answeredKey]);
        }
    }

    // Length-bound the stored freeform text
    if ($freeform !== null && strlen($freeform) > RMT_INTAKE_FREEFORM_MAX_LEN) {
        $run['answers'][$key]['freeform'] = mb_substr($freeform, 0, RMT_INTAKE_FREEFORM_MAX_LEN);
    }

    // Bump the revision so a stale in-flight request (one that was aborted
    // client-side but kept running on the server) cannot silently overwrite
    // this newer state; it will fail the client_revision check instead.
    $run['revision'] = ((int) ($run['revision'] ?? 0)) + 1;
}

/**
 * Go back one step in the navigation history.
 * The current node's answer is kept (to pre-fill the form on return).
 * Does nothing if already at the start node.
 *
 * @param array $run  Run array (passed by reference).
 */
function rmt_intake_go_back(array &$run): void
{
    if (count($run['history']) <= 1) {
        return; // Already at start, nothing to go back to
    }

    // Pop the current node
    array_pop($run['history']);
    $run['current_node_id'] = end($run['history']);

    // Clear any stale validation state left from a failed step POST
    unset(
        $run['_validation_errors'],
        $run['_validation_node_id'],
        $run['_validation_option_id'],
        $run['_validation_freeform']
    );
}


// ============================================================================
// Outcome / reaudit mapping
// ============================================================================

/**
 * Data-driven mapping from an outcome_code to the legacy reauditFlag integer.
 *
 * Convention (documented here, not hard-coded to SSC content):
 *   Any outcome_code that contains the substring 'reassess' (case-insensitive)
 *   signals a re-audit.  This matches the SSC seed's 'reassessment' code and
 *   any future flow that uses 'reassessment_*' variants.
 *
 * @param string $outcome_code  Value from tblintakenodes.outcome_code.
 * @return int  1 if this is a re-audit/reassessment, 0 otherwise.
 */
function rmt_intake_outcome_reaudit(string $outcome_code): int
{
    return (stripos($outcome_code, 'reassess') !== false) ? 1 : 0;
}


// ============================================================================
// Rendering helpers
// ============================================================================

/**
 * Render the active resources for a node as a safe HTML list.
 * Only https:// and mailto: URLs are permitted; others are silently skipped.
 *
 * @param array  $resources  Array of resource rows from rmt_intake_load_node().
 * @param string $lang       'en' or 'fr'
 * @param string $heading    Translated heading text (already HTML-escaped by caller).
 *
 * @return string  HTML fragment, or empty string if no valid resources.
 */
function rmt_intake_render_resources(array $resources, string $lang, string $heading): string
{
    if (empty($resources)) {
        return '';
    }

    $isFr = $lang === 'fr';
    $items = '';
    foreach ($resources as $res) {
        $title = htmlspecialchars($isFr ? $res['titlefr'] : $res['titleen'], ENT_QUOTES, 'UTF-8');
        // Use FR URL if set and non-empty, else fall back to EN URL
        $rawUrl = ($isFr && !empty($res['url_fr'])) ? $res['url_fr'] : $res['url_en'];
        // Security: only allow https:// and mailto:
        if (!preg_match('/^(https:\/\/|mailto:)/i', $rawUrl)) {
            continue;
        }
        $url = htmlspecialchars($rawUrl, ENT_QUOTES, 'UTF-8');
        // External HTTPS links get rel="noopener noreferrer"; mailto: links do not need it
        $rel = (stripos($rawUrl, 'https://') === 0)
            ? ' rel="noopener noreferrer" target="_blank"'
            : '';
        $items .= '<li><a href="' . $url . '"' . $rel . '>' . $title . '</a></li>';
    }

    if ($items === '') {
        return '';
    }

    return '<section class="intake-resources" aria-label="' . $heading . '">'
         . '<h3>' . $heading . '</h3>'
         . '<ul>' . $items . '</ul>'
         . '</section>';
}

/**
 * Render a Markdown string using the CommonMark converter already loaded
 * in the AJAX endpoints.  Caller must have already `require`d the autoloader.
 *
 * @param string                                   $raw
 * @param \League\CommonMark\CommonMarkConverter   $converter
 * @return string  Safe HTML.
 */
function rmt_intake_render_markdown(string $raw, object $converter): string
{
    if ($raw === '') {
        return '';
    }
    return $converter->convert($raw)->getContent();
}


// ============================================================================
// Node fragment renderer
// ============================================================================

// ============================================================================
// Centralized shutdown helper
// ============================================================================

/**
 * Flush the session to MySQL and close the DB connection.
 * Must be called before any exit path in intake-flow.php so that the
 * MySQL session handler does not try to write using a closed connection.
 *
 * @param mysqli $link
 */
function rmt_intake_clean_exit(mysqli $link): void
{
    session_write_close();
    mysqli_close($link);
}


// ============================================================================
// Node fragment renderer
// ============================================================================

/**
 * Render a single intake node as a self-contained HTML path item.
 *
 * Every return value has one top-level .intake-path-item wrapper. JavaScript
 * uses these wrappers to remove a complete downstream branch consistently,
 * whether the path was built incrementally by AJAX or reconstructed by PHP.
 * Destination nodes include their own <form> pointing at openrequest2.php;
 * placing #intake-workflow outside the cascade form avoids nested-form issues.
 *
 * @param mysqli $link         Live DB connection (needed for destination validation).
 * @param array  $node         Full node row from rmt_intake_load_node().
 * @param array  $run          Current run state.
 * @param string $runToken     Opaque 32-char hex run token.
 * @param string $lang         'en' or 'fr'
 * @param object $mdConverter  League\CommonMark\CommonMarkConverter instance.
 * @param array  $t            Loaded translations array (from lang/en.php or fr.php).
 * @param bool   $noJs         When true, renders question controls for a real
 *                             (non-AJAX) form submission: every option carries
 *                             name="option_id" so a plain POST resolves the
 *                             chosen answer, free-form fields are always
 *                             visible (there is no JS to reveal them), and the
 *                             JS-only "Continue" button used for freeform
 *                             options is omitted (this renderer supplies one
 *                             fallback form and submit button instead).
 * @param bool   $isCurrent    When true in no-JS mode, marks this node as the
 *                             single focus target after a full-page redirect.
 * @return string  One HTML path item ready for insertion into the page.
 */
function rmt_intake_render_node_fragment(
    mysqli $link,
    array  $node,
    array  $run,
    string $runToken,
    string $lang,
    object $mdConverter,
    array  $t = [],
    bool   $noJs = false,
    bool   $isCurrent = false
): string {
    $isFr   = ($lang === 'fr');
    $nodeId = (int) $node['id'];

    // Keep the committed answer distinct from a retained no-JS validation
    // attempt. Controls display the attempted values after a validation
    // redirect, while data-committed-option still identifies the last answer
    // the server accepted (used by JS if the hydrated attempt later fails).
    $answerKey       = (string) $nodeId;
    $committedOptId  = isset($run['answers'][$answerKey])
        ? (int) $run['answers'][$answerKey]['option_id']
        : null;
    $preFillOptId    = $committedOptId;
    $preFillFreeform = $run['answers'][$answerKey]['freeform'] ?? null;
    $validationErrors = [];
    if ((int) ($run['_validation_node_id'] ?? 0) === $nodeId) {
        $validationErrors = is_array($run['_validation_errors'] ?? null)
            ? $run['_validation_errors']
            : [];
        if (isset($run['_validation_option_id'])) {
            $preFillOptId = (int) $run['_validation_option_id'];
        }
        if (array_key_exists('_validation_freeform', $run)) {
            $preFillFreeform = (string) $run['_validation_freeform'];
        }
    }

    // Strings from lang file with fallbacks
    $requiredLabel   = $t['required']                    ?? ($isFr ? 'requis'             : 'required');
    $placeholder     = $t['select_placeholder']          ?? ($isFr ? 'Faites votre choix\u2026' : 'Make your selection\u2026');
    $resourcesLabel  = $t['intake_resources_heading']    ?? ($isFr ? 'Ressources'          : 'Resources');
    $returnPromptStr = $t['intake_guidance_return_prompt'] ?? ($isFr
        ? 'Pour continuer, veuillez revenir \u00e0 ce formulaire apr\u00e8s avoir suivi les consignes ci-dessus.'
        : 'To continue, please return to this form after following the guidance above.');
    $specifyLabel    = $t['intake_specify']              ?? ($isFr ? 'Pr\u00e9cisez'       : 'Please specify');
    $continueLabel   = $t['intake_continue']             ?? ($isFr ? 'Continuer'           : 'Continue');
    $completeMsg     = $t['intake_destination_heading']  ?? ($isFr
        ? 'Votre parcours est complet. Cliquez ci-dessous pour ouvrir le formulaire de demande.'
        : 'Your intake is complete. Click below to open the request form.');
    $btnLabel        = $t['intake_continue_to_form']     ?? ($isFr
        ? 'Continuer vers le formulaire de demande'
        : 'Continue to request form');
    $unavailableMsg  = $t['intake_error_flow_invalid']   ?? ($isFr
        ? "Ce service n'est pas disponible pour le moment."
        : 'This service is currently unavailable.');

    $nodeType = htmlspecialchars((string) $node['node_type'], ENT_QUOTES, 'UTF-8');
    $out = '<div id="intake-node-' . $nodeId . '" class="intake-path-item" data-node-id="' . $nodeId
         . '" data-node-type="' . $nodeType . '">';

    if ($noJs && $node['node_type'] === 'question') {
        $out .= '<form method="POST" action="/intake-flow.php" class="intake-nojs-form">';
        $out .= '<input type="hidden" name="action"          value="step">';
        $out .= '<input type="hidden" name="run_token"       value="' . htmlspecialchars($runToken, ENT_QUOTES, 'UTF-8') . '">';
        $out .= '<input type="hidden" name="csrf_token"      value="' . htmlspecialchars(rmt_intake_csrf_token($run), ENT_QUOTES, 'UTF-8') . '">';
        $out .= '<input type="hidden" name="node_id"         value="' . $nodeId . '">';
        $out .= '<input type="hidden" name="client_revision" value="' . (int) ($run['revision'] ?? 0) . '">';
    }

    // ------------------------------------------------------------------
    // Question node
    // ------------------------------------------------------------------
    if ($node['node_type'] === 'question') {
        $prompt = htmlspecialchars(
            (string) ($isFr ? $node['prompt_fr'] : $node['prompt_en']),
            ENT_QUOTES, 'UTF-8'
        );
        $intro   = (string) ($isFr ? ($node['intro_fr'] ?? '') : ($node['intro_en'] ?? ''));
        $pres    = $node['presentation'] ?? 'select';
        $labelId = 'intake-q-' . $nodeId;

        // Determine if any option has allow_freeform so we can add the Continue button
        $hasFreeformOption = false;
        foreach ($node['options'] as $opt) {
            if (!empty($opt['allow_freeform'])) { $hasFreeformOption = true; break; }
        }

        $out .= '<div class="form-group intake-node"'
              . ' data-node-id="' . $nodeId . '" data-node-type="question"'
              . ' data-committed-option="' . ($committedOptId !== null ? $committedOptId : '') . '">';

        if (!empty($validationErrors)) {
            $summaryHeading = $t['intake_error_summary_heading'] ?? 'There are errors on this form';
            $out .= '<section id="intake-validation-summary-' . $nodeId . '" class="alert alert-danger intake-validation-summary" role="alert" tabindex="-1" autofocus>';
            $out .= '<h2 class="h3">' . htmlspecialchars($summaryHeading, ENT_QUOTES, 'UTF-8') . '</h2><ul>';
            foreach ($validationErrors as $fieldId => $message) {
                $out .= '<li><a href="#' . htmlspecialchars((string) $fieldId, ENT_QUOTES, 'UTF-8') . '">'
                      . htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8') . '</a></li>';
            }
            $out .= '</ul></section>';
        }

        if ($intro !== '') {
            $out .= '<div class="intake-intro mrgn-bttm-sm">'
                  . rmt_intake_render_markdown($intro, $mdConverter) . '</div>';
        }

        if ($pres === 'select') {
            $out .= '<label for="' . $labelId . '" class="required">'
                  . $prompt
                  . ' <strong class="required">(' . $requiredLabel . ')</strong></label>';
            $out .= '<select id="' . $labelId . '"'
                  . ' name="option_id"'
                  . ' class="form-control intake-question-select"'
                  . ' data-node-id="' . $nodeId . '"'
                  . ($noJs && $isCurrent && empty($validationErrors) ? ' autofocus' : '')
                  . ' aria-required="true" required>';
            $out .= '<option value="">' . htmlspecialchars($placeholder, ENT_QUOTES, 'UTF-8') . '</option>';
            foreach ($node['options'] as $optionIndex => $opt) {
                $optId    = (int) $opt['id'];
                $optLabel = htmlspecialchars(
                    (string) ($isFr ? $opt['labelfr'] : $opt['labelen']),
                    ENT_QUOTES, 'UTF-8'
                );
                $selected = ($preFillOptId !== null && $preFillOptId === $optId) ? ' selected' : '';
                $ffAttr   = !empty($opt['allow_freeform'])
                    ? ' data-allow-freeform="1" data-freeform-required="' . (int)$opt['freeform_required'] . '"'
                      . ' data-freeform-id="intake-ff-' . $nodeId . '-' . $optId . '"'
                    : '';
                $out .= '<option value="' . $optId . '"' . $selected . $ffAttr . '>'
                      . $optLabel . '</option>';
            }
            $out .= '</select>';
        } else {
            // radio presentation — all radios in the group share name="option_id"
            // so a plain (no-JS) form POST resolves to the chosen option.
            $out .= '<fieldset><legend class="required">'
                  . $prompt
                  . ' <strong class="required">(' . $requiredLabel . ')</strong></legend>';
            foreach ($node['options'] as $optionIndex => $opt) {
                $optId    = (int) $opt['id'];
                $optLabel = htmlspecialchars(
                    (string) ($isFr ? $opt['labelfr'] : $opt['labelen']),
                    ENT_QUOTES, 'UTF-8'
                );
                $rid     = 'intake-radio-' . $nodeId . '-' . $optId;
                $checked = ($preFillOptId !== null && $preFillOptId === $optId) ? ' checked' : '';
                $ffAttr  = !empty($opt['allow_freeform'])
                    ? ' data-allow-freeform="1" data-freeform-required="' . (int)$opt['freeform_required'] . '"'
                      . ' data-freeform-id="intake-ff-' . $nodeId . '-' . $optId . '"'
                    : '';
                $out .= '<div class="radio"><label for="' . $rid . '">'
                      . '<input type="radio" id="' . $rid . '"'
                      . ' class="intake-question-radio" data-node-id="' . $nodeId . '"'
                      . ' name="option_id"'
                      . ($noJs && $isCurrent && empty($validationErrors) && $optionIndex === 0 ? ' autofocus' : '')
                      . ' value="' . $optId . '"' . $checked . $ffAttr . ' required>'
                      . ' ' . $optLabel . '</label></div>';
            }
            $out .= '</fieldset>';
        }

        // Freeform fields (one per freeform-capable option).  In JS mode these
        // start hidden and are toggled by openrequest.js on change; without JS
        // there is no way to reveal them dynamically, so they are always
        // visible and labelled so the required one can always be found and
        // validated server-side regardless of which option is ultimately chosen.
        foreach ($node['options'] as $opt) {
            if (empty($opt['allow_freeform'])) continue;
            $optId       = (int) $opt['id'];
            $ffId        = 'intake-ff-' . $nodeId . '-' . $optId;
            $ffReq       = !empty($opt['freeform_required']);
            $ffLabelRaw  = (string) ($isFr ? ($opt['freeform_label_fr'] ?? '') : ($opt['freeform_label_en'] ?? ''));
            $ffLabelDisp = htmlspecialchars($ffLabelRaw !== '' ? $ffLabelRaw : $specifyLabel, ENT_QUOTES, 'UTF-8');
            $ffValue     = ($preFillOptId === $optId && $preFillFreeform !== null)
                ? htmlspecialchars($preFillFreeform, ENT_QUOTES, 'UTF-8') : '';
            $displayStyle = $noJs ? '' : (($preFillOptId === $optId) ? '' : ' style="display:none"');
            $fieldError  = (string) ($validationErrors[$ffId . '-text'] ?? '');
            // Native conditional required validation is enabled only when JS
            // can keep it synchronized with the selected option. In no-JS
            // mode every freeform field stays visible and the selected
            // option's requirement is enforced server-side, so an irrelevant
            // field can never block submission of a different option.
            $reqAttr      = (!$noJs && $ffReq && $preFillOptId === $optId) ? ' required' : '';
            $committedFreeform = ($committedOptId === $optId
                && isset($run['answers'][$answerKey]['freeform']))
                ? (string) $run['answers'][$answerKey]['freeform']
                : '';

            $out .= '<div id="' . $ffId . '" class="intake-freeform-group form-group mrgn-lft-md mrgn-tp-sm"'
                  . $displayStyle . ' data-for-option="' . $optId . '">';
            $out .= '<label for="' . $ffId . '-text">' . $ffLabelDisp;
            if ($ffReq) { $out .= ' <strong class="required">(' . $requiredLabel . ')</strong>'; }
            $out .= '</label>';
            if ($fieldError !== '') {
                $out .= '<p id="' . $ffId . '-error" class="text-danger intake-field-error">'
                    . htmlspecialchars($fieldError, ENT_QUOTES, 'UTF-8') . '</p>';
            }
                        $out .= '<textarea id="' . $ffId . '-text"'
                                    . ' name="freeform_opt_' . $optId . '"'
                                    . ' class="form-control intake-freeform-text" rows="3"'
                                    . ' data-committed-value="' . htmlspecialchars($committedFreeform, ENT_QUOTES, 'UTF-8') . '"'
                                    . ' maxlength="' . RMT_INTAKE_FREEFORM_MAX_LEN . '"'
                                    . ($fieldError !== '' ? ' aria-invalid="true" aria-describedby="' . $ffId . '-error"' : '')
                                    . $reqAttr . '>'
                                    . $ffValue . '</textarea>';
            $out .= '</div>';
        }

        // JS-only "Continue" button for freeform options (revealed by JS once
        // an option requiring free text is selected). Not rendered in no-JS
        // mode; this path item's fallback form supplies one visible submit
        // button for the whole question instead.
        if ($hasFreeformOption && !$noJs) {
            $out .= '<div class="form-group form-buttons intake-freeform-submit mrgn-tp-sm"'
                  . ' style="display:none">';
            $out .= '<button type="button" class="btn btn-primary intake-ff-continue"'
                  . ' data-node-id="' . $nodeId . '">'
                  . htmlspecialchars($continueLabel, ENT_QUOTES, 'UTF-8') . '</button>';
            $out .= '</div>';
        }

        $out .= rmt_intake_render_resources($node['resources'], $lang,
                    htmlspecialchars($resourcesLabel, ENT_QUOTES, 'UTF-8'));

        // Inline error zone — JS writes validation messages here
        $out .= '<div class="intake-error-zone text-danger" role="alert" aria-live="assertive"></div>';

        if ($noJs) {
            $out .= '<div class="form-group form-buttons mrgn-tp-md intake-nojs-submit">';
            $out .= '<button type="submit" class="btn btn-primary">'
                  . htmlspecialchars($continueLabel, ENT_QUOTES, 'UTF-8') . '</button>';
            $out .= '</div></div></form>';
        } else {
            $out .= '</div>';
        }

    // ------------------------------------------------------------------
    // Guidance node (terminal — no request-form button)
    // ------------------------------------------------------------------
    } elseif ($node['node_type'] === 'guidance') {
        $heading = htmlspecialchars(
            (string) ($isFr ? ($node['heading_fr'] ?? '') : ($node['heading_en'] ?? '')),
            ENT_QUOTES, 'UTF-8'
        );
        $body = (string) ($isFr ? ($node['body_fr'] ?? '') : ($node['body_en'] ?? ''));

        $out .= '<div id="intake-focus-' . $nodeId . '" class="intake-node alert alert-info" data-node-id="' . $nodeId . '" data-node-type="guidance"'
              . ($noJs && $isCurrent ? ' tabindex="-1" autofocus' : '') . '>';
        $out .= '<h3 class="h4">' . $heading . '</h3>';
        if ($body !== '') {
            $out .= '<div>' . rmt_intake_render_markdown($body, $mdConverter) . '</div>';
        }
        $out .= rmt_intake_render_resources($node['resources'], $lang,
                    htmlspecialchars($resourcesLabel, ENT_QUOTES, 'UTF-8'));
        $out .= '<p><em>' . htmlspecialchars($returnPromptStr, ENT_QUOTES, 'UTF-8') . '</em></p>';
        $out .= '</div>';

    // ------------------------------------------------------------------
    // Destination node — re-validate from DB; no browser-sourced IDs
    // ------------------------------------------------------------------
    } elseif ($node['node_type'] === 'destination') {
        $destInfo = rmt_intake_verify_destination($link, $node);

        $out .= '<div id="intake-focus-' . $nodeId . '" class="intake-node intake-destination" data-node-id="' . $nodeId . '" data-node-type="destination"'
              . ($noJs && $isCurrent ? ' tabindex="-1" autofocus' : '') . '>';

        if ($destInfo === null) {
            $out .= '<section class="alert alert-danger" role="alert"><p>'
                  . htmlspecialchars($unavailableMsg, ENT_QUOTES, 'UTF-8') . '</p></section>';
        } else {
            $outcomeCode = (string) ($destInfo['outcome_code'] ?? '');
            $reauditFlag = rmt_intake_outcome_reaudit($outcomeCode);
            $destCat     = (int) $destInfo['catalogueid'];
            $destSvc     = $destInfo['serviceid'];
            $destSub     = $destInfo['subserviceid'];

            $destinationSubheading = $t['intake_destination_subheading'] ?? '';
            $out .= '<section class="alert alert-success"><h3 class="h4">'
                . htmlspecialchars($completeMsg, ENT_QUOTES, 'UTF-8') . '</h3>';
            if ($destinationSubheading !== '') {
                $out .= '<p>' . htmlspecialchars($destinationSubheading, ENT_QUOTES, 'UTF-8') . '</p>';
            }
            $out .= '</section>';
            $out .= rmt_intake_render_resources($node['resources'], $lang,
                        htmlspecialchars($resourcesLabel, ENT_QUOTES, 'UTF-8'));

            // Standalone form — outside cascade form, no nested-form violation
            $out .= '<form method="POST" action="/openrequest2.php?lang=' . urlencode($lang) . '" class="mrgn-tp-sm">';
            $out .= '<input type="hidden" name="catalogueid"      value="' . $destCat . '">';
            if ($destSvc !== null) {
                $out .= '<input type="hidden" name="serviceid"    value="' . (int) $destSvc . '">';
            }
            if ($destSub !== null) {
                $out .= '<input type="hidden" name="subserviceid" value="' . (int) $destSub . '">';
            }
            $out .= '<input type="hidden" name="reauditFlag"      value="' . $reauditFlag . '">';
            $out .= '<input type="hidden" name="intake_run_token" value="' . htmlspecialchars($runToken, ENT_QUOTES, 'UTF-8') . '">';
            $out .= '<button type="submit" class="btn btn-primary">'
                  . htmlspecialchars($btnLabel, ENT_QUOTES, 'UTF-8') . '</button>';
            $out .= '</form>';
        }
        $out .= '</div>';
    }

    return $out . '</div>';
}


// ============================================================================
// Full-path renderer (no-JS fallback + language switch reconstruction)
// ============================================================================

/**
 * Render the complete visible decision path for a run.
 *
 * Iterates run['history'] and renders every node. Question nodes are always
 * rendered interactively/editable — including previously-answered ones —
 * so an earlier answer can be changed (the answer-change handling in
 * rmt_intake_answer_node() truncates and rebuilds anything downstream).
 * Guidance and destination nodes can only ever be the last node in history
 * (they are terminal), so no wrapping form is needed for them here.
 *
 * Used by:
 *   - openrequest.php (no-JS GET ?run=TOKEN fallback, and after a full-page
 *     language-switch navigation)
 *   - intake-flow.php action=reconstruct (available for programmatic partial
 *     reconstruction of a run's rendered path; the client language switch
 *     performs a full page navigation instead, so this action is not
 *     currently invoked by openrequest.js)
 *
 * @param mysqli $link
 * @param array  $run        Current run state.
 * @param string $runToken   Opaque token.
 * @param string $lang       'en' or 'fr'
 * @param object $mdConverter
 * @param array  $t          Loaded translations array.
 * @param bool   $noJs       When true, each question path item contains its
 *                           own non-nested <form> so it can be submitted (or
 *                           resubmitted, to change an earlier answer)
 *                           without JavaScript.
 * @return string  Complete HTML for #intake-workflow.
 */
function rmt_intake_render_full_path(
    mysqli $link,
    array  $run,
    string $runToken,
    string $lang,
    object $mdConverter,
    array  $t = [],
    bool   $noJs = false
): string {
    if (empty($run['history'])) {
        return '';
    }

    $history = $run['history'];
    $out     = '';

    $currentNodeId = (int) end($history);
    foreach ($history as $nodeId) {
        $nodeId = (int) $nodeId;
        $node   = rmt_intake_load_node($link, (int) $run['flow_id'], $nodeId);
        if (!$node) { continue; }

        $out .= rmt_intake_render_node_fragment(
            $link, $node, $run, $runToken, $lang, $mdConverter, $t, $noJs,
            $noJs && $nodeId === $currentNodeId
        );
    }

    return $out;
}


// ============================================================================
// Shared submission-boundary validator (openrequest2.php + openrequest3.php)
// ============================================================================

/**
 * Validate the intake run token and posted classification at a form boundary.
 *
 * Call this in openrequest2.php and openrequest3.php before trusting any
 * classification IDs.  On any error the function redirects to openrequest.php
 * and exits.  Only the two documented return shapes are ever returned.
 *
 * Return shapes:
 *   ['flow' => true,  'token'        => string (32 hex),
 *                     'run'          => array,
 *                     'catalogueid'  => int,
 *                     'serviceid'    => int|null,
 *                     'subserviceid' => int|null,
 *                     'reauditFlag'  => int]
 *   — Token was present, valid, and the run's destination is active.
 *     Caller must use the returned IDs; browser POST values must be ignored.
 *
 *   ['flow' => false]
 *   — No token was submitted AND the posted classification resolves to NONE.
 *     Caller continues with the standard (non-flow) intake path.
 *
 * @param mysqli   $link
 * @param string   $lang          Current language code.
 * @param string   $rawToken      Raw token string from POST or restored draft.
 * @param int      $catalogueid   Browser-posted catalogue ID.
 * @param int|null $serviceid     Browser-posted service ID.
 * @param int|null $subserviceid  Browser-posted subservice ID.
 * @return array
 */
function rmt_intake_validate_submission(
    mysqli $link,
    string $lang,
    string $rawToken,
    int    $catalogueid,
    ?int   $serviceid,
    ?int   $subserviceid
): array {
    $base = "/openrequest.php?lang={$lang}";

    if ($rawToken !== '') {
        // Token submitted: validate exact 32-char hex format (case-insensitive accept, normalise lower)
        if (!preg_match('/^[0-9a-f]{32}$/i', $rawToken)) {
            // Malformed — never fall back to standard path
            header("Location: {$base}&intake_error=invalid");
            exit;
        }
        $token = strtolower($rawToken);

        $run = rmt_intake_get_run($token);
        if (!$run) {
            header("Location: {$base}&intake_error=expired");
            exit;
        }

        $destNode = rmt_intake_load_node($link, (int) $run['flow_id'], (int) $run['current_node_id']);
        if (!$destNode || $destNode['node_type'] !== 'destination') {
            header("Location: {$base}&intake_error=invalid");
            exit;
        }

        $destInfo = rmt_intake_verify_destination($link, $destNode);
        if (!$destInfo) {
            header("Location: {$base}&intake_error=invalid");
            exit;
        }

        return [
            'flow'         => true,
            'token'        => $token,
            'run'          => $run,
            'catalogueid'  => $destInfo['catalogueid'],
            'serviceid'    => $destInfo['serviceid'],
            'subserviceid' => $destInfo['subserviceid'],
            'reauditFlag'  => rmt_intake_outcome_reaudit($destInfo['outcome_code']),
        ];
    }

    // No token submitted: check whether the posted classification requires a flow.
    if ($catalogueid > 0) {
        $check = rmt_intake_resolve_flow($link, $catalogueid, $serviceid, $subserviceid);
        if ($check['result'] === RMT_INTAKE_RESOLVE_OK) {
            // A flow is attached — token is required; token bypass rejected
            header("Location: {$base}&intake_error=flow_required");
            exit;
        }
        if ($check['result'] === RMT_INTAKE_RESOLVE_INVALID) {
            // Broken hierarchy or invalid attachment — fail closed
            header("Location: {$base}&intake_error=invalid");
            exit;
        }
        // RMT_INTAKE_RESOLVE_NONE — standard non-flow path allowed
    }

    return ['flow' => false];
}
