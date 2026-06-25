<?php
/**
 * Consolidated Bilingual Help Page
 * 
 * This page replaces the separate help-en.php and help-fr.php files
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

// =============================================================================
// PAGE FRONTMATTER - Define page metadata
// =============================================================================
$page = [
	'title' => [
		'en' => 'Help',
		'fr' => 'Aide'
	],
	'description' => [
		'en' => '',
		'fr' => ''
	]
];

// Extract values for current language
$pageTitle = $page['title'][$_SESSION['lang']];
$pageDescription = $page['description'][$_SESSION['lang']];

// Page-specific language strings
$translations = [
	'en' => [
		'legend_heading' => 'Legend for icons used',
		'icon_view' => 'View request details',
		'icon_new_window' => 'Link will open in a new window',
		'icon_warning' => 'Warning message for requests',
	],
	'fr' => [
		'legend_heading' => 'Légende des icônes utilisées',
		'icon_view' => 'Voir les détails de la demande',
		'icon_new_window' => 'Le lien s\'ouvrira dans une nouvelle fenêtre',
		'icon_warning' => 'Avertissement pour les demandes',
	]
];

$langStrings = $translations[$_SESSION['lang']];

// Include template head
include 'includes/template/head.php';
?>
		<?php include 'includes/template/header.php'; ?>
		<main role="main" property="mainContentOfPage" class="container">
			<h1 property="name" id="wb-cont"><?= $pageTitle ?></h1>
			
			<h2><?= $langStrings['legend_heading'] ?></h2>
			
			<ul class="list-unstyled">
				<li><span class="glyphicon glyphicon-eye-open"></span> = <?= $langStrings['icon_view'] ?></li>
				<li><span class="glyphicon glyphicon-new-window"></span> = <?= $langStrings['icon_new_window'] ?></li>
				<li><span class="glyphicon glyphicon-warning-sign"></span> = <?= $langStrings['icon_warning'] ?></li>
			</ul>
			
			<?php include 'includes/template/page-details.php'; ?>
		</main>
		<?php 
		include 'includes/template/footer.php';
		include 'includes/template/scripts.php';
		?>
	</body>
</html>
<?php
// Close connection
mysqli_close($link);
?>
