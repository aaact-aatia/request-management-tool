<?php
// Grab HTTPS check
require('includes/httpscheck.php');

// Include file for calculating business days
require('includes/calculate-bdays.php');
require('includes/sla-calculator.php');
// Grab MySQL connection
require('sql.php');

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
		'actions' => 'Actions',
		'no_requests' => 'No requests available!',
		'escalation_required' => 'Escalation required',
		'past_sla' => 'Request is past SLA',
		'close_to_sla' => 'Request is close to SLA',
		'urgent_review' => 'Urgent review required',
		'view_request' => 'viewrequest.php?lang=en',
		'edit_request' => 'editrequest.php',
		'delete_request' => 'includes/delete-request.php'
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
		'actions' => 'Actions',
		'no_requests' => 'Aucune demande disponible!',
		'escalation_required' => 'Escalade requise',
		'past_sla' => 'La demande a dépassé le NdS',
		'close_to_sla' => 'La demande approche du NdS',
		'urgent_review' => 'Examen urgent requis',
		'view_request' => 'viewrequest.php?lang=fr',
		'edit_request' => 'editrequest-fr.php',
		'delete_request' => 'includes/delete-request.php'
	]
];

$t = $translations[$lang];

// Check if there is a status
if (!empty($_GET['status'])){
	$status = $_GET['status'];
}
else{
	$status = "";
}
if (function_exists("curl_init")){
	$message = "Yes Curl";
}
else{
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
			
			$result = mysqli_query($link,$sql);
			//List it
			if(mysqli_num_rows($result)>0){
			?>
			<table class="wb-tables table table-striped table-hover table-sm" data-wb-tables='{"paging": false, "columnDefs": [{ "type": "html-num", "targets": 0 }]}'>
				<thead>
				<tr>
					<th><?= $t['request_num'] ?></th>
					<th><?= $t['title'] ?></th>
					<?php if(!empty($_SESSION['pid'])){ ?>
					<th><?= $t['client'] ?></th>
					<?php } ?>
					<th><?= $t['request_service'] ?></th>
					<th><?= $t['status'] ?></th>
					<?php
					// Check if the account is admin level to show this option 
					if ($_SESSION['atype']=='1' OR $_SESSION['atype']=='2' OR $_SESSION['atype']=='3' OR $_SESSION['atype']=='4' OR $_SESSION['atype']=='6') {
					?>
					<th><?= $t['actions'] ?></th>
					<?php } else {
						?>
					
						<?php
					} ?>
				</tr>
				</thead>
				<tbody>
				<?php
				while($row = mysqli_fetch_array($result)){
					// Check if clientlname or clientfname is not empty
					$clientfname = $row['clientfname'];
					$clientlname = $row['clientlname'];
					$clientname = "";
					if ($clientfname!="" AND $clientlname!="") {
						$clientname = $clientlname . ", " . $clientfname;
					}					
					// We need to calculate if ticket is close to SLA (or on the date) or if past SLA and grab the names
					$subserviceid = $row['subserviceid'];
					$serviceid = $row['serviceid'];
					$catalogueid = $row['catalogueid'];
					$statusid = $row['statusid'];
					$subservicename = "";
					$servicename= "";
					$cataloguename = "";
					$tarraycontactid = "";
					
					$sla = 0;
					$dsla = 0;
					$overdue = false;
					$doverdue = false;
					$closedue = false;
					$uReview = false;
										
					// we may have to change this
					if (!empty($subserviceid) && $subserviceid !== 0 && $subserviceid !== 95 && $subserviceid !== 96) {
						// Sub-service is not empty so grab the name
						$nameField = $lang == 'fr' ? 'namefr' : 'nameen';
			$result_lookup = mysqli_query($link, "SELECT $nameField, sds, contactid FROM tblsubservices WHERE id = '$subserviceid'");
			$row_lookup = mysqli_fetch_array($result_lookup);
				if (!empty($row_lookup)) 
				{
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
				if ($sla==0) {
					if ($serviceid==21 || $serviceid==22 || $serviceid==23 || $serviceid==24) {
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

				if ($statusid==10) { 
						$uReview = true;
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
					// Now check if the SLA is close but ignore uReview
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
					<tr <?php if ($doverdue) { ?> style="background-color: #e87d88;"<?php } elseif($overdue) { ?> style="background-color: #f5c6cb;"<?php } elseif ($closedue) { ?> style="background-color: #ffeeba;"<?php } elseif ($uReview) { ?> style="background-color: #D7FAFF;"<?php } ?>>
					<td>
							<a href="<?= $t['view_request'] ?>&erid=<?php echo base64_encode($row['id']);?>">a11y-<?php echo $row['requestid'];?> <span class="glyphicon glyphicon-eye-open"></span><span class="wb-inv">details</span></a>
						<?php if (!empty($row['nsd']) && !empty($_SESSION['pid']) && !in_array($row['nsd'], ['Yes I have', 'No I do not have', 'Oui j\'ai', 'Non je n\'ai pas'])) { ?>
								<br />
								<?php if(preg_match('/^[0-9]+$/', $row['nsd'])){?>
									<a href="http://arweb.prv/SRMIS.htm?Ticket=<?php echo $row['nsd'];?>" target="_blank"># NSD<?php echo $row['nsd'];?><span class="glyphicon glyphicon-new-window"></span><span class="wb-inv">details (will open in a new window)</span></a>
								<?php }else{?>
									<a href="https://smartitesdc.service.gc.ca/smartit/app/#/search/<?php echo $row['nsd'];?>" target="_blank"># Smart IT <?php echo $row['nsd'];?><span class="glyphicon glyphicon-eye-open"></span><span class="wb-inv">details (will open in a new window)</span></a>
								<?php } ?>
								<?php }?>
						</td>					
					<td><?php echo htmlspecialchars($row['title'] ?? '');?></td>
						<?php if(!empty($_SESSION['pid'])){ ?><td><?php echo $clientname;?></td><?php } ?>
						<td><?php echo $cataloguename; ?><?php if (!empty($serviceid)) { echo " / " . $servicename; } ?><?php if ($uReview==false) { if (!empty($subservicename)) { echo " / " . $subservicename; } } ?><?php if (!empty($row['bdm'])) { ?> - <span class="badge">BDM <span class="glyphicon glyphicon-tag"></span></span><?php } ?></td>					
						<td>
						<?php
						// Grab the status id
						$nameField = $lang == 'fr' ? 'namefr' : 'nameen';
						$result2 = mysqli_query($link, "SELECT $nameField FROM tblstatus WHERE id = '$statusid'");
						$row2 = mysqli_fetch_array($result2);
						$statusname = $row2 ? $row2[0] : '';
						?>
							<?php echo $statusname; ?>
							<?php
							// Check if a user is logged in 
							if (!empty($_SESSION['pid'])) {
								$workerid = $row['workerid'];
								if (!empty($workerid)) {
									// Check the name
									$result2 = mysqli_query($link, "SELECT lastname,firstname FROM tblusers WHERE id = '$workerid'");
									$row2 = mysqli_fetch_array($result2);
									if (!empty($row2)){
										$ulastname = $row2[0];
										$ufirstname = $row2[1];
									}
							?>
							<br /><?php echo $ulastname ?>, <?php echo $ufirstname ?>
							<?php
								}
							}
							?>
							<?php if ($doverdue) { ?><br /><span class="glyphicon glyphicon-warning-sign"></span> <?= $t['escalation_required'] ?><?php } elseif ($overdue) { ?><br /><span class="glyphicon glyphicon-warning-sign"></span> <?= $t['past_sla'] ?><?php } elseif ($closedue) { ?><br /><span class="glyphicon glyphicon-warning-sign"></span> <?= $t['close_to_sla'] ?><?php } elseif ($uReview) { ?><br /><span class="glyphicon glyphicon-transfer"></span> <?= $t['urgent_review'] ?><?php } ?>
						</td>
						<?php 
						// Check if the account is admin level to show this option 
						if ($_SESSION['atype']=='1' OR $_SESSION['atype']=='2' OR $_SESSION['atype']=='3' OR $_SESSION['atype']=='4' OR $_SESSION['atype']=='6') {
						?>
						<td>
						<?php
						if ($_SESSION['atype']=='1' OR $_SESSION['atype']=='3' OR $_SESSION['atype']=='4') {
						?>
						<a href="<?= $t['edit_request'] ?>?erid=<?php echo base64_encode($row['id']);?>" class="btn btn-default btn-sm"><span class="glyphicon glyphicon-pencil"></span><span class="wb-inv">edit</span></a>
						<?php } else { 
							echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
						} ?>
						<?php
						if ($_SESSION['atype']=='1') {
						?>
						<a href="includes/delete-request.php?id=<?php echo $row['id'];?>" class="wb-lbx btn btn-default btn-sm" title="Delete this request"><span class="glyphicon glyphicon-trash"></span><span class="wb-inv">delete</span></a>
						<?php } ?>
						</td>
						<?php 
						} 
						?>
					</tr>
				<?php } ?>
					</tbody>
				</table>
				
				<?php } else { ?>
				<p><strong><?= $t['no_requests'] ?></strong></p>
				<?php } ?>
				
<?php include 'includes/template/page-details.php'; ?>
		</main>
		
		<?php include 'includes/template/footer.php'; include 'includes/template/scripts.php'; ?>
		</body>
	</html>
	<?php
	// Close connection
	mysqli_close($link);
	?>
