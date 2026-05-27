<?php
/**
 * Template: Head Section
 * Loads WET4 CSS and JavaScript resources
 * 
 * Expected variables from page:
 * - $pageTitle: The page title (will be appended with app name and org)
 * - $pageDescription (optional): Meta description for the page
 * - $extraStyles (optional): Page-level CSS injected as an inline style block
 */
require_once(__DIR__ . '/../config.php');

$config = get_app_config();
$lang = $_SESSION['lang'] ?? 'en';
$pageDescription = $pageDescription ?? '';
$otherLang = $lang === 'en' ? 'fr' : 'en';

// Build full page title: "Page Title - App Name - Organization"
$appName = $config['app']['name'][$lang];
$orgName = $config['app']['organization'][$lang];
$fullPageTitle = $pageTitle . ' - ' . $appName . ' - ' . $orgName;
?>
<!DOCTYPE html>
<html class="no-js" lang="<?= $lang ?>" dir="ltr">
	<head>
		<meta charset="utf-8">

		<!-- Web Experience Toolkit (WET) / Boîte à outils de l'expérience Web (BOEW)
		wet-boew.github.io/wet-boew/License-en.html / wet-boew.github.io/wet-boew/Licence-fr.html -->

		<title><?= htmlspecialchars($fullPageTitle) ?></title>
		<meta content="width=device-width,initial-scale=1" name="viewport">
		<meta name="description" content="<?= htmlspecialchars($pageDescription) ?>">

		<link rel="alternate" hreflang="<?= $otherLang ?>" href="" />
		
		<!-- WET4 Stylesheets -->
		<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.8.1/css/all.css" integrity="sha384-50oBUHEmvpQ+1lW4y57PTFmhCaXp0ML5d60M1M7uH2+nqUivzIebhndOJK28anvf" crossorigin="anonymous"/>
		<link rel="stylesheet" href="https://www.canada.ca/etc/designs/canada/wet-boew/css/theme.min.css"/>
		<!-- Temporary: Will be removing these CDTS stylesheets later -->
		<link rel="stylesheet" href="https://www.canada.ca/etc/designs/canada/cdts/gcweb/v5_0_2/cdts/cdtsfixes.css">
		<link rel="stylesheet" href="https://www.canada.ca/etc/designs/canada/cdts/gcweb/v5_0_2/cdts/cdtsapps.css">
		<link rel="stylesheet" href="includes/template/app.css">

		<?php if (!empty($extraStyles)): ?>
		<style>
		<?= $extraStyles ?>
		</style>
		<?php endif; ?>

		<!-- Dublin Core Metadata -->
		<meta name="dcterms.title" content="<?= htmlspecialchars($fullPageTitle) ?>" />
		<meta name="dcterms.description" content="<?= htmlspecialchars($pageDescription) ?>" />
		<meta name="dcterms.language" content="<?= $lang ?>" />
		<meta name="dcterms.modified" content="<?= date('Y-m-d') ?>" />
	</head>
