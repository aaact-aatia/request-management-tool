<?php
/**
 * Consolidated Bilingual Dashboard - Resolved Requests
 * 
 * This page replaces the separate indexresolved-en.php and indexresolved-fr.php files
 * by using a language file system. The language is determined by $_SESSION['lang'].
 * 
 * @package RMT
 * @since 2.0.0
 */

// Grab MySQL connection (includes session management)
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
$langFile = require("lang/{$_SESSION['lang']}.php");

// Grab HTTPS check
require('includes/httpscheck.php');

// Security check
if ($_SESSION['lang'] === 'fr') {
	require('includes/loggedincheck.php');
} else {
	require('includes/loggedincheck.php');
}

require('includes/sla-calculator.php');

// Include file for calculating business days
require('includes/calculate-bdays.php');

// Determine database column for name fields
$nameColumn = ($_SESSION['lang'] === 'fr') ? 'namefr' : 'nameen';

// =============================================================================
// PAGE FRONTMATTER - Define page metadata
// =============================================================================
$page = [
	'title' => [
		'en' => 'Resolved requests',
		'fr' => 'Demandes résolues'
	],
	'description' => [
		'en' => 'View resolved accessibility requests',
		'fr' => 'Voir les demandes d\'accessibilité résolues'
	]
];

// Store language code for templates (header.php needs $lang)
$lang = $_SESSION['lang'];

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
			<h1 property="name" id="wb-cont"><?= htmlspecialchars($langFile['indexresolved_heading']) ?></h1>
			<?php
			// Grab all the resolved Requests from the Triage table with a limit
			// Status ID 4 = "Resolved" in both English and French
			$statusFilter = '4';
			$limit = 1000;
			$sql = "SELECT * FROM tbltriage WHERE status = '1' AND statusid = '$statusFilter' ORDER BY requestid DESC LIMIT $limit";
			
			$result = mysqli_query($link,$sql);
			
			//List it
			if(mysqli_num_rows($result)>0){
			?>
			<section class="provisional wb-tagfilter wb-filter" data-wb-filter='{"selector": "[data-wb-tags]", "section": ".wb-tagfilter-items", "uiTemplate": "#indexresolved-filter-ui"}'>
				<h2 class="wb-inv"><?= ($_SESSION['lang'] === 'fr') ? 'Options de filtrage' : 'Filter options' ?></h2>
				<div id="indexresolved-filter-ui" class="row">
					<div class="col-md-12">
						<div class="form-group">
							<div class="input-group">
								<label for="indexresolved-search" class="input-group-addon"><?= ($_SESSION['lang'] === 'fr') ? 'Filtrer' : 'Filter' ?></label>
								<input type="search" class="form-control" id="indexresolved-search">
							</div>
						</div>
					</div>
				</div>
				<div class="row wb-eqht-grd wb-tagfilter-items">
				<?php
				$hasVisibleRows = false;
				while($row = mysqli_fetch_array($result)){
					// Check if clientlname or clientfname is not empty
					$clientfname = $row['clientfname'];
					$clientlname = $row['clientlname'];
					$statusid = $row['statusid'];
					$clientname = "";
					$subservicename = "";
					$servicename = "";
					$cataloguename = "";
					if (!empty($clientfname) AND !empty($clientlname)) {
						$clientname = $clientlname . ", " . $clientfname;
					}					
					// We need to calculate if ticket is close to SLA (or on the date) or if past SLA and grab the names
					$subserviceid = $row['subserviceid'];
					$serviceid = $row['serviceid'];
					$catalogueid = $row['catalogueid'];
					$tarraycontactid = "";
					
					$sla = 0;
					$dsla = 0;
					$overdue = false;
					$doverdue = false;
					$closedue = false;
										
					if (!empty($subserviceid)) {
						// Sub-service is not empty so grab the name
						$result2 = mysqli_query($link, "SELECT $nameColumn,sds,contactid FROM tblsubservices WHERE id = '$subserviceid'");
						$row2 = mysqli_fetch_array($result2);
						if (!empty($row2)) 
						{
							$subservicename = $row2[0];
							$sla = $row2[1];
							$dsla = $sla * 2;
							$tarraycontactid = $row2[2];
						}
					}
					
					if (!empty($serviceid)) {
						// Sub-service is not empty so grab the name
						$result2 = mysqli_query($link, "SELECT $nameColumn,sds,contactid FROM tblservices WHERE id = '$serviceid'");
						$row2 = mysqli_fetch_array($result2);
						
						if (empty($sla) and !empty($row2)) {
							$servicename = $row2[0];
							$sla = $row2[1];
							$dsla = $sla * 2;
						}
						if (empty($tarraycontactid) and !empty($row2)) {
							$servicename = $row2[0];
							$tarraycontactid = $row2[2];
						}
					}

					if (empty($servicename)){
						$servicename = "";
					}

					if (empty($tarraycontactid)){
						$tarraycontactid = "";
					}
					
					if (!empty($catalogueid)) {
						// Sub-service is not empty so grab the name
						$result2 = mysqli_query($link, "SELECT $nameColumn FROM tblcatalogue WHERE id = '$catalogueid'");
						$row2 = mysqli_fetch_array($result2);
						$cataloguename = $row2[0];
					}
					
					// Grab the date it was received
					$slatimer = $row['slatimer'];
					if (empty($slatimer)) {
						$datereceived = $row['datereceived'];
					} else {
						$datereceived = $slatimer;
					}
					$ndatereceived = date('Y-m-d H:i:s', strtotime($datereceived . ' +1 day'));
					 
					// Calculate the business days
					$cBdays = calculateSLA($link, $row['requestid'], $ndatereceived);

					$sla2 = $sla - 1;
					// Now check if the SLA is close
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
					
					// Now we need to check if this should be displayed
					$userid = $_SESSION['pid'];
					$result2 = mysqli_query($link, "SELECT team FROM tblusers WHERE id = '$userid'");
					$row2 = mysqli_fetch_array($result2);
					if (!empty($row2)){
						$teams = $row2[0];
					}
					if (empty($teams)){
						$teams = '';
					}
					$tarray = explode(",",$teams);
					
					// Admins see everything, other users only see requests for their teams
					$showRequest = ($_SESSION['atype'] == '1') || in_array($tarraycontactid, $tarray);
					
					if($showRequest) {
						$hasVisibleRows = true;
						$suppressSlaWarning = in_array((int)$statusid, [4, 5, 6], true);

						$cardTags = 'status-' . (int)$statusFilter;
						if (!$suppressSlaWarning && ($doverdue || $overdue)) {
							$cardTags .= ' sla-escalation';
						} elseif (!$suppressSlaWarning && $closedue) {
							$cardTags .= ' sla-close';
						}

						if (!$suppressSlaWarning && ($doverdue || $overdue)) {
							$panelClass = 'panel-danger';
							$slaLabel = ($_SESSION['lang'] === 'fr') ? 'Escalade requise' : 'Escalation required';
						} elseif (!$suppressSlaWarning && $closedue) {
							$panelClass = 'panel-warning';
							$slaLabel = ($_SESSION['lang'] === 'fr') ? 'Demande proche du SLA' : 'Request is close to SLA';
						} else {
							$panelClass = 'panel-default';
							$slaLabel = '';
						}

						$statusLabelClass = 'label-success';

						$result2 = mysqli_query($link, "SELECT $nameColumn FROM tblstatus WHERE id = '$statusid'");
						$row2 = mysqli_fetch_array($result2);
						$statusname = $row2[0] ?? '';

						$workerName = '';
						if (!empty($row['workerid'])) {
							$result2 = mysqli_query($link, "SELECT lastname,firstname FROM tblusers WHERE id = '" . $row['workerid'] . "'");
							$row2 = mysqli_fetch_array($result2);
							if (!empty($row2)) {
								$workerName = htmlspecialchars($row2[0] . ', ' . $row2[1]);
							}
						}

						ob_start();
						?>
						<dl>
							<dt><?= htmlspecialchars($langFile['indexresolved_col_client']) ?>:</dt>
							<dd><?= htmlspecialchars($clientname) ?></dd>
							<dt><?= htmlspecialchars($langFile['indexresolved_col_service']) ?>:</dt>
							<dd><?= htmlspecialchars($cataloguename) ?> / <?= htmlspecialchars($servicename) ?><?php if (!empty($subservicename)) { echo ' / ' . htmlspecialchars($subservicename); } ?><?php if (!empty($row['bdm'])) { ?> <span class="label label-default">BDM</span><?php } ?></dd>
							<?php if (!empty($row['nsd']) && !in_array($row['nsd'], ['Yes I have', 'No I do not have', 'Oui j\'ai', 'Non je n\'ai pas'])): ?>
								<dt>NSD:</dt>
								<dd>
									<?php if(preg_match('/^[0-9]+$/', $row['nsd'])): ?>
										<a href="http://arweb.prv/SRMIS.htm?Ticket=<?= htmlspecialchars($row['nsd']) ?>"># NSD<?= htmlspecialchars($row['nsd']) ?></a>
									<?php else: ?>
										<a href="https://smartitesdc.service.gc.ca/smartit/app/#/search/<?= htmlspecialchars($row['nsd']) ?>"># Smart IT <?= htmlspecialchars($row['nsd']) ?></a>
									<?php endif; ?>
								</dd>
							<?php endif; ?>
							<dt><?= ($_SESSION['lang'] === 'fr') ? 'Date de soumission' : 'Submitted date' ?>:</dt>
							<dd><?= date('Y-m-d', strtotime($row['datereceived'])) ?></dd>
							<?php if (!empty($workerName)): ?>
								<dt><?= ($_SESSION['lang'] === 'fr') ? 'Attribue a' : 'Assigned to' ?>:</dt>
								<dd><?= $workerName ?></dd>
							<?php endif; ?>
						</dl>
						<?php
						$cardBodyHtml = ob_get_clean();

						ob_start();
						?>
						<a class="btn btn-primary btn-block" href="editrequest.php?erid=<?= base64_encode($row['id']) ?>"><?= htmlspecialchars($langFile['indexresolved_edit']) ?> <span class="wb-inv">a11y-<?= htmlspecialchars($row['requestid']) ?> <?= htmlspecialchars($langFile['indexresolved_request']) ?></span></a>
						<?php if ($_SESSION['atype']==1) { ?>
							<a class="wb-lbx btn btn-primary btn-block" href="includes/delete-request.php?id=<?= $row['id'] ?>"><?= htmlspecialchars($langFile['indexresolved_delete']) ?><span class="wb-inv"> a11y-<?= htmlspecialchars($row['requestid']) ?> <?= htmlspecialchars($langFile['indexresolved_request']) ?></span></a>
						<?php } ?>
						<?php if(in_array('1', $_SESSION['team'])){?>
							<a class="btn btn-primary btn-block" href="clonerequest.php?lang=<?= $_SESSION['lang'] ?>&erid=<?= base64_encode($row['id']) ?>&toClose=2"><?= htmlspecialchars($langFile['indexresolved_clone']) ?> <span class="wb-inv">a11y-<?= htmlspecialchars($row['requestid']) ?> <?= htmlspecialchars($langFile['indexresolved_request']) ?></span></a>
						<?php } ?>
						<?php
						$cardFooterHtml = ob_get_clean();

						$requestCard = [
							'tags' => $cardTags,
							'panelClass' => $panelClass,
							'requestUrl' => 'viewrequest.php?lang=' . $_SESSION['lang'] . '&erid=' . base64_encode($row['id']),
							'requestCode' => 'a11y-' . ($row['requestid'] ?? ''),
							'title' => !empty($row['title']) ? $row['title'] : '[No title entered]',
							'statusPrefix' => $langFile['indexresolved_col_status'],
							'statusText' => $statusname,
							'statusLabelClass' => $statusLabelClass,
							'slaLabel' => $slaLabel,
							'slaAlertClass' => ($panelClass === 'panel-danger') ? 'alert-danger' : 'alert-warning',
							'bodyHtml' => $cardBodyHtml,
							'footerHtml' => $cardFooterHtml,
						];
						include 'includes/template/request-card.php';
						?>
			<?php
					}
				}
				?>
				</div>
				<?php if ($hasVisibleRows): ?>
				<div class="wb-tagfilter-noresult">
					<p><?= ($_SESSION['lang'] === 'fr') ? 'Aucune demande ne correspond au filtre selectionne.' : 'No requests match the selected filter.' ?></p>
				</div>
				<?php else: ?>
				<p><strong><?= htmlspecialchars($langFile['indexresolved_no_requests']) ?></strong></p>
				<?php endif; ?>
			</section>
			
			<?php } else { ?>
			<p><strong><?= htmlspecialchars($langFile['indexresolved_no_requests']) ?></strong></p>
			<?php } ?>
			
			<?php include 'includes/template/page-details.php'; ?>
		</main>
		
		<?php include 'includes/template/footer.php'; include 'includes/template/scripts.php'; ?>
	</body>
</html>
<?php
// Close connection
mysqli_close($link);
