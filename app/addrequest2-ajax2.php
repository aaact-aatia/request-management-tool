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
	$serviceid = mysqli_real_escape_string($link,$_GET['v1']);
}
else
{
	$serviceid = "";
}

// Load language file for common translations
$langFile = __DIR__ . "/lang/{$lang}.php";
$t = file_exists($langFile) ? include($langFile) : [];

// Local translation arrays for this file
$translations = [
	'request_details' => [
		'en' => 'Request details: <strong>(required)</strong>',
		'fr' => 'Détails de la demande: <strong>(requis)</strong>'
	],
	'continue' => [
		'en' => 'Continue',
		'fr' => 'Continuer'
	],
	'language_label' => [
		'en' => 'In what language do you need the coaching session? <strong>(required)</strong>',
		'fr' => 'Dans quelle langue avez-vous besoin de la séance de coaching? <strong>(requis)</strong>'
	],
	'select_placeholder' => [
		'en' => 'Make your selection',
		'fr' => 'Faites votre choix'
	],
	'english' => [
		'en' => 'English',
		'fr' => 'Anglais'
	],
	'french' => [
		'en' => 'French',
		'fr' => 'Français'
	],
	'type_of_service' => [
		'en' => 'Type of service: <strong>(required)</strong>',
		'fr' => 'Type de service: <strong>(requis)</strong>'
	],
	'select_type_request' => [
		'en' => 'Select the type of request: <strong>(required)</strong>',
		'fr' => 'Sélectionnez le type de demande: <strong>(requis)</strong>'
	],
	'audit' => [
		'en' => 'Audit',
		'fr' => 'Vérification'
	],
	'reaudit' => [
		'en' => 'Re-audit',
		'fr' => 'Vérification de suivi'
	],
	'coaching' => [
		'en' => 'Coaching',
		'fr' => 'Coaching'
	],
	'installation_removal' => [
		'en' => 'Installation / Removal',
		'fr' => 'Installation / Suppression de logiciels'
	],
	'troubleshooting' => [
		'en' => 'Troubleshooting / Configuration',
		'fr' => 'Dépannage / Configuration'
	],
	'no_match' => [
		'en' => 'The choices listed do not match my request.',
		'fr' => 'Les choix listés ne correspondent pas à ma demande.'
	],
	'courses' => [
		'en' => 'Courses',
		'fr' => 'Cours'
	],
	'documents' => [
		'en' => 'Documents',
		'fr' => 'Documents'
	],
	'emails' => [
		'en' => 'Emails',
		'fr' => 'Courriels'
	],
	'forms' => [
		'en' => 'Forms',
		'fr' => 'Formulaires'
	],
	'services' => [
		'en' => 'Services',
		'fr' => 'Services'
	],
	'testing' => [
		'en' => 'Testing',
		'fr' => 'Tests'
	],
	'web_content' => [
		'en' => 'Web content',
		'fr' => 'Contenu Web'
	],
	'yes' => [
		'en' => 'Yes',
		'fr' => 'Oui'
	],
	'no' => [
		'en' => 'No',
		'fr' => 'Non'
	],
	'project_type' => [
		'en' => 'Select the project type: <strong>(required)</strong>',
		'fr' => 'Sélectionnez le type de projet: <strong>(requis)</strong>'
	],
	'sprint_spot_check' => [
		'en' => 'Sprint spot-check',
		'fr' => 'Vérification ponctuelle du sprint'
	],
	'audit_sample' => [
		'en' => 'Audit of representative sample',
		'fr' => 'Audit d\'un échantillon représentatif'
	],
	'audit_type' => [
		'en' => 'Select the type of audit: <strong>(required)</strong>',
		'fr' => 'Sélectionnez le type de demande: <strong>(requis)</strong>'
	],
	'software_apps' => [
		'en' => 'Software applications',
		'fr' => 'Logiciel(s)'
	],
	'websites' => [
		'en' => 'Websites / web applications',
		'fr' => 'Site web / application'
	],
	'epmo_checklist' => [
		'en' => 'Have you completed the <a href="https://bati-itao.github.io/ict/ict-en.html" target="blank">EPMO accessibility checklist</a>? <strong>(required)</strong>',
		'fr' => 'Avez-vous rempli la <a href="https://bati-itao.github.io/ict/ict-fr.html" target="blank">liste de contrôle d\'accessibilité du Bureau de gestion des projets de l\'entreprise (BGPE)</a>? <strong>(requis)</strong>'
	]
];

// Alert messages with links
$alerts = [
	'2:2' => [
		'en' => 'Please consult the <a href="https://bati-itao.github.io/learning/esdc-self-paced-web-accessibility-course/index.html"> ESDC Self-paced Web Accessibility Course - IT Accessibility office </a> before opening a new request, the answer you are seeking is probably there! If this does not answer your question, select the continue button that follows to submit a new request.',
		'fr' => 'Veuillez consulter le <a href="https://bati-itao.github.io/learning/esdc-self-paced-web-accessibility-course/index-fr.html">EDSC - Cours en accessibilité web</a> avant d\'ouvrir une nouvelle demande. La réponse que vous cherchez s\'y trouve probablement! Sinon, sélectionnez le bouton continuer qui suit pour soumettre une nouvelle demande.'
	],
	'2:4_2:5' => [
		'en' => '<p tabindex="0">The ITAO offers the following courses with the College@ESDC, please register directly in SABA:</p>
    <ul>
        <li><a href="https://esdc.sabacloud.com/Saba/Web_wdk/CA1PRD0006/index/prelogin.rdf?spfUrl=%2FSaba%2FWeb_spf%2FCA1PRD0006%2Fapp%2Fme%2Flearningeventdetail%3Bspf-url%3Dcommon%252Fledetail%252Fcours000000000117733%253Freturnurl%253Dcatalog%25252Fsearch%25253FsearchText%25253Daccessibility%252526selectedTab%25253DLEARNINGEVENT%252526filter%25253D%2525257B%2525257D%26context%253Duser%26learnerId%253Demplo000000000046929#/login">Accessibility - Introduction to Digital Accessibility (ID: 0000134733)</a></li>
        <li><a href="https://esdc.sabacloud.com/Saba/Web_wdk/CA1PRD0006/index/prelogin.rdf?spfUrl=%2FSaba%2FWeb_spf%2FCA1PRD0006%2Fapp%2Fme%2Flearningeventdetail%3Bspf-url%3Dcommon%252Fledetail%252Fcours000000000117734%253Freturnurl%253Dcatalog%25252Fsearch%25253FsearchText%25253Daccessibility%25252520m365%252526selectedTab%25253DLEARNINGEVENT%252526filter%25253D%2525257B%2525257D%26context%253Duser%26learnerId%253Demplo000000000046929#/login">Accessibility – Creating accessible content using Microsoft 365 (ID: 0000134734)</a></li>
    </ul>
    <p tabindex="0">If this does not answer your question, select the continue button that follows to submit a new request.</p>',
		'fr' => '<p tabindex="0">Le BATI offre les cours suivants avec le Collège@EDSC, veuillez-vous inscrire directement dans SABA :</p>
    <ul>
        <li><a href="https://esdc.sabacloud.com/Saba/Web_spf/CA1PRD0006/app/me/learningeventdetail;spf-url=common%2Fledetail%2Fcours000000000117733%3Freturnurl%3Dcatalog%252Fsearch%253FsearchText%253Daccessibility%2526selectedTab%253DLEARNINGEVENT%2526filter%253D%25257B%25257D&context%3Duser&learnerId%3Demplo000000000046929">Accessibilité - Introduction à l\'accessibilité numérique (ID : 0000134733)</a></li>
        <li><a href="https://esdc.sabacloud.com/Saba/Web_spf/CA1PRD0006/app/me/learningeventdetail;spf-url=common%2Fledetail%2Fcours000000000117734%3Freturnurl%3Dcatalog%252Fsearch%253FsearchText%253Daccessibility%252520m365%2526selectedTab%253DLEARNINGEVENT%2526filter%253D%25257B%25257D&context%3Duser&learnerId%3Demplo000000000046929">Accessibilité - Créer du contenu accessible avec Microsoft 365 (ID : 0000134734)</a></li>
    </ul>
    <p tabindex="0">Si cela ne répond pas à votre question, sélectionnez le bouton continuer qui suit pour soumettre une nouvelle demande.</p>'
	],
	'2:1' => [
		'en' => 'Please consult the <a href="https://bati-itao.github.io/learning/curriculum/index.html">ITAO accessibility curriculum</a> before opening a new request, the answer you are seeking is probably there! If this does not answer your question, select the continue button that follows to submit a new request',
		'fr' => 'Veuillez consulter le <a href="https://bati-itao.github.io/learning/curriculum/index-fr.html">BATI programme de formation sur l\'accessibilité</a> avant d\'ouvrir une nouvelle demande. La réponse que vous cherchez s\'y trouve probablement! Sinon, sélectionnez le bouton continuer qui suit pour soumettre une nouvelle demande.'
	],
	'11:1' => [
		'en' => '<p>The Colour Contrast Analyzer is able to be installed or removed by yourself via the <a href="http://srmis-sigdi-iagent.prv/AppPortal/en/Home/Details/234">application catalogue</a>.</p>
<p>If you are having difficulty using or viewing the software window:</p>
<ol>
    <li>Navigate to the Colour Contrast Analyzer icon in your taskbar, or hover over it with your mouse until the preview fully appears.</li>
    <li>Use the <kbd>Shift</kbd> + <kbd>F10</kbd> keys, or right click the preview with your mouse.</li>
    <li>Select \'Move\'.</li>
    <li>Use the arrow keys to move the software window to your desired location.</li>
    <li>Use the <kbd>Enter</kbd> key, and the window should now be visible.</li>
</ol>
<p>If this doesn\'t work, try uninstalling and re-installing the application.</p>
<p>If this does not answer your question, select the continue button that follows to submit a new request.</p>',
		'fr' => '<p>Vous pouvez installer ou supprimer vous-même le Analyzeur de contraste couleur via le  <a href="http://srmis-sigdi-iagent.prv/AppPortal/fr/Home/Details/234">catalogue des applications</a>.</p>
<p>Si vous rencontrez des difficultés pour utiliser ou afficher la fenêtre du logiciel :</p>
<ol>
    <li>Accédez à l\'icône Analyzeur de contraste couleur dans votre barre des tâches ou passez dessus avec votre souris jusqu\'à ce que l\'aperçu apparaisse entièrement.</li>
    <li>Utilisez les touches <kbd>Shift</kbd> + <kbd>F10</kbd> ou faites un clic droit sur l\'aperçu avec votre souris.</li>
    <li>Sélectionnez « Déplacer ».</li>
    <li>Utilisez les touches fléchées pour déplacer la fenêtre du logiciel à l\'emplacement souhaité.</li>
    <li>Utilisez la touche <kbd>Entrée</kbd> et la fenêtre devrait maintenant être visible.</li>
</ol>
<p>Si cela ne fonctionne pas, essayez de désinstaller et de réinstaller l\'application.</p>
<p>Si cela ne répond pas à votre question, sélectionnez le bouton continuer qui suit pour soumettre une nouvelle demande.</p>'
	],
	'3:2' => [
		'en' => 'Please consult the <a href="https://a11y.canada.ca/en/best-practices-for-accessible-virtual-events/">Best practices for accessible virtual events - Digital Accessibility Toolkit </a>before opening a new request, the answer you are seeking is probably there! If this does not answer your question, select the continue button that follows to submit a new request.',
		'fr' => 'Veuillez consulter le <a href="https://a11y.canada.ca/fr/bonnes-pratiques-pour-les-evenements-virtuels-accessibles/index.html">Bonnes pratiques pour les événements virtuels accessibles - Boîte à outils de l\'accessibilité numérique</a> avant d\'ouvrir une nouvelle demande. La réponse que vous cherchez s\'y trouve probablement! Sinon, sélectionnez le bouton continuer qui suit pour soumettre une nouvelle demande.'
	]
];

// Render request details form for "99:X" cases
if (preg_match('/^99:\d+$/', $serviceid)) {
?>
<div class="form-group">
    <label for="clientnotes"><span class="field-name"><?php echo $translations['request_details'][$lang]; ?></span></label>
    <textarea class="form-control" id="clientnotes" name="clientnotes" cols="50" rows="10" required></textarea>
</div>

<div class="form-group form-buttons">
    <button type="submit" class="btn btn-primary"><?php echo $translations['continue'][$lang]; ?></button>
</div>
<?php
} elseif ($serviceid=="2:3") {
?>
<div class="form-group">
    <label for="language"><span class="field-name"><?php echo $translations['language_label'][$lang]; ?></span></label>
    <select class="form-control" id="language" name="language" required>
        <option value=""><?php echo $translations['select_placeholder'][$lang]; ?></option>
        <option value="<?php echo $translations['english'][$lang]; ?>"><?php echo $translations['english'][$lang]; ?></option>
        <option value="<?php echo $translations['french'][$lang]; ?>"><?php echo $translations['french'][$lang]; ?></option>
    </select>
</div>

<div class="form-group form-buttons">
    <button type="submit" class="btn btn-primary"><?php echo $translations['continue'][$lang]; ?></button>
</div>
<?php
} elseif ($serviceid=="2:2") {
?>
<div class="alert alert-warning">
    <p tabindex="0"><?php echo $alerts['2:2'][$lang]; ?></p>
</div>

<div class="form-group form-buttons">
    <button type="submit" class="btn btn-primary"><?php echo $translations['continue'][$lang]; ?></button>
</div>
<?php
} elseif ($serviceid=='2:4' || $serviceid=='2:5') {
?>
<div class="alert alert-info">
    <?php echo $alerts['2:4_2:5'][$lang]; ?>
</div>

<div class="form-group form-buttons">
    <button type="submit" class="btn btn-primary"><?php echo $translations['continue'][$lang]; ?></button>
</div>
<?php
} elseif ($serviceid=="2:1") {
?>
<div class="alert alert-warning">
    <p tabindex="0"><?php echo $alerts['2:1'][$lang]; ?></p>
</div>

<div class="form-group form-buttons">
    <button type="submit" class="btn btn-primary"><?php echo $translations['continue'][$lang]; ?></button>
</div>
<?php
}else if($serviceid == "11:1"){
?>
<div class="alert alert-warning">
<?php echo $alerts['11:1'][$lang]; ?>
</div>

<div class="form-group form-buttons">
    <button type="submit" class="btn btn-primary"><?php echo $translations['continue'][$lang]; ?></button>
</div>

<?php
} else if($serviceid == "3:1"){
?>
<label for="subserviceid"><span class="field-name"><?php echo $translations['type_of_service'][$lang]; ?></span></label>
<select class="form-control" id="subserviceid" name="subserviceid" onchange="ajax3(this.value)" required>
    <option value=""><?php echo $translations['select_placeholder'][$lang]; ?></option>
    <?php if ($lang === 'fr'): ?>
    <option value="3:1:5"><?php echo $translations['web_content'][$lang]; ?></option>
    <option value="3:1:4"><?php echo $translations['emails'][$lang]; ?></option>
    <option value="3:1:2"><?php echo $translations['courses'][$lang]; ?></option>
    <option value="3:1:3"><?php echo $translations['documents'][$lang]; ?></option>
    <option value="3:1:1"><?php echo $translations['forms'][$lang]; ?></option>
    <option value="3:1:6"><?php echo $translations['services'][$lang]; ?></option>
    <option value="3:1:7"><?php echo $translations['testing'][$lang]; ?></option>
    <?php else: ?>
    <option value="3:1:2"><?php echo $translations['courses'][$lang]; ?></option>
    <option value="3:1:3"><?php echo $translations['documents'][$lang]; ?></option>
    <option value="3:1:4"><?php echo $translations['emails'][$lang]; ?></option>
    <option value="3:1:1"><?php echo $translations['forms'][$lang]; ?></option>
    <option value="3:1:6"><?php echo $translations['services'][$lang]; ?></option>
    <option value="3:1:7"><?php echo $translations['testing'][$lang]; ?></option>
    <option value="3:1:5"><?php echo $translations['web_content'][$lang]; ?></option>
    <?php endif; ?>
</select>
<?php
}
elseif ($serviceid=="1:1" || $serviceid=="2:6" || $serviceid=="3:3" || $serviceid=="5:1" || $serviceid=="5:2" || $serviceid=="5:3" || $serviceid=="5:4" || $serviceid=="5:5" || $serviceid=="6:6" || $serviceid=="9:1" || $serviceid=="10:1" || $serviceid=="10:2" || $serviceid=="11:2" OR $serviceid=="11:3") {
?>
<div class="form-group form-buttons">
    <button type="submit" class="btn btn-primary"><?php echo $translations['continue'][$lang]; ?></button>
</div>
<?php	
} elseif ($serviceid=="3:2") {
?>
<div class="alert alert-warning">
<p><?php echo $alerts['3:2'][$lang]; ?></p>
</div>
<div class="form-group form-buttons">
    <button type="submit" class="btn btn-primary"><?php echo $translations['continue'][$lang]; ?></button>
</div>
<?php
} elseif ($serviceid=="4:1" || $serviceid=="4:2" || $serviceid=="4:3" || $serviceid=="4:4" || $serviceid=='4:5' || $serviceid=="4:6" || $serviceid=="4:7" || $serviceid=="4:8" || $serviceid=="4:10" || $serviceid=="4:11" || $serviceid=="4:12" || $serviceid=="4:13" || $serviceid=="4:14" || $serviceid=="4:15" || $serviceid=="4:16" ||  $serviceid=="4:17"  || $serviceid=="4:18" ) {
?>
<label for="subserviceid"><span class="field-name"><?php echo $translations['type_of_service'][$lang]; ?></span></label>
<select class="form-control" id="subserviceid" name="subserviceid" onchange="ajax3(this.value)" required>
    <option value=""><?php echo $translations['select_placeholder'][$lang]; ?></option>
    <option value="4:1:1"><?php echo $translations['coaching'][$lang]; ?></option>
    <option value="4:2:1"><?php echo $translations['installation_removal'][$lang]; ?></option>
    <option value="4:3:1"><?php echo $translations['troubleshooting'][$lang]; ?></option>
    <option value="99:4:1"><?php echo $translations['no_match'][$lang]; ?></option>
</select>
<?php
} elseif ($serviceid=='6:1' OR $serviceid=='6:2' OR $serviceid=='6:3' OR $serviceid=='6:4') {
?>
<label for="subserviceid"><span class="field-name"><?php echo $translations['select_type_request'][$lang]; ?></span></label>
<select class="form-control" id="subserviceid" name="subserviceid" onchange="ajax3(this.value)" required>
    <option value=""><?php echo $translations['select_placeholder'][$lang]; ?></option>
    <option value="6:1:1"><?php echo $translations['audit'][$lang]; ?></option>
    <option value="6:2:1"><?php echo $translations['reaudit'][$lang]; ?></option>
</select>
<?php
} elseif ($serviceid=='6:5') {
?>
<label for="subserviceid"><span class="field-name"><?php echo $translations['select_type_request'][$lang]; ?></span></label>
<select class="form-control" id="subserviceid" name="subserviceid" onchange="ajax3(this.value)" required>
	<option value=""><?php echo $translations['select_placeholder'][$lang]; ?></option>
	<option value="6:5:1"><?php echo $translations['audit'][$lang]; ?></option>
	<option value="6:5:2"><?php echo $translations['reaudit'][$lang]; ?></option>
</select>
<?php
} elseif ($serviceid=='7:1') {
?>
<label for="subserviceid"><span class="field-name"><?php echo $translations['epmo_checklist'][$lang]; ?></span></label>
<select class="form-control" id="subserviceid" name="subserviceid" onchange="ajax3(this.value)" required>
	<option value=""><?php echo $translations['select_placeholder'][$lang]; ?></option>
	<option value="7:1:1"><?php echo $translations['yes'][$lang]; ?></option>
	<option value="7:1:2"><?php echo $translations['no'][$lang]; ?></option>
</select>
<?php
// This is the one
} elseif ($serviceid=='8:1') {
?>
<label for="subserviceid"><span class="field-name"><?php echo $translations['select_type_request'][$lang]; ?></span></label>
<select class="form-control" id="subserviceid" name="subserviceid" onchange="ajax3(this.value)" required>
	<option value=""><?php echo $translations['select_placeholder'][$lang]; ?></option>
	<option value="8:1:1"><?php echo $translations['audit'][$lang]; ?></option>
	<option value="8:1:2"><?php echo $translations['reaudit'][$lang]; ?></option>
</select>
<?php
} elseif ($serviceid=='8:4') {
?>
<label for="subserviceid"><span class="field-name"><?php echo $translations['audit_type'][$lang]; ?></span></label>
<select class="form-control" id="subserviceid" name="subserviceid" onchange="ajax3(this.value)" required>
	<option value=""><?php echo $translations['select_placeholder'][$lang]; ?></option>
	<option value="8:4:1"><?php echo $translations['software_apps'][$lang]; ?></option>
	<option value="8:4:2"><?php echo $translations['websites'][$lang]; ?></option>
</select>
<?php
}
elseif ($serviceid=='8:2:1' || $serviceid=='8:2:2' || $serviceid=="8:2") {
	?>
<label for="subserviceid"><span class="field-name"><?php echo $translations['select_type_request'][$lang]; ?></span></label>
<select class="form-control" id="subserviceid" name="subserviceid" onchange="ajax3(this.value)" required>
	<option value=""><?php echo $translations['select_placeholder'][$lang]; ?></option>
	<?php if ($serviceid == '8:2:1') { ?>
	<option value="8:2:1:1"><?php echo $translations['sprint_spot_check'][$lang]; ?></option>
	<option value="8:2:1:2"><?php echo $translations['audit_sample'][$lang]; ?></option>
	<?php } elseif ($serviceid == '8:2:2') { ?>
	<option value="8:2:2:1"><?php echo $translations['audit'][$lang]; ?></option>
	<option value="8:2:2:2"><?php echo $translations['reaudit'][$lang]; ?></option>
	<?php } else { ?>
	<option value="8:2:1:1"><?php echo $translations['sprint_spot_check'][$lang]; ?></option>
	<option value="8:2:1:2"><?php echo $translations['audit_sample'][$lang]; ?></option>
	<option value="8:2:2:1"><?php echo $translations['audit'][$lang]; ?></option>
	<option value="8:2:2:2"><?php echo $translations['reaudit'][$lang]; ?></option>
	<?php } ?>
</select>
<?php
	}
// Close connection
mysqli_close($link);
?>
