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

$isFr = $_SESSION['lang'] === 'fr';

$routerCatalogues = [];
$routerCatalogueQuery = mysqli_query(
	$link,
	"SELECT id, nameen, namefr, is_guidance_only, requires_ssc_check,
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
			
			<form method="post" action="/openrequest2.php?lang=<?= $_SESSION['lang'] ?>">
				<input type="hidden" id="catalogueid" name="catalogueid" value="">

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
					<select id="service_stream" name="service_stream" class="form-control" required>
						<option value="" selected disabled><?= htmlspecialchars($lang['select_placeholder']) ?></option>
					<?php foreach ($routerCatalogues as $cat): ?>
					<option value="catalogue_<?= (int) $cat['id'] ?>">
						<?= htmlspecialchars($isFr ? $cat['namefr'] : $cat['nameen']) ?>
					</option>
					<?php endforeach; ?>
				</select>
			</div>

			<section id="guidance-only" class="alert alert-info" style="display:none;" role="status" aria-live="polite" aria-atomic="true" tabindex="-1">
				<h2 class="h4"><?= $isFr ? 'Orientation' : 'Guidance' ?></h2>
				<div id="guidance-only-content"></div>
			</section>

				<div class="form-group divservice">
				</div>
				<div class="form-group divsubservice">
				</div>
				<div class="form-group divsubservice2">
				</div>
				<div class="form-group divsubservice3">
				</div>
			</form>
			<?php include 'includes/template/page-details.php'; ?>
		</main>
		<?php include 'includes/template/footer.php'; ?>
		<?php include 'includes/template/scripts.php'; ?>
	<script src="/public/js/openrequest.js"></script>
	</body>
</html>
<?php
// Close connection
mysqli_close($link);
?>
