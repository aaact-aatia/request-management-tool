<?php
/**
 * Template: Page Details
 * Displays date modified information
 */

// Page details language strings
$pageDetailsTranslations = [
	'en' => [
		'page_details' => 'Page details',
		'date_modified' => 'Date modified:',
	],
	'fr' => [
		'page_details' => 'Détails de la page',
		'date_modified' => 'Date de modification :',
	]
];

$pageDetailsLangCode = $_SESSION['lang'] ?? 'en';
$pageDetailsLangStrings = $pageDetailsTranslations[$pageDetailsLangCode];

// Use provided date or derive it from the executing page file
if (!isset($dateModified)) {
	$pageDetailsSourceFile = $_SERVER['SCRIPT_FILENAME'] ?? '';
	if ($pageDetailsSourceFile !== '' && is_file($pageDetailsSourceFile)) {
		$dateModified = date('Y-m-d', filemtime($pageDetailsSourceFile));
	} else {
		$dateModified = date('Y-m-d');
	}
}
?>
<section class="pagedetails">
	<h2 class="wb-inv"><?= $pageDetailsLangStrings['page_details'] ?></h2>
	<div class="row">
		<div class="col-xs-12">
			<dl id="wb-dtmd">
				<dt><?= $pageDetailsLangStrings['date_modified'] ?></dt>
				<dd><time property="dateModified" datetime="<?= htmlspecialchars($dateModified, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($dateModified, ENT_QUOTES, 'UTF-8') ?></time></dd>
			</dl>
		</div>
	</div>
</section>
