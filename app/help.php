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

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\MarkdownConverter;

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

$isAdminUser = !empty($_SESSION['pid']) && isset($_SESSION['atype']) && (int) $_SESSION['atype'] === 1;
$isEnglishHelp = $_SESSION['lang'] === 'en';

$docsRoot = dirname(__DIR__) . '/docs';
$markdownDocuments = [];
$markdownDocumentsByPath = [];
$groupedMarkdownDocuments = [];
$requestedDoc = isset($_GET['doc']) ? trim((string) $_GET['doc']) : '';
$selectedDocument = null;

$extractMarkdownHeading = static function (string $markdown): string {
	$lines = preg_split('/\R/', $markdown) ?: [];
	$lineCount = count($lines);

	for ($i = 0; $i < $lineCount; $i++) {
		$line = trim($lines[$i]);
		if ($line === '') {
			continue;
		}

		if (preg_match('/^#{1,6}\s+(.+)$/', $line, $matches) === 1) {
			$title = trim($matches[1]);
			$title = preg_replace('/\s+#+$/', '', $title);
			return trim((string) $title);
		}

		if ($i + 1 < $lineCount) {
			$nextLine = trim($lines[$i + 1]);
			if (preg_match('/^=+$/', $nextLine) === 1 || preg_match('/^-+$/', $nextLine) === 1) {
				return $line;
			}
		}
	}

	return '';
};

$formatGroupTitle = static function (string $groupKey): string {
	$normalized = trim($groupKey);
	if ($normalized === '') {
		return '';
	}

	if (strtolower($normalized) === 'adr') {
		return 'Architecture Decision Records (ADR)';
	}

	if (strpos($normalized, '-') === false && strpos($normalized, '_') === false && strlen($normalized) <= 4) {
		return strtoupper($normalized);
	}

	$label = str_replace(['-', '_'], ' ', $normalized);
	return ucwords($label);
};

if ($isAdminUser && $isEnglishHelp && is_dir($docsRoot)) {
	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($docsRoot, FilesystemIterator::SKIP_DOTS)
	);

	foreach ($iterator as $fileInfo) {
		if (!$fileInfo->isFile()) {
			continue;
		}

		if (strtolower($fileInfo->getExtension()) !== 'md') {
			continue;
		}

		$absolutePath = $fileInfo->getPathname();
		$relativePath = str_replace('\\', '/', substr($absolutePath, strlen($docsRoot) + 1));
		$displayName = preg_replace('/\.md$/i', '', basename($relativePath));
		$markdownContent = @file_get_contents($absolutePath);
		$headingTitle = '';

		if (is_string($markdownContent) && $markdownContent !== '') {
			$headingTitle = $extractMarkdownHeading($markdownContent);
		}

		$markdownDocuments[] = [
			'absolute_path' => $absolutePath,
			'relative_path' => $relativePath,
			'display_name' => $displayName,
			'link_title' => $headingTitle !== '' ? $headingTitle : $displayName,
			'url_path' => rawurlencode($relativePath),
		];
	}

	usort($markdownDocuments, static function (array $a, array $b): int {
		return strnatcasecmp($a['relative_path'], $b['relative_path']);
	});

	foreach ($markdownDocuments as $doc) {
		$markdownDocumentsByPath[$doc['relative_path']] = $doc;

		$directory = dirname($doc['relative_path']);
		$groupKey = $directory === '.' ? '__root__' : explode('/', $directory)[0];

		if (!isset($groupedMarkdownDocuments[$groupKey])) {
			$groupedMarkdownDocuments[$groupKey] = [
				'title' => $groupKey === '__root__' ? '' : $formatGroupTitle($groupKey),
				'documents' => [],
			];
		}

		$groupedMarkdownDocuments[$groupKey]['documents'][] = $doc;
	}

	if (isset($groupedMarkdownDocuments['__root__'])) {
		$rootGroup = ['__root__' => $groupedMarkdownDocuments['__root__']];
		unset($groupedMarkdownDocuments['__root__']);
		ksort($groupedMarkdownDocuments, SORT_NATURAL | SORT_FLAG_CASE);
		$groupedMarkdownDocuments = $rootGroup + $groupedMarkdownDocuments;
	} else {
		ksort($groupedMarkdownDocuments, SORT_NATURAL | SORT_FLAG_CASE);
	}

	if ($requestedDoc !== '') {
		$requestedDoc = str_replace('\\', '/', rawurldecode($requestedDoc));
		$hasTraversal = strpos($requestedDoc, '..') !== false;
		$hasNullByte = strpos($requestedDoc, "\0") !== false;
		$isAbsolute = strpos($requestedDoc, '/') === 0;

		if (!$hasTraversal && !$hasNullByte && !$isAbsolute && isset($markdownDocumentsByPath[$requestedDoc])) {
			$selectedDocument = $markdownDocumentsByPath[$requestedDoc];
		}
	}
}

$markdownConverter = null;
if ($isAdminUser && class_exists(MarkdownConverter::class)) {
	$environment = new Environment([
		'html_input' => 'escape',
		'allow_unsafe_links' => false,
	]);
	$environment->addExtension(new CommonMarkCoreExtension());
	$markdownConverter = new MarkdownConverter($environment);
}

// Page-specific language strings
$translations = [
	'en' => [
		'legend_heading' => 'Legend for icons used',
		'icon_view' => 'View request details',
		'icon_new_window' => 'Link will open in a new window',
		'icon_warning' => 'Warning message for requests',
		'docs_heading' => 'Documentation',
		'docs_intro' => 'Markdown files from /docs are listed automatically for administrators.',
		'docs_empty' => 'No markdown files were found in /docs.',
		'docs_list_heading' => 'Available documents',
		'docs_open' => 'Open document',
		'docs_back' => 'Back to all documentation',
		'docs_not_found' => 'The requested document was not found.',
		'docs_load_error' => 'This file could not be loaded.',
		'docs_parser_unavailable' => 'Markdown parser is unavailable. Showing raw content.',
		'docs_english_only' => 'Documentation pages are available in English only.',
	],
	'fr' => [
		'legend_heading' => 'Légende des icônes utilisées',
		'icon_view' => 'Voir les détails de la demande',
		'icon_new_window' => 'Le lien s\'ouvrira dans une nouvelle fenêtre',
		'icon_warning' => 'Avertissement pour les demandes',
		'docs_heading' => 'Documentation',
		'docs_intro' => 'Les fichiers Markdown du dossier /docs sont listés automatiquement pour les administrateurs.',
		'docs_empty' => 'Aucun fichier Markdown n\'a ete trouve dans /docs.',
		'docs_list_heading' => 'Documents disponibles',
		'docs_open' => 'Ouvrir le document',
		'docs_back' => 'Retour a toute la documentation',
		'docs_not_found' => 'Le document demande est introuvable.',
		'docs_load_error' => 'Ce fichier n\'a pas pu etre charge.',
		'docs_parser_unavailable' => 'Le convertisseur Markdown est indisponible. Affichage du contenu brut.',
		'docs_english_only' => 'Les pages de documentation sont disponibles en anglais seulement.',
	]
];

$langStrings = $translations[$_SESSION['lang']];

$hideLanguageToggle = $isAdminUser && $selectedDocument !== null;

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

			<?php if ($isAdminUser): ?>
				<h2><?= htmlspecialchars($langStrings['docs_heading']) ?></h2>
				<p><?= htmlspecialchars($langStrings['docs_intro']) ?></p>

				<?php if (!$isEnglishHelp): ?>
					<div class="alert alert-info" role="status">
						<?= htmlspecialchars($langStrings['docs_english_only']) ?>
					</div>
				<?php elseif (empty($markdownDocuments)): ?>
					<div class="alert alert-info" role="status">
						<?= htmlspecialchars($langStrings['docs_empty']) ?>
					</div>
				<?php else: ?>
					<?php if ($requestedDoc !== '' && $selectedDocument === null): ?>
						<div class="alert alert-warning" role="status">
							<?= htmlspecialchars($langStrings['docs_not_found']) ?>
						</div>
					<?php endif; ?>

					<?php if ($selectedDocument !== null): ?>
						<?php
						$markdownContent = @file_get_contents($selectedDocument['absolute_path']);
						$renderedHtml = '';

						if ($markdownContent === false) {
							$renderedHtml = '<div class="alert alert-danger" role="status">' . htmlspecialchars($langStrings['docs_load_error']) . '</div>';
						} elseif ($markdownConverter instanceof MarkdownConverter) {
							$renderedHtml = $markdownConverter->convert($markdownContent)->getContent();
						} else {
							$renderedHtml = '<div class="alert alert-warning" role="status">' . htmlspecialchars($langStrings['docs_parser_unavailable']) . '</div>'
								. '<pre>' . htmlspecialchars($markdownContent) . '</pre>';
						}
						?>

						<p>
							<a href="help.php?lang=<?= urlencode($_SESSION['lang']) ?>"><?= htmlspecialchars($langStrings['docs_back']) ?></a>
						</p>
						<section class="panel panel-default" style="margin-bottom: 1.5em;">
							<header class="panel-heading">
								<h3 class="panel-title" style="margin: 0;"><?= htmlspecialchars($selectedDocument['link_title']) ?></h3>
								<p style="margin: 0.5em 0 0;"><small><?= htmlspecialchars($selectedDocument['relative_path']) ?></small></p>
							</header>
							<div class="panel-body">
								<?= $renderedHtml ?>
							</div>
						</section>
					<?php else: ?>
						<h3><?= htmlspecialchars($langStrings['docs_list_heading']) ?></h3>

						<?php if (isset($groupedMarkdownDocuments['__root__']) && !empty($groupedMarkdownDocuments['__root__']['documents'])): ?>
							<ul>
								<?php foreach ($groupedMarkdownDocuments['__root__']['documents'] as $doc): ?>
									<li>
										<a href="help.php?lang=<?= urlencode($_SESSION['lang']) ?>&amp;doc=<?= $doc['url_path'] ?>"><?= htmlspecialchars($doc['link_title']) ?></a>
										<small>(<?= htmlspecialchars($doc['relative_path']) ?>)</small>
									</li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>

						<?php foreach ($groupedMarkdownDocuments as $groupKey => $group): ?>
							<?php if ($groupKey === '__root__'): ?>
								<?php continue; ?>
							<?php endif; ?>

							<h4><?= htmlspecialchars($group['title']) ?></h4>
							<ul>
								<?php foreach ($group['documents'] as $doc): ?>
									<li>
										<a href="help.php?lang=<?= urlencode($_SESSION['lang']) ?>&amp;doc=<?= $doc['url_path'] ?>"><?= htmlspecialchars($doc['link_title']) ?></a>
										<small>(<?= htmlspecialchars($doc['relative_path']) ?>)</small>
									</li>
								<?php endforeach; ?>
							</ul>
						<?php endforeach; ?>
					<?php endif; ?>
				<?php endif; ?>
			<?php endif; ?>
			
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
