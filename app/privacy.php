<?php
/**
 * Privacy Statement Page
 */

// Start session
if (session_status() != PHP_SESSION_ACTIVE)
{
	session_start();
}

// Grab HTTPS check
require('includes/httpscheck.php');

// Grab MySQL connection
require('sql.php');

// Handle language from query string or session
if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'fr'])) {
	$_SESSION['lang'] = $_GET['lang'];
}

// Set default language if not set
if (!isset($_SESSION['lang']) || !in_array($_SESSION['lang'], ['en', 'fr'])) {
	$_SESSION['lang'] = 'en';
}

// Load language file
$lang = $_SESSION['lang'];
$langFile = require("lang/{$_SESSION['lang']}.php");

// Check if there is a status
if (!empty($_GET['status'])){
	$status = $_GET['status'];
}
else{
	$status = "";
}

// Load config
require_once 'includes/config.php';

// Page-specific metadata
$pageTitle = $_SESSION['lang'] == 'fr' ? 'Énoncé de confidentialité' : 'Privacy Statement';
$pageDescription = '';

include 'includes/template/head.php';
include 'includes/template/header.php';
?>
		<main role="main" property="mainContentOfPage" class="container">
			
			<?php if ($_SESSION['lang'] == 'fr'): ?>
			<h2>Énoncé de confidentialité pour l'Outil de gestion des demandes d'accessibilité (OGDA11y)</h2>
			<p>Évitez d'ajouter des renseignements personnels ou médicaux dans la zone de texte ouverte où il est indiqué « Décrivez votre demande ».</p>
			<p>Veuillez prendre quelques instants pour lire notre énoncé de confidentialité. Lorsque vous ouvrez et soumettez une nouvelle demande d'accessibilité au Bureau de l'accessibilité des TI (BATI), vous fournissez des renseignements personnels. Les renseignements que vous fournissez sont protégés et gérés conformément à la <a href="https://laws-lois.justice.gc.ca/fra/lois/p-21/index.html">Loi sur la protection des renseignements personnels </a> et à la <a href="https://laws.justice.gc.ca/fra/lois/h-5.7/index.html">Loi sur le ministère de l'Emploi et du Développement social, partie 4.</a> Cet énoncé explique comment nous recueillons, utilisons et protégeons vos renseignements personnels lorsque vous soumettez une demande au moyen de l'Outil de gestion des demandes d'accessibilité (OGDA11y).</p>
			
			<h3>Pourquoi vos renseignements sont-ils recueillis?</h3>
			<p>Nous recueillons des renseignements personnels afin de traiter et de gérer votre demande, de communiquer avec le client et de conserver les dossiers. La Loi canadienne sur l'accessibilité nous autorise à recueillir des renseignements à cette fin.</p>
			<p>Si vous ne fournissez pas les renseignements requis, nous ne pourrons pas traiter votre demande.</p>
			
			<h3>Comment vos renseignements sont utilisés</h3>
			<p>Les renseignements personnels que nous recueillons sont utilisés comme suit :</p>
			<ul>
				<li>Traiter et gérer vos demandes d'accessibilité et de mesures d'adaptation liées à la technologie de l'information et des communications (TIC).</li>
				<li>Communiquer avec vous pour obtenir des renseignements complémentaires ou faire le point sur l'état de votre demande.</li>
				<li>Confier et trier votre demande au sein du Bureau de l'accessibilité des TI.</li>
				<li>Consigner les progrès et les mesures prises par rapport à votre demande.</li>
			</ul>
			<p>Seul le personnel autorisé du BATI, y compris les directeurs, les gestionnaires, les chefs d'équipe et les conseillers techniques, qui ont besoin de vos renseignements personnels pour exercer leurs fonctions, y a accès. Le personnel du BATI n'utilisera vos renseignements que pour répondre à votre demande et à des fins de gestion interne.</p>
			<p>Les renseignements personnels recueillis au moyen de l'OGDA11y seront conservés aussi longtemps que nécessaire pour traiter votre demande et répondre aux exigences en matière de rapports. Lorsqu'une demande relative aux technologies d'assistance, au Service de la banque de prêt et autres est achevée, tous les identifiants personnels sont remplacés par des données anonymisées. Les autres demandes seront gérées conformément aux pratiques habituelles de tenue de documents et aux calendriers de conservation.</p>
			
			<h3>Accéder à vos renseignements ou les corriger</h3>
			<p>Vous avez le droit de <a href="https://atip-aiprp.apps.gc.ca/atip/welcome.do">demander l'accès à vos renseignements personnels</a> détenus par l'Outil de gestion des demandes d'accessibilité (OGDA11y) et de demander des corrections si vous estimez que ces renseignements sont inexacts ou incomplets.</p>
			
			<h3>Pour obtenir de plus amples renseignements</h3>
			<p>Les renseignements personnels recueillis par l'intermédiaire de l'OGDA11y sont gérés conformément au Fichier de renseignements personnels ordinaire PSE 916 – Programme d'aide aux employés du Secrétariat du Conseil du Trésor du Canada. Consultez ce fichier de renseignements personnels dans <a href="https://www.canada.ca/fr/secretariat-conseil-tresor/services/acces-information-protection-reseignements-personnels/acces-information/info-source.html">Info Source</a> pour obtenir une description de la manière dont les renseignements personnels associés à cette activité sont gérés. Vous pouvez également consulter Info Source en ligne dans tout Centre Service Canada.</p>
			<p>Si vous n'êtes pas satisfait de la manière dont nous traitons vos renseignements personnels, vous pouvez <a href="https://www.priv.gc.ca/fr/communiquer-avec-le-commissariat/communiquer-avec-le-centre-d-information/">communiquer</a> avec le Commissariat à la protection de la vie privée du Canada ou <a href="https://www.priv.gc.ca/fr/signaler-un-probleme/deposer-une-plainte-officielle-concernant-la-protection-de-la-vie-privee/deposer-une-plainte-visant-une-institution-du-gouvernement-federal/">déposer une plainte</a> auprès de celui-ci.</p>
			
			<?php else: ?>
			<h2>Privacy Notice for Accessibility Request Management Tool (A11yRMT)</h2>
			<p>Please avoid adding personal or medical information in the open text box where it says "describe your request".</p>
			<p>Please take a few moments to read our privacy notice. When you open and submit a new request for accessibility to the IT Accessibility Office (ITAO), you are providing personal information. The information you provide is protected under and managed in accordance with the <a href="https://laws-lois.justice.gc.ca/fra/lois/p-21/index.html">Privacy Act</a> and the <a href="https://laws.justice.gc.ca/fra/lois/h-5.7/index.html">Department of Employment and Social Development Act, Part 4.</a> This notice explains how we collect, use, and safeguard your personal information when you submit a request through the Accessibility Request Management Tool (A11yRMT).</p>
			
			<h3>Why your information is collected</h3>
			<p>We collect personal information in order to process and manage your request, communicating with the client and record keeping. Our authority to collect your information for this purpose is stated in Accessible Canada Act.</p>
			<p>If you don't provide the information required, we will be unable to process your request.</p>
			
			<h3>How your information is used</h3>
			<p>The personal information collected is used to:</p>
			<ul>
				<li>Process and manage your accessibility and accommodation requests related to Information and Communications Technology (ICT)</li>
				<li>Contact you for additional information or to provide updates on the status of your request</li>
				<li>Assign and triage your request within the IT Accessibility Office</li>
				<li>Record progress and actions taken on your request</li>
			</ul>
			<p>Your personal information is only accessible to authorized ITAO staff, including directors, managers, team leads, and technical advisors, who require it to perform their duties. ITAO staff will only use your information to fulfill your request and for internal management purposes.</p>
			<p>Personal information collected through A11yRMT will be retained for as long as necessary to process your request and fulfill reporting requirements. Once a request related to assistive technology, the Loan Bank, etc is completed, all personal identifiers will be replaced with anonymized data. Other requests will be managed according to standard record-keeping practices and retention schedules.</p>
			
			<h3>Accessing or correcting your information</h3>
			<p>You have the right to <a href="https://atip-aiprp.apps.gc.ca/atip/welcome.do">request access to your personal information</a> held by Accessibility Request Management Tool (A11yRMT) and also to request corrections to it if you believe that the information is inaccurate or incomplete.</p>
			
			<h3>For more information</h3>
			<p>The personal information collected through A11yRMT is managed in accordance with the Treasury Board of Canada Secretariat's Standard Personal Information Bank PSE 916 – Employee Assistance Program. Refer to this personal information bank in <a href="https://www.canada.ca/fr/secretariat-conseil-tresor/services/acces-information-protection-reseignements-personnels/acces-information/info-source.html">Info Source</a> for a description of how personal information related to this activity are managed. Info Source may also be accessed on-line at any Service Canada Center.</p>
			<p>If you are not satisfied with our handling of your personal information, you may wish to <a href="https://www.priv.gc.ca/fr/communiquer-avec-le-commissariat/communiquer-avec-le-centre-d-information/">contact</a> or <a href="https://www.priv.gc.ca/fr/signaler-un-probleme/deposer-une-plainte-officielle-concernant-la-protection-de-la-vie-privee/deposer-une-plainte-visant-une-institution-du-gouvernement-federal/">file a complaint</a> with the Office of the Privacy Commissioner of Canada.</p>
			<?php endif; ?>
			
<?php include 'includes/template/page-details.php'; ?>
		</main>
<?php 
include 'includes/template/footer.php';
include 'includes/template/scripts.php';

// Close connection
mysqli_close($link);
