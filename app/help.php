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
require_once __DIR__ . '/includes/help_docs.php';

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
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
$maxMarkdownBytes = 262144;
$maxMarkdownReadSeconds = 0.25;

$docsRoot = dirname(__DIR__) . '/docs';
$markdownDocuments = [];
$markdownDocumentsByPath = [];
$groupedMarkdownDocuments = [];
$requestedDocRaw = isset($_GET['doc']) ? trim((string) $_GET['doc']) : '';
$requestedDoc = rmt_docs_sanitize_request_doc($requestedDocRaw);
$requestedDocInvalid = $requestedDocRaw !== '' && $requestedDoc === '';
$selectedDocument = null;

if (rmt_docs_should_show_index($isAdminUser, $_SESSION['lang']) && is_dir($docsRoot)) {
	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($docsRoot, FilesystemIterator::SKIP_DOTS)
	);

	foreach ($iterator as $fileInfo) {
		if (!$fileInfo->isFile()) {
			continue;
		}

		if (!rmt_docs_is_allowed_extension($fileInfo->getExtension())) {
			continue;
		}

		$absolutePath = $fileInfo->getPathname();
		$relativePath = str_replace('\\', '/', substr($absolutePath, strlen($docsRoot) + 1));
		if (rmt_docs_is_denied_path($relativePath)) {
			continue;
		}

		$displayName = preg_replace('/\.md$/i', '', basename($relativePath));
		$mtime = (int) @filemtime($absolutePath);
		$headingCacheKey = rmt_docs_build_cache_key('heading', $relativePath, $mtime);
		$cachedHeading = rmt_docs_cache_fetch($headingCacheKey);
		$headingTitle = '';

		if (is_string($cachedHeading) && $cachedHeading !== '') {
			$headingTitle = $cachedHeading;
		} else {
			$readResult = rmt_docs_read_file_with_limits($absolutePath, $maxMarkdownBytes, $maxMarkdownReadSeconds);
			if (!empty($readResult['ok']) && !empty($readResult['content'])) {
				$headingTitle = rmt_docs_extract_markdown_heading((string) $readResult['content']);
			}

			if ($headingTitle !== '') {
				rmt_docs_cache_store($headingCacheKey, $headingTitle);
			}
		}

		$markdownDocuments[] = [
			'absolute_path' => $absolutePath,
			'relative_path' => $relativePath,
			'display_name' => $displayName,
			'link_title' => $headingTitle !== '' ? $headingTitle : $displayName,
			'url_path' => rawurlencode($relativePath),
			'mtime' => $mtime,
		];
	}

	usort($markdownDocuments, static function (array $a, array $b): int {
		return strnatcasecmp($a['relative_path'], $b['relative_path']);
	});

	foreach ($markdownDocuments as $doc) {
		$markdownDocumentsByPath[$doc['relative_path']] = $doc;
	}

	$groupedMarkdownDocuments = rmt_docs_group_by_top_level($markdownDocuments);

	if ($requestedDoc !== '') {
		if (isset($markdownDocumentsByPath[$requestedDoc])) {
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
	$environment->addExtension(new GithubFlavoredMarkdownExtension());
	$markdownConverter = new MarkdownConverter($environment);
}

$addTableClasses = static function (string $html): string {
	return (string) preg_replace_callback('/<table(\s[^>]*)?>/i', static function (array $matches): string {
		$attrs = $matches[1] ?? '';
		if ($attrs === '') {
			return '<table class="table table-bordered">';
		}

		if (preg_match('/\bclass\s*=\s*(["\'])(.*?)\1/i', $attrs, $classMatch) === 1) {
			$existing = preg_split('/\s+/', trim($classMatch[2])) ?: [];
			$merged = array_values(array_unique(array_merge($existing, ['table', 'table-bordered'])));
			$newClass = 'class=' . $classMatch[1] . implode(' ', $merged) . $classMatch[1];
			$attrs = str_replace($classMatch[0], $newClass, $attrs);
			return '<table' . $attrs . '>';
		}

		return '<table' . $attrs . ' class="table table-bordered">';
	}, $html);
};

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
		'docs_load_too_large' => 'This markdown file is too large to display in Help.',
		'docs_load_timeout' => 'This markdown file took too long to load.',
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
		'docs_load_too_large' => 'Ce fichier Markdown est trop volumineux pour etre affiche dans l\'aide.',
		'docs_load_timeout' => 'Ce fichier Markdown prend trop de temps a charger.',
		'docs_parser_unavailable' => 'Le convertisseur Markdown est indisponible. Affichage du contenu brut.',
		'docs_english_only' => 'Les pages de documentation sont disponibles en anglais seulement.',
	]
];

$langStrings = $translations[$_SESSION['lang']];

$hideLanguageToggle = $isAdminUser && $selectedDocument !== null;

if ($selectedDocument !== null) {
	$pageTitle = $selectedDocument['link_title'];
}

// Include template head
include 'includes/template/head.php';
?>
		<?php include 'includes/template/header.php'; ?>
		<main role="main" property="mainContentOfPage" class="container">
			<?php if ($selectedDocument !== null): ?>
				<?php
				$markdownContent = '';
				$renderedHtml = '';
				$readResult = rmt_docs_read_file_with_limits(
					$selectedDocument['absolute_path'],
					$maxMarkdownBytes,
					$maxMarkdownReadSeconds
				);

				if (empty($readResult['ok'])) {
					$errorCode = (string) ($readResult['error'] ?? '');
					if ($errorCode === 'too_large') {
						$renderedHtml = '<div class="alert alert-warning" role="status">' . htmlspecialchars($langStrings['docs_load_too_large']) . '</div>';
					} elseif ($errorCode === 'timeout') {
						$renderedHtml = '<div class="alert alert-warning" role="status">' . htmlspecialchars($langStrings['docs_load_timeout']) . '</div>';
					} else {
						$renderedHtml = '<div class="alert alert-danger" role="status">' . htmlspecialchars($langStrings['docs_load_error']) . '</div>';
					}
				} else {
					$markdownContent = (string) $readResult['content'];
				}

				if ($renderedHtml === '' && $markdownContent === '') {
					$renderedHtml = '<div class="alert alert-danger" role="status">' . htmlspecialchars($langStrings['docs_load_error']) . '</div>';
				} elseif ($renderedHtml === '' && $markdownConverter instanceof MarkdownConverter) {
					$renderCacheKey = rmt_docs_build_cache_key('render', $selectedDocument['relative_path'], (int) $selectedDocument['mtime']);
					$cachedRender = rmt_docs_cache_fetch($renderCacheKey);
					if (is_string($cachedRender) && $cachedRender !== '') {
						$renderedHtml = $cachedRender;
					} else {
						$markdownBody = rmt_docs_strip_first_heading($markdownContent);
						$renderedHtml = $markdownConverter->convert($markdownBody)->getContent();
						rmt_docs_cache_store($renderCacheKey, $renderedHtml);
					}
				} elseif ($renderedHtml === '') {
					$renderedHtml = '<div class="alert alert-warning" role="status">' . htmlspecialchars($langStrings['docs_parser_unavailable']) . '</div>'
						. '<pre>' . htmlspecialchars($markdownContent) . '</pre>';
				}

				$renderedHtml = $addTableClasses($renderedHtml);
				?>

				<h1 property="name" id="wb-cont"><?= htmlspecialchars($selectedDocument['link_title']) ?></h1>
				<?= $renderedHtml ?>
			<?php elseif ($isAdminUser): ?>
				<h1 property="name" id="wb-cont"><?= $pageTitle ?></h1>

				<h2><?= $langStrings['legend_heading'] ?></h2>

				<ul class="list-unstyled">
					<li><span class="glyphicon glyphicon-eye-open"></span> = <?= $langStrings['icon_view'] ?></li>
					<li><span class="glyphicon glyphicon-new-window"></span> = <?= $langStrings['icon_new_window'] ?></li>
					<li><span class="glyphicon glyphicon-warning-sign"></span> = <?= $langStrings['icon_warning'] ?></li>
				</ul>

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
					<?php if (($requestedDoc !== '' || $requestedDocInvalid) && $selectedDocument === null): ?>
						<div class="alert alert-warning" role="status">
							<?= htmlspecialchars($langStrings['docs_not_found']) ?>
						</div>
					<?php endif; ?>

					<?php if ($selectedDocument === null): ?>
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
			<?php else: ?>
				<h1 property="name" id="wb-cont"><?= $pageTitle ?></h1>
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
