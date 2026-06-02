<?php
/**
 * PAGE TEMPLATE - Copy this to create new pages
 * 
 * This template demonstrates the frontmatter pattern for RMT pages.
 * Define your page metadata at the top, and the templates will use those values.
 */

// Start session
if (session_status() != PHP_SESSION_ACTIVE) {
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

// =============================================================================
// PAGE FRONTMATTER - Define page metadata
// =============================================================================
$page = [
	'title' => [
		'en' => 'Page Title - Request Management Tool - IT Accessibility Office',
		'fr' => 'Titre de la page - Outil de gestion des demandes - Bureau de l\'accessibilité de la TI'
	],
	'description' => [
		'en' => 'Page description for SEO',
		'fr' => 'Description de la page pour le référencement'
	]
];

// Extract values for current language
$pageTitle = $page['title'][$_SESSION['lang']];
$pageDescription = $page['description'][$_SESSION['lang']];

// Page-specific language strings
$translations = [
	'en' => [
		'main_heading' => 'Main Page Heading',
		'subheading' => 'Subheading text',
		// Add more strings as needed
	],
	'fr' => [
		'main_heading' => 'Titre principal de la page',
		'subheading' => 'Texte de sous-titre',
		// Add more strings as needed
	]
];

$langStrings = $translations[$_SESSION['lang']];

// Include template head (outputs opening HTML, head tag, and CSS)
include 'includes/template/head.php';
?>

	<?php include 'includes/template/header.php'; ?>
	
	<main role="main" property="mainContentOfPage" class="container">
		<h1 property="name" id="wb-cont"><?= $langStrings['main_heading'] ?></h1>
		
		<!-- Your page content here -->
		
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
