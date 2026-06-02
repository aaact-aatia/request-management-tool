<?php
if (session_status() != PHP_SESSION_ACTIVE)
{
	session_start();
}
// Grab MySQL connection
require('sql.php');

// Get language from session
$lang = $_SESSION['lang'] ?? 'en';

// Grab the catalogue id
if(!empty($_GET['v1']))
{
	$catalogueid = mysqli_real_escape_string($link,$_GET['v1']);
}
else
{
	$catalogueid = "";
}

// Translation arrays for dropdown options
$translations = [
	'label' => [
		'en' => 'Which of the choices below best describes your request? <strong>(required)</strong>',
		'fr' => 'Lequel des choix ci-dessous décrit le mieux votre demande? <strong>(requis)</strong>'
	],
	'select_placeholder' => [
		'en' => 'Make your selection',
		'fr' => 'Faites votre choix'
	],
	'no_match' => [
		'en' => 'The choices listed do not match my request.',
		'fr' => 'Les choix listés ne correspondent pas à ma demande.'
	],
	// Catalogue 1 options
	'1:1' => [
		'en' => 'Project related information',
		'fr' => 'Informations relatives au projet'
	],
	// Catalogue 2 options
	'2:1' => [
		'en' => 'Accessibility learning curriculum',
		'fr' => 'Programme de formation sur l\'accessibilité'
	],
	'2:2' => [
		'en' => 'ICT developer coaching',
		'fr' => 'Coaching pour développeur TIC'
	],
	'2:5' => [
		'en' => 'Microsoft document and email coaching',
		'fr' => 'Coaching pour document Microsoft et courriel'
	],
	'2:4' => [
		'en' => 'PDF documents coaching',
		'fr' => 'Coaching pour document PDF'
	],
	// Catalogue 3 options
	'3:3' => [
		'en' => 'Adaptive technologies',
		'fr' => 'Technologies adaptatives'
	],
	'3:1' => [
		'en' => 'ICT design and development (including documents)',
		'fr' => 'Conception et développement TIC (y compris les documents)'
	],
	'3:2' => [
		'en' => 'Planning inclusive events',
		'fr' => 'Planification d\'événements inclusifs'
	],
	// Catalogue 4 options - software names remain same in both languages
	'4:12' => [
		'en' => 'ZoomText for Windows',
		'fr' => 'ZoomText pour Windows'
	],
	// Catalogue 5 options
	'5:1' => [
		'en' => 'Blindness / Low vision',
		'fr' => 'Basse Vision / Cécité'
	],
	'5:2' => [
		'en' => 'Cognitive disability',
		'fr' => 'Cognition'
	],
	'5:3' => [
		'en' => 'Deafness / Hard of hearing',
		'fr' => 'Déficience auditive'
	],
	'5:4' => [
		'en' => 'Mobility',
		'fr' => 'Mobilité et dextérité'
	],
	'5:5' => [
		'en' => 'Multiple needs',
		'fr' => 'Besoins multiples'
	],
	// Catalogue 6 options
	'6:4' => [
		'en' => 'Emails',
		'fr' => 'Courriels'
	],
	'6:2' => [
		'en' => 'Microsoft Excel documents',
		'fr' => 'Documents Microsoft Excel'
	],
	'6:3' => [
		'en' => 'Microsoft PowerPoint presentations',
		'fr' => 'Présentations Microsoft PowerPoint'
	],
	'6:1' => [
		'en' => 'Microsoft Word documents',
		'fr' => 'Documents Microsoft Word'
	],
	'6:5' => [
		'en' => 'PDF documents',
		'fr' => 'Documents PDF'
	],
	'6:6' => [
		'en' => 'Other document type',
		'fr' => 'Autre type de document'
	],
	// Catalogue 7 options
	'7:1' => [
		'en' => 'Project consultation',
		'fr' => 'Consultation de projet'
	],
	// Catalogue 8 options
	'8:4' => [
		'en' => 'Audit report question(s)',
		'fr' => 'Questions relatives au rapport d\'évaluation'
	],
	'8:1' => [
		'en' => 'Software applications',
		'fr' => 'Logiciel(s)'
	],
	'8:2' => [
		'en' => 'Websites / web applications',
		'fr' => 'Site web / application'
	],
	// Catalogue 9 options
	'9:1' => [
		'en' => 'Adaptive hardware loan',
		'fr' => 'Prêt d\'équipement adaptatif'
	],
	// Catalogue 10 options
	'10:1' => [
		'en' => 'Procurement guidelines or consultation',
		'fr' => 'Directives d\'acquisition ou consultation'
	],
	'10:2' => [
		'en' => 'Vendor / Request for proposals (RFP) evaluation',
		'fr' => 'Évaluation des fournisseurs / demande de propositions (RFP)'
	],
	// Catalogue 11 options
	'11:1' => [
		'en' => 'Colour Contrast Analyzer',
		'fr' => 'Analyzeur de contraste couleur'
	]
];

if ($catalogueid==1) {
?>
				<label for="serviceid"><span class="field-name"><?php echo $translations['label'][$lang]; ?></span></label>
				<select class="form-control" id="serviceid" name="serviceid" onchange="ajax2(this.value)" required>
					<option value=""><?php echo $translations['select_placeholder'][$lang]; ?></option>
					<option value="1:1"><?php echo $translations['1:1'][$lang]; ?></option>
					<option value="99:1"><?php echo $translations['no_match'][$lang]; ?></option>					
				</select>
<?php
} elseif ($catalogueid==2) {
?>
				<label for="serviceid"><span class="field-name"><?php echo $translations['label'][$lang]; ?></span></label>
				<select class="form-control" id="serviceid" name="serviceid" onchange="ajax2(this.value)" required>
					<option value=""><?php echo $translations['select_placeholder'][$lang]; ?></option>
					<?php if ($lang === 'fr'): ?>
					<option value="2:2"><?php echo $translations['2:2'][$lang]; ?></option>
					<!-- <option value="2:3">Coaching pour gestionnaire</option> -->
					<option value="2:5"><?php echo $translations['2:5'][$lang]; ?></option>
					<option value="2:4"><?php echo $translations['2:4'][$lang]; ?></option>
					<!-- <option value="2:6">PCA - Coaching en développement d'applications</option>					 -->
					<option value="2:1"><?php echo $translations['2:1'][$lang]; ?></option>
					<?php else: ?>
					<option value="2:1"><?php echo $translations['2:1'][$lang]; ?></option>
					<!-- <option value="2:6">ACP - Application development coaching</option> -->
					<option value="2:2"><?php echo $translations['2:2'][$lang]; ?></option>
					<!-- <option value="2:3">Manager coaching</option> -->
					<option value="2:5"><?php echo $translations['2:5'][$lang]; ?></option>
					<option value="2:4"><?php echo $translations['2:4'][$lang]; ?></option>
					<?php endif; ?>
					<option value="99:2"><?php echo $translations['no_match'][$lang]; ?></option>					
				</select>
<?php
} elseif ($catalogueid==3) {
?>
				<label for="serviceid"><span class="field-name"><?php echo $translations['label'][$lang]; ?></span></label>
				<select class="form-control" id="serviceid" name="serviceid" onchange="ajax2(this.value)" required>
					<option value=""><?php echo $translations['select_placeholder'][$lang]; ?></option>
					<?php if ($lang === 'fr'): ?>
					<option value="3:1"><?php echo $translations['3:1'][$lang]; ?></option>
					<option value="3:2"><?php echo $translations['3:2'][$lang]; ?></option>
					<option value="3:3"><?php echo $translations['3:3'][$lang]; ?></option>
					<?php else: ?>
					<option value="3:3"><?php echo $translations['3:3'][$lang]; ?></option>
					<option value="3:1"><?php echo $translations['3:1'][$lang]; ?></option>
					<option value="3:2"><?php echo $translations['3:2'][$lang]; ?></option>
					<?php endif; ?>
					<option value="99:3"><?php echo $translations['no_match'][$lang]; ?></option>					
				</select>
<?php
} elseif ($catalogueid==4) {
?>
				<label for="serviceid"><span class="field-name"><?php echo $translations['label'][$lang]; ?></span></label>
				<select class="form-control" id="serviceid" name="serviceid" onchange="ajax2(this.value)" required>
					<option value=""><?php echo $translations['select_placeholder'][$lang]; ?></option>
					<option value="4:1">Dragon Medical Practice</option>
					<option value="4:2">Dragon NaturallySpeaking (Professional Edition)</option>
					<option value="4:13">Interact AS</option>
					<option value="4:14">Interact streamer</option>
					<option value="4:3">J-Say</option>
					<option value="4:4">JAWS</option>
					<option value="4:5">Kurzweil 3000</option>
					<option value="4:15">NVDA</option>
					<option value="4:6">OpenBook</option>
					<!-- <option value="4:7">Scribe Médialexie</option> -->
					<option value="4:16">SuperNova</option>
					<option value="4:8">TextAloud</option>
					<option value="4:10">wordQ & speakQ</option>
					<!--<option value="4:11">WorkSafe Sam</option> -->
					<option value="4:12"><?php echo $translations['4:12'][$lang]; ?></option>					
					<option value="4:17">Tint & Track</option>
					<option value="4:18">Pixie</option>
					<option value="99:4"><?php echo $translations['no_match'][$lang]; ?></option>					
				</select>
<?php
} elseif ($catalogueid==5) {
?>
				<label for="serviceid"><span class="field-name"><?php echo $translations['label'][$lang]; ?></span></label>
				<select class="form-control" id="serviceid" name="serviceid" onchange="ajax2(this.value)" required>
					<option value=""><?php echo $translations['select_placeholder'][$lang]; ?></option>
					<?php if ($lang === 'fr'): ?>
					<option value="5:1"><?php echo $translations['5:1'][$lang]; ?></option>
					<option value="5:5"><?php echo $translations['5:5'][$lang]; ?></option>
					<option value="5:2"><?php echo $translations['5:2'][$lang]; ?></option>
					<option value="5:3"><?php echo $translations['5:3'][$lang]; ?></option>
					<option value="5:4"><?php echo $translations['5:4'][$lang]; ?></option>
					<?php else: ?>
					<option value="5:1"><?php echo $translations['5:1'][$lang]; ?></option>
					<option value="5:2"><?php echo $translations['5:2'][$lang]; ?></option>
					<option value="5:3"><?php echo $translations['5:3'][$lang]; ?></option>
					<option value="5:4"><?php echo $translations['5:4'][$lang]; ?></option>
					<option value="5:5"><?php echo $translations['5:5'][$lang]; ?></option>
					<?php endif; ?>
					<option value="99:5"><?php echo $translations['no_match'][$lang]; ?></option>					
				</select>
<?php
} elseif ($catalogueid==6) {
?>
				<label for="serviceid"><span class="field-name"><?php echo $translations['label'][$lang]; ?></span></label>
				<select class="form-control" id="serviceid" name="serviceid" onchange="ajax2(this.value)" required>
					<option value=""><?php echo $translations['select_placeholder'][$lang]; ?></option>
					<?php if ($lang === 'fr'): ?>
					<option value="6:6"><?php echo $translations['6:6'][$lang]; ?></option>
					<option value="6:4"><?php echo $translations['6:4'][$lang]; ?></option>
					<option value="6:2"><?php echo $translations['6:2'][$lang]; ?></option>
					<option value="6:1"><?php echo $translations['6:1'][$lang]; ?></option>
					<option value="6:5"><?php echo $translations['6:5'][$lang]; ?></option>
					<option value="6:3"><?php echo $translations['6:3'][$lang]; ?></option>
					<?php else: ?>
					<option value="6:4"><?php echo $translations['6:4'][$lang]; ?></option>
					<option value="6:2"><?php echo $translations['6:2'][$lang]; ?></option>
					<option value="6:3"><?php echo $translations['6:3'][$lang]; ?></option>
					<option value="6:1"><?php echo $translations['6:1'][$lang]; ?></option>
					<option value="6:5"><?php echo $translations['6:5'][$lang]; ?></option>
					<option value="6:6"><?php echo $translations['6:6'][$lang]; ?></option>
					<?php endif; ?>
					<option value="99:6"><?php echo $translations['no_match'][$lang]; ?></option>					
				</select>
<?php
} elseif ($catalogueid==7) {
?>
				<label for="serviceid"><span class="field-name"><?php echo $translations['label'][$lang]; ?></span></label>
				<select class="form-control" id="serviceid" name="serviceid" onchange="ajax2(this.value)" required>
					<option value=""><?php echo $translations['select_placeholder'][$lang]; ?></option>
					<option value="7:1"><?php echo $translations['7:1'][$lang]; ?></option>
					<option value="99:7"><?php echo $translations['no_match'][$lang]; ?></option>					
				</select>
<?php
} elseif ($catalogueid==8) {
?>
				<label for="serviceid"><span class="field-name"><?php echo $translations['label'][$lang]; ?></span></label>
				<select class="form-control" id="serviceid" name="serviceid" onchange="ajax2(this.value)" required>
					<option value=""><?php echo $translations['select_placeholder'][$lang]; ?></option>
					<?php if ($lang === 'fr'): ?>
					<option value="8:1"><?php echo $translations['8:1'][$lang]; ?></option>
					<option value="8:4"><?php echo $translations['8:4'][$lang]; ?></option>
					<option value="8:2"><?php echo $translations['8:2'][$lang]; ?></option>
					<?php else: ?>
					<option value="8:4"><?php echo $translations['8:4'][$lang]; ?></option>
					<option value="8:1"><?php echo $translations['8:1'][$lang]; ?></option>
					<option value="8:2"><?php echo $translations['8:2'][$lang]; ?></option>
					<?php endif; ?>
					<option value="99:8"><?php echo $translations['no_match'][$lang]; ?></option>					
				</select>
<?php
} elseif ($catalogueid==9) {
?>
				<label for="serviceid"><span class="field-name"><?php echo $translations['label'][$lang]; ?></span></label>
				<select class="form-control" id="serviceid" name="serviceid" onchange="ajax2(this.value)" required>
					<option value=""><?php echo $translations['select_placeholder'][$lang]; ?></option>
					<option value="9:1"><?php echo $translations['9:1'][$lang]; ?></option>
					<option value="99:9"><?php echo $translations['no_match'][$lang]; ?></option>					
				</select>
<?php
} elseif ($catalogueid==10) {
?>
				<label for="serviceid"><span class="field-name"><?php echo $translations['label'][$lang]; ?></span></label>
				<select class="form-control" id="serviceid" name="serviceid" onchange="ajax2(this.value)" required>
					<option value=""><?php echo $translations['select_placeholder'][$lang]; ?></option>
					<option value="10:1"><?php echo $translations['10:1'][$lang]; ?></option>
					<option value="10:2"><?php echo $translations['10:2'][$lang]; ?></option>
					<option value="99:10"><?php echo $translations['no_match'][$lang]; ?></option>					
				</select>
<?php
} elseif ($catalogueid==11) {
?>
				<label for="serviceid"><span class="field-name"><?php echo $translations['label'][$lang]; ?></span></label>
				<select class="form-control" id="serviceid" name="serviceid" onchange="ajax2(this.value)" required>
					<option value=""><?php echo $translations['select_placeholder'][$lang]; ?></option>
					<option value="11:1"><?php echo $translations['11:1'][$lang]; ?></option>
					<!--<option value="11:2">Compliance Deputy</option> -->
					<!-- <option value="11:3">Compliance Sheriff</option> -->
					<option value="99:11"><?php echo $translations['no_match'][$lang]; ?></option>					
				</select>
<?php
}
// Close connection
mysqli_close($link);
?>
