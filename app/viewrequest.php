<?php
// Detect language from query parameter, default to English  
$lang = isset($_GET['lang']) && $_GET['lang'] === 'fr' ? 'fr' : 'en';

// Language-aware column selection for database queries
$nameField = $lang == 'fr' ? 'namefr' : 'nameen';

// Translations
$translations = [
	'en' => [
		'page_title' => 'Request details - a11y-',
		'title_suffix' => ' - Request Management Tool - IT Accessibility Office',
		'success' => 'Success',
		'success_updated' => 'You have successfully updated the request, thank you!',
		'success_submitted' => 'You have successfully submitted a new request, details are below. Thank you!',
		'success_css' => 'You have successfully submitted the client satisfaction survey. Thank you!',
		'success_css_sent' => 'You have successfully marked the survey as sent. Thank you!',
		'edit' => 'Edit',
		'delete' => 'Delete',
		'request' => 'request',
		'escalation_required' => 'Request is now past SLA, escalation required!',
		'past_sla' => 'Request is now past SLA!',
		'close_to_sla' => 'Request is close to SLA!',
		'urgent_review' => 'New request needs urgent review to determine proper service catalogue selection!',
		'title' => 'Title',
		'last_name' => 'Last name',
		'first_name' => 'First name',
		'client_email' => 'Client email',
		'send_email' => 'Send email',
		'department_agency' => 'Department/agency',
		'client_phone' => 'Client phone number',
		'source' => 'Source',
		'sprint_start' => 'Sprint Start Date',
		'sprint_end' => 'Sprint End Date',
		'sprint_schedule' => 'Sprint Schedule',
		'view_sprint_schedule' => 'View Sprint Schedule',
		'sprint_defect' => 'Sprint Defect',
		'view_sprint_defect' => 'View Sprint Defect',
		'date_received' => 'Date received',
		'sla_due_date' => 'SLA due date',
		'sla_days_required' => 'SLA days required',
		'business_days' => 'business days',
		'date_updated' => 'Date updated',
		'date_required' => 'Date required',
		'coaching_date' => 'Requested coaching session date',
		'date_resolved' => 'Date resolved',
		'status' => 'Status',
		'audience' => 'Audience',
		'nsd_ticket' => 'NSD/Smart IT ticket #',
		'details_new_window' => 'details (will open in a new window)',
		'yes' => 'Yes',
		'no' => 'No',
		'catalogue_name' => 'Catalogue name',
		'service_name' => 'Service name',
		'subservice_name' => 'Sub-service name',
		'assigned_member' => 'Assigned ITAO team member',
		'files' => 'Files',
		'checkbox' => 'CheckBox',
		'file_name' => 'File Name',
		'file_type' => 'File Type',
		'file_size' => 'File Size',
		'date_submitted' => 'Date Submitted',
		'action' => 'Action',
		'no_files' => 'No files found.',
		'select_all' => 'Select All',
		'download_all' => 'Download All',
		'download' => 'Download',
		'css_completed' => 'Client satisfaction survey',
		'completed' => 'completed',
		'not_completed' => 'not completed',
		'overall_satisfaction' => 'Over-all satisfaction',
		'response_time' => 'Response time',
		'comments' => 'Comments',
		'na' => 'N/A',
		'css_send' => 'Please send the client satisfaction survey link to the client using the copy function below.',
		'generate_email' => 'Generate email with survey link',
		'survey_sent' => 'Survey was sent',
		'resend' => 'resend?',
		'mark_sent' => 'Mark survey as sent',
		'request_attachments' => 'Request attachments',
		'attachment_url' => 'Attachment',
		'client_comms' => 'Client communications log',
		'delete_comment' => 'Delete comment',
		'no_comms' => 'No communications available!',
		'itao_comms' => 'ITAO communications log',
		'not_found_title' => 'Request not found!',
		'not_found_msg' => 'Sorry something went wrong with your request, please try again!',
		'image_preview_opened' => 'Image preview opened.',
		'close_image_preview' => 'Close image preview',
		'delete_confirm' => 'Are you sure you want to delete this file?',
		'delete_success' => 'File deleted successfully!',
	],
	'fr' => [
		'page_title' => 'Détails de la demande - a11y-',
		'title_suffix' => ' - Outil de gestion des demandes - Bureau de l\'accessibilité des TI',
		'success' => 'Succès',
		'success_updated' => 'Vous avez mis à jour la demande avec succès, merci!',
		'success_submitted' => 'Vous avez soumis une nouvelle demande avec succès, les détails sont ci-dessous. Merci!',
		'success_css' => 'Vous avez soumis le sondage de satisfaction de la clientèle avec succès. Merci!',
		'success_css_sent' => 'Vous avez marqué le sondage comme envoyé avec succès. Merci!',
		'edit' => 'Modifier',
		'delete' => 'Supprimer',
		'request' => 'demande',
		'escalation_required' => 'La demande a dépassé le NdS, escalade requise!',
		'past_sla' => 'La demande a dépassé le NdS!',
		'close_to_sla' => 'La demande est proche de la NPS!',
		'urgent_review' => 'Une nouvelle demande nécessite un examen urgent pour déterminer la sélection appropriée du catalogue de services!',
		'title' => 'Titre',
		'last_name' => 'Nom',
		'first_name' => 'Prénom',
		'client_email' => 'Courriel du client',
		'send_email' => 'Envoyer un courriel',
		'department_agency' => 'Ministère/organisme',
		'client_phone' => 'Numéro de téléphone client',
		'source' => 'Source',
		'sprint_start' => 'Date de début du sprint',
		'sprint_end' => 'Date de fin du sprint',
		'sprint_schedule' => 'Calendrier du sprint',
		'view_sprint_schedule' => 'Voire le calendrier du sprint',
		'sprint_defect' => 'Défauts du sprint',
		'view_sprint_defect' => 'Voire les défauts du sprint',
		'date_received' => 'Date de réception',
		'sla_due_date' => 'Date d\'échéance du SLA',
		'sla_days_required' => 'Jours SLA requis',
		'business_days' => 'jours ouvrables',
		'date_updated' => 'Date mise à jour',
		'date_required' => 'Date requise',
		'coaching_date' => 'Date de la séance de coaching demandée',
		'date_resolved' => 'Date de résolution',
		'status' => 'Statut',
		'audience' => 'Audience',
		'nsd_ticket' => '# de billet NSD/Smart IT',
		'details_new_window' => 'details (will open in a new window)',
		'yes' => 'Oui',
		'no' => 'Non',
		'catalogue_name' => 'Nom du catalogue',
		'service_name' => 'Nom du service',
		'subservice_name' => 'Nom du sous-service',
		'assigned_member' => 'Membre de l\'équipe du BATI assigné',
		'files' => 'Fichiers',
		'checkbox' => 'Case à cocher',
		'file_name' => 'Nom du fichier',
		'file_type' => 'Type de fichier',
		'file_size' => 'Taille du fichier',
		'date_submitted' => 'Date de soumission',
		'action' => 'Action',
		'no_files' => 'Aucun fichier trouvé.',
		'select_all' => 'Tout sélectionner',
		'download_all' => 'Tout télécharger',
		'download' => 'Télécharger',
		'css_completed' => 'Sondage de satisfaction de la clientèle',
		'completed' => 'complété',
		'not_completed' => 'non complété',
		'overall_satisfaction' => 'Satisfaction globale',
		'response_time' => 'Temps de réponse',
		'comments' => 'Commentaires',
		'na' => 'S.O.',
		'css_send' => 'Veuillez envoyer le lien du sondage de satisfaction de la clientèle au client en utilisant la fonction de copie ci-dessous.',
		'generate_email' => 'Générer un courriel avec le lien du sondage',
		'survey_sent' => 'Le sondage a été envoyé',
		'resend' => 'renvoyer?',
		'mark_sent' => 'Marquer le sondage comme envoyé',
		'request_attachments' => 'Pièces jointes de la demande',
		'attachment_url' => 'Pièce jointe',
		'client_comms' => 'Journal des communications avec le client',
		'delete_comment' => 'Supprimer le commentaire',
		'no_comms' => 'Aucune communication disponible!',
		'itao_comms' => 'Journal des communications du BATI',
		'not_found_title' => 'Demande introuvable!',
		'not_found_msg' => 'Désolé, quelque chose s\'est mal passé avec votre demande, veuillez réessayer!',
		'image_preview_opened' => 'Aperçu de l\'image ouvert.',
		'close_image_preview' => 'Fermer l\'aperçu de l\'image',
		'delete_confirm' => 'Êtes-vous sûr de vouloir supprimer ce fichier?',
		'delete_success' => 'Fichier supprimé avec succès!',
	]
];

$t = $translations[$lang];

// Start session
if (session_status() != PHP_SESSION_ACTIVE)
{
	session_start();
}
require('BlobStorage.php');
// Grab HTTPS check
require('includes/httpscheck.php');
require('includes/sla-calculator.php');
// Include file for calculating business days
require('includes/calculate-bdays.php');

// Grab MySQL connection
require('sql.php');

// Set session language from query parameter
$_SESSION['lang'] = $lang;

// Now first get the request ID
$triageid = null;
if (!empty($_GET['rid']))
{
	$triageid = $_GET['rid'];
}

// Check if there was an email request ID
if (!empty($_GET['erid']))
{
	// There is a request email id so grab it
	$triageid = base64_decode($_GET['erid']);
}

// Create encoded request ID
$nrequestid = base64_encode($triageid);

// Check if there is a status
if (!empty($_GET['status'])){
	$status = $_GET['status'];
}
else{
	$status = "";
}
		
// Construct SQL statement
$sql = "SELECT * FROM tbltriage WHERE id='$triageid'";

$result = mysqli_query($link,$sql);
//List it
if(mysqli_num_rows($result)>0){
	while($row = mysqli_fetch_array($result)){
		
		// We need to calculate if ticket is close to SLA (or on the date) or if past SLA and grab the names
		$subserviceid = $row['subserviceid'];
		$serviceid = $row['serviceid'];
		$catalogueid = $row['catalogueid'];
		$statusid = $row['statusid'];
		$audienceid = $row['audienceid'] ?? null;

		$sla = 0;
		$dsla = 0;
		$overdue = false;
		$doverdue = false;
		$closedue = false;
		$uReview = false;
		$tarraycontactid = null;
		$subservicename = '';
		$servicename = '';
		$cataloguename = '';
		$departmentAgency = '';
		
		if ($subserviceid!=0) {
			// Sub-service is not empty so grab the name
		$result2 = mysqli_query($link, "SELECT $nameField, sds, contactid FROM tblsubservices WHERE id = '$subserviceid'");
		$row2 = mysqli_fetch_array($result2);
		if ($row2 !== null) {
			$subservicename = $row2[0];
			$sla = $row2[1];
			$dsla = $sla * 2;
			$tarraycontactid = $row2[2];
			}
			
		}
		
		if ($serviceid!="" AND $serviceid!=0) {
			// Sub-service is not empty so grab the name
		$result2 = mysqli_query($link, "SELECT $nameField, sds, contactid FROM tblservices WHERE id = '$serviceid'");
		$row2 = mysqli_fetch_array($result2);
		$servicename = $row2 ? $row2[0] : '';
		if ($sla==0) {
			if ($serviceid==21 || $serviceid==22 || $serviceid==23 || $serviceid==24) {
				$sla = 15;
				$dsla = $sla * 2;
			} else {
				$sla = $row2 ? $row2[1] : 0;
				$dsla = $sla * 2;
			}
		}
		if ($tarraycontactid==0 || $tarraycontactid===null) {
			$tarraycontactid = $row2 ? $row2[2] : 0;
		}
	} else {
		$uReview = true;
	}
	
	if ($catalogueid!=0) {
		// Sub-service is not empty so grab the name
		$result2 = mysqli_query($link, "SELECT $nameField FROM tblcatalogue WHERE id = '$catalogueid'");
		$row2 = mysqli_fetch_array($result2);
		$cataloguename = $row2 ? $row2[0] : '';
	}

		if ($audienceid!=0 && $audienceid != null) {
			// Sub-service is not empty so grab the name
			$result2 = mysqli_query($link, "SELECT $nameField FROM tblaudience WHERE id = '$audienceid'");
			$row2 = mysqli_fetch_array($result2);
			$audiencename = $row2 ? $row2[0] : '';
		}
		
		if ($statusid==10) { 
			$uReview = true;
		}

		$deptResult = mysqli_query($link, "SELECT notes FROM tblcommlog WHERE triageid = '$triageid' AND status = '1' ORDER BY id ASC");
		if ($deptResult && mysqli_num_rows($deptResult) > 0) {
			while ($deptRow = mysqli_fetch_assoc($deptResult)) {
				if (preg_match('/^(Department\/agency|Ministère\/organisme):\s*(.+)$/miu', (string)$deptRow['notes'], $matches)) {
					$departmentAgency = trim($matches[2]);
					break;
				}
			}
		}
		
		// Grab the date it was received
		$slatimer = $row['slatimer'];
		if ($slatimer=="" OR is_null($slatimer)) {
			$datereceived = $row['datereceived'];
		} else {
			$datereceived = $slatimer;
		}
		$ndatereceived = date('Y-m-d H:i:s', strtotime($datereceived . ' +1 day'));
		 
		// Calculate the business days
		//$cBdays = getWorkingDays($ndatereceived,date('Y-m-d'),$holidays);
		$cBdays = calculateSLA($link, $row['requestid'], $ndatereceived);

		$sla2 = $sla - 1;
		// Now check if the SLA is close
		if ($uReview==false) {
			if ($cBdays > $dsla) {
				$doverdue = true;
			}
			if ($cBdays > $sla) {
				$overdue = true;
			}
			if ($cBdays == $sla) {
				$closedue = true;
			}
			
			if ($cBdays >= $sla2) {
				$closedue = true;
			}
		}
?>
<!DOCTYPE html>
<!--[if lt IE 9]><html class="no-js lt-ie9" lang="en" dir="ltr"><![endif]-->
<!--[if gt IE 8]><!--><html class="no-js" lang="en" dir="ltr"><!--<![endif]-->
	<head>
		<meta charset="utf-8">
		<!-- Web Experience Toolkit (WET) / Boîte à outils de l'expérience Web (BOEW) wet-boew.github.io/wet-boew/License-en.html / wet-boew.github.io/wet-boew/Licence-fr.html -->
		<title><?= $t['page_title'] ?><?php echo $row['requestid'] ?><?= $t['title_suffix'] ?></title>
		<meta content="width=device-width,initial-scale=1" name="viewport">
		<!-- Meta data -->
		<meta name="description" content="">
		<!-- Meta data-->
		<?php include 'includes/refTop.php';?>
	</head>
	<body vocab="https://schema.org/" typeof="WebPage">
		<div id="def-top">
		</div>
		<?php include $lang == 'fr' ? 'includes/appTop-fr.php' : 'includes/appTop.php';?>
		<main role="main" property="mainContentOfPage" class="container">
			<h1 property="name" id="wb-cont"><?= $t['page_title'] ?><?php echo $row['requestid'] ?></h1>
			
			<?php 
			if ($status == 'success') {
			?>
			<section class="alert alert-success">
				<h2><?= $t['success'] ?></h2>
				<ul>
					<li><?= $t['success_updated'] ?></li>
				</ul>
			</section>
			<?php 
			} elseif ($status == 'newrequestcomplete') {
			?>
			<section class="alert alert-success">
				<h2><?= $t['success'] ?></h2>
				<ul>
					<li><?= $t['success_submitted'] ?></li>
				</ul>
			</section>
			<?php } elseif ($status == 'csscomplete') {
			?>
			<section class="alert alert-success">
				<h2><?= $t['success'] ?></h2>
				<ul>
					<li><?= $t['success_css'] ?></li>
				</ul>
			</section>
			<?php } elseif ($status == 'csssent') {
			?>
			<section class="alert alert-success">
				<h2><?= $t['success'] ?></h2>
				<ul>
					<li><?= $t['success_css_sent'] ?></li>
				</ul>
			</section>
			<?php } ?>
			<?php
				// Now that we know the user is logged in we need to check if this ticket is assigned to them except for atype 1 and 2
				if ($_SESSION['atype']=='1' OR $_SESSION['atype']=='2') {	
			?>
			<div class="pull-right">
				<p><a class="btn btn-primary" href="editrequest.php?erid=<?php echo base64_encode($row['id']);?>">Edit <span class="wb-inv"> a11y-<?php echo $row['requestid'];?> request</span></a><?php if ($_SESSION['atype']=='1') { ?> <a class="wb-lbx btn btn-primary" href="includes/delete-request.php?id=<?php echo $row['id'];?>">Delete<span class="wb-inv"> a11y-<?php echo $row['requestid'];?> request</span> </a><?php } ?></p>
			</div>
			<div class="clearfix"></div>
			<?php
				} else  {
				// User is 3 (Manager) or 4 (Team Leader) so check if they have permission to edit this request
				// First grab any existing teams
				$userid = $_SESSION['pid'];
				$result2 = mysqli_query($link, "SELECT team FROM tblusers WHERE id = '$userid'");
				$row2 = mysqli_fetch_array($result2);
				$teams = "";
				if (!empty($row2))
				{
					$teams = $row2[0];
				}
				$tarray = explode(",",$teams);
					if(in_array($tarraycontactid, $tarray)) {
			?>
			<div class="pull-right">
				<p><a class="btn btn-primary" href="editrequest.php?erid=<?php echo base64_encode($row['id']);?>">Edit <span class="wb-inv"> a11y-<?php echo $row['requestid'];?> request</span></a><?php if ($_SESSION['atype']=='1') { ?> <a class="wb-lbx btn btn-primary" href="includes/delete-request.php?id=<?php echo $row['id'];?>">Delete<span class="wb-inv"> a11y-<?php echo $row['requestid'];?> request</span> </a><?php } ?></p>
			</div>
			<div class="clearfix"></div>
			<?php 
					}
				}
			?>
			
			<?php if ($row['statusid']!=2) { ?>
			<?php if ($doverdue) { ?>
			<div class="alert alert-danger">
				<p><?= $t['escalation_required'] ?></p>
			</div>
			<?php } elseif ($overdue) {
			?>
			<div class="alert alert-danger">
				<p><?= $t['past_sla'] ?></p>
			</div>
			<?php } elseif ($closedue) { ?>
			<div class="alert alert-warning">
			   <p><?= $t['close_to_sla'] ?></p>
			</div>
			<?php } elseif ($uReview && !empty($_SESSION['pid'])) { ?>
			<div class="alert alert-info">
			   <p><?= $t['urgent_review'] ?></p>
			</div>
			<?php } ?>
			<?php } ?>

			<dl class="colcount-sm-2">
				<div style="break-inside: avoid;">
				<dt><?= $t['title'] ?></dt> 
			<dd><?php echo htmlspecialchars($row['title'] ?? '') ?></dd>
				</div>
				<?php if($_SESSION['pid']!=""){ ?>
				<?php if ($row['clientlname']!="") { ?>
				<div style="break-inside: avoid;">
				<dt><?= $t['last_name'] ?></dt>			
				<dd><?php echo htmlspecialchars ($row['clientlname']) ?></dd>
				</div>
				<?php } ?>
				<?php if ($row['clientfname']!="") { ?>
				<div style="break-inside: avoid;">
				<dt><?= $t['first_name'] ?></dt>
				<dd><?php echo htmlspecialchars ($row['clientfname']) ?></dd>
				</div>
				<?php } ?>
				<?php if ($row['clientemail']!="") { ?>
				<div style="break-inside: avoid;">
				<dt><?= $t['client_email'] ?></dt>
				<dd><a href="mailto:<?php echo htmlspecialchars ($row['clientemail']) ?>?Subject=a11y-<?php echo $row['requestid'] ?> - <?php echo htmlspecialchars ($row['title']) ?>"><?php echo htmlspecialchars ($row['clientemail']) ?> <span class="glyphicon glyphicon-envelope"></span><span class="wb-inv">- <?= $t['send_email'] ?></span></a></dd>
				</div>
				<?php } ?>
				<?php if ($row['clientphone']!="") { ?>
				<div style="break-inside: avoid;">
				<dt><?= $t['client_phone'] ?></dt>
				<dd><?php echo htmlspecialchars ($row['clientphone']) ?></dd>
				</div>
				<?php } ?>
				<?php if ($departmentAgency!="") { ?>
				<div style="break-inside: avoid;">
				<dt><?= $t['department_agency'] ?></dt>
				<dd><?php echo htmlspecialchars($departmentAgency, ENT_QUOTES, 'UTF-8'); ?></dd>
				</div>
				<?php } } ?>
				<?php
				// Grab the source name
				$sourceid = $row['sourceid'];
			if ($sourceid) {
				$result2 = mysqli_query($link, "SELECT $nameField FROM tblsources WHERE id = '$sourceid'");
				$row2 = mysqli_fetch_array($result2);
				$sourcename = $row2 ? $row2[0] : '';
			} else {
				$sourcename = '';
			}
			if ($sourcename) {
			?>
			<div>
			<dt><?= $t['source'] ?></dt>
			<dd><?php echo $sourcename ?></dd>
			</div>
			<?php } ?>
			
			
			<?php if ($row['firstsprintstartdate']!="") { ?>
				<div style="break-inside: avoid;">
				<dt><?= $t['sprint_start'] ?></dt>
				<dd><?php echo htmlspecialchars ($row['firstsprintstartdate']) ?></dd>
				</div>
				<?php } ?>

				<?php if ($row['firstsprintenddate']!="") { ?>
				<div style="break-inside: avoid;">
				<dt><?= $t['sprint_end'] ?></dt>
				<dd><?php echo htmlspecialchars ($row['firstsprintenddate']) ?></dd>
				</div>
				<?php } ?>

<?php if (isset($row['sprintschedule']) && $row['sprintschedule']!="") { ?>
			<div style="break-inside: avoid;">
			<dt><?= $t['sprint_schedule'] ?><dt>
			<dd><a href="<?php echo htmlspecialchars ($row['sprintschedule']) ?>"><?= $t['view_sprint_schedule'] ?></a></dd>
			</div>
			<?php } ?>

			<?php if (isset($row['sprintdefects']) && $row['sprintdefects']!="") { ?>
				<div style="break-inside: avoid;">
				<dt><?= $t['sprint_defect'] ?></dt>
				<dd><a href="<?php echo htmlspecialchars ($row['sprintdefects']) ?>"><?= $t['view_sprint_defect'] ?></a></dd>
				</div>
				<?php } ?>

				<div style="break-inside: avoid;">
				<dt><?= $t['date_received'] ?></dt>
				<dd><?php echo htmlspecialchars ($row['datereceived']) ?></dd>
				</div>
				<?php if ($sla > 0 && $statusid != 4 && $statusid != 5) { 
					// Load SLA calculator functions for holiday checking
					require_once('includes/sla-calculator.php');
					
					// Calculate SLA due date by adding business days to start date
					$businessDaysAdded = 0;
					$currentDate = date('Y-m-d', strtotime($datereceived));
					
					while ($businessDaysAdded < $sla) {
						$currentDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));
						if (isBusinessDay($currentDate, $link)) { // Check weekday and holidays
							$businessDaysAdded++;
						}
					}
					$slaDueDate = $currentDate;
				?>
				<div style="break-inside: avoid;">
				<dt><?= $t['sla_days_required'] ?></dt>
				<dd><?php echo $sla; ?> <?= $t['business_days'] ?></dd>
				</div>
				<div style="break-inside: avoid;">
				<dt><?= $t['sla_due_date'] ?></dt>
				<dd><?php echo date('Y-m-d', strtotime($slaDueDate)); ?> <?php $daysRemaining = getWorkingDays(date('Y-m-d'), $slaDueDate, $holidays); if ($daysRemaining > 0) { echo '<span class="text-muted">(' . $daysRemaining . ' ' . $t['business_days'] . ')</span>'; } elseif ($daysRemaining == 0) { echo '<span class="label label-warning">' . ($nameField === 'namefr' ? 'Dû aujourd\'hui' : 'Due today') . '</span>'; } else { echo '<span class="label label-danger">' . ($nameField === 'namefr' ? 'En retard' : 'Overdue') . '</span>'; } ?></dd>
				</div>
				<?php } ?>
				<?php if ($row['dateupdated']!="") { ?>
				<div style="break-inside: avoid;">
				<dt><?= $t['date_updated'] ?></dt>
				<dd><?php echo htmlspecialchars ($row['dateupdated']) ?></dd>
				</div>
				<?php } ?>
				<?php if ($row['daterequired']!="") { ?>
				<div style="break-inside: avoid;">
				<dt><?php if(($catalogueid==5) AND ($serviceid!=47)){ ?><?= $t['coaching_date'] ?><?php } else { ?><?= $t['date_required'] ?><?php } ?></dt>
				<dd><?php echo htmlspecialchars ($row['daterequired']) ?></dd>
				</div>
				<?php } ?>
				<?php if ($row['dateresolved']!="") { ?>
				<div style="break-inside: avoid;">
				<dt><?= $t['date_resolved'] ?></dt>
				<dd><?php echo htmlspecialchars ($row['dateresolved']) ?></dd>
				</div>
				<?php } ?>
				<?php
				// Grab the status name
				$result2 = mysqli_query($link, "SELECT $nameField FROM tblstatus WHERE id = '$statusid'");
				$row2 = mysqli_fetch_array($result2);
				$statusname = $row2 ? $row2[0] : '';
				?>
				<div style="break-inside: avoid;">
					<dt><?= $t['status'] ?></dt>
					<dd><?php echo $statusname ?></dd>
				</div>
				<?php if ($catalogueid==9 && $audienceid != 0 && $audienceid != null) { ?>
				<div>
					<?php ?>
					<dt><?= $t['audience'] ?></dt>
					<dd><?php echo $audiencename ?></dd>
				</div>
				<?php } ?>
				<?php if ($row['nsd']!=0 && !empty($userid)) { ?>
				<div>
					<dt><?= $t['nsd_ticket'] ?></dt>
					<dd>
							<br />
							<?php if(preg_match('/^[0-9]+$/', $row['nsd'])){?>
								<a href="http://arweb.prv/SRMIS.htm?Ticket=<?php echo $row['nsd'];?>" target="_blank"># NSD<?php echo $row['nsd'];?><span class="glyphicon glyphicon-new-window"></span><span class="wb-inv"><?= $t['details_new_window'] ?></span></a>
							<?php }else{?>
								<a href="https://smartitesdc.service.gc.ca/smartit/app/#/search/<?php echo $row['nsd'];?>" target="_blank"># Smart IT <?php echo $row['nsd'];?><span class="glyphicon glyphicon-eye-open"></span><span class="wb-inv"><?= $t['details_new_window'] ?></span></a>
							<?php } ?>
					</dd>				
						</div>
				<?php } ?>
				<?php 
				if ($catalogueid!=0) {
				?>
				<div style="break-inside: avoid;">
					<dt><?= $t['catalogue_name'] ?></dt>
					<dd><?php echo $cataloguename ?></dd>
				</div>
				<?php
				}
				if ($serviceid!=0) {
				?>
				<div style="break-inside: avoid;">
					<dt><?= $t['service_name'] ?></dt>
					<dd><?php echo $servicename; ?></dd>
				</div>
				<?php
				}
				if ($subserviceid!=0 && !empty($subservicename)) {
				?>
				<div tyle="break-inside: avoid;">
					<dt><?= $t['subservice_name'] ?></dt>
					<dd><?php echo $subservicename ?></dd>
				</div>

				
				<?php
				}
				?>
				<?php
				// Check if the account is admin level to show this option 
				if ($_SESSION['atype']=='1' OR $_SESSION['atype']=='2' OR $_SESSION['atype']=='3' OR $_SESSION['atype']=='4' OR $_SESSION['atype']=='5') {
					$workerid = $row['workerid'];
					if ($workerid!=0 AND $workerid!="") {
						// Check the name
						$result2 = mysqli_query($link, "SELECT lastname,firstname FROM tblusers WHERE id = '$workerid'");
						$row2 = mysqli_fetch_array($result2);
						$ulastname = $row2[0];
						$ufirstname = $row2[1];
				?>
				<div tyle="break-inside: avoid;">
					<dt>Assigned ITAO team member</dt>
					<dd><?php echo $ulastname ?>, <?php echo $ufirstname ?></dd>
				</div>
				<?php
					}
				}
				if ($_SESSION['atype']==1 OR $_SESSION['atype']==2 OR $_SESSION['atype']==5 OR $_SESSION['atype']==3 OR $_SESSION['atype']==4 OR $_SESSION['atype'] == 6) 
{
				?>
			</dl>
			
			
			<style>
        .image-preview {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .image-preview img {
            max-width: 90%;
            max-height: 90%;
            border-radius: 10px;
        }

        .close-btn {
            position: absolute;
            top: 15px;
            right: 20px;
            background: transparent;
            border: none;
            font-size: 30px;
            color: white;
            cursor: pointer;
        }

        .close-btn:focus {
            outline: 2px solid white;
        }

        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            border: 0;
        }
        </style>




<?php 
$blobStorage = new AzureBlobStorageManager();
?>

            <h2><?= $t['files'] ?></h2>
            <br>

            <?php
            $requestid = $row['requestid'];
            $result_files = mysqli_query($link, "SELECT * FROM tblfiles WHERE requestid = '$requestid'");
            $validImageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'tiff', 'svg', 'ico'];
            
            if (mysqli_num_rows($result_files) > 0) {
            ?>

            <table class="wb-tables table" data-wb-tables="{ &quot;ordering&quot;: true, &quot;searching&quot;: true }"
                id="fileTable">
                <thead>
                    <tr>
                        <th><?= $t['checkbox'] ?></th>
                        <th><?= $t['file_name'] ?></th>
                        <th><?= $t['file_type'] ?></th>
                        <th><?= $t['file_size'] ?></th>
                        <th><?= $t['date_submitted'] ?></th>
                        <th><?= $t['action'] ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    while($file = mysqli_fetch_array($result_files)) {
                        $fileExtension = strtolower($file['type']);
                        echo "<tr>";
                        echo "<td><input type='checkbox' class='fileCheckbox' value='" . $file['name'] . "'></td>";
                        echo "<td>";
                        if (in_array($fileExtension, $validImageExtensions)){ 
                            echo "<a href='#' class='image-link' data-src='" . $blobStorage->getFileUrl($file['code']) . "'>" . $file['name'] . "</a>";
                        } else {
                            echo "<a href='" . $blobStorage->getFileUrl($file['code']) . "' download>" . $file['name'] . "</a>";
                        }
                        echo "</td>";
                        echo "<td>" . $file['type'] . "</td>";
                        echo "<td>" . $file['size'] .  " KB" ."</td>";
                        echo "<td>" . $file['date'] . "</td>";
                        echo "<td>
                        <a href='#' class='btn btn-primary download-btn' 
                           data-name='" . htmlspecialchars($file['name'], ENT_QUOTES, 'UTF-8') . "' 
                           data-file='" . $file['code'] . "'>Download</a>
                    
                        <a class='btn btn-danger delete-btn' style='color:white;' 
                           data-file='" . $file['code'] . "'>Delete</a>
                    </td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>

            <br>
            <div class="form-group">
                <input type="checkbox" id="selectAll">
                <label for="selectAll"><span class="field-name"><?= $t['select_all'] ?></span></label>
            </div>
            <a class="btn btn-primary" style="color:white;" id="downloadAll"><?= $t['download_all'] ?></a>

            <?php
            } else {
                echo "<p>" . $t['no_files'] . "</p>";
            }
            ?>




<?php } ?>

			<?php if($_SESSION['pid']!=""){ ?>
			<?php
			// Check if status is resolved, if it is then display the client satisfaction survey link and results if available
			if ($statusid==2){
				// Status is resolved so first check if a CSS has been completed
				$sqlcss = "SELECT * FROM tblcss WHERE requestid='$triageid' AND status=1";
				$resultcss = mysqli_query($link,$sqlcss);
				//List it
				if(mysqli_num_rows($resultcss)>0){
					while($rowcss = mysqli_fetch_array($resultcss)){
						$overall = $rowcss['overall'];
						$response = $rowcss['response'];
						$comments = $rowcss['comments'];
						if ($comments=="") {
							$comments = "N/A";
						}
					}				
			?>
			<h2>Client satisfaction survey <span class="glyphicon glyphicon-ok"></span><span class="wb-inv">completed</span></h2>
			
			<dl>
				<dt>Over-all satisfaction</dt>
				<dd><?php echo $overall ?>/10</dd>
				<dt>Response time</dt>
				<dd><?php echo $response ?>/10</dd>
				<dt>Comments</dt>
				<dd><?php echo $comments ?></dd>
			</dl>
			<?php
				} else {
					// No results so display copy form link for triage agent
					$cssurvey = $row['cssurvey'];
			?>
			<h2>Client satisfaction survey <span class="glyphicon glyphicon-remove"></span><span class="wb-inv">not completed</span></h2>
			
			<p>Please send the client satisfaction survey link to the client using the copy function below.</p>
			
			<?php
			// Prepare email
			$erequestnum = $row['requestid'];
			$eclientemail = $row['clientemail'];
			$esubject = "Sondage sur la satisfaction de la clientèle pour / Client satisfaction survey for a11y-".$erequestnum;
			$ebody = "Bonjour,%0d%0a%0d%0aVotre demande d’accessibilité a été complété par un membre de notre équipe, serait-il possible pour vous de compléter sondage sur la satisfaction de la clientèle? Ce sondage nous aidera à mieux servir nos clients et ne prendra que 30 secondes à remplir.%0d%0a%0d%0ahttps://a11y.itaormt-batiogd-int.service.cloud-nuage.canada.ca/css-fr.php?erid=".$nrequestid."%0d%0a%0d%0a**********************************************************%0d%0a%0d%0aHello,%0d%0a%0d%0aYour accessibility request has now been completed by one of our team members, could you please fill out the following client satisfaction survey? This survey will help us serve our clients better and will only take 30 seconds to complete.%0d%0a%0d%0ahttps://a11y.itaormt-batiogd-int.service.cloud-nuage.canada.ca/css-en.php?erid=".$nrequestid."%0d%0a%0d%0aMerci / Thank you"
			?>
			
			<p><a class="btn btn-primary" href="mailto:<?php echo $eclientemail ?>?subject=<?php echo $esubject ?>&body=<?php echo $ebody ?>">Generate email with survey link</a> <?php if ($cssurvey>=1) { ?><a class="wb-lbx btn btn-primary" href="includes/css-sent-en.php?id=<?php echo $row['id'];?>">Survey was sent (<?php echo $cssurvey ?>), resend? <span class="glyphicon glyphicon-ok"></span></a><?php } else {?><a class="wb-lbx btn btn-primary" href="includes/css-sent-en.php?id=<?php echo $row['id'];?>">Mark survey as sent</a><?php } ?></p>
			
			<?php
				}
			}
			?>			
			<?php
			// Check if attachments
			$attach1 = $row['attach1'];
			$attach2 = $row['attach2'];
			$attach3 = $row['attach3'];
			
			if ($attach1!="" OR $attach2!="" OR $attach3!="") {
			?>
			<h2>Request attachments</h2>
			
			<ul>
			<?php if ($attach1!="") {?><li><a href="<?php echo $attach1 ?>" target="_blank"><span class="wb-inv">Attachment 1 URL </span><?php echo $attach1 ?></a></li><?php } ?>
			<?php if ($attach2!="") {?><li><a href="<?php echo $attach2 ?>" target="_blank"><span class="wb-inv">Attachment 2 URL </span><?php echo $attach2 ?></a></li><?php } ?>
			<?php if ($attach3!="") {?><li><a href="<?php echo $attach3 ?>" target="_blank"><span class="wb-inv">Attachment 3 URL </span><?php echo $attach3 ?></a></li><?php } ?>
			</ul>
			<?php	
			}
			?>
			
			<h2>Client communications log</h2>
			
			<?php
			// Construct SQL statement
			$sql2 = "SELECT * FROM tblcommlog WHERE triageid = '$triageid' AND status = '1' ORDER BY id DESC";
			//echo $sql;
			
			$result2 = mysqli_query($link,$sql2);
			//List it
			if(mysqli_num_rows($result2)>0) {
			$hasVisibleClientComms = false;
			?>
			<dl>
				<?php
				while($row2 = mysqli_fetch_array($result2)){
					// Check if clientlname or clientfname is not empty
					$dateadded = $row2['dateadded'];
					$notes = preg_replace('/^\s*(Department\/agency|Ministère\/organisme):\s*.*(?:\R|$)/miu', '', (string)$row2['notes']);
					$notes = trim((string)$notes);
					if ($notes === '') {
						continue;
					}
					$hasVisibleClientComms = true;
					$nnotes = nl2br(htmlspecialchars($notes));
				?>
				
				<dt><?php echo $dateadded ?><?php if ($_SESSION['atype']=='1') {?> <a class="wb-lbx" href="includes/delete-comms.php?t=c&id=<?php echo $row2['id'];?>&rid=<?php echo $triageid ?>"><span class="glyphicon glyphicon-trash"></span><span class="wb-inv"> Delete comment</span></a><?php } ?></dt>
				<dd><?php echo $nnotes ?></dd>
				<?php } ?>
			</dl>
			<?php if (!$hasVisibleClientComms) { ?>
			<p>No communications available!</p>
			<?php } ?>
			<?php } else { ?>
			<p>No communications available!</p>
			<?php } } ?>
			
			<?php
			// Check if the account is admin level to show this option 
			if ($_SESSION['atype']=='1' OR $_SESSION['atype']=='2' OR $_SESSION['atype']=='3' OR $_SESSION['atype']=='4' OR $_SESSION['atype'] == '6') {
			?>			
			<h2>ITAO communications log</h2>
			
			<?php
			// Construct SQL statement
			$sql2 = "SELECT * FROM tbladminlog WHERE triageid = '$triageid' AND status = '1' ORDER BY id DESC";
			//echo $sql;
			
			$result2 = mysqli_query($link,$sql2);
			//List it
			if(mysqli_num_rows($result2)>0) {
			?>
			<dl>
				<?php
				while($row2 = mysqli_fetch_array($result2)){
					// Check if clientlname or clientfname is not empty
					$dateadded = $row2['dateadded'];
					$anotes = $row2['notes'];
					$annotes = nl2br(htmlspecialchars($anotes));
					$creatorid = $row2['creatorid'];
					// Get the name of the user
					$result3 = mysqli_query($link, "SELECT firstname, lastname FROM tblusers WHERE id = '$creatorid'");
					$row3 = mysqli_fetch_array($result3);
					$cfname = $row3['firstname'];
					$clname = $row3['lastname'];
				?>
				<dt><?php echo $dateadded ?><?php if($creatorid!=0) {?> - <?php echo $clname ?>, <?php echo $cfname ?><?php } ?><?php if ($_SESSION['atype']=='1') {?> <a class="wb-lbx" href="includes/delete-comms.php?t=a&id=<?php echo $row2['id'];?>&rid=<?php echo $triageid ?>"><span class="glyphicon glyphicon-trash"></span><span class="wb-inv"> Delete comment</span></a><?php } ?></dt>
				<dd><?php echo $annotes ?></dd>
				<?php } ?>
			</dl>
			<?php } else { ?>
			<p>No communications available!</p>
			<?php } ?>
			<?php } ?>
		</main>
		<div class="image-preview" id="imagePreview" role="dialog" aria-hidden="true" style="display:none">
        <button class="close-btn" id="closePreview" aria-label="Close image preview">&times;</button>
        <img id="previewImage" src="" alt="Preview">
        <p id="imageAnnouncement" class="sr-only" aria-live="assertive"></p>
    </div>
		<div id="def-footer">
		</div>
		<?php include 'includes/appFooter.php';?>
		<script>
   document.getElementById('downloadAll').addEventListener('click', async function() {
    // Get only visible rows after search/filtering
    const visibleRows = document.querySelectorAll('#fileTable tbody tr:not(.hidden)');


    for (const row of visibleRows) {
        const downloadBtn = row.querySelector('.download-btn');
        const checkbox = row.querySelector('.fileCheckbox');
        if (downloadBtn && checkbox.checked) {
            await downloadFile(downloadBtn.getAttribute('data-file'),  downloadBtn.getAttribute('data-name'));
        }
    }
});


document.querySelectorAll('.download-btn').forEach(button => {
    button.addEventListener('click', async function(event) {
        event.preventDefault();

        await downloadFile(this.getAttribute('data-file'), this.getAttribute('data-name'));
    });
});

document.getElementById('selectAll').addEventListener('change', function() {
    var checkboxes = document.querySelectorAll('.fileCheckbox');
    for (var checkbox of checkboxes) {
        checkbox.checked = this.checked;
    }
});


document.querySelectorAll('.image-link').forEach(link => {
    link.addEventListener('click', function(event) {
        event.preventDefault();
        const imagePreview = document.getElementById('imagePreview');
        const previewImage = document.getElementById('previewImage');
        const imageAnnouncement = document.getElementById('imageAnnouncement');

        previewImage.src = this.dataset.src;
        imagePreview.style.display = 'flex';
        imagePreview.setAttribute('aria-hidden', 'false');

        // Announce to screen readers
        imageAnnouncement.textContent = "Image preview opened.";

        // Focus the close button for better keyboard navigation
        document.getElementById('closePreview').focus();
    });
});

function closePreview() {
    const imagePreview = document.getElementById('imagePreview');
    imagePreview.style.display = 'none';
    imagePreview.setAttribute('aria-hidden', 'true');

    // Clear announcement text
    document.getElementById('imageAnnouncement').textContent = "";
}

async function downloadFile(code, name) {
    try {
        const response = await fetch(`download.php?code=${encodeURIComponent(code)}`);
        
        if (!response.ok) {
            alert('File not found!');
            return;
        }

        const blob = await response.blob();  // Get the content as a Blob (binary data)
        const sanitizedFileName = sanitizeFileName(name); // Optional: sanitize the file name

        // Create a download link
        const downloadLink = document.createElement('a');
        downloadLink.href = URL.createObjectURL(blob);  // Create a URL from the Blob
        downloadLink.download = sanitizedFileName;  // Set the filename for download

        // Append the link to the DOM, trigger the click, and remove it afterward
        document.body.appendChild(downloadLink);
        downloadLink.click();
        document.body.removeChild(downloadLink);

        // Revoke the object URL to free up resources
        URL.revokeObjectURL(downloadLink.href);
    } catch (error) {
        console.error('Error downloading the file:', error);
        alert('Failed to download file.');
    }
}


// Utility function (optional) to sanitize locally if needed
function sanitizeFileName(fileName) {
    return fileName.replace(/[^\w.-]/g, '_'); // Replace invalid characters with underscores
}


// Close when clicking on overlay or button
document.getElementById('imagePreview').addEventListener('click', closePreview);
document.getElementById('closePreview').addEventListener('click', closePreview);

// Close on Escape key press
document.addEventListener('keydown', function(event) {
    if (event.key === "Escape") {
        closePreview();
    }
});

// Function to delete a file using its code
async function deleteFile(fileCode) {
    // Confirm with the user before deleting
    if (confirm("Are you sure you want to delete this file?")) {
        // Make an AJAX call to the PHP delete script
        fetch(`delete-file.php?code=${fileCode}`, {
            method: 'GET',
        })
        .then(response => response.text())
        .then(data => {
            // Handle the response from the server
            console.log(data);
            alert("File deleted successfully!");
        })
    }
}

// Add event listener to all buttons with the class 'delete-btn'
document.querySelectorAll('.delete-btn').forEach(button => {
    button.addEventListener('click', async function () {
        // Get the file code from the button's data-file attribute
        const fileCode = this.getAttribute('data-file');

        // Call the deleteFile function with the file code
        await deleteFile(fileCode);
		location.reload();
    });
});



    </script>
	</body>

</html>
<?php
	}
} else { 
// Wrong ID so display an error message
?>
<!DOCTYPE html>
<!--[if lt IE 9]><html class="no-js lt-ie9" lang="<?= $lang ?>" dir="ltr"><![endif]-->
<!--[if gt IE 8]><!--><html class="no-js" lang="<?= $lang ?>" dir="ltr"><!--<![endif]-->
	<head>
		<meta charset="utf-8">
		<!-- Web Experience Toolkit (WET) / Boîte à outils de l'expérience Web (BOEW) wet-boew.github.io/wet-boew/License-en.html / wet-boew.github.io/wet-boew/Licence-fr.html -->
		<title><?= $t['title_suffix'] ?></title>
		<meta content="width=device-width,initial-scale=1" name="viewport">
		<!-- Meta data -->
		<meta name="description" content="">
		<!-- Meta data-->
		<?php include 'includes/refTop.php';?>
	</head>
	<body vocab="https://schema.org/" typeof="WebPage">
		<div id="def-top">
		</div>
		<?php include $lang == 'fr' ? 'includes/appTop-fr.php' : 'includes/appTop.php';?>
		<main role="main" property="mainContentOfPage" class="container">
			<h1 property="name" id="wb-cont"><?= $t['not_found_title'] ?></h1>
			
			<p><?= $t['not_found_msg'] ?></p>
		</main>
		<div id="def-footer">
		</div>
		<?php include 'includes/appFooter.php';?>
	</body>
</html>
<?php
}
// Close connection
mysqli_close($link);
?>