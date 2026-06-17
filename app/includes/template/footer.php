<?php

/**
 * Template: Application Footer
 */

// Footer-specific language strings
$footerLang = $_SESSION['lang'] ?? 'en';
$footerTranslations = [
	'en' => [
		'gc_corporate' => 'Government of Canada Corporate',
		'terms_conditions' => 'Terms and conditions',
		'privacy' => 'Privacy',
		'top_of_page' => 'Top of Page',
		'gc_symbol' => 'Symbol of the Government of Canada',
	],
	'fr' => [
		'gc_corporate' => 'Gouvernement du Canada',
		'terms_conditions' => 'Avis',
		'privacy' => 'Confidentialité',
		'top_of_page' => 'Haut de la page',
		'gc_symbol' => 'Symbole du gouvernement du Canada',
	]
];

$footerLangStrings = $footerTranslations[$footerLang];
?>
<footer id="wb-info" class="visible-sm visible-md visible-lg">
	<div class="gc-sub-footer">
		<div class="container d-flex align-items-center">
			<nav aria-labelledby="aboutWebApp" class="row">
				<h2 id="aboutWebApp"><?= $footerLangStrings['gc_corporate'] ?></h2>
				<ul>
					<li><a href="https://www.canada.ca/<?= $footerLang ?>/transparency/terms.html"><?= $footerLangStrings['terms_conditions'] ?></a></li>
					<li><a href="https://www.canada.ca/<?= $footerLang ?>/transparency/privacy.html"><?= $footerLangStrings['privacy'] ?></a></li>
				</ul>
			</nav>
			<div class="wtrmrk align-self-end">
				<img src="https://www.canada.ca/etc/designs/canada/wet-boew/assets/wmms-blk.svg" alt="<?= $footerLangStrings['gc_symbol'] ?>">
			</div>
		</div>
	</div>
</footer>