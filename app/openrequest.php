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

// Handle language from query string or session
if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'fr'])) {
	$_SESSION['lang'] = $_GET['lang'];
}

// Set default language if not set
if (!isset($_SESSION['lang']) || !in_array($_SESSION['lang'], ['en', 'fr'])) {
	$_SESSION['lang'] = 'en';
}

// Signed-in users should go to the request list.
if (!empty($_SESSION['pid'])) {
	header("location:index.php?lang={$_SESSION['lang']}");
	exit();
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
?>

<!DOCTYPE html>
<!--[if lt IE 9]><html class="no-js lt-ie9" lang="<?= $_SESSION['lang'] ?>" dir="ltr"><![endif]-->
<!--[if gt IE 8]><!--><html class="no-js" lang="<?= $_SESSION['lang'] ?>" dir="ltr"><!--<![endif]-->
	<head>
		<meta charset="utf-8">
		<!-- Web Experience Toolkit (WET) / Boîte à outils de l'expérience Web (BOEW) wet-boew.github.io/wet-boew/License-en.html / wet-boew.github.io/wet-boew/Licence-fr.html -->
		<title><?= htmlspecialchars($lang['page_title']) ?></title>
		<meta content="width=device-width,initial-scale=1" name="viewport">
		<!-- Meta data -->
		<meta name="description" content="<?= htmlspecialchars($lang['page_description']) ?>">
		<!-- Meta data-->
		<?php include 'includes/refTop.php'; ?>
	</head>
	<body vocab="https://schema.org/" typeof="WebPage">
		<div id="def-top">
		</div>
		<!-- Write closure template -->
		<script>
			var defTop = document.getElementById("def-top");
			defTop.outerHTML = wet.builder.appTop({
				"appName": [{"text": "<?= $_SESSION['lang'] == 'fr' ? 'Outil de gestion des demandes (OGD)' : 'Request Management Tool (RMT)' ?>", "href": "/openrequest.php"}],
				<?php if(empty($_SESSION['pid'])){ ?>
				"signIn": [{"href": "/signin.php?lang=<?= $_SESSION['lang'] ?>"}],
				<?php } else { ?>
				"signOut": [{"href": "/signout.php?lang=<?= $_SESSION['lang'] ?>"}],
				"appSettings": [{"href": "/settings.php?lang=<?= $_SESSION['lang'] ?>"}],
				<?php } ?>
				"lngLinks": [{"lang": "<?= $_SESSION['lang'] == 'fr' ? 'en' : 'fr' ?>", "href": "/openrequest.php?lang=<?= $_SESSION['lang'] == 'fr' ? 'en' : 'fr' ?>", "text": "<?= $_SESSION['lang'] == 'fr' ? 'English' : 'Français' ?>"}],
				"menuPath": "/includes/appmenu.php",
				"breadcrumbs": [{
					"title": "<?= $_SESSION['lang'] == 'fr' ? 'Bureau de l\'accessibilité des TI' : 'IT Accessibility Office' ?>",
					"href": "http://iservice.prv/accessibility"
				}, {
					"title": "<?= $_SESSION['lang'] == 'fr' ? 'Outil de gestion des demandes' : 'Request Management Tool' ?>",
					"href": "/openrequest.php"
				}]
			});
		</script>
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
				<div class="form-group">
					<label for="catalogueid"><span class="field-name"><?= htmlspecialchars($lang['catalogue_label']) ?> <strong>(<?= htmlspecialchars($lang['required']) ?>)</strong></span></label>
					<select class="form-control" id="catalogueid" name="catalogueid" onchange="ajax1(this.value)" required>
						<option value=""><?= htmlspecialchars($lang['select_placeholder']) ?></option>
						<?php if ($_SESSION['lang'] == 'fr') { ?>
						<!-- French order -->
						<option value="10"><?= htmlspecialchars($lang['catalogue_procurement']) ?></option>
						<option value="7"><?= htmlspecialchars($lang['catalogue_epmo']) ?></option>
						<option value="3"><?= htmlspecialchars($lang['catalogue_advice']) ?></option>
						<option value="5"><?= htmlspecialchars($lang['catalogue_needs_assessment']) ?></option>
						<option value="11"><?= htmlspecialchars($lang['catalogue_testing_tools']) ?></option>
						<!-- <option value="1">Projet de conformité d'accessibilité (PCA)</option> -->
						<option value="2"><?= htmlspecialchars($lang['catalogue_accessibility_coaching']) ?></option>
						<option value="9"><?= htmlspecialchars($lang['catalogue_loan_bank']) ?></option>
						<option value="4"><?= htmlspecialchars($lang['catalogue_adaptive_technology']) ?></option>
						<option value="6"><?= htmlspecialchars($lang['catalogue_document_audits']) ?></option>
						<option value="8"><?= htmlspecialchars($lang['catalogue_accessibility_audit']) ?></option>
						<?php } else { ?>
						<!-- English order -->
						<!-- <option value="1">Accessibility Compliance Project (ACP)</option> -->
						<option value="2"><?= htmlspecialchars($lang['catalogue_accessibility_coaching']) ?></option>
						<option value="11"><?= htmlspecialchars($lang['catalogue_testing_tools']) ?></option>
						<option value="4"><?= htmlspecialchars($lang['catalogue_adaptive_technology']) ?></option>
						<option value="3"><?= htmlspecialchars($lang['catalogue_advice']) ?></option>
						<option value="5"><?= htmlspecialchars($lang['catalogue_needs_assessment']) ?></option>
						<option value="6"><?= htmlspecialchars($lang['catalogue_document_audits']) ?></option>
						<option value="7"><?= htmlspecialchars($lang['catalogue_epmo']) ?></option>
						<option value="8"><?= htmlspecialchars($lang['catalogue_accessibility_audit']) ?></option>
						<option value="9"><?= htmlspecialchars($lang['catalogue_loan_bank']) ?></option>
						<option value="10"><?= htmlspecialchars($lang['catalogue_procurement']) ?></option>
						<?php } ?>
					</select>
				</div>
				<div class="form-group divservice">
				</div>
				<div class="form-group divsubservice">
				</div>
				<div class="form-group divsubservice2">
				</div>
				<div class="form-group divsubservice3">
				</div>
			</form>
			
			<div id="def-preFooter">
			</div>
			<?php include 'includes/preFooter.php';?>
		</main>
		<div id="def-footer">
		</div>
		<?php include 'includes/appFooter.php';?>
	</body>
	<script>
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
</html>
<?php
// Close connection
mysqli_close($link);
?>
