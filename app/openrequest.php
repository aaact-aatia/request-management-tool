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
if (session_status() != PHP_SESSION_ACTIVE)
{
	session_start();
}

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
	"SELECT id, nameen, namefr FROM tblcatalogue WHERE status = 1 AND id IN (3, 6, 8) ORDER BY FIELD(id, 3, 8, 6)"
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
						<option value="guidance_workshops"><?= $isFr ? 'Ateliers et sessions d\'apprentissage (orientation seulement)' : 'Workshops and learning sessions (guidance only)' ?></option>
						<?php foreach ($routerCatalogues as $cat) { ?>
						<option value="catalogue_<?= (int) $cat['id'] ?>">
							<?= htmlspecialchars($isFr ? $cat['namefr'] : $cat['nameen']) ?>
						</option>
						<?php } ?>
					</select>
				</div>

				<div id="informational-options" class="form-group" style="display:none;">
					<label for="informational_kind"><?= $isFr ? 'Quel type de service informationnel recherchez-vous?' : 'What kind of informational service are you looking for?' ?></label>
					<select id="informational_kind" name="informational_kind" class="form-control">
						<option value="" selected disabled><?= htmlspecialchars($lang['select_placeholder']) ?></option>
						<option value="workshops"><?= $isFr ? 'Ateliers et sessions d\'apprentissage' : 'Workshops and learning sessions' ?></option>
						<option value="advice"><?= $isFr ? 'Conseils et orientation' : 'Advice and guidance' ?></option>
					</select>
				</div>

				<div id="software-options" class="form-group" style="display:none;">
					<label for="software_kind"><?= $isFr ? 'Quel type de test logiciel avez-vous besoin?' : 'What kind of software testing do you need?' ?></label>
					<select id="software_kind" name="software_kind" class="form-control">
						<option value="" selected disabled><?= htmlspecialchars($lang['select_placeholder']) ?></option>
						<option value="usability"><?= $isFr ? 'Tests utilisateurs (ergonomie)' : 'User testing (usability)' ?></option>
						<option value="conformance"><?= $isFr ? 'Tests de conformité d\'accessibilité' : 'Accessibility conformance testing' ?></option>
					</select>
				</div>

				<div id="documents-options" class="form-group" style="display:none;">
					<label for="documents_ssc"><?= $isFr ? 'Êtes-vous membre de Services partagés Canada (SPC)?' : 'Are you a member of Shared Services Canada (SSC)?' ?></label>
					<select id="documents_ssc" name="documents_ssc" class="form-control">
						<option value="" selected disabled><?= htmlspecialchars($lang['select_placeholder']) ?></option>
						<option value="yes"><?= $isFr ? 'Oui' : 'Yes' ?></option>
						<option value="no"><?= $isFr ? 'Non' : 'No' ?></option>
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
	<script>
	// Phase 1 policy: workshops/learning is guidance-only.
	// Future option: convert this branch to a lightweight tracked request.
	const guidanceWorkshop = `<?= $isFr
		? "<p>Ce parcours est informatif seulement pour le moment. Consultez les ressources de formation, puis contactez-nous pour toute question supplémentaire.</p><ul><li><a href='https://www.gcpedia.gc.ca/wiki/GC_Accessibility_Training_and_Events_/_Formation_et_%C3%A9v%C3%A9nements_du_GC_sur_l%27accessibilit%C3%A9' target='_blank' rel='noopener noreferrer'>Ressources de formation en accessibilité du GC (ouvre dans un nouvel onglet)</a></li><li><a href='mailto:AAACT-AATIA@ssc-spc.gc.ca'>AAACT-AATIA@ssc-spc.gc.ca</a></li></ul>"
		: "<p>This path is guidance-only for now. Review learning resources and contact us if you still need help.</p><ul><li><a href='https://www.gcpedia.gc.ca/wiki/GC_Accessibility_Training_and_Events_/_Formation_et_%C3%A9v%C3%A9nements_du_GC_sur_l%27accessibilit%C3%A9' target='_blank' rel='noopener noreferrer'>GC Accessibility Training and Events (opens in a new tab)</a></li><li><a href='mailto:AAACT-AATIA@ssc-spc.gc.ca'>AAACT-AATIA@ssc-spc.gc.ca</a></li></ul>" ?>`;

	// Phase 1 policy: non-SSC document requests are guidance-only.
	// Future option: convert this branch to a lightweight tracked request.
	const guidanceNonSscDocuments = `<?= $isFr
		? "<p>Actuellement, cette option est informative seulement. Veuillez communiquer avec votre direction des communications pour le soutien en accessibilité documentaire.</p><ul><li><a href='https://a11y.canada.ca/en/create-document/' target='_blank' rel='noopener noreferrer'>Digital Accessibility Toolkit - Create document (ouvre dans un nouvel onglet)</a></li><li><a href='https://www.csps-efpc.gc.ca/video/making-documents-accessible-eng.aspx' target='_blank' rel='noopener noreferrer'>CSPS - Making Documents Accessible (ouvre dans un nouvel onglet)</a></li><li><a href='mailto:AAACT-AATIA@ssc-spc.gc.ca'>AAACT-AATIA@ssc-spc.gc.ca</a></li></ul>"
		: "<p>This option is guidance-only for now. Please contact your communications branch for document accessibility support.</p><ul><li><a href='https://a11y.canada.ca/en/create-document/' target='_blank' rel='noopener noreferrer'>Digital Accessibility Toolkit - Create document (opens in a new tab)</a></li><li><a href='https://www.csps-efpc.gc.ca/video/making-documents-accessible-eng.aspx' target='_blank' rel='noopener noreferrer'>CSPS - Making Documents Accessible (opens in a new tab)</a></li><li><a href='mailto:AAACT-AATIA@ssc-spc.gc.ca'>AAACT-AATIA@ssc-spc.gc.ca</a></li></ul>" ?>`;

	function showElement(id, visible) {
		const node = document.getElementById(id);
		if (!node) return;
		node.style.display = visible ? '' : 'none';
	}

	function clearLegacySelectors() {
		$(".divservice").empty();
		$(".divsubservice").empty();
		$(".divsubservice2").empty();
		$(".divsubservice3").empty();
	}

	function setTrackableCatalogue(catalogueId) {
		document.getElementById('catalogueid').value = catalogueId;
		showElement('guidance-only', false);
		ajax1(catalogueId);
	}

	function showGuidanceOnly(html) {
		document.getElementById('catalogueid').value = '';
		clearLegacySelectors();
		document.getElementById('guidance-only-content').innerHTML = html;
		showElement('guidance-only', true);
		document.getElementById('guidance-only').focus();
	}

	function onStreamChange(stream) {
		showElement('informational-options', stream === 'informational');
		showElement('software-options', stream === 'software');
		showElement('documents-options', stream === 'documents');
		document.getElementById('informational_kind').value = '';
		document.getElementById('software_kind').value = '';
		document.getElementById('documents_ssc').value = '';
		document.getElementById('catalogueid').value = '';
		clearLegacySelectors();
		showElement('guidance-only', false);
	}

	function onInformationalChoice(kind) {
		if (kind === 'workshops') {
			showGuidanceOnly(guidanceWorkshop);
			return;
		}
		setTrackableCatalogue(3);
	}

	function onSoftwareChoice() {
		setTrackableCatalogue(8);
	}

	function onDocumentsChoice(isSsc) {
		if (isSsc === 'yes') {
			setTrackableCatalogue(6);
			return;
		}
		showGuidanceOnly(guidanceNonSscDocuments);
	}

	document.addEventListener('DOMContentLoaded', function () {
		document.getElementById('service_stream').addEventListener('change', function (evt) {
			const selected = evt.target.value;
			if (!selected) {
				onStreamChange('none');
				return;
			}

			if (selected === 'guidance_workshops') {
				onStreamChange('none');
				showGuidanceOnly(guidanceWorkshop);
				return;
			}

			if (selected === 'catalogue_3') {
				onStreamChange('informational');
				return;
			}

			if (selected === 'catalogue_8') {
				onStreamChange('software');
				return;
			}

			if (selected === 'catalogue_6') {
				onStreamChange('documents');
				return;
			}
		});

		document.getElementById('informational_kind').addEventListener('change', function (evt) {
			onInformationalChoice(evt.target.value);
		});

		document.getElementById('software_kind').addEventListener('change', function () {
			onSoftwareChoice();
		});

		document.getElementById('documents_ssc').addEventListener('change', function (evt) {
			onDocumentsChoice(evt.target.value);
		});
	});

	// AJAX functions for cascading dropdowns
	function ajax1(val1){
		$.ajax({url:"addrequest2-ajax1.php?v1="+val1,success:function(result){
			$(".divservice").html(result);
		}});
		$(".divsubservice").hide();
		$(".divsubservice2").hide();
		$(".divsubservice3").hide();
	}
	function ajax2(val1){
		$.ajax({url:"addrequest2-ajax2.php?v1="+val1,success:function(result){
			$(".divsubservice").html(result);
		}});
		$(".divsubservice").show();
		$(".divsubservice2").hide();
		$(".divsubservice3").hide();
	}
	function ajax3(val1){
		$.ajax({url:"addrequest2-ajax3.php?v1="+val1,success:function(result){
			$(".divsubservice2").html(result);
		}});
		$(".divsubservice2").show();
		$(".divsubservice3").hide();
	}
	function ajax4(val1){
		$.ajax({url:"addrequest2-ajax4.php?v1="+val1,success:function(result){
			$(".divsubservice3").html(result);
		}});
		$(".divsubservice3").show();
	}
	</script>
	</body>
</html>
<?php
// Close connection
mysqli_close($link);
?>
