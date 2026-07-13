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
		'fieldset_request_details' => 'Request details',
		'fieldset_client_info' => 'Client information',
		'fieldset_dates' => 'Dates',
		'title' => 'Title',
		'last_name' => 'Last name',
		'first_name' => 'First name',
		'client_email' => 'Client email',
		'request_language' => 'Original requested language',
		'language_english' => 'English',
		'language_french' => 'French',
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
		'details_new_window' => 'details (will open in a new window)',
		'yes' => 'Yes',
		'no' => 'No',
		'catalogue_name' => 'Catalogue name',
		'service_name' => 'Service name',
		'subservice_name' => 'Sub-service name',
		'assigned_member' => 'Assigned AAACT team member',
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
		'resolved_email_status' => 'Resolved email to client',
		'resolved_email_sent' => 'Sent',
		'resolved_email_not_sent' => 'Not sent',
		'resolved_email_sent_on' => 'Sent on',
		'resolved_email_send_button' => 'Send resolved + survey email now',
		'resolved_email_missing_client' => 'Client email is missing for this request.',
		'resolved_email_send_success' => 'Resolved email sent to the client.',
		'resolved_email_send_failed' => 'Email could not be sent. Please verify GC Notify settings and try again.',
		'completed' => 'completed',
		'not_completed' => 'not completed',
		'overall_satisfaction' => 'Over-all satisfaction',
		'response_time' => 'Response time',
		'comments' => 'Comments',
		'na' => 'N/A',
		'css_send' => 'Use the resolved email action above to send the survey links.',
		'view_links' => 'Open client notification page',
		'survey_sent' => 'Survey was sent',
		'resend' => 'resend?',
		'mark_sent' => 'Mark survey as sent',
		'request_attachments' => 'Request attachments',
		'attachment_url' => 'Attachment',
		'client_comms' => 'Client communications log',
		'delete_comment' => 'Delete comment',
		'no_comms' => 'No communications available!',
		'staff_comms' => 'AAACT communications log',
		'status_change_log' => 'Status change log',
		'status_change_previous' => 'Previous status',
		'status_change_new' => 'New status',
		'status_change_changed_on' => 'Changed on',
		'status_change_actor' => 'Changed by',
		'status_change_type' => 'Change type',
		'status_change_assignment_from' => 'Assignment from',
		'status_change_assignment_to' => 'Assignment to',
		'status_change_type_status' => 'Status change',
		'status_change_type_assignment' => 'Assignment change',
		'status_change_type_status_and_assignment' => 'Status and assignment change',
		'status_change_sla_due' => 'SLA due date snapshot',
		'status_change_sla_elapsed' => 'SLA elapsed snapshot',
		'status_change_no_entries' => 'No status changes logged yet.',
		'other_change_log' => 'Other request changes',
		'other_change_field' => 'Field',
		'other_change_old' => 'Previous value',
		'other_change_new' => 'New value',
		'other_change_no_entries' => 'No other request changes logged yet.',
		'other_change_client_comms' => 'Client communication log update',
		'other_change_staff_comms' => 'Staff communication log update',
		'other_change_staff_note' => 'Staff note added',
		'other_change_request_title' => 'Request title update',
		'unknown_user' => 'Unknown user',
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
		'fieldset_request_details' => 'Détails de la demande',
		'fieldset_client_info' => 'Renseignements sur le client',
		'fieldset_dates' => 'Dates',
		'title' => 'Titre',
		'last_name' => 'Nom',
		'first_name' => 'Prénom',
		'client_email' => 'Courriel du client',
		'request_language' => 'Langue demandee initiale',
		'language_english' => 'Anglais',
		'language_french' => 'Francais',
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
		'details_new_window' => 'details (will open in a new window)',
		'yes' => 'Oui',
		'no' => 'Non',
		'catalogue_name' => 'Nom du catalogue',
		'service_name' => 'Nom du service',
		'subservice_name' => 'Nom du sous-service',
		'assigned_member' => 'Membre de l\'équipe du AATIA assigné',
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
		'resolved_email_status' => 'Courriel de resolution au client',
		'resolved_email_sent' => 'Envoye',
		'resolved_email_not_sent' => 'Non envoye',
		'resolved_email_sent_on' => 'Envoye le',
		'resolved_email_send_button' => 'Envoyer le courriel de resolution + sondage',
		'resolved_email_missing_client' => 'Le courriel du client est manquant pour cette demande.',
		'resolved_email_send_success' => 'Le courriel de resolution a ete envoye au client.',
		'resolved_email_send_failed' => 'Le courriel n\'a pas pu etre envoye. Verifiez la configuration de GC Notify et reessayez.',
		'completed' => 'complété',
		'not_completed' => 'non complété',
		'overall_satisfaction' => 'Satisfaction globale',
		'response_time' => 'Temps de réponse',
		'comments' => 'Commentaires',
		'na' => 'S.O.',
		'css_send' => 'Utilisez l\'action de courriel de resolution ci-dessus pour envoyer les liens du sondage.',
		'view_links' => 'Ouvrir la page de notification client',
		'survey_sent' => 'Le sondage a été envoyé',
		'resend' => 'renvoyer?',
		'mark_sent' => 'Marquer le sondage comme envoyé',
		'request_attachments' => 'Pièces jointes de la demande',
		'attachment_url' => 'Pièce jointe',
		'client_comms' => 'Journal des communications avec le client',
		'delete_comment' => 'Supprimer le commentaire',
		'no_comms' => 'Aucune communication disponible!',
		'staff_comms' => 'Journal des communications du AATIA',
		'status_change_log' => 'Journal des changements de statut',
		'status_change_previous' => 'Statut precedent',
		'status_change_new' => 'Nouveau statut',
		'status_change_changed_on' => 'Date du changement',
		'status_change_actor' => 'Modifie par',
		'status_change_type' => 'Type de changement',
		'status_change_assignment_from' => 'Affectation precedente',
		'status_change_assignment_to' => 'Nouvelle affectation',
		'status_change_type_status' => 'Changement de statut',
		'status_change_type_assignment' => 'Changement d affectation',
		'status_change_type_status_and_assignment' => 'Changement de statut et d affectation',
		'status_change_sla_due' => 'Date d echeance SLA (instantane)',
		'status_change_sla_elapsed' => 'SLA ecoule (instantane)',
		'status_change_no_entries' => 'Aucun changement de statut enregistre.',
		'other_change_log' => 'Autres changements de la demande',
		'other_change_field' => 'Champ',
		'other_change_old' => 'Valeur precedente',
		'other_change_new' => 'Nouvelle valeur',
		'other_change_no_entries' => 'Aucun autre changement enregistre.',
		'other_change_client_comms' => 'Mise a jour du journal des communications client',
		'other_change_staff_comms' => 'Mise a jour du journal des communications du personnel',
		'other_change_staff_note' => 'Note du personnel ajoutee',
		'other_change_request_title' => 'Mise a jour du titre de la demande',
		'unknown_user' => 'Utilisateur inconnu',
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
require_once __DIR__ . '/includes/session_start.php';
require('BlobStorage.php');
// Grab HTTPS check
require('includes/httpscheck.php');
require('includes/sla-calculator.php');
require('includes/helpers.php');
require('emailController.php');
// Include file for calculating business days
require('includes/calculate-bdays.php');
/** @var array $holidays */

// Grab MySQL connection
require('sql.php');
/** @var mysqli $link */

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
		$requestLanguageCode = rmt_get_request_language($link, (int) $triageid, $lang);
		$requestLanguageLabel = ($requestLanguageCode === 'fr') ? $t['language_french'] : $t['language_english'];
		
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

		$effectiveAtype = (int)($_SESSION['atype'] ?? 0);
		if ($effectiveAtype === 4) {
			$teamIds = getEffectiveTeamIds($link);
			if (empty($tarraycontactid) || !in_array((string)$tarraycontactid, $teamIds, true)) {
				header("location:/index.php?lang=$lang&status=accessdenied");
				exit();
			}
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
		$suppressSlaWarning = rmt_is_resolved_status_id($link, $row['statusid']) || in_array((int)$row['statusid'], [5, 6], true);
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
	<?php
	$pageTitle = $t['page_title'] . $row['requestid'];
	$pageDescription = '';
	include 'includes/template/head.php';
	include 'includes/template/header.php';
	?>
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
				$canShowEditControls = !empty($_SESSION['pid']) && canEditRequests();
				$canEditThisRequest = false;
				$canDeleteThisRequest = canDeleteRequests();
				$isManagerAccount = ((int)($_SESSION['atype'] ?? 0) === 3);
				$isEmployeeAccount = ((int)($_SESSION['atype'] ?? 0) === 5);
				$effectiveEmployeeId = getEffectiveEmployeeUserId($link);
				// Only authenticated users with edit permissions can see request controls.
			if ($canShowEditControls && (isSuperAdmin() || isAdmin() || $isManagerAccount)) {	
				$canEditThisRequest = true;
			?>
			<div class="pull-right">
			<p><a class="btn btn-primary" href="editrequest.php?lang=<?php echo $lang; ?>&erid=<?php echo base64_encode($row['id']);?>&reqid=<?php echo urlencode('a11y-' . $row['requestid']); ?>">Edit <span class="wb-inv"> a11y-<?php echo $row['requestid'];?> request</span></a><?php if ($canDeleteThisRequest) { ?> <a class="wb-lbx btn btn-primary" href="includes/delete-request.php?id=<?php echo $row['id'];?>">Delete<span class="wb-inv"> a11y-<?php echo $row['requestid'];?> request</span> </a><?php } ?></p>
			</div>
			<div class="clearfix"></div>
			<?php
				} elseif ($canShowEditControls) {
				// User is 3 (Manager) or 4 (Team Leader) so check if they have permission to edit this request
				if ($isEmployeeAccount) {
					if ((int)($row['workerid'] ?? 0) === $effectiveEmployeeId) {
						$canEditThisRequest = true;
					}
				} else {
					$tarray = getEffectiveTeamIds($link);
					if(in_array($tarraycontactid, $tarray)) {
						$canEditThisRequest = true;
					}
				}
				if ($canEditThisRequest) {
			?>
			<div class="pull-right">
				<p><a class="btn btn-primary" href="editrequest.php?lang=<?php echo $lang; ?>&erid=<?php echo base64_encode($row['id']);?>&reqid=<?php echo urlencode('a11y-' . $row['requestid']); ?>">Edit <span class="wb-inv"> a11y-<?php echo $row['requestid'];?> request</span></a><?php if ($canDeleteThisRequest) { ?> <a class="wb-lbx btn btn-primary" href="includes/delete-request.php?id=<?php echo $row['id'];?>">Delete<span class="wb-inv"> a11y-<?php echo $row['requestid'];?> request</span> </a><?php } ?></p>
			</div>
			<div class="clearfix"></div>
			<?php 
					}
				}
			?>
			
			<?php if (!$suppressSlaWarning) { ?>
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
			<?php } elseif ($uReview && canEditRequests()) { ?>
			<div class="alert alert-info">
			   <p><?= $t['urgent_review'] ?></p>
			</div>
			<?php } ?>
			<?php } ?>

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

			// Grab the status name
			$result2 = mysqli_query($link, "SELECT $nameField FROM tblstatus WHERE id = '$statusid'");
			$row2 = mysqli_fetch_array($result2);
			$statusname = $row2 ? $row2[0] : '';
			?>

			<h2><?= htmlspecialchars($t['fieldset_request_details']) ?></h2>
			<dl class="colcount-sm-2">
				<div style="break-inside: avoid;">
					<dt><?= $t['title'] ?></dt>
					<dd><?php echo htmlspecialchars($row['title'] ?? '') ?></dd>
				</div>
				<div style="break-inside: avoid;">
					<dt><?= $t['status'] ?></dt>
					<dd><?php echo $statusname ?></dd>
				</div>
				<?php if ($catalogueid != 0) { ?>
				<div style="break-inside: avoid;">
					<dt><?= $t['catalogue_name'] ?></dt>
					<dd><?php echo $cataloguename ?></dd>
				</div>
				<?php } ?>
				<?php if ($serviceid != 0) { ?>
				<div style="break-inside: avoid;">
					<dt><?= $t['service_name'] ?></dt>
					<dd><?php echo $servicename; ?></dd>
				</div>
				<?php } ?>
				<?php if ($subserviceid != 0 && !empty($subservicename)) { ?>
				<div style="break-inside: avoid;">
					<dt><?= $t['subservice_name'] ?></dt>
					<dd><?php echo $subservicename ?></dd>
				</div>
				<?php } ?>
				<?php if ($sourcename) { ?>
				<div style="break-inside: avoid;">
					<dt><?= $t['source'] ?></dt>
					<dd><?php echo $sourcename ?></dd>
				</div>
				<?php } ?>
				<?php if ($catalogueid == 9 && $audienceid != 0 && $audienceid != null) { ?>
				<div style="break-inside: avoid;">
					<dt><?= $t['audience'] ?></dt>
					<dd><?php echo $audiencename ?></dd>
				</div>
				<?php } ?>
				<?php if ($row['firstsprintstartdate'] != "") { ?>
				<div style="break-inside: avoid;">
					<dt><?= $t['sprint_start'] ?></dt>
					<dd><?php echo htmlspecialchars($row['firstsprintstartdate']) ?></dd>
				</div>
				<?php } ?>
				<?php if ($row['firstsprintenddate'] != "") { ?>
				<div style="break-inside: avoid;">
					<dt><?= $t['sprint_end'] ?></dt>
					<dd><?php echo htmlspecialchars($row['firstsprintenddate']) ?></dd>
				</div>
				<?php } ?>
				<?php if (isset($row['sprintschedule']) && $row['sprintschedule'] != "") { ?>
				<div style="break-inside: avoid;">
					<dt><?= $t['sprint_schedule'] ?></dt>
					<dd><a href="<?php echo htmlspecialchars($row['sprintschedule']) ?>"><?= $t['view_sprint_schedule'] ?></a></dd>
				</div>
				<?php } ?>
				<?php if (isset($row['sprintdefects']) && $row['sprintdefects'] != "") { ?>
				<div style="break-inside: avoid;">
					<dt><?= $t['sprint_defect'] ?></dt>
					<dd><a href="<?php echo htmlspecialchars($row['sprintdefects']) ?>"><?= $t['view_sprint_defect'] ?></a></dd>
				</div>
				<?php } ?>
				<?php
				// Check if the account is admin level to show the assigned member.
			if ($_SESSION['is_superuser'] || $_SESSION['is_admin'] || $_SESSION['atype'] == '3' || $_SESSION['atype'] == '4' || $_SESSION['atype'] == '5') {
					$workerid = $row['workerid'];
					if ($workerid != 0 AND $workerid != "") {
						$result2 = mysqli_query($link, "SELECT firstname, lastname FROM tblusers WHERE id = '$workerid'");
						$row2 = $result2 ? mysqli_fetch_assoc($result2) : null;
						$ufirstname = $row2['firstname'] ?? '';
						$ulastname = $row2['lastname'] ?? '';
						$assignedName = trim($ufirstname . ' ' . $ulastname);
						if ($assignedName === '') {
							$assignedName = ($lang === 'fr') ? 'Utilisateur indisponible' : 'User unavailable';
						}
				?>
				<div style="break-inside: avoid;">
					<dt><?= $t['assigned_member'] ?></dt>
					<dd><?php echo htmlspecialchars($assignedName, ENT_QUOTES, 'UTF-8') ?></dd>
				</div>
				<?php
					}
				}
				?>
			</dl>

			<?php if ($_SESSION['pid'] != "") { ?>
			<h2><?= htmlspecialchars($t['fieldset_client_info']) ?></h2>
			<dl class="colcount-sm-2">
				<?php if ($row['clientfname'] != "") { ?>
				<div style="break-inside: avoid;">
					<dt><?= $t['first_name'] ?></dt>
					<dd><?php echo htmlspecialchars($row['clientfname']) ?></dd>
				</div>
				<?php } ?>
				<?php if ($row['clientlname'] != "") { ?>
				<div style="break-inside: avoid;">
					<dt><?= $t['last_name'] ?></dt>
					<dd><?php echo htmlspecialchars($row['clientlname']) ?></dd>
				</div>
				<?php } ?>
				<?php if ($row['clientemail'] != "") { ?>
				<div style="break-inside: avoid;">
					<dt><?= $t['client_email'] ?></dt>
					<dd><a href="mailto:<?php echo htmlspecialchars($row['clientemail']) ?>?Subject=a11y-<?php echo $row['requestid'] ?> - <?php echo htmlspecialchars($row['title']) ?>"><?php echo htmlspecialchars($row['clientemail']) ?> <span class="glyphicon glyphicon-envelope"></span><span class="wb-inv">- <?= $t['send_email'] ?></span></a></dd>
				</div>
				<?php } ?>
				<div style="break-inside: avoid;">
					<dt><?= $t['request_language'] ?></dt>
					<dd><?php echo htmlspecialchars($requestLanguageLabel, ENT_QUOTES, 'UTF-8'); ?></dd>
				</div>
				<?php if ($row['clientphone'] != "") { ?>
				<div style="break-inside: avoid;">
					<dt><?= $t['client_phone'] ?></dt>
					<dd><?php echo htmlspecialchars($row['clientphone']) ?></dd>
				</div>
				<?php } ?>
				<?php if ($departmentAgency != "") { ?>
				<div style="break-inside: avoid;">
					<dt><?= $t['department_agency'] ?></dt>
					<dd><?php echo htmlspecialchars($departmentAgency, ENT_QUOTES, 'UTF-8'); ?></dd>
				</div>
				<?php } ?>
			</dl>
			<?php } ?>

			<h2><?= htmlspecialchars($t['fieldset_dates']) ?></h2>
			<dl class="colcount-sm-2">
				<div style="break-inside: avoid;">
					<dt><?= $t['date_received'] ?></dt>
					<dd><?php echo htmlspecialchars($row['datereceived']) ?></dd>
				</div>
				<?php if ($sla > 0 && $statusid != 4 && $statusid != 5) {
					require_once('includes/sla-calculator.php');
					$businessDaysAdded = 0;
					$currentDate = date('Y-m-d', strtotime($datereceived));

					while ($businessDaysAdded < $sla) {
						$currentDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));
						if (isBusinessDay($currentDate, $link)) {
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
				<?php if ($row['dateupdated'] != "") { ?>
				<div style="break-inside: avoid;">
					<dt><?= $t['date_updated'] ?></dt>
					<dd><?php echo htmlspecialchars($row['dateupdated']) ?></dd>
				</div>
				<?php } ?>
				<?php if ($row['daterequired'] != "") { ?>
				<div style="break-inside: avoid;">
					<dt><?php if (($catalogueid == 5) AND ($serviceid != 47)) { ?><?= $t['coaching_date'] ?><?php } else { ?><?= $t['date_required'] ?><?php } ?></dt>
					<dd><?php echo htmlspecialchars($row['daterequired']) ?></dd>
				</div>
				<?php } ?>
				<?php if ($row['dateresolved'] != "") { ?>
				<div style="break-inside: avoid;">
					<dt><?= $t['date_resolved'] ?></dt>
					<dd><?php echo htmlspecialchars($row['dateresolved']) ?></dd>
				</div>
				<?php } ?>
			</dl>

			<?php
        if ($_SESSION['is_superuser'] || $_SESSION['is_admin'] || $_SESSION['atype'] == 5 || $_SESSION['atype'] == 3 || $_SESSION['atype'] == 4 || $_SESSION['atype'] == 6)
			{
			?>
			
			
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
			// Check if status is resolved, if it is then display the client satisfaction survey status
			if (rmt_is_resolved_status_id($link, $statusid)){
			$resolvedClientEmail = trim((string) ($row['clientemail'] ?? ''));
			$resolvedActionStatus = '';

			// Check if surveys are enabled for this catalogue
			$catalogueSurveySql = "SELECT survey FROM tblcatalogue WHERE id = '$catalogueid'";
			$catalogueSurveyResult = mysqli_query($link, $catalogueSurveySql);
			$catalogueSurveyRow = mysqli_fetch_array($catalogueSurveyResult);
			$surveyEnabled = ((int) ($catalogueSurveyRow['survey'] ?? 0) === 1);
			$allowResolvedEmailSendInView = false;

			if ($allowResolvedEmailSendInView && $_SERVER['REQUEST_METHOD'] === 'POST' && (($_POST['email_action'] ?? '') === 'send_resolved_email')) {
				if (!$canEditThisRequest) {
					$resolvedActionStatus = 'failed';
				} else {
				if ($resolvedClientEmail === '') {
					$resolvedActionStatus = 'missing_email';
				} else {
					$requestLanguage = rmt_get_request_language($link, (int) $triageid, $lang);
					$encodedTriageId = base64_encode((string) $triageid);
					$encodedRequestPublicId = urlencode('a11y-' . (string) $row['requestid']);
					$requestViewUrl = app_url('viewrequest.php?lang=' . $requestLanguage . '&erid=' . $encodedTriageId . '&reqid=' . $encodedRequestPublicId);
					$frSurveyLink = app_url('client-survey.php?lang=fr&erid=' . $encodedTriageId . '&reqid=' . $encodedRequestPublicId);
					$enSurveyLink = app_url('client-survey.php?lang=en&erid=' . $encodedTriageId . '&reqid=' . $encodedRequestPublicId);

					$resolvedContext = [
						'requestid' => (string) $row['requestid'],
						'client_fname' => (string) ($row['clientfname'] ?? ''),
						'client_lname' => (string) ($row['clientlname'] ?? ''),
						'url' => $requestViewUrl,
					];
					if ($surveyEnabled) {
						$resolvedContext['survey_link_en'] = $enSurveyLink;
						$resolvedContext['survey_link_fr'] = $frSurveyLink;
					}

					$category = rmt_notification_template_category('resolved');
					$personalisation = [
						'requestid' => (string) $row['requestid'],
						'requesttitle' => (string) ($row['title'] ?? ''),
						'client_fname' => (string) ($row['clientfname'] ?? ''),
						'client_lname' => (string) ($row['clientlname'] ?? ''),
						'url' => $requestViewUrl,
						'notification_event' => 'resolved',
						'template_category_id' => $category['id'],
						'template_category_name_en' => $category['name_en'],
						'template_category_name_fr' => $category['name_fr'],
						'subject' => rmt_notification_subject('resolved', 'client', $requestLanguage, ['requestid' => (string) $row['requestid']]),
						'message' => rmt_notification_message('resolved', 'client', $requestLanguage, $resolvedContext),
					];

					$templateId = app_notify_template_id('notification_generic');
					$sent = sendEmail($resolvedClientEmail, $templateId, json_encode($personalisation), ['recipientType' => 'client']);
					if ($sent) {
						if ($surveyEnabled) {
							$currentSurveySentCount = (int) ($row['cssurvey'] ?? 0);
							$updatedSurveySentCount = ($currentSurveySentCount <= 0) ? 1 : ($currentSurveySentCount + 1);
							mysqli_query($link, "UPDATE tbltriage SET cssurvey = '$updatedSurveySentCount' WHERE id = '$triageid'");
							$row['cssurvey'] = $updatedSurveySentCount;
						}

						$senderId = isset($_SESSION['pid']) ? (int) $_SESSION['pid'] : 0;
						rmt_mark_resolved_email_sent($link, (int) $triageid, $senderId);
						$resolvedActionStatus = 'success';
					} else {
						$resolvedActionStatus = 'failed';
					}
				}
					}
			}

			$resolvedEmailSentDate = rmt_get_resolved_email_sent_date($link, (int) $triageid);
			$resolvedEmailSent = ($resolvedEmailSentDate !== null && $resolvedEmailSentDate !== '');
			?>
			<h2><?= htmlspecialchars($t['resolved_email_status']) ?></h2>
			<?php if ($resolvedActionStatus === 'success'): ?>
			<p class="text-success"><strong><?= htmlspecialchars($t['resolved_email_send_success']) ?></strong></p>
			<?php elseif ($resolvedActionStatus === 'failed'): ?>
			<p class="text-danger"><strong><?= htmlspecialchars($t['resolved_email_send_failed']) ?></strong></p>
			<?php elseif ($resolvedActionStatus === 'missing_email'): ?>
			<p class="text-danger"><strong><?= htmlspecialchars($t['resolved_email_missing_client']) ?></strong></p>
			<?php endif; ?>
			<p>
				<strong><?= htmlspecialchars($resolvedEmailSent ? $t['resolved_email_sent'] : $t['resolved_email_not_sent']) ?></strong>
				<?php if ($resolvedEmailSent): ?>
				- <?= htmlspecialchars($t['resolved_email_sent_on']) ?> <?= htmlspecialchars($resolvedEmailSentDate) ?>
				<?php endif; ?>
			</p>
			<?php if ($allowResolvedEmailSendInView && !$resolvedEmailSent && $canEditThisRequest): ?>
				<?php if ($resolvedClientEmail !== ''): ?>
				<form method="post" action="" class="form-inline mrgn-bttm-md">
					<input type="hidden" name="email_action" value="send_resolved_email">
					<button type="submit" class="btn btn-primary"><?= htmlspecialchars($t['resolved_email_send_button']) ?></button>
				</form>
				<?php else: ?>
				<p><?= htmlspecialchars($t['resolved_email_missing_client']) ?></p>
				<?php endif; ?>
			<?php endif; ?>
			<?php if ($surveyEnabled) {
				// Status is resolved and surveys are enabled, so first check if a client survey has been completed.
				$surveySql = "SELECT * FROM tblcss WHERE requestid='$triageid' AND status=1";
				$surveyResult = mysqli_query($link,$surveySql);
				//List it
				if(mysqli_num_rows($surveyResult)>0){
					while($surveyRow = mysqli_fetch_array($surveyResult)){
						$overall = $surveyRow['overall'];
						$response = $surveyRow['response'];
						$comments = $surveyRow['comments'];
						if ($comments=="") {
							$comments = "N/A";
						}
					}				
			?>
			<h2><?= htmlspecialchars($t['css_completed']) ?> <span class="glyphicon glyphicon-ok"></span><span class="wb-inv"><?= htmlspecialchars($t['completed']) ?></span></h2>
				<dt><?= htmlspecialchars($t['response_time']) ?></dt>
				<dd><?php echo $response ?>/10</dd>
				<dt><?= htmlspecialchars($t['comments']) ?></dt>
				<dd><?php echo $comments ?></dd>
			</dl>
			<?php
				} else {
					$surveySentCount = (int) ($row['cssurvey'] ?? 0);
			?>
			<h2><?= htmlspecialchars($t['css_completed']) ?> <span class="glyphicon glyphicon-remove"></span><span class="wb-inv"><?= htmlspecialchars($t['not_completed']) ?></span></h2>
			
			<p><?= htmlspecialchars($t['css_send']) ?></p>
			<?php if ($surveySentCount>=1) { ?><p><span class="label label-success"><?= htmlspecialchars($t['survey_sent']) ?> (<?php echo $surveySentCount ?>)</span></p><?php } ?>
			
			<?php
				}
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
			
			<h2><?= htmlspecialchars($t['client_comms']) ?></h2>
			
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
				
			<dt><?php echo $dateadded ?><?php if ($canDeleteThisRequest) {?> <a class="wb-lbx" href="includes/delete-comms.php?t=c&id=<?php echo $row2['id'];?>&rid=<?php echo $triageid ?>"><span class="glyphicon glyphicon-trash"></span><span class="wb-inv"> <?= htmlspecialchars($t['delete_comment']) ?></span></a><?php } ?></dt>
				<dd><?php echo $nnotes ?></dd>
				<?php } ?>
			</dl>
			<?php if (!$hasVisibleClientComms) { ?>
			<p><?= htmlspecialchars($t['no_comms']) ?></p>
			<?php } ?>
			<?php } else { ?>
			<p><?= htmlspecialchars($t['no_comms']) ?></p>
			<?php } } ?>

			<?php
			$canViewStatusChangeLog = isSuperAdmin()
				|| !empty($_SESSION['is_admin'])
				|| in_array((int)($_SESSION['atype'] ?? 0), [1, 3, 4], true);

			if ($canViewStatusChangeLog) {
				$hasPreviousStatusColumn = rmt_table_has_column($link, 'StatusHistory', 'previousStatusID');
				$hasActorUserColumn = rmt_table_has_column($link, 'StatusHistory', 'actorUserID');
				$hasChangeTypeColumn = rmt_table_has_column($link, 'StatusHistory', 'changeType');
				$hasPreviousWorkerColumn = rmt_table_has_column($link, 'StatusHistory', 'previousWorkerID');
				$hasNewWorkerColumn = rmt_table_has_column($link, 'StatusHistory', 'newWorkerID');
				$hasSlaDueDateColumn = rmt_table_has_column($link, 'StatusHistory', 'slaDueDate');
				$hasSlaElapsedColumn = rmt_table_has_column($link, 'StatusHistory', 'slaElapsedBusinessDays');
				$hasRequestFieldHistoryTable = rmt_table_has_column($link, 'RequestFieldHistory', 'requestID');

				$requestIdEscaped = mysqli_real_escape_string($link, (string) $row['requestid']);
				$previousStatusSelect = $hasPreviousStatusColumn
					? 'sh.previousStatusID AS previousStatusID'
					: "(SELECT sh_prev.statusID FROM StatusHistory sh_prev WHERE sh_prev.requestID = sh.requestID AND sh_prev.id < sh.id ORDER BY sh_prev.id DESC LIMIT 1) AS previousStatusID";
				$actorUserSelect = $hasActorUserColumn ? 'sh.actorUserID AS actorUserID' : 'NULL AS actorUserID';
				$changeTypeSelect = $hasChangeTypeColumn ? 'sh.changeType AS changeType' : "'status_change' AS changeType";
				$previousWorkerSelect = $hasPreviousWorkerColumn ? 'sh.previousWorkerID AS previousWorkerID' : 'NULL AS previousWorkerID';
				$newWorkerSelect = $hasNewWorkerColumn ? 'sh.newWorkerID AS newWorkerID' : 'NULL AS newWorkerID';
				$slaDueDateSelect = $hasSlaDueDateColumn ? 'sh.slaDueDate AS slaDueDate' : 'NULL AS slaDueDate';
				$slaElapsedSelect = $hasSlaElapsedColumn ? 'sh.slaElapsedBusinessDays AS slaElapsedBusinessDays' : 'NULL AS slaElapsedBusinessDays';

				$statusHistorySql = "SELECT sh.id, sh.changeTimeStamp, sh.statusID AS newStatusID, $previousStatusSelect, $actorUserSelect, $changeTypeSelect, $previousWorkerSelect, $newWorkerSelect, $slaDueDateSelect, $slaElapsedSelect FROM StatusHistory sh WHERE sh.requestID = '$requestIdEscaped' ORDER BY sh.id DESC";
				$statusHistoryResult = mysqli_query($link, $statusHistorySql);

				$statusMap = [];
				$statusMapResult = mysqli_query($link, "SELECT id, $nameField AS status_name FROM tblstatus");
				if ($statusMapResult && mysqli_num_rows($statusMapResult) > 0) {
					while ($statusMapRow = mysqli_fetch_assoc($statusMapResult)) {
						$statusMap[(int)$statusMapRow['id']] = (string)$statusMapRow['status_name'];
					}
				}
				$userNameCache = [];
			?>
			<h2><?= htmlspecialchars($t['status_change_log']) ?></h2>
			<?php if ($statusHistoryResult && mysqli_num_rows($statusHistoryResult) > 0) { ?>
			<table class="wb-tables table table-striped" data-paging="false" data-order='[[5, "desc"]]'>
				<thead>
					<tr>
						<th><?= htmlspecialchars($t['status_change_type']) ?></th>
						<th><?= htmlspecialchars($t['status_change_previous']) ?></th>
						<th><?= htmlspecialchars($t['status_change_new']) ?></th>
						<th><?= htmlspecialchars($t['status_change_assignment_from']) ?></th>
						<th><?= htmlspecialchars($t['status_change_assignment_to']) ?></th>
						<th><?= htmlspecialchars($t['status_change_changed_on']) ?></th>
						<th><?= htmlspecialchars($t['status_change_actor']) ?></th>
						<th><?= htmlspecialchars($t['status_change_sla_due']) ?></th>
						<th><?= htmlspecialchars($t['status_change_sla_elapsed']) ?></th>
					</tr>
				</thead>
				<tbody>
					<?php while ($historyRow = mysqli_fetch_assoc($statusHistoryResult)) {
						$changeTypeRaw = (string)($historyRow['changeType'] ?? 'status_change');
						$changeTypeLabel = $t['status_change_type_status'];
						if ($changeTypeRaw === 'assignment_change') {
							$changeTypeLabel = $t['status_change_type_assignment'];
						} elseif ($changeTypeRaw === 'status_and_assignment_change') {
							$changeTypeLabel = $t['status_change_type_status_and_assignment'];
						}

						$previousStatusId = (int)($historyRow['previousStatusID'] ?? 0);
						$newStatusId = (int)($historyRow['newStatusID'] ?? 0);
						$previousStatusLabel = $previousStatusId > 0 && isset($statusMap[$previousStatusId]) ? $statusMap[$previousStatusId] : $t['na'];
						$newStatusLabel = $newStatusId > 0 && isset($statusMap[$newStatusId]) ? $statusMap[$newStatusId] : $t['na'];

						$actorUserId = (int)($historyRow['actorUserID'] ?? 0);
						$actorLabel = $t['na'];
						if ($actorUserId > 0) {
							if (!isset($userNameCache[$actorUserId])) {
								$actorUserEscaped = mysqli_real_escape_string($link, (string)$actorUserId);
								$actorResult = mysqli_query($link, "SELECT firstname, lastname FROM tblusers WHERE id = '$actorUserEscaped' LIMIT 1");
								$actorRow = $actorResult ? mysqli_fetch_assoc($actorResult) : null;
								$actorName = trim(((string)($actorRow['firstname'] ?? '')) . ' ' . ((string)($actorRow['lastname'] ?? '')));
								$userNameCache[$actorUserId] = $actorName !== '' ? $actorName : $t['unknown_user'];
							}
							$actorLabel = $userNameCache[$actorUserId];
						}

						$previousWorkerId = (int)($historyRow['previousWorkerID'] ?? 0);
						$newWorkerId = (int)($historyRow['newWorkerID'] ?? 0);
						$previousWorkerLabel = $t['na'];
						$newWorkerLabel = $t['na'];
						if ($previousWorkerId > 0) {
							if (!isset($userNameCache[$previousWorkerId])) {
								$previousWorkerEscaped = mysqli_real_escape_string($link, (string)$previousWorkerId);
								$previousWorkerResult = mysqli_query($link, "SELECT firstname, lastname FROM tblusers WHERE id = '$previousWorkerEscaped' LIMIT 1");
								$previousWorkerRow = $previousWorkerResult ? mysqli_fetch_assoc($previousWorkerResult) : null;
								$previousWorkerName = trim(((string)($previousWorkerRow['firstname'] ?? '')) . ' ' . ((string)($previousWorkerRow['lastname'] ?? '')));
								$userNameCache[$previousWorkerId] = $previousWorkerName !== '' ? $previousWorkerName : $t['unknown_user'];
							}
							$previousWorkerLabel = $userNameCache[$previousWorkerId];
						}
						if ($newWorkerId > 0) {
							if (!isset($userNameCache[$newWorkerId])) {
								$newWorkerEscaped = mysqli_real_escape_string($link, (string)$newWorkerId);
								$newWorkerResult = mysqli_query($link, "SELECT firstname, lastname FROM tblusers WHERE id = '$newWorkerEscaped' LIMIT 1");
								$newWorkerRow = $newWorkerResult ? mysqli_fetch_assoc($newWorkerResult) : null;
								$newWorkerName = trim(((string)($newWorkerRow['firstname'] ?? '')) . ' ' . ((string)($newWorkerRow['lastname'] ?? '')));
								$userNameCache[$newWorkerId] = $newWorkerName !== '' ? $newWorkerName : $t['unknown_user'];
							}
							$newWorkerLabel = $userNameCache[$newWorkerId];
						}

						$slaDueDateLabel = !empty($historyRow['slaDueDate']) ? (string)$historyRow['slaDueDate'] : $t['na'];
						$slaElapsedLabel = isset($historyRow['slaElapsedBusinessDays']) && $historyRow['slaElapsedBusinessDays'] !== null
							? (int)$historyRow['slaElapsedBusinessDays'] . ' ' . $t['business_days']
							: $t['na'];
					?>
					<tr>
						<td><?= htmlspecialchars($changeTypeLabel) ?></td>
						<td><?= htmlspecialchars($previousStatusLabel) ?></td>
						<td><?= htmlspecialchars($newStatusLabel) ?></td>
						<td><?= htmlspecialchars($previousWorkerLabel) ?></td>
						<td><?= htmlspecialchars($newWorkerLabel) ?></td>
						<td><?= htmlspecialchars((string)$historyRow['changeTimeStamp']) ?></td>
						<td><?= htmlspecialchars($actorLabel) ?></td>
						<td><?= htmlspecialchars($slaDueDateLabel) ?></td>
						<td><?= htmlspecialchars($slaElapsedLabel) ?></td>
					</tr>
					<?php } ?>
				</tbody>
			</table>
			<?php } else { ?>
			<p><?= htmlspecialchars($t['status_change_no_entries']) ?></p>
			<?php } ?>

			<h2><?= htmlspecialchars($t['other_change_log']) ?></h2>
			<?php
			if ($hasRequestFieldHistoryTable) {
				$otherChangeSql = "SELECT id, fieldName, oldValue, newValue, actorUserID, changeTimeStamp FROM RequestFieldHistory WHERE requestID = '$requestIdEscaped' ORDER BY id DESC";
				$otherChangeResult = mysqli_query($link, $otherChangeSql);
				if ($otherChangeResult && mysqli_num_rows($otherChangeResult) > 0) {
					$otherChangeFieldMap = [
						'request_title' => $t['other_change_request_title'],
						'client_communication_log' => $t['other_change_client_comms'],
						'staff_communication_log' => $t['other_change_staff_comms'],
						'staff_note_added' => $t['other_change_staff_note'],
					];
			?>
			<table class="wb-tables table table-striped" data-paging="false" data-order='[[4, "desc"]]'>
				<thead>
					<tr>
						<th><?= htmlspecialchars($t['other_change_field']) ?></th>
						<th><?= htmlspecialchars($t['other_change_old']) ?></th>
						<th><?= htmlspecialchars($t['other_change_new']) ?></th>
						<th><?= htmlspecialchars($t['status_change_actor']) ?></th>
						<th><?= htmlspecialchars($t['status_change_changed_on']) ?></th>
					</tr>
				</thead>
				<tbody>
					<?php while ($otherRow = mysqli_fetch_assoc($otherChangeResult)) {
						$fieldNameRaw = (string)($otherRow['fieldName'] ?? '');
						$fieldNameLabel = $otherChangeFieldMap[$fieldNameRaw] ?? ($fieldNameRaw !== '' ? $fieldNameRaw : $t['na']);
						$oldValueLabel = array_key_exists('oldValue', $otherRow) && $otherRow['oldValue'] !== null && $otherRow['oldValue'] !== ''
							? (string)$otherRow['oldValue']
							: $t['na'];
						$newValueLabel = array_key_exists('newValue', $otherRow) && $otherRow['newValue'] !== null && $otherRow['newValue'] !== ''
							? (string)$otherRow['newValue']
							: $t['na'];

						$otherActorUserId = (int)($otherRow['actorUserID'] ?? 0);
						$otherActorLabel = $t['na'];
						if ($otherActorUserId > 0) {
							if (!isset($userNameCache[$otherActorUserId])) {
								$otherActorEscaped = mysqli_real_escape_string($link, (string)$otherActorUserId);
								$otherActorResult = mysqli_query($link, "SELECT firstname, lastname FROM tblusers WHERE id = '$otherActorEscaped' LIMIT 1");
								$otherActorRow = $otherActorResult ? mysqli_fetch_assoc($otherActorResult) : null;
								$otherActorName = trim(((string)($otherActorRow['firstname'] ?? '')) . ' ' . ((string)($otherActorRow['lastname'] ?? '')));
								$userNameCache[$otherActorUserId] = $otherActorName !== '' ? $otherActorName : $t['unknown_user'];
							}
							$otherActorLabel = $userNameCache[$otherActorUserId];
						}
					?>
					<tr>
						<td><?= htmlspecialchars($fieldNameLabel) ?></td>
						<td><?= htmlspecialchars($oldValueLabel) ?></td>
						<td><?= htmlspecialchars($newValueLabel) ?></td>
						<td><?= htmlspecialchars($otherActorLabel) ?></td>
						<td><?= htmlspecialchars((string)($otherRow['changeTimeStamp'] ?? '')) ?></td>
					</tr>
					<?php } ?>
				</tbody>
			</table>
			<?php
				} else {
			?>
			<p><?= htmlspecialchars($t['other_change_no_entries']) ?></p>
			<?php
				}
			} else {
			?>
			<p><?= htmlspecialchars($t['other_change_no_entries']) ?></p>
			<?php } ?>
			<?php } ?>
			
			<?php
			// Check if the account is admin level to show this option 
		if ($_SESSION['is_superuser'] OR $_SESSION['is_admin'] OR $_SESSION['atype']=='3' OR $_SESSION['atype']=='4' OR $_SESSION['atype'] == '6') {
			?>			
			<h2><?= htmlspecialchars($t['staff_comms']) ?></h2>
			
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
					$row3 = $result3 ? mysqli_fetch_assoc($result3) : null;
					$cfname = $row3['firstname'] ?? '';
					$clname = $row3['lastname'] ?? '';
				?>
				<dt><?php echo $dateadded ?><?php if($creatorid!=0 && ($cfname !== '' || $clname !== '')) {?> - <?php echo htmlspecialchars(trim($cfname . ' ' . $clname), ENT_QUOTES, 'UTF-8') ?><?php } ?><?php if ($canDeleteThisRequest) {?> <a class="wb-lbx" href="includes/delete-comms.php?t=a&id=<?php echo $row2['id'];?>&rid=<?php echo $triageid ?>"><span class="glyphicon glyphicon-trash"></span><span class="wb-inv"> <?= htmlspecialchars($t['delete_comment']) ?></span></a><?php } ?></dt>
				<dd><?php echo $annotes ?></dd>
				<?php } ?>
			</dl>
			<?php } else { ?>
			<p><?= htmlspecialchars($t['no_comms']) ?></p>
			<?php } ?>
			<?php } ?>
			<?php include 'includes/template/page-details.php'; ?>
		</main>
		<div class="image-preview" id="imagePreview" role="dialog" aria-hidden="true" style="display:none">
        <button class="close-btn" id="closePreview" aria-label="Close image preview">&times;</button>
        <img id="previewImage" src="" alt="Preview">
        <p id="imageAnnouncement" class="sr-only" aria-live="assertive"></p>
    </div>
		<?php include 'includes/template/footer.php'; ?>
		<?php include 'includes/template/scripts.php'; ?>
		<script src="/public/js/file-manager.js"></script>
	</body>

</html>
<?php
	}
} else { 
// Wrong ID so display an error message
?>
	<?php
	$pageTitle = $t['not_found_title'];
	$pageDescription = '';
	include 'includes/template/head.php';
	include 'includes/template/header.php';
	?>
		<main role="main" property="mainContentOfPage" class="container">
			<h1 property="name" id="wb-cont"><?= $t['not_found_title'] ?></h1>
			
			<p><?= $t['not_found_msg'] ?></p>
			<?php include 'includes/template/page-details.php'; ?>
		</main>
		<?php include 'includes/template/footer.php'; ?>
		<?php include 'includes/template/scripts.php'; ?>
	</body>
</html>
<?php
}
// Close connection
mysqli_close($link);
?>