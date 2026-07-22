<?php
/**
 * Consolidated Bilingual Open Request Page
 * 
 * This page replaces the separate openrequest-en.php and openrequest-fr.php files
 * by using a language file system. The language is determined by $_SESSION['lang'].
 * 
 * @package RMT
 * @since 2.0.0
 */

// Start session
require_once __DIR__ . '/includes/session_start.php';

// Grab HTTPS check
require('includes/httpscheck.php');

// Grab MySQL connection
require('sql.php');
/** @var mysqli $link */

// Handle language from query string or session
if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'fr'])) {
	$_SESSION['lang'] = $_GET['lang'];
}

// Set default language if not set
if (!isset($_SESSION['lang']) || !in_array($_SESSION['lang'], ['en', 'fr'])) {
	$_SESSION['lang'] = 'en';
}

// Load language file
$lang = require("lang/{$_SESSION['lang']}.php");

// Check if there is a status
if (!empty($_GET['status'])){
	$status = $_GET['status'];
}
else{
	$status = "";
}

// Intake flow error code (redirected from intake-flow.php)
$intakeError = isset($_GET['intake_error'])
    ? preg_replace('/[^a-z0-9_]/', '', (string) $_GET['intake_error'])
    : '';

// Session-level CSRF token used to validate action=start posts to intake-flow.php.
// Generated once per session; consumed but not rotated (multi-tab safe).
if (empty($_SESSION['openrequest_csrf'])) {
    $_SESSION['openrequest_csrf'] = bin2hex(random_bytes(16));
}
$openrequestCsrf = $_SESSION['openrequest_csrf'];

$isFr = $_SESSION['lang'] === 'fr';

// Intake flow helpers (needed for rmt_intake_validate_submission and no-JS fallback)
require_once __DIR__ . '/includes/intake-flow-helpers.php';

// ============================================================================
// No-JS fallback: GET ?run=TOKEN renders the full decision path inline.
// JavaScript users never reach this path (JS stays on the page via AJAX).
// ============================================================================
$noJsRun      = null;
$noJsFragment = '';
$noJsToken    = '';
$rawNoJsToken = isset($_GET['run']) ? (string) $_GET['run'] : '';
if ($rawNoJsToken !== '' && preg_match('/^[0-9a-f]{32}$/i', $rawNoJsToken)) {
    $noJsToken = strtolower($rawNoJsToken);
    $tmpRun    = rmt_intake_get_run($noJsToken);
    if ($tmpRun) {
        // Honour ?lang= on this request (may differ from run lang)
        if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'fr'], true)
            && $_GET['lang'] !== $tmpRun['lang']) {
            $tmpRun['lang'] = $_GET['lang'];
            rmt_intake_save_run($noJsToken, $tmpRun);
            $noJsLang        = $_GET['lang'];
            $_SESSION['lang'] = $noJsLang;
            $lang             = require("lang/{$noJsLang}.php");
            $isFr             = ($noJsLang === 'fr');
        }
        $noJsRun = $tmpRun;
    }
    // If run is invalid/expired, fall through to normal page (with no-JS error alert)
    if (!$noJsRun) {
        $intakeError = $intakeError ?: 'expired';
    }
}

if ($noJsRun) {
    require_once __DIR__ . '/vendor/autoload.php';
    $noJsMd       = new League\CommonMark\CommonMarkConverter(['html_input' => 'strip', 'allow_unsafe_links' => false]);
    $noJsFragment = rmt_intake_render_full_path(
        $link, $noJsRun, $noJsToken, $noJsRun['lang'], $noJsMd, $lang, true
    );
    // Session and lang already set above; write session before closing DB at end
}

// Values used to hydrate the client (data attributes) and to render the
// no-JS Start Over form. Always defined (empty when no run is active) so
// the markup below does not need repeated isset() checks.
$noJsRunToken    = $noJsRun ? $noJsToken : '';
$noJsRunCsrf     = $noJsRun ? rmt_intake_csrf_token($noJsRun) : '';
$noJsRunRevision = $noJsRun ? (int) ($noJsRun['revision'] ?? 0) : 0;

// Client-facing strings openrequest.js needs but cannot read from the PHP
// language files directly (it is a static asset, not templated). Sourced
// from the current language file so wording stays centralised and bilingual.
$intakeClientStrings = [
    'retry'         => $lang['intake_retry']              ?? ($isFr ? 'Réessayer' : 'Retry'),
    'error_generic' => $lang['intake_error_invalid']       ?? ($isFr ? 'Une erreur s\'est produite. Veuillez recommencer.' : 'An error occurred. Please start over.'),
    'error_network' => $lang['intake_error_network']       ?? ($isFr ? 'Erreur réseau. Veuillez réessayer.' : 'Network error. Please try again.'),
	'error_freeform_required' => $lang['intake_error_freeform_required'] ?? ($isFr ? 'Veuillez saisir du texte dans ce champ.' : 'Please enter text in this field.'),
    'start_over'    => $lang['intake_start_over']          ?? ($isFr ? 'Recommencer' : 'Start over'),
	'restart_complete' => $lang['intake_restart_complete'] ?? ($isFr ? "Le questionnaire d'accueil a été réinitialisé." : 'The intake workflow has been reset.'),
];

$routerCatalogues = [];
$routerCatalogueQuery = mysqli_query(
	$link,
	"SELECT id, nameen, namefr, is_guidance_only,
	        guidance_text_en, guidance_text_fr
	 FROM tblcatalogue
	 WHERE status = 1 AND show_in_openrequest = 1
	 ORDER BY " . ($isFr ? 'namefr' : 'nameen') . " ASC"
);

if ($routerCatalogueQuery) {
	while ($row = mysqli_fetch_assoc($routerCatalogueQuery)) {
		$routerCatalogues[] = $row;
	}
}

// Progressive-enhancement fallback for the initial catalogue/service cascade.
// JavaScript replaces these server-rendered stages with the existing AJAX
// endpoints. Without JavaScript, each named submitter posts back here so the
// selected IDs can be validated before the flow is started.
$serverCascadeAction = $_SERVER['REQUEST_METHOD'] === 'POST'
	? trim((string) ($_POST['cascade_action'] ?? ''))
	: '';
$selectedCatalogueId = 0;
$selectedServiceId = 0;
$serverServices = [];
$serverFlowReady = false;
$serverCascadeError = '';
$serverCascadeErrorTarget = 'service_stream';

if (in_array($serverCascadeAction, ['select_catalogue', 'select_service'], true)) {
	$submittedCsrf = (string) ($_POST['form_csrf'] ?? '');
	$streamValue = trim((string) ($_POST['service_stream'] ?? ''));

	if ($submittedCsrf === '' || !hash_equals($openrequestCsrf, $submittedCsrf)) {
		$serverCascadeError = $lang['intake_error_csrf'];
	} elseif (!preg_match('/^catalogue_([1-9][0-9]*)$/', $streamValue, $matches)) {
		$serverCascadeError = $lang['intake_cascade_invalid'];
	} else {
		$candidateCatalogueId = (int) $matches[1];
		foreach ($routerCatalogues as $catalogue) {
			if ((int) $catalogue['id'] === $candidateCatalogueId) {
				$selectedCatalogueId = $candidateCatalogueId;
				break;
			}
		}

		if ($selectedCatalogueId === 0) {
			$serverCascadeError = $lang['intake_cascade_invalid'];
		}
	}

	if ($selectedCatalogueId > 0) {
		$serviceStmt = $link->prepare(
			'SELECT id, nameen, namefr
			 FROM tblservices
			 WHERE catalogueid = ? AND status = 1
			 ORDER BY ' . ($isFr ? 'namefr' : 'nameen') . ' ASC'
		);
		$serviceStmt->bind_param('i', $selectedCatalogueId);
		$serviceStmt->execute();
		$serverServices = $serviceStmt->get_result()->fetch_all(MYSQLI_ASSOC);
		$serviceStmt->close();

		if ($serverCascadeAction === 'select_service') {
			$candidateServiceId = (int) ($_POST['serviceid'] ?? 0);
			foreach ($serverServices as $service) {
				if ((int) $service['id'] === $candidateServiceId) {
					$selectedServiceId = $candidateServiceId;
					break;
				}
			}

			if ($selectedServiceId === 0) {
				$serverCascadeError = $lang['intake_cascade_invalid'];
				$serverCascadeErrorTarget = 'serviceid';
			} else {
				$resolution = rmt_intake_resolve_flow(
					$link,
					$selectedCatalogueId,
					$selectedServiceId,
					null
				);
				if ($resolution['result'] === RMT_INTAKE_RESOLVE_OK) {
					$serverFlowReady = true;
				} else {
					$serverCascadeError = $resolution['result'] === RMT_INTAKE_RESOLVE_INVALID
						? $lang['intake_error_flow_invalid']
						: $lang['intake_cascade_invalid'];
					$serverCascadeErrorTarget = 'serviceid';
				}
			}
		}
	}
}

$pageTitle = $lang['main_heading'];
$pageDescription = $lang['page_description'];

include 'includes/template/head.php';
?>
	<?php
	$langStrings = $lang;
	include 'includes/template/header.php';
	$lang = $langStrings;
	unset($langStrings);
	?>
		<main role="main" property="mainContentOfPage" class="container">
			<h1 property="name" id="wb-cont"><?= htmlspecialchars($lang['main_heading']) ?></h1>
			<?php if ($serverCascadeError !== ''): ?>
			<section class="alert alert-danger" role="alert" tabindex="-1" autofocus id="cascade-error-summary">
				<h2 class="h4"><?= htmlspecialchars($lang['intake_error_summary_heading']) ?></h2>
				<ul>
					<li><a href="#<?= htmlspecialchars($serverCascadeErrorTarget, ENT_QUOTES) ?>"><?= htmlspecialchars($serverCascadeError) ?></a></li>
				</ul>
			</section>
			<?php endif; ?>
			<?php
			// Intake error alert (redirected from intake-flow.php)
			if ($intakeError !== ''):
				$errMessages = [
					'expired'      => $isFr ? ($lang['intake_error_expired']       ?? 'Votre session a expir&eacute;. Veuillez recommencer.') : ($lang['intake_error_expired']       ?? 'Your session has expired. Please start over.'),
					'csrf'         => $isFr ? ($lang['intake_error_csrf']           ?? 'La requ&ecirc;te n&apos;&eacute;tait pas valide. Veuillez recommencer.') : ($lang['intake_error_csrf']           ?? 'The request was not valid. Please start over.'),
					'flow_invalid' => $isFr ? ($lang['intake_error_flow_invalid']   ?? "Ce service n'est pas disponible pour le moment.") : ($lang['intake_error_flow_invalid']   ?? 'This service is currently unavailable.'),
					'flow_required'=> $isFr ? ($lang['intake_error_flow_required']  ?? "Ce service n&eacute;cessite un questionnaire d'accueil. Veuillez recommencer.") : ($lang['intake_error_flow_required']  ?? 'This service requires an intake questionnaire. Please start over.'),
				];
				$errMsg = $errMessages[$intakeError]
				    ?? ($isFr ? ($lang['intake_error_invalid'] ?? 'Une erreur s\'est produite. Veuillez recommencer.') : ($lang['intake_error_invalid'] ?? 'An error occurred. Please start over.'));
			?>
			<section class="alert alert-danger" role="alert" tabindex="-1" id="intake-error-notice">
				<h2 class="h4"><?= $isFr ? 'Erreur' : 'Error' ?></h2>
				<p><?= htmlspecialchars($errMsg) ?></p>
			</section>
			<script>(function(){var e=document.getElementById('intake-error-notice');if(e){e.focus();}}());</script>
			<?php endif; ?>
			<?php 
			if ($status == 'failed') {
			?>
			<section class="alert alert-danger">
				<h2><?= htmlspecialchars($lang['alert_failed_heading']) ?></h2>
				<ul>
					<li><?= htmlspecialchars($lang['alert_failed_message']) ?></li>
				</ul>
			</section>
			<?php
			}elseif ($status == 'accessdenied') {
				?>
				<section class="alert alert-danger">
					<h2><?= htmlspecialchars($lang['alert_access_denied_heading']) ?></h2>
					<ul>
						<li><?= htmlspecialchars($lang['alert_access_denied_message']) ?></li>
					</ul>
				</section>
				<?php
				}
				?>
			
			<!-- Standard catalogue/service/subservice cascade.
			     Always visible. Intake flow questions appear in
			     #intake-workflow below, outside this form. -->
			<form id="openrequest-cascade" method="post" action="/openrequest2.php?lang=<?= $_SESSION['lang'] ?>">
				<input type="hidden" id="catalogueid" name="catalogueid" value="<?= $selectedCatalogueId > 0 ? $selectedCatalogueId : '' ?>">
				<!-- Session-level CSRF token; validated by intake-flow.php action=start -->
				<input type="hidden" name="form_csrf" value="<?= htmlspecialchars($openrequestCsrf, ENT_QUOTES) ?>">

				<div class="form-group">
					<p id="router-instruction" class="small text-muted">
						<?= $isFr
							? 'Le formulaire affichera la prochaine étape lorsque vous sélectionnerez une option.'
							: 'The form will display the next step after you select an option.' ?>
					</p>
					<label for="service_stream">
						<span class="field-name">
							<?= $isFr ? 'Type de service' : 'Service type' ?>
							<strong>(<?= htmlspecialchars($lang['required']) ?>)</strong>
						</span>
					</label>
					<select id="service_stream" name="service_stream" class="form-control" required<?= $serverCascadeErrorTarget === 'service_stream' && $serverCascadeError !== '' ? ' aria-invalid="true" aria-describedby="cascade-error-summary"' : '' ?>>
						<option value=""<?= $selectedCatalogueId === 0 ? ' selected' : '' ?> disabled><?= htmlspecialchars($lang['select_placeholder']) ?></option>
					<?php foreach ($routerCatalogues as $cat): ?>
					<option value="catalogue_<?= (int) $cat['id'] ?>"<?= $selectedCatalogueId === (int) $cat['id'] ? ' selected' : '' ?>>
						<?= htmlspecialchars($isFr ? $cat['namefr'] : $cat['nameen']) ?>
					</option>
					<?php endforeach; ?>
				</select>
			</div>

			<?php if ($selectedCatalogueId === 0): ?>
			<noscript>
				<div class="form-group form-buttons">
					<button type="submit" class="btn btn-primary" name="cascade_action" value="select_catalogue"
					        formaction="/openrequest.php?lang=<?= htmlspecialchars($_SESSION['lang'], ENT_QUOTES) ?>">
						<?= htmlspecialchars($lang['intake_select_catalogue_continue']) ?>
					</button>
				</div>
			</noscript>
			<?php endif; ?>

			<section id="guidance-only" class="alert alert-info" style="display:none;" role="status" aria-live="polite" aria-atomic="true" tabindex="-1">
				<h2 class="h4"><?= $isFr ? 'Orientation' : 'Guidance' ?></h2>
				<div id="guidance-only-content"></div>
			</section>

				<div class="form-group divservice">
				<?php if ($selectedCatalogueId > 0 && !empty($serverServices)): ?>
					<label for="serviceid">
						<span class="field-name"><?= htmlspecialchars($lang['intake_service_label']) ?> <strong>(<?= htmlspecialchars($lang['required']) ?>)</strong></span>
					</label>
					<select class="form-control" id="serviceid" name="serviceid" required<?= $serverCascadeAction === 'select_catalogue' && $serverCascadeError === '' ? ' autofocus' : '' ?><?= $serverCascadeErrorTarget === 'serviceid' && $serverCascadeError !== '' ? ' aria-invalid="true" aria-describedby="cascade-error-summary"' : '' ?>>
						<option value=""<?= $selectedServiceId === 0 ? ' selected' : '' ?>><?= htmlspecialchars($lang['select_placeholder']) ?></option>
						<?php foreach ($serverServices as $service): ?>
						<option value="<?= (int) $service['id'] ?>"<?= $selectedServiceId === (int) $service['id'] ? ' selected' : '' ?>>
							<?= htmlspecialchars($isFr ? $service['namefr'] : $service['nameen']) ?>
						</option>
						<?php endforeach; ?>
					</select>
					<noscript>
						<div class="form-group form-buttons">
						<?php if ($serverFlowReady): ?>
							<input type="hidden" name="action" value="start">
							<button type="submit" class="btn btn-primary" formaction="/intake-flow.php" autofocus>
								<?= htmlspecialchars($lang['intake_start_questionnaire']) ?>
							</button>
						<?php else: ?>
							<button type="submit" class="btn btn-primary" name="cascade_action" value="select_service"
							        formaction="/openrequest.php?lang=<?= htmlspecialchars($_SESSION['lang'], ENT_QUOTES) ?>">
								<?= htmlspecialchars($lang['intake_select_service_continue']) ?>
							</button>
						<?php endif; ?>
						</div>
					</noscript>
				<?php endif; ?>
				</div>
				<div class="form-group divsubservice">
				</div>
				<div class="form-group divsubservice2">
				</div>
				<div class="form-group divsubservice3">
				</div>
			</form>

			<!-- Intake workflow questions are appended here by openrequest.js.
			     Placed outside the cascade form to avoid nested-form violations.
			     Destination nodes render their own <form> pointing at openrequest2.php.
			     No-JS: pre-rendered content from $noJsFragment is injected here.
			     data-intake-* attributes let openrequest.js rehydrate its in-memory
			     run state after a full page load/reload (e.g. after a language
			     switch) instead of relying on anything the browser remembers. -->
			<h2 id="intake-workflow-heading" class="h3<?= $noJsRun ? '' : ' wb-inv' ?>">
				<?= htmlspecialchars($lang['intake_step_heading'], ENT_QUOTES, 'UTF-8') ?>
			</h2>
			<div id="intake-workflow"
			     role="region"
			     aria-labelledby="intake-workflow-heading"
			     aria-live="polite"
			     data-intake-run-token="<?= htmlspecialchars($noJsRunToken, ENT_QUOTES, 'UTF-8') ?>"
			     data-intake-csrf="<?= htmlspecialchars($noJsRunCsrf, ENT_QUOTES, 'UTF-8') ?>"
			     data-intake-revision="<?= $noJsRunRevision ?>"><?= $noJsFragment ?></div>

			<!-- Start over control: visible whenever a run is active. Works
			     without JavaScript as a real form post to intake-flow.php
			     (action=restart); openrequest.js intercepts the click when JS
			     is available so the workflow can be cleared without a full
			     page reload. -->
			<div id="intake-start-over" class="mrgn-tp-md"<?= $noJsRun ? '' : ' style="display:none"' ?>>
				<form method="POST" action="/intake-flow.php">
					<input type="hidden" name="action" value="restart">
					<input type="hidden" name="run_token" id="intake-start-over-token" value="<?= htmlspecialchars($noJsRunToken, ENT_QUOTES, 'UTF-8') ?>">
					<input type="hidden" name="csrf_token" id="intake-start-over-csrf" value="<?= htmlspecialchars($noJsRunCsrf, ENT_QUOTES, 'UTF-8') ?>">
					<button type="submit" class="btn btn-link intake-start-over-btn">
						<?= htmlspecialchars($intakeClientStrings['start_over'], ENT_QUOTES, 'UTF-8') ?>
					</button>
				</form>
				<div id="intake-start-over-error" class="text-danger" role="alert" aria-live="assertive"></div>
			</div>
			<div id="intake-status" class="wb-inv" role="status" aria-live="polite"></div>

			<?php include 'includes/template/page-details.php'; ?>
		</main>
		<?php include 'includes/template/footer.php'; ?>
		<?php include 'includes/template/scripts.php'; ?>
	<script>
		// Client-facing strings for openrequest.js, sourced from the current
		// language file so wording is not duplicated/hardcoded in JavaScript.
		window.RMT_INTAKE_STRINGS = <?= json_encode(
			$intakeClientStrings,
			JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
		) ?>;
	</script>
	<script src="/public/js/openrequest.js"></script>
	</body>
</html>
<?php
// Commit the session (including openrequest_csrf) before closing the DB
// connection.  MySQLSessionHandler writes to tblphp_sessions using $link;
// calling mysqli_close() first would silently discard the session write.
session_write_close();
mysqli_close($link);
?>
