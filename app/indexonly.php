<?php
/**
 * Consolidated Bilingual Dashboard - Active Requests
 * 
 * This page replaces the separate indexonly-en.php and indexonly-fr.php files
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
require('includes/sla-calculator.php');

// Grab HTTPS check
require('includes/httpscheck.php');

// Security check
if ($_SESSION['lang'] === 'fr') {
	require('includes/loggedincheck.php');
} else {
	require('includes/loggedincheck.php');
}

// Include file for calculating business days
require('includes/calculate-bdays.php');

// Determine database column for name fields
$nameColumn = ($_SESSION['lang'] === 'fr') ? 'namefr' : 'nameen';

// =============================================================================
// PAGE FRONTMATTER - Define page metadata
// =============================================================================
$page = [
	'title' => [
		'en' => 'My requests',
		'fr' => 'Mes demandes'
	],
	'description' => [
		'en' => 'View requests assigned to me',
		'fr' => 'Voir les demandes qui me sont assignées'
	]
];

// Store language code for templates (header.php needs $lang)
$lang = $_SESSION['lang'];

// Extract values for current language
$pageTitle = $page['title'][$lang];
$pageDescription = $page['description'][$lang];

// Include template head
include 'includes/template/head.php';
?>
	<?php include 'includes/template/header.php'; ?>
		<main role="main" property="mainContentOfPage" class="container">
			<h1 property="name" id="wb-cont"><?= htmlspecialchars($langFile['indexonly_heading']) ?></h1>
			<?php
			// Construct SQL statement
			$sql = "SELECT * FROM tbltriage WHERE status = '1' AND (statusid='1' OR statusid='3' OR statusid='5' OR statusid='6' OR statusid='7' OR statusid='10' OR statusid='11' OR statusid='12') ORDER BY requestid DESC";
			
			$result = mysqli_query($link,$sql);
			//List it
			if(mysqli_num_rows($result)>0){
			?>
			<table class="wb-tables table table-striped table-hover table-sm" data-wb-tables='{"paging": false, "columnDefs": [{ "type": "html-num", "targets": 0 }]}'>
				<thead>
				<tr>
					<th><?= htmlspecialchars($langFile['indexonly_col_request']) ?></th>
					<th><?= htmlspecialchars($langFile['indexonly_col_title']) ?></th>
					<th><?= htmlspecialchars($langFile['indexonly_col_client']) ?></th>
					<th><?= htmlspecialchars($langFile['indexonly_col_service']) ?></th>
					<th><?= htmlspecialchars($langFile['indexonly_col_status']) ?></th>
					<th><?= htmlspecialchars($langFile['indexonly_col_actions']) ?></th>
				</tr>
				</thead>
				<tbody>
				<?php
				while($row = mysqli_fetch_array($result)){
					// Check if clientlname or clientfname is not empty
					$clientfname = $row['clientfname'];
					$clientlname = $row['clientlname'];
					$clientname = "";
					if (!empty($clientfname) AND !empty($clientlname)) {
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
						$servicename = $row2[0];
						if ($sla==0) {
							if ($serviceid==21 || $serviceid==22 || $serviceid==23 || $serviceid==24) {
								$sla = 15;
								$dsla = $sla * 2;
							} else {
								$sla = $row2[1];
								$dsla = $sla * 2;
							}
						}
						if (empty($tarraycontactid)) {
							$tarraycontactid = $row2[2];
						}						
					}
					
					if (!empty($catalogueid)) {
						// Sub-service is not empty so grab the name
						$result2 = mysqli_query($link, "SELECT $nameColumn FROM tblcatalogue WHERE id = '$catalogueid'");
						$row2 = mysqli_fetch_array($result2);
						$cataloguename = $row2[0];
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
					$tarray = explode(",",$teams);
					if(in_array($tarraycontactid, $tarray) OR $userid == $row['workerid']) {
				?>
				<tr <?php if ($doverdue) { ?> style="background-color: #e87d88;"<?php } elseif($overdue) { ?> style="background-color: #f5c6cb;"<?php } elseif ($closedue) { ?> style="background-color: #ffeeba;"<?php } ?>>
					<td>
						<a href="viewrequest.php?lang=<?= $_SESSION['lang'] ?>&erid=<?php echo base64_encode($row['id']);?>">a11y-<?php echo $row['requestid'];?> <span class="glyphicon glyphicon-eye-open"></span><span class="wb-inv"><?= htmlspecialchars($langFile['indexonly_details']) ?></span></a>
					<?php if (!empty($row['nsd']) && !empty($_SESSION['pid']) && !in_array($row['nsd'], ['Yes I have', 'No I do not have', 'Oui j\'ai', 'Non je n\'ai pas'])) { ?>
							<br />
							<?php if(preg_match('/^[0-9]+$/', $row['nsd'])){?>
								<a href="http://arweb.prv/SRMIS.htm?Ticket=<?php echo $row['nsd'];?>" target="_blank"># NSD<?php echo $row['nsd'];?><span class="glyphicon glyphicon-new-window"></span><span class="wb-inv"><?= htmlspecialchars($langFile['indexonly_details_new_window']) ?></span></a>
							<?php }else{?>
								<a href="https://smartitesdc.service.gc.ca/smartit/app/#/search/<?php echo $row['nsd'];?>" target="_blank"># Smart IT <?php echo $row['nsd'];?><span class="glyphicon glyphicon-eye-open"></span><span class="wb-inv"><?= htmlspecialchars($langFile['indexonly_details_new_window']) ?></span></a>
							<?php } ?>
							<?php }?>
					</td>
					<td><?php echo htmlspecialchars($row['title']);?></td>
					<td><?php echo $clientname;?></td>
					<td><?php echo $cataloguename; ?><?php echo " / " . $servicename; ?><?php if (!empty($subservicename)) { echo " / " . $subservicename; } ?><?php if (!empty($row['bdm'])) { ?> - <span class="badge">BDM <span class="glyphicon glyphicon-tag"></span></span><?php } ?></td>					
					<td>
					<?php 
					// Grab the status id
					$statusid = $row['statusid'];
					$result2 = mysqli_query($link, "SELECT $nameColumn FROM tblstatus WHERE id = '$statusid'");
					$row2 = mysqli_fetch_array($result2);
					$statusname = $row2[0];
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
								$ulastname = $row2[0];
								$ufirstname = $row2[1];
						?>
						<br /><?php echo $ulastname ?>, <?php echo $ufirstname ?>
						<?php
							}
						}
						?>
						<?php if ($doverdue) { ?><br /><span class="glyphicon glyphicon-warning-sign"></span> <?= htmlspecialchars($langFile['indexonly_escalation_required']) ?><?php } elseif ($overdue) { ?><br /><span class="glyphicon glyphicon-warning-sign"></span> <?= htmlspecialchars($langFile['indexonly_request_past_sla']) ?><?php } elseif ($closedue) { ?><br /><span class="glyphicon glyphicon-warning-sign"></span> <?= htmlspecialchars($langFile['indexonly_request_close_sla']) ?><?php } ?>
					</td>
					<td>
						<a class="btn btn-primary btn-block" href="editrequest.php?erid=<?php echo base64_encode($row['id']);?>"><?= htmlspecialchars($langFile['indexonly_edit']) ?> &nbsp;<span class="wb-inv"> a11y-<?php echo $row['requestid'];?> <?= htmlspecialchars($langFile['indexonly_request']) ?></span></a><?php if ($_SESSION['atype']==1) { ?> <a class="wb-lbx btn btn-primary btn-block" href="includes/delete-request.php?id=<?php echo $row['id'];?>"><?= htmlspecialchars($langFile['indexonly_delete']) ?>&nbsp;<span class="wb-inv"> a11y-<?php echo $row['requestid'];?> <?= htmlspecialchars($langFile['indexonly_request']) ?></span></a><?php } ?>
						<?php if(in_array('1', $_SESSION['team'])){?><a class="btn btn-primary btn-block" href="clonerequest.php?lang=<?= $_SESSION['lang'] ?>&erid=<?php echo base64_encode($row['id']);?>&toClose=2"><?= htmlspecialchars($langFile['indexonly_clone']) ?> &nbsp;<span class="wb-inv"> a11y-<?php echo $row['requestid'];?> <?= htmlspecialchars($langFile['indexonly_request']) ?></span></a>
						<a class="btn btn-primary btn-block" href="clonerequest.php?lang=<?= $_SESSION['lang'] ?>&erid=<?php echo base64_encode($row['id']);?>&toClose=1"><?= htmlspecialchars($langFile['indexonly_clone_close']) ?> &nbsp;<span class="wb-inv"> a11y-<?php echo $row['requestid'];?> <?= htmlspecialchars($langFile['indexonly_request']) ?></span></a><?php } ?>
					</td>
				</tr>
			<?php
					}
				} ?>
				</tbody>
			</table>
			
			<?php } else { ?>
			<p><strong><?= htmlspecialchars($langFile['indexonly_no_requests']) ?></strong></p>
			<?php } ?>
			
			<?php include 'includes/template/page-details.php'; ?>
		</main>
		
		<?php include 'includes/template/footer.php'; include 'includes/template/scripts.php'; ?>
	</body>
</html>
<?php
// Close connection
mysqli_close($link);
