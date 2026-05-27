<?php
// Grab HTTPS check
require('includes/httpscheck.php');

// Include file for calculating business days
require('includes/calculate-bdays.php');
require('includes/sla-calculator.php');
// Grab MySQL connection
require('sql.php');

// Route guests to the public new request page.
if (empty($_SESSION['pid'])) {
	$lang = isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'fr'])
		? $_GET['lang']
		: (isset($_SESSION['lang']) && in_array($_SESSION['lang'], ['en', 'fr']) ? $_SESSION['lang'] : 'en');

	$_SESSION['lang'] = $lang;
	header("location:openrequest.php?lang={$lang}");
	exit();
}

// Language detection
$lang = isset($_GET['lang']) ? $_GET['lang'] : (isset($_SESSION['lang']) ? $_SESSION['lang'] : 'en');
$_SESSION['lang'] = $lang;

// Translations
$translations = [
	'en' => [
		'page_title' => 'Request Management Tool - IT Accessibility Office',
		'heading' => 'Requests overview',
		'access_denied' => 'Access denied:',
		'access_denied_text' => 'You don\'t have sufficient access level to view that page, sorry!',
		'login_required' => 'You need to login to access administrative pages, sorry!',
		'success' => 'Success',
		'new_request_success' => 'You have successfully submitted a new request, thank you!',
		'please_read' => 'Please Read',
		'old_site_redirect' => 'You have been redirected to the new domain and host, please update your bookmarks and links.',
		'batch_success' => 'You have successfully run the batch process request, thank you!',
		'delete_success' => 'You have successfully deleted the request, thank you!',
		'wrong_id' => 'There was an issue with the request ID, please try again, thank you!',
		'request_num' => 'Request #',
		'title' => 'Title',
		'client' => 'Client',
		'request_service' => 'Request service',
		'status' => 'Status',
		'edit' => 'Edit',
		'no_requests' => 'No requests available!',
		'escalation_required' => 'Escalation required',
		'close_to_sla' => 'Request is close to SLA',
		'view_request' => 'viewrequest.php?lang=en',
		'edit_request' => 'editrequest.php',
		'delete_request' => 'includes/delete-request.php',
		'filter_options' => 'Filter options',
		'filter_all' => 'All',
		'filter_status_label' => 'Status',
		'filter_catalogue_label' => 'Service type',
		'filter_priority_label' => 'Priority',
		'submitted_date' => 'Submitted date',
		'no_title' => '[No title entered]',
		'no_results_filter' => 'No requests match the selected filters.',
		'delete_request_title' => 'Delete this request',
		'delete_label' => 'Delete',
		'assigned_to' => 'Assigned to',
		'search_label' => 'Filter',
		'results_of' => 'results out of'
	],
	'fr' => [
		'page_title' => 'Outil de gestion des demandes - Bureau de l\'accessibilité de la TI',
		'heading' => 'Aperçu des demandes',
		'access_denied' => 'Accès refusé:',
		'access_denied_text' => 'Vous ne disposez pas d\'un niveau d\'accès suffisant pour afficher cette page, désolé!',
		'login_required' => 'Vous devez vous connecter pour accéder aux pages administratives, désolé!',
		'success' => 'Succès',
		'new_request_success' => 'Vous avez soumis avec succès une nouvelle demande, merci!',
		'please_read' => 'Please Read',
		'old_site_redirect' => 'You have been redirected to the new domain and host, please update your bookmarks and links.',
		'batch_success' => 'Vous avez exécuté avec succès la mise-à-jour des demandes du CEA, merci!',
		'delete_success' => 'Vous avez bien supprimé la demande, merci!',
		'wrong_id' => 'Il y a eu un problème avec l\'ID de la demande, veuillez réessayer, merci!',
		'request_num' => '# de la demande',
		'title' => 'Titre',
		'client' => 'Client',
		'request_service' => 'Service demandé',
		'status' => 'Statut',
		'edit' => 'Modifier',
		'no_requests' => 'Aucune demande disponible!',
		'escalation_required' => 'Escalade requise',
		'close_to_sla' => 'La demande approche du NdS',
		'view_request' => 'viewrequest.php?lang=fr',
		'edit_request' => 'editrequest.php',
		'delete_request' => 'includes/delete-request.php',
		'filter_options' => 'Options de filtrage',
		'filter_all' => 'Tous',
		'filter_status_label' => 'Statut',
		'filter_catalogue_label' => 'Type de service',
		'filter_priority_label' => 'Priorité',
		'submitted_date' => 'Date de soumission',
		'no_title' => '[Aucun titre saisi]',
		'no_results_filter' => 'Aucune demande ne correspond aux filtres sélectionnés.',
		'delete_request_title' => 'Supprimer cette demande',
		'delete_label' => 'Supprimer',
		'assigned_to' => 'Attribué à',
		'search_label' => 'Filtrer',
		'results_of' => 'résultats sur'
	]
];

$t = $translations[$lang];

// Check if there is a status
if (!empty($_GET['status'])) {
	$status = $_GET['status'];
} else {
	$status = "";
}
if (function_exists("curl_init")) {
	$message = "Yes Curl";
} else {
	$message = "No Curl";
}

// =============================================================================
// PAGE FRONTMATTER - Define page metadata
// =============================================================================
$page = [
	'title' => [
		'en' => 'Requests overview',
		'fr' => 'Aperçu des demandes'
	],
	'description' => [
		'en' => 'Overview of all open accessibility requests',
		'fr' => 'Aperçu de toutes les demandes d\'accessibilité ouvertes'
	]
];

// Extract values for current language
$pageTitle = $page['title'][$lang];
$pageDescription = $page['description'][$lang];
$extraStyles = '
	.wb-eqht-grd .panel.hght-inhrt {
		display: flex;
		flex-direction: column;
	}
	.wb-eqht-grd .panel.hght-inhrt .panel-body {
		flex: 1 1 auto;
	}
';

// Include template head
include 'includes/template/head.php';
?>
	<?php include 'includes/template/header.php'; ?>
		<main role="main" property="mainContentOfPage" class="container">
			<h1 property="name" id="wb-cont"><?= $t['heading'] ?></h1>
			<?php if ($_SESSION['pid'] == 108) { ?>
			<section class="alert alert-danger">
				<h2>There are cookies in the cupborad</h2>
				<ul>
					<li>The best kind is chocolate chip, fight me if you disagree</li>
				</ul>
			</section>
			<?php } ?>
			<?php 
			if ($status == 'accessdenied') {
			?>			
			<section class="alert alert-danger">
				<h2><?= $t['access_denied'] ?></h2>
				<ul>
					<li><?= $t['access_denied_text'] ?></li>
				</ul>
			</section>
			<?php
			} elseif ($status == 'notlogged') {
			?>
			<section class="alert alert-danger">
				<h2><?= $t['access_denied'] ?></h2>
				<ul>
					<li><?= $t['login_required'] ?></li>
				</ul>
			</section>
			<?php
			} elseif ($status == 'newrequestcomplete') {
			?>
			<section class="alert alert-success">
				<h2><?= $t['success'] ?></h2>
				<ul>
					<li><?= $t['new_request_success'] ?></li>
				</ul>
			</section>
			<?php
			} elseif ($status == 'fromoldsite') {
			?>
			<section class="alert alert-danger">
				<h2><?= $t['please_read'] ?></h2>
				<ul>
					<li><?= $t['old_site_redirect'] ?></li>
				</ul>
			</section>
			<?php
			} elseif ($status == 'batchsuccess') {
			?>
			<section class="alert alert-success">
				<h2><?= $t['success'] ?></h2>
				<ul>
					<li><?= $t['batch_success'] ?></li>
				</ul>
			</section>
			<?php
			} elseif ($status == 'dsuccess') {
			?>
			<section class="alert alert-success">
				<h2><?= $t['success'] ?></h2>
				<ul>
					<li><?= $t['delete_success'] ?></li>
				</ul>
			</section>
			<?php
			} elseif ($status == 'wrongid') {
			?>
			<section class="alert alert-danger">
				<h2><?= $t['access_denied'] ?></h2>
				<ul>
					<li><?= $t['wrong_id'] ?></li>
				</ul>
			</section>
			<?php
			}
			?>

			<?php
			// Construct SQL statement
			$sql = "SELECT * FROM tbltriage WHERE status = 1 AND (statusid=1 OR statusid=2 OR statusid=3 OR statusid=7 OR statusid=10 OR statusid='11' OR statusid='12') ORDER BY requestid DESC";
			//echo $sql;
			
	$result = mysqli_query($link, $sql);

	// Pre-fetch filter options for tag filter controls
	$nameField = $lang == 'fr' ? 'namefr' : 'nameen';
	$statusOptions = [];
	$statusOptResult = mysqli_query($link, "SELECT id, `$nameField` FROM tblstatus WHERE status = 1 ORDER BY id");
	while ($sr = mysqli_fetch_assoc($statusOptResult)) {
		$statusOptions[] = $sr;
	}
	$catalogueOptions = [];
	$catOptResult = mysqli_query($link, "SELECT id, `$nameField` FROM tblcatalogue WHERE status = 1 ORDER BY `$nameField`");
	while ($cr = mysqli_fetch_assoc($catOptResult)) {
		$catalogueOptions[] = $cr;
	}

			//List it
	if (mysqli_num_rows($result) > 0) {
			?>
		<section class="provisional wb-tagfilter wb-filter" data-wb-filter='{"selector": "[data-wb-tags]", "section": ".wb-tagfilter-items", "uiTemplate": "#rmt-search-filter"}'>
			<h2 class="wb-inv"><?= $t['filter_options'] ?></h2>
			<div id="rmt-search-filter" class="row">
				<div class="col-sm-12">
					<p class="wb-fltr-info mrgn-bttm-sm"><span data-nbitem></span> <?= $t['results_of'] ?> <span data-total></span></p>
				</div>
				<div class="col-md-4">
					<div class="form-group">
						<fieldset>
							<legend class="mrgn-bttm-0"><label for="status-filter" class="fnt-nrml"><?= $t['filter_status_label'] ?></label></legend>
							<select id="status-filter" name="status-filter" class="full-width wb-tagfilter-ctrl form-control">
								<option value=""><?= $t['filter_all'] ?></option>
								<?php foreach ($statusOptions as $so): ?>
									<option value="status-<?= $so['id'] ?>"><?= htmlspecialchars($so[$nameField]) ?></option>
								<?php endforeach; ?>
							</select>
						</fieldset>
					</div>
				</div>
				<div class="col-md-4">
					<div class="form-group">
						<fieldset>
							<legend class="mrgn-bttm-0"><label for="catalogue-filter" class="fnt-nrml"><?= $t['filter_catalogue_label'] ?></label></legend>
							<select id="catalogue-filter" name="catalogue-filter" class="full-width wb-tagfilter-ctrl form-control">
								<option value=""><?= $t['filter_all'] ?></option>
								<?php foreach ($catalogueOptions as $co): ?>
									<option value="cat-<?= $co['id'] ?>"><?= htmlspecialchars($co[$nameField]) ?></option>
								<?php endforeach; ?>
							</select>
						</fieldset>
					</div>
				</div>
				<div class="col-md-12">
					<div class="form-group">
						<fieldset>
							<legend class="mrgn-bttm-0"><span class="field-name"><?= $t['filter_priority_label'] ?></span></legend>
							<ul class="list-unstyled list-inline">
								<li class="checkbox"><label><input type="checkbox" name="priority-filter" class="wb-tagfilter-ctrl" value="sla-escalation"> <?= $t['escalation_required'] ?></label></li>
								<li class="checkbox"><label><input type="checkbox" name="priority-filter" class="wb-tagfilter-ctrl" value="sla-close"> <?= $t['close_to_sla'] ?></label></li>
							</ul>
						</fieldset>
					</div>
				</div>				<div class="col-md-12">
					<div class="form-group">
						<div class="input-group">
							<label for="rmt-search" class="input-group-addon"><?= $t['search_label'] ?></label>
							<input type="search" class="form-control" id="rmt-search">
						</div>
					</div>
				</div>			</div>
			<div class="row wb-eqht-grd wb-tagfilter-items">
				<?php
				while ($row = mysqli_fetch_array($result)) {
					// Check if clientlname or clientfname is not empty
					$clientfname = $row['clientfname'];
					$clientlname = $row['clientlname'];
					$clientname = "";
					if ($clientfname != "" and $clientlname != "") {
						$clientname = $clientlname . ", " . $clientfname;
					}					
					// We need to calculate if ticket is close to SLA (or on the date) or if past SLA and grab the names
					$subserviceid = $row['subserviceid'];
					$serviceid = $row['serviceid'];
					$catalogueid = $row['catalogueid'];
					$statusid = $row['statusid'];
					$subservicename = "";
					$servicename = "";
					$cataloguename = "";
					$tarraycontactid = "";
					
					$sla = 0;
					$dsla = 0;
					$doverdue = false;
					$closedue = false;
										
					// we may have to change this
					if (!empty($subserviceid) && $subserviceid !== 0 && $subserviceid !== 95 && $subserviceid !== 96) {
						// Sub-service is not empty so grab the name
						$nameField = $lang == 'fr' ? 'namefr' : 'nameen';
			$result_lookup = mysqli_query($link, "SELECT $nameField, sds, contactid FROM tblsubservices WHERE id = '$subserviceid'");
			$row_lookup = mysqli_fetch_array($result_lookup);
						if (!empty($row_lookup)) {
					$subservicename = $row_lookup[0];
					$sla = $row_lookup[1];
						$dsla = $sla * 2;
						$tarraycontactid = $row_lookup[2];
						}
					}
					
					if (!empty($serviceid)) {
						// Sub-service is not empty so grab the name
						$nameField = $lang == 'fr' ? 'namefr' : 'nameen';
			$result_lookup = mysqli_query($link, "SELECT $nameField, sds, contactid FROM tblservices WHERE id = '$serviceid'");
				$row_lookup = mysqli_fetch_array($result_lookup);
				$servicename = $row_lookup ? $row_lookup[0] : '';
						if ($sla == 0) {
							if ($serviceid == 21 || $serviceid == 22 || $serviceid == 23 || $serviceid == 24) {
						$sla = 15;
						$dsla = $sla * 2;
					} else {
						$sla = $row_lookup ? $row_lookup[1] : 0;
							$dsla = $sla * 2;
						}
					}
					if (empty($tarraycontactid)) {
					$tarraycontactid = $row_lookup ? $row_lookup[2] : 0;
			}
		}
		
		// Sub-service is not empty so grab the name
		$nameField = $lang == 'fr' ? 'namefr' : 'nameen';
		$result_lookup = mysqli_query($link, "SELECT $nameField FROM tblcatalogue WHERE id = '$catalogueid'");
		$row_lookup = mysqli_fetch_array($result_lookup);
				$cataloguename = $row_lookup ? $row_lookup[0] : '';

					// Grab the date it was received
					$slatimer = $row['slatimer'];
					if ($slatimer == "" or is_null($slatimer)) {
						$datereceived = $row['datereceived'];
					} else {
						$datereceived = $slatimer;
					}
					$ndatereceived = date('Y-m-d H:i:s', strtotime($datereceived . ' +1 day'));
					 
					// Calculate the business days
					//$cBdays = getWorkingDays($ndatereceived,date('Y-m-d'),$holidays);
					$cBdays = calculateSLA($link, $row['requestid'], $ndatereceived);

					// Check if ticket is close to SLA or past SLA
							if ($cBdays > $dsla) {
								$doverdue = true;
							}
					if ($cBdays >= $sla - 1) {
								$closedue = true;
							}
				?>
					<?php
					// Build tag string for this card
					$cardTags = 'status-' . $statusid . ' cat-' . $catalogueid;
					if ($doverdue) {
						$cardTags .= ' sla-escalation';
					} elseif ($closedue) {
						$cardTags .= ' sla-close';
					}

					// Determine SLA label and panel class
					if ($doverdue) {
						$panelClass = 'panel-danger';
						$slaLabel = $t['escalation_required'];
					} elseif ($closedue) {
						$panelClass = 'panel-warning';
						$slaLabel = $t['close_to_sla'];
					} else {
						$panelClass = 'panel-default';
						$slaLabel = '';
					}

					// Grab the status name
						$result2 = mysqli_query($link, "SELECT $nameField FROM tblstatus WHERE id = '$statusid'");
						$row2 = mysqli_fetch_array($result2);
						$statusname = $row2 ? $row2[0] : '';

					// Map status ID to Bootstrap label class
					$statusLabelClasses = [
						1  => 'label-primary',   // New
						2  => 'label-info',      // In Progress
						3  => 'label-warning',   // Pending
						7  => 'label-warning',   // On Hold (or similar)
						10 => 'label-default',   // Re-Audit / Review
						11 => 'label-default',
						12 => 'label-default',
					];
					$statusLabelClass = $statusLabelClasses[(int)$statusid] ?? 'label-default';

					// Assigned worker name
					$workerName = '';
							if (!empty($_SESSION['pid'])) {
								$workerid = $row['workerid'];
								if (!empty($workerid)) {
							$result2 = mysqli_query($link, "SELECT lastname, firstname FROM tblusers WHERE id = '$workerid'");
									$row2 = mysqli_fetch_array($result2);
							if (!empty($row2)) {
								$workerName = htmlspecialchars($row2[0] . ', ' . $row2[1]);
							}
						}
									}
							?>
					<?php
					ob_start();
					?>
					<dl>
						<?php if (!empty($clientname) && !empty($_SESSION['pid'])): ?>
							<dt><?= $t['client'] ?>:</dt>
							<dd><?= htmlspecialchars($clientname) ?></dd>
						<?php endif; ?>
						<dt><?= $t['request_service'] ?>:</dt>
						<dd><?= htmlspecialchars($cataloguename) ?><?php if (!empty($serviceid)): ?> / <?= htmlspecialchars($servicename) ?><?php endif; ?><?php if (!empty($subservicename)): ?> / <?= htmlspecialchars($subservicename) ?><?php endif; ?><?php if (!empty($row['bdm'])): ?> <span class="label label-default">BDM</span><?php endif; ?></dd>
						<?php if (!empty($row['nsd']) && !empty($_SESSION['pid']) && !in_array($row['nsd'], ['Yes I have', 'No I do not have', 'Oui j\'ai', 'Non je n\'ai pas'])): ?>
							<dt>NSD:</dt>
							<dd>
								<?php if (preg_match('/^[0-9]+$/', $row['nsd'])): ?>
									<a href="http://arweb.prv/SRMIS.htm?Ticket=<?= htmlspecialchars($row['nsd']) ?>"># NSD<?= htmlspecialchars($row['nsd']) ?></a>
								<?php else: ?>
									<a href="https://smartitesdc.service.gc.ca/smartit/app/#/search/<?= htmlspecialchars($row['nsd']) ?>"># Smart IT <?= htmlspecialchars($row['nsd']) ?></a>
								<?php endif; ?>
							</dd>
						<?php endif; ?>
						<dt><?= $t['submitted_date'] ?>:</dt>
						<dd><?= date('Y-m-d', strtotime($row['datereceived'])) ?></dd>
						<?php if (!empty($workerName)): ?>
							<dt><?= $t['assigned_to'] ?>:</dt>
							<dd><?= $workerName ?></dd>
						<?php endif; ?>
					</dl>
					<?php
					$cardBodyHtml = ob_get_clean();

					$cardFooterHtml = '';
					if ($_SESSION['atype'] == '1' || $_SESSION['atype'] == '2' || $_SESSION['atype'] == '3' || $_SESSION['atype'] == '4' || $_SESSION['atype'] == '6') {
						ob_start();
						?>
						<div class="row">
							<?php if ($_SESSION['atype'] == '1' || $_SESSION['atype'] == '3' || $_SESSION['atype'] == '4'): ?>
								<div class="col-xs-6">
									<a href="<?= $t['edit_request'] ?>?erid=<?= base64_encode($row['id']) ?>" class="btn btn-default btn-block"><span class="glyphicon glyphicon-pencil" aria-hidden="true"></span><span class="mrgn-lft-sm"><?= $t['edit'] ?></span></a>
								</div>
							<?php endif; ?>
							<?php if ($_SESSION['atype'] == '1'): ?>
								<div class="col-xs-6">
									<a href="includes/delete-request.php?id=<?= $row['id'] ?>" class="wb-lbx btn btn-default btn-block" title="<?= $t['delete_request_title'] ?>"><span class="glyphicon glyphicon-trash" aria-hidden="true"></span><span class="mrgn-lft-sm"><?= $t['delete_label'] ?></span></a>
								</div>
							<?php endif; ?>
						</div>
						<?php
						$cardFooterHtml = ob_get_clean();
					}

					$requestCard = [
						'tags' => $cardTags,
						'panelClass' => $panelClass,
						'requestUrl' => $t['view_request'] . '&erid=' . base64_encode($row['id']),
						'requestCode' => 'a11y-' . ($row['requestid'] ?? ''),
						'title' => !empty($row['title']) ? $row['title'] : $t['no_title'],
						'statusPrefix' => $t['status'],
						'statusText' => $statusname,
						'statusLabelClass' => $statusLabelClass,
						'slaLabel' => $slaLabel,
						'slaAlertClass' => $doverdue ? 'alert-danger' : 'alert-warning',
						'bodyHtml' => $cardBodyHtml,
						'footerHtml' => $cardFooterHtml,
					];
					include 'includes/template/request-card.php';
					?>
				<?php } ?>
			</div>
			<div class="wb-tagfilter-noresult">
				<p><?= $t['no_results_filter'] ?></p>
			</div>
		</section>
				
				<?php } else { ?>
				<p><strong><?= $t['no_requests'] ?></strong></p>
				<?php } ?>
				
<?php include 'includes/template/page-details.php'; ?>
		</main>
		
<?php include 'includes/template/footer.php';
include 'includes/template/scripts.php'; ?>
		</body>

	</html>
	<?php
	// Close connection
	mysqli_close($link);
	?>