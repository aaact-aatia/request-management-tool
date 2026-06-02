<?php
if (session_status() != PHP_SESSION_ACTIVE)
{
	session_start();
}
// Grab MySQL connection
require('sql.php');
/** @var mysqli $link */

// Get language from session
$lang = $_SESSION['lang'] ?? 'en';

// Grab the catalogue id
if(!empty($_GET['v1']))
{
	$subserviceid = mysqli_real_escape_string($link,$_GET['v1']);
}
else
{
	$subserviceid = "";
}

// Local translation arrays
$translations = [
	'request_details' => [
		'en' => 'Request details: <strong>(required)</strong>',
		'fr' => 'Détails de la demande: <strong>(requis)</strong>'
	],
	'continue' => [
		'en' => 'Continue',
		'fr' => 'Continuer'
	],
	'select_placeholder' => [
		'en' => 'Make your selection',
		'fr' => 'Faites votre choix'
	],
	'yes' => [
		'en' => 'Yes',
		'fr' => 'Oui'
	],
	'no' => [
		'en' => 'No',
		'fr' => 'Non'
	],
	'completed_checklist' => [
		'en' => 'Have you completed the',
		'fr' => 'Avez-vous complété la'
	],
	'corrected_failures' => [
		'en' => 'Have you corrected all mentioned failures from the previous audit? <strong>(required)</strong>',
		'fr' => 'Avez-vous corrigé tous les échecs mentionnés lors de la vérification précédente? <strong>(requis)</strong>'
	],
	'ms_doc_checklist' => [
		'en' => '<a href="https://bati-itao.github.io/resources/ms-doc-compliance-checklist-en.html" target="blank">Microsoft document checklist</a>',
		'fr' => '<a href="https://bati-itao.github.io/resources/ms-doc-compliance-checklist-fr.html" target="blank">liste de vérification des documents Microsoft</a>'
	],
	'pdf_checklist' => [
		'en' => '<a href="https://bati-itao.github.io/resources/pdf-accessibility-checklist-en.html" target="blank">PDF document checklist</a>',
		'fr' => '<a href="https://bati-itao.github.io/resources/pdf-accessibility-checklist-fr.html" target="blank">Liste de vérification de l\'accessibilité des documents PDF</a>'
	],
	'software_checklist' => [
		'en' => '<a href="https://bati-itao.github.io/resources/accessible-software-en.html" target="blank">software accessibility checklist</a>',
		'fr' => '<a href="https://bati-itao.github.io/resources/accessible-software-fr.html" target="blank">Liste de contrôle des évaluations de la conformité de l\'accessibilité (non Web / logiciel)</a>'
	],
	'easy_checks' => [
		'en' => '<a href="https://bati-itao.github.io/resources/a11ycheck-en.html" target="blank">Easy Checks for Web Accessibility</a>',
		'fr' => '<a href="https://bati-itao.github.io/resources/a11ycheck-fr.html" target="blank">Vérifications faciles pour l\'accessibilité web</a>'
	],
	'easy_checks_new' => [
		'en' => 'New Easy Checks for Web Accessibility',
		'fr' => 'Nouvelles Vérifications faciles pour l\'accessibilité du Web'
	],
	'easy_checks_desc' => [
		'en' => 'These simple steps help developers & content creators find and fix basic accessibility issues, reducing audit rounds',
		'fr' => 'Ces étapes simples aident les développeurs et les créateurs de contenu à identifier et corriger les problèmes d\'accessibilité de base, réduisant ainsi les cycles d\'audit.'
	],
	'march_2025_release' => [
		'en' => 'March 2025 Release',
		'fr' => 'Publication de mars 2025'
	]
];

// Alert messages
$alerts = [
	'3:1:1' => [
		'en' => 'Please consult the <a href="https://a11y.canada.ca/en/create-forms/">Create forms - Digital Accessibility Toolkit</a> before opening a new request, the answer you are seeking is probably there! If this does not answer your question, select the continue button that follows to submit a new request.',
		'fr' => 'Veuillez consulter le <a href="https://a11y.canada.ca/fr/creer-un-formulaire/index.html/">Créer un formulaire - Boîte à outils de l\'accessibilité numérique</a> avant d\'ouvrir une nouvelle demande. La réponse que vous cherchez s\'y trouve probablement! Sinon, sélectionnez le bouton continuer qui suit pour soumettre une nouvelle demande.'
	],
	'3:1:2' => [
		'en' => 'Please consult the <a href="https://a11y.canada.ca/en/design-a-course/">Design a course - Digital Accessibility Toolkit</a> before opening a new request, the answer you are seeking is probably there! If this does not answer your question, select the continue button that follows to submit a new request.',
		'fr' => 'Veuillez consulter le <a href="https://a11y.canada.ca/fr/concevoir-un-cours/index.html">Concevoir un cours - Boîte à outils de l\'accessibilité numérique</a> avant d\'ouvrir une nouvelle demande. La réponse que vous cherchez s\'y trouve probablement! Sinon, sélectionnez le bouton continuer qui suit pour soumettre une nouvelle demande.'
	],
	'3:1:3' => [
		'en' => 'Please consult the <a href="https://a11y.canada.ca/en/create-document/">Create document - Digital Accessibility Toolkit</a> before opening a new request, the answer you are seeking is probably there! If this does not answer your question, select the continue button that follows to submit a new request.',
		'fr' => 'Veuillez consulter le <a href="https://a11y.canada.ca/fr/creer-un-document/index.html">Créer un document - Boîte à outils de l\'accessibilité numérique</a> avant d\'ouvrir une nouvelle demande. La réponse que vous cherchez s\'y trouve probablement! Sinon, sélectionnez le bouton continuer qui suit pour soumettre une nouvelle demande.'
	],
	'3:1:4' => [
		'en' => 'Please consult the <a href="https://a11y.canada.ca/en/making-accessible-emails/">Making Accessible Emails - Digital Accessibility Toolkit</a> before opening a new request, the answer you are seeking is probably there! If this does not answer your question, select the continue button that follows to submit a new request.',
		'fr' => 'Veuillez consulter le <a href="https://a11y.canada.ca/fr/rendre-vos-courriels-accessibles/index.html">Rendre vos courriels accessibles - Boîte à outils de l\'accessibilité numérique</a> avant d\'ouvrir une nouvelle demande. La réponse que vous cherchez s\'y trouve probablement! Sinon, sélectionnez le bouton continuer qui suit pour soumettre une nouvelle demande.'
	],
	'3:1:5' => [
		'en' => 'Please consult the <a href="https://bati-itao.github.io/learning/esdc-self-paced-web-accessibility-course/index.html">ESDC Self-paced Web Accessibility Course </a>and <a href="https://a11y.canada.ca/en/create-web-content/">Create web content - Digital Accessibility Toolkit</a> before opening a new request, the answer you are seeking is probably there! If this does not answer your question, select the continue button that follows to submit a new request.',
		'fr' => 'Veuillez consulter le <a href="https://bati-itao.github.io/learning/esdc-self-paced-web-accessibility-course/index-fr.html">EDSC - Cours en accessibilité web</a> et <a href="https://a11y.canada.ca/fr/creer-du-contenu-web/index.html">Créer du contenu Web - Boîte à outils de l\'accessibilité numérique</a> avant d\'ouvrir une nouvelle demande. La réponse que vous cherchez s\'y trouve probablement! Sinon, sélectionnez le bouton continuer qui suit pour soumettre une nouvelle demande.'
	],
	'3:1:6' => [
		'en' => 'Please consult the <a href="https://a11y.canada.ca/en/designing-accessible-services/">Designing accessible services - Digital Accessibility Toolkit</a> before opening a new request, the answer you are seeking is probably there! If this does not answer your question, select the continue button that follows to submit a new request.',
		'fr' => 'Veuillez consulter le <a href="https://a11y.canada.ca/fr/principes-de-conception-pour-des-services-accessibles/index.html">Principes de conception pour des services accessibles - Boîte à outils de l\'accessibilité numérique</a> avant d\'ouvrir une nouvelle demande. La réponse que vous cherchez s\'y trouve probablement! Sinon, sélectionnez le bouton continuer qui suit pour soumettre une nouvelle demande.'
	],
	'3:1:7' => [
		'en' => 'Please consult the <a href="https://a11y.canada.ca/en/test-your-products/">Test your products - Digital Accessibility Toolkit</a> before opening a new request, the answer you are seeking is probably there! If this does not answer your question, select the continue button that follows to submit a new request.',
		'fr' => 'Veuillez consulter le <a href="https://a11y.canada.ca/fr/testez-vos-produits/index.html">Testez vos produits - Boîte à outils de l\'accessibilité numérique</a> avant d\'ouvrir une nouvelle demande. La réponse que vous cherchez s\'y trouve probablement! Sinon, sélectionnez le bouton continuer qui suit pour soumettre une nouvelle demande.'
	],
	'3:2:2' => [
		'en' => 'Please consult the planning inclusive meetings information page before opening a new request, the answer you are seeking is probably there!',
		'fr' => 'Veuillez consulter la page d\'information sur la planification des réunions inclusives avant d\'ouvrir une nouvelle demande, la réponse que vous cherchez est probablement là!'
	],
	'7:1:2' => [
		'en' => 'Please complete the EPMO accessibility checklist prior to opening a new request.',
		'fr' => 'Veuillez compléter la liste de contrôle d\'accessibilité du BGPE avant d\'ouvrir une nouvelle demande.'
	]
];


if ($subserviceid=='99:4:1' || $subserviceid=='99:4:9') {
?>
<div class="form-group">
    <label for="clientnotes"><span class="field-name"><?php echo $translations['request_details'][$lang]; ?></span></label>
    <textarea class="form-control" id="clientnotes" name="clientnotes" cols="50" rows="10" required></textarea>
</div>

<div class="form-group form-buttons">
    <button type="submit" class="btn btn-primary"><?php echo $translations['continue'][$lang]; ?></button>
</div>
<?php
} elseif (in_array($subserviceid, ['3:1:1', '3:1:2', '3:1:3', '3:1:4', '3:1:5', '3:1:6', '3:1:7'])) {
?>
<div class="alert alert-warning">
    <p><?php echo $alerts[$subserviceid][$lang]; ?></p>
</div>
<div class="form-group form-buttons">
        <button type="submit" class="btn btn-primary"><?php echo $translations['continue'][$lang]; ?></button>
    </div>
<?php
} elseif ($subserviceid=='3:2:2') {
?>
<div class="alert alert-warning">
    <p tabindex="0"><?php echo $alerts['3:2:2'][$lang]; ?></p>
</div>

<?php
} elseif ($subserviceid=='7:1:2') {
?>
<div class="alert alert-warning">
    <p tabindex="0"><?php echo $alerts['7:1:2'][$lang]; ?></p>
</div>

<?php
} elseif ($subserviceid=='6:1:1') {
?>
<label for="subserviceid2"><span class="field-name"><?php echo $translations['completed_checklist'][$lang]; ?> <?php echo $translations['ms_doc_checklist'][$lang]; ?>? <strong>(<?php echo $lang === 'fr' ? 'obligatoire' : 'required'; ?>)</strong></span></label>
<select class="form-control" id="subserviceid2" name="subserviceid2" onchange="ajax4(this.value)" required>
    <option value=""><?php echo $translations['select_placeholder'][$lang]; ?></option>
    <option value="6:1:1:1"><?php echo $translations['yes'][$lang]; ?></option>
    <option value="6:1:1:2"><?php echo $translations['no'][$lang]; ?></option>
</select>
<?php	
} elseif ($subserviceid=='6:2:1') {
?>
<label for="subserviceid2"><span class="field-name"><?php echo $translations['corrected_failures'][$lang]; ?></span></label>
<select class="form-control" id="subserviceid2" name="subserviceid2" onchange="ajax4(this.value)" required>
    <option value=""><?php echo $translations['select_placeholder'][$lang]; ?></option>
    <option value="6:2:1:1"><?php echo $translations['yes'][$lang]; ?></option>
    <option value="6:2:1:2"><?php echo $translations['no'][$lang]; ?></option>
</select>
<?php	
} elseif ($subserviceid=='6:5:1') {
?>
<label for="subserviceid2"><span class="field-name"><?php echo $translations['completed_checklist'][$lang]; ?> <?php echo $translations['pdf_checklist'][$lang]; ?>? <strong>(<?php echo $lang === 'fr' ? 'requis' : 'required'; ?>)</strong></span></label>
<select class="form-control" id="subserviceid2" name="subserviceid2" onchange="ajax4(this.value)" required>
    <option value=""><?php echo $translations['select_placeholder'][$lang]; ?></option>
    <option value="6:5:1:1"><?php echo $translations['yes'][$lang]; ?></option>
    <option value="6:5:1:2"><?php echo $translations['no'][$lang]; ?></option>
</select>
<?php	
} elseif ($subserviceid=='6:5:2') {
?>
<label for="subserviceid2"><span class="field-name"><?php echo $translations['corrected_failures'][$lang]; ?></span></label>
<select class="form-control" id="subserviceid2" name="subserviceid2" onchange="ajax4(this.value)" required>
    <option value=""><?php echo $translations['select_placeholder'][$lang]; ?></option>
    <option value="6:5:2:1"><?php echo $translations['yes'][$lang]; ?></option>
    <option value="6:5:2:2"><?php echo $translations['no'][$lang]; ?></option>
</select>
<?php
} elseif ($subserviceid=='8:1:1') {
?>
<label for="subserviceid2"><span class="field-name"><?php echo $translations['completed_checklist'][$lang]; ?> <?php echo $translations['software_checklist'][$lang]; ?>? <strong>(<?php echo $lang === 'fr' ? 'requis' : 'required'; ?>)</strong></span></label>
<select class="form-control" id="subserviceid2" name="subserviceid2" onchange="ajax4(this.value)" required>
    <option value=""><?php echo $translations['select_placeholder'][$lang]; ?></option>
    <option value="8:1:1:1"><?php echo $translations['yes'][$lang]; ?></option>
    <option value="8:1:1:2"><?php echo $translations['no'][$lang]; ?></option>
</select>
<?php
} elseif ($subserviceid=='8:1:2') {
?>
<label for="subserviceid2"><span class="field-name"><?php echo $translations['corrected_failures'][$lang]; ?></span></label>
<select class="form-control" id="subserviceid2" name="subserviceid2" onchange="ajax4(this.value)" required>
    <option value=""><?php echo $translations['select_placeholder'][$lang]; ?></option>
    <option value="8:1:2:1"><?php echo $translations['yes'][$lang]; ?></option>
    <option value="8:1:2:2"><?php echo $translations['no'][$lang]; ?></option>
</select>
<?php
} elseif ($subserviceid=='8:2:2:1') {
?>
<label for="subserviceid2"><span class="field-name"><?php echo $translations['completed_checklist'][$lang]; ?> <?php echo $translations['easy_checks'][$lang]; ?>? <strong>(<?php echo $lang === 'fr' ? 'requis' : 'required'; ?>)</strong></span>					
            <span class="label label-default"><?php echo $translations['march_2025_release'][$lang]; ?></span>
            
        </label>
        
    </span>
    <div class="alert alert-info">
    <h3><?php echo $translations['easy_checks_new'][$lang]; ?></h3>
    <p><?php echo $translations['easy_checks_desc'][$lang]; ?></p>
</div>
<select class="form-control" id="subserviceid2" name="subserviceid2" onchange="ajax4(this.value)" required>
    <option value=""><?php echo $translations['select_placeholder'][$lang]; ?></option>
    <option value="8:2:2:1:1"><?php echo $translations['yes'][$lang]; ?></option>
    <option value="8:2:2:1:2"><?php echo $translations['no'][$lang]; ?></option>
</select>
<br>
<!-- Notice message -->

<?php
} elseif ($subserviceid=='8:2:2:2') {
?>
<label for="subserviceid2"><span class="field-name"><?php echo $translations['corrected_failures'][$lang]; ?></span></label>
<select class="form-control" id="subserviceid2" name="subserviceid2" onchange="ajax4(this.value)" required>
    <option value=""><?php echo $translations['select_placeholder'][$lang]; ?></option>
    <option value="8:2:2:2:1"><?php echo $translations['yes'][$lang]; ?></option>
    <option value="8:2:2:2:2"><?php echo $translations['no'][$lang]; ?></option>
</select>
<?php
}elseif ($subserviceid=='4:1:1' OR $subserviceid=='4:2:1' OR $subserviceid=='4:3:1' OR $subserviceid=='3:2:1' OR $subserviceid=='7:1:1' OR $subserviceid=='8:4:1' OR $subserviceid=='8:4:2' OR $subserviceid = '8:2:1:1' OR $subserviceid = '8:2:1:2') {
	?>
<div class="form-group form-buttons">
    <button type="submit" class="btn btn-primary"><?php echo $translations['continue'][$lang]; ?></button>
</div>
<?php	
}
// Close connection
mysqli_close($link);
?>
