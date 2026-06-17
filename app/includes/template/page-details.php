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

// Use provided date or default to today
$dateModified = $dateModified ?? date('Y-m-d');
?>
<section class="pagedetails">
	<h2 class="wb-inv"><?= $pageDetailsLangStrings['page_details'] ?></h2>
	<div class="row">
		<div class="col-xs-12">
			<dl id="wb-dtmd">
				<dt><?= $pageDetailsLangStrings['date_modified'] ?></dt>
				<dd><time property="dateModified"><?= $dateModified ?></time></dd>
			</dl>
		</div>
	</div>
</section>
