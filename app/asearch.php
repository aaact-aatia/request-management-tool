<?php
/**
 * Consolidated Bilingual Advanced Search Page
 * 
 * This page replaces the separate asearch-en.php and asearch-fr.php files
 * by using a language file system. The language is determined by $_SESSION['lang'].
 * 
 * @package RMT
 * @since 2.0.0
 */

// Start session
require_once __DIR__ . '/includes/session_start.php';
require('includes/sla-calculator.php');

// Grab HTTPS check
require('includes/httpscheck.php');

// Include file for calculating business days
require('includes/calculate-bdays.php');

require('includes/helpers.php');

// Grab MySQL connection
require('sql.php');
/** @var mysqli $link */

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

// Set variable to show form or results
$showform = true;

if (!empty($_POST['clientlname'] ))
{
	$clientlname = mysqli_real_escape_string($link,strtolower($_POST['clientlname']));
}
else
{
	$clientlname = "";
}

if (!empty($_POST['clientfname'] ))
{
	$clientfname = mysqli_real_escape_string($link,strtolower($_POST['clientfname']));
}
else
{
	$clientfname = "";
}

if (!empty($_POST['clientemail'] ))
{
	$clientemail = mysqli_real_escape_string($link,strtolower($_POST['clientemail']));
}
else
{
	$clientemail = "";
}

if (!empty($_POST['clientphone'] ))
{
	$clientphone = mysqli_real_escape_string($link,$_POST['clientphone']);
}
else
{
	$clientphone = "";
}

if (!empty($_POST['serviceid'] ))
{
	$serviceid = mysqli_real_escape_string($link,$_POST['serviceid']);
}
else
{
	$serviceid = "";
}

if (!empty($_POST['subserviceid'] ))
{
	$subserviceid = mysqli_real_escape_string($link,$_POST['subserviceid']);
}
else
{
	$subserviceid = "";
}

// Check if there is a status
if (!empty($_GET['status'])){
	$status = $_GET['status'];
}
else{
	$status = "";
}

// Process the add product form
if ($_SERVER['REQUEST_METHOD']=='POST'){
	// Set no search value
	$showform = false;
	$nosearch = true;
	$SQLSV = "";
	
	// Grab form elements	
	if (!empty($_POST['requestid'])) {
		$requestid = mysqli_real_escape_string($link,$_POST['requestid']);
		$nosearch = false;
		$SQLSV .= " requestid = '$requestid' AND";
	}
	
	if (!empty($_POST['requesttitle'])) {
		$requesttitle = mysqli_real_escape_string($link,strtolower($_POST['requesttitle']));
		$nosearch = false;
		$SQLSV .= " LOWER(title) LIKE '%$requesttitle%' AND";
	}

	if (!empty($clientlname)) {
		$nosearch = false;
		$SQLSV .= " LOWER(clientlname) LIKE '%$clientlname%' AND";
	}
	
	if (!empty($clientfname)) {
		$nosearch = false;
		$SQLSV .= " LOWER(clientfname) LIKE '%$clientfname%' AND";
	}
	
	if (!empty($clientemail)) {
		$nosearch = false;
		$SQLSV .= " LOWER(clientemail) LIKE '%$clientemail%' AND";
	}
	
	if (!empty($clientphone)) {
		$nosearch = false;
		$SQLSV .= " clientphone LIKE '%$clientphone%' AND";
	}
	
	if (!empty($_POST['sourceid'])) {
		$sourceid = mysqli_real_escape_string($link,$_POST['sourceid']);
		$nosearch = false;
		$SQLSV .= " sourceid = '$sourceid' AND";
	}
	$datereceived = mysqli_real_escape_string($link,$_POST['datereceived']);
	$datereceived2 = mysqli_real_escape_string($link,$_POST['datereceived2']);
	if ($datereceived!="" && $datereceived2!="") {
		$nosearch = false;
		$SQLSV .= " (datereceived BETWEEN '$datereceived' AND '$datereceived2') AND";
	} elseif ($datereceived!="") {
		$nosearch = false;
		$SQLSV .= " datereceived = '$datereceived' AND";
	} elseif ($datereceived2!="") {
		$nosearch = false;
		$SQLSV .= " datereceived = '$datereceived2' AND";
	}
	$dateupdated = mysqli_real_escape_string($link,$_POST['dateupdated']);
	$dateupdated2 = mysqli_real_escape_string($link,$_POST['dateupdated2']);
	if ($dateupdated!="" && $dateupdated2!="") {
		$nosearch = false;
		$SQLSV .= " (dateupdated BETWEEN '$dateupdated' AND '$dateupdated2') AND";
	} elseif ($dateupdated!="") {
		$nosearch = false;
		$SQLSV .= " dateupdated = '$dateupdated' AND";
	} elseif ($dateupdated2!="") {
		$nosearch = false;
		$SQLSV .= " dateupdated = '$dateupdated2' AND";
	}
	$daterequired = mysqli_real_escape_string($link,$_POST['daterequired']);
	$daterequired2 = mysqli_real_escape_string($link,$_POST['daterequired2']);
	if ($daterequired!="" && $daterequired2!="") {
		$nosearch = false;
		$SQLSV .= " (daterequired BETWEEN '$daterequired' AND '$daterequired2') AND";
	} elseif ($daterequired!="") {
		$nosearch = false;
		$SQLSV .= " daterequired = '$daterequired' AND";
	} elseif ($daterequired2!="") {
		$nosearch = false;
		$SQLSV .= " daterequired = '$daterequired2' AND";
	}
	$dateresolved = mysqli_real_escape_string($link,$_POST['dateresolved']);
	$dateresolved2 = mysqli_real_escape_string($link,$_POST['dateresolved2']);
	if ($dateresolved!="" && $dateresolved2!="") {
		$nosearch = false;
		$SQLSV .= " (dateresolved BETWEEN '$dateresolved' AND '$dateresolved2') AND";
	} elseif ($dateresolved!="") {
		$nosearch = false;
		$SQLSV .= " dateresolved = '$dateresolved' AND";
	} elseif ($dateresolved2!="") {
		$nosearch = false;
		$SQLSV .= " dateresolved = '$dateresolved2' AND";
	}
	$statusid = mysqli_real_escape_string($link,$_POST['statusid']);
	if ($statusid!="") {
		$nosearch = false;
		$SQLSV .= " statusid = '$statusid' AND";
	}
	$catalogueid = mysqli_real_escape_string($link,$_POST['catalogueid']);
	if ($catalogueid!="") {
		$nosearch = false;
		$SQLSV .= " catalogueid = '$catalogueid' AND";
	}
	
	if (!empty($serviceid)) {
		$nosearch = false;
		$SQLSV .= " serviceid = '$serviceid' AND";
	}
	
	if (!empty($subserviceid)) {
		$nosearch = false;
		$SQLSV .= " subserviceid = '$subserviceid' AND";
	}
	
	// Now trim the last AND
	$last_space_position = strrpos($SQLSV, ' ');
	$SQLSV = substr($SQLSV, 0, $last_space_position);

	// Create SQL statement
	if ($nosearch) {
		$sql = "SELECT * FROM tbltriage WHERE status = '1' ORDER BY requestid DESC LIMIT 1000";
	} else {
		$sql = "SELECT * FROM tbltriage WHERE$SQLSV AND status = '1' ORDER BY requestid DESC";
	}
	
	//echo $sql;
	//exit();
}

// =============================================================================
// PAGE FRONTMATTER - Define page metadata
// =============================================================================
$page = [
	'title' => [
		'en' => 'Search requests',
		'fr' => 'Recherche d\'une demande'
	],
	'description' => [
		'en' => 'Advanced search for accessibility requests',
		'fr' => 'Recherche avancée de demandes d\'accessibilité'
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
			<?php 
			// Check if this is a search
			if ($showform) {
			?>
			<h1 property="name" id="wb-cont"><?= htmlspecialchars($langFile['asearch_heading']) ?></h1>
			<?php
			if ($status=='noresults') {
			?>
			<section class="alert alert-danger">
				<h2><?= htmlspecialchars($langFile['asearch_no_results_heading']) ?></h2>
				<ul>
					<li><?= htmlspecialchars($langFile['asearch_no_results_message']) ?></li>
				</ul>
			</section>
			<?php
			}
			?>
		
			<form method="post" action="/asearch.php?lang=<?= $_SESSION['lang'] ?>">
			<div class="row">
				<div class="col-xs-6">
					<div class="form-group">
						<label for="requestid"><span class="field-name"><?= htmlspecialchars($langFile['asearch_request_id']) ?></span></label>
						<input type="text" class="form-control" id="requestid" name="requestid" value="">
					</div>
				</div>
				<div class="col-xs-6">
					<div class="form-group">
						<label for="requesttitle"><span class="field-name"><?= htmlspecialchars($langFile['request_title']) ?></span></label>
						<input type="text" class="form-control" id="requesttitle" name="requesttitle" value="">
					</div>
				</div>
			</div>
			<?php if($_SESSION['pid']!=""){ ?>
			<div class="row">
				<div class="col-xs-6">				
					<div class="form-group">
						<label for="clientlname"><span class="field-name"><?= htmlspecialchars($langFile['client_lname']) ?></span></label>
						<input type="text" class="form-control" id="clientlname" name="clientlname" value="">
					</div>
				</div>
				<div class="col-xs-6">				
					<div class="form-group">
						<label for="clientfname"><span class="field-name"><?= htmlspecialchars($langFile['client_fname']) ?></span></label>
						<input type="text" class="form-control" id="clientfname" name="clientfname" value="">
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-xs-6">
					<div class="form-group">
						<label for="clientemail"><span class="field-name"><?= htmlspecialchars($langFile['client_email']) ?></span></label>
						<input type="email" class="form-control" id="clientemail" name="clientemail" value="">
					</div>
				</div>
				<div class="col-xs-6">
					<div class="form-group">
						<label for="clientphone"><span class="field-name"><?= htmlspecialchars($langFile['asearch_client_phone']) ?></span></label>
						<input type="tel" data-rule-phoneUS="true" class="form-control" id="clientphone" name="clientphone" value="">
					</div>
				</div>
			</div>
			<?php } ?>
			<div class="row">
				<div class="col-xs-6">			
					<div class="form-group">
						<label for="sourceid"><span class="field-name"><?= htmlspecialchars($langFile['request_source']) ?></span></label>
						<select class="form-control" id="sourceid" name="sourceid">
							<option value=""><?= htmlspecialchars($langFile['select_source']) ?></option>
							<?php 
							$nameField = ($_SESSION['lang'] === 'fr') ? 'namefr' : 'nameen';
							$sql2 = "SELECT * FROM tblsources WHERE status='1' ORDER BY $nameField ASC";
							$result2 = mysqli_query($link,$sql2);	
							while($row2 = mysqli_fetch_array($result2)){
							?>
								<option value="<?php echo $row2['id']; ?>"><?php echo $row2[$nameField]; ?></option>
							<?php
							}
							?>
						</select>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-xs-6">
					<div class="form-group">
						<label for="datereceived"><span class="field-name"><?= htmlspecialchars($langFile['asearch_date_received_from']) ?></span></label>
						<input type="date" class="form-control" id="datereceived" name="datereceived"  max="<?php echo date('Y-m-d', strtotime('+1 years'));?>" />
					</div>
				</div>
				<div class="col-xs-6">
					<div class="form-group">
						<label for="datereceived2"><span class="field-name"><?= htmlspecialchars($langFile['asearch_date_received_to']) ?></span></label>
						<input type="date" class="form-control" id="datereceived2" name="datereceived2"  max="<?php echo date('Y-m-d', strtotime('+1 years'));?>" />
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-xs-6">
					<div class="form-group">
						<label for="dateupdated"><span class="field-name"><?= htmlspecialchars($langFile['asearch_date_updated_from']) ?></span></label>
						<input type="date" class="form-control" id="dateupdated" name="dateupdated"  max="<?php echo date('Y-m-d', strtotime('+1 years'));?>" />
					</div>
				</div>
				<div class="col-xs-6">
					<div class="form-group">
						<label for="dateupdated2"><span class="field-name"><?= htmlspecialchars($langFile['asearch_date_updated_to']) ?></span></label>
						<input type="date" class="form-control" id="dateupdated2" name="dateupdated2" max="<?php echo date('Y-m-d', strtotime('+1 years'));?>" />
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-xs-6">
					<div class="form-group">
						<label for="daterequired"><span class="field-name"><?= htmlspecialchars($langFile['asearch_date_required_from']) ?></span></label>
						<input type="date" class="form-control" id="daterequired" name="daterequired"  max="<?php echo date('Y-m-d', strtotime('+1 years'));?>" />
					</div>
				</div>
				<div class="col-xs-6">
					<div class="form-group">
						<label for="daterequired2"><span class="field-name"><?= htmlspecialchars($langFile['asearch_date_required_to']) ?></span></label>
						<input type="date" class="form-control" id="daterequired2" name="daterequired2" max="<?php echo date('Y-m-d', strtotime('+1 years'));?>" />
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-xs-6">
					<div class="form-group">
						<label for="dateresolved"><span class="field-name"><?= htmlspecialchars($langFile['asearch_date_resolved_from']) ?></span></label>
						<input type="date" class="form-control" id="dateresolved" name="dateresolved"  max="<?php echo date('Y-m-d', strtotime('+1 years'));?>" />
					</div>
				</div>
				<div class="col-xs-6">
					<div class="form-group">
						<label for="dateresolved2"><span class="field-name"><?= htmlspecialchars($langFile['asearch_date_resolved_to']) ?></span></label>
						<input type="date" class="form-control" id="dateresolved2" name="dateresolved2" max="<?php echo date('Y-m-d', strtotime('+1 years'));?>" />
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-xs-6">
					<div class="form-group">
						<label for="statusid"><span class="field-name"><?= htmlspecialchars($langFile['status']) ?></span></label>
						<select class="form-control" id="statusid" name="statusid">
							<option value=""><?= htmlspecialchars($langFile['select_status']) ?></option>
							<?php 
							$sql2 = "SELECT * FROM tblstatus WHERE status='1' ORDER BY $nameField ASC";
							$result2 = mysqli_query($link,$sql2);	
							while($row2 = mysqli_fetch_array($result2)){
							?>
								<option value="<?php echo $row2['id']; ?>"><?php echo $row2[$nameField]; ?></option>
							<?php
							}
							?>
						</select>
					</div>
				</div>
			</div>
			
			<div class="row">
				<div class="col-xs-6">
					<div class="form-group">
						<label for="catalogueid"><span class="field-name"><?= htmlspecialchars($langFile['catalogue_name']) ?></span></label>
						<select class="form-control" id="catalogueid" name="catalogueid" onchange="ajax1(this.value)">
							<option value=""><?= htmlspecialchars($langFile['select_catalogue']) ?></option>
							<?php 
							$sql2 = "SELECT * FROM tblcatalogue WHERE status='1' ORDER BY $nameField ASC";
							$result2 = mysqli_query($link,$sql2);	
							while($row2 = mysqli_fetch_array($result2)){
							?>
							<option value="<?php echo $row2['id']; ?>"><?php echo $row2[$nameField]; ?></option>
							<?php
							}
							?>
						</select>
					</div>
					<div class="form-group divservice">
					</div>
					<div class="form-group divsubservice">
					</div>
				</div>
			</div>
			
			<div class="form-group form-buttons">
				<button type="submit" class="btn btn-default"><?= htmlspecialchars($langFile['asearch_button']) ?></button>
			</div>
			</form>
			<?php
			} elseif ($showform==false) {

			$result = mysqli_query($link,$sql);
			//List it
			if(mysqli_num_rows($result)>0){
			$numEntries = mysqli_num_rows($result);
			?>
			<h1 property="name" id="wb-cont"><?= htmlspecialchars($langFile['asearch_results_heading']) ?> - <?= $numEntries ?> <?= htmlspecialchars($langFile['asearch_entries']) ?></h1>
			
			<div class="row wb-eqht-grd">
		<?php
			// Determine database column for name fields based on language
			$nameField = ($_SESSION['lang'] === 'fr') ? 'namefr' : 'nameen';
			
			while($row = mysqli_fetch_array($result)){
				// Check if clientlname or clientfname is not empty
				$clientfname = htmlspecialchars ($row['clientfname'] ?? '');
				$clientlname = htmlspecialchars ($row['clientlname'] ?? '');
				$clientname = "";
				if ($clientfname!="" AND $clientlname!="") {
					$clientname = $clientlname . ", " . $clientfname;
				}
				
				// Grab the services related to this catalogue item
				$subserviceid = $row['subserviceid'];
				$serviceid = $row['serviceid'];
				$catalogueid = $row['catalogueid'];
				$statusid = $row['statusid'];
				$tarraycontactid = "";
				
				$sla = 0;
				$dsla = 0;
				$overdue = false;
				$doverdue = false;
				$closedue = false;
				$servicename = '';
				$cataloguename = '';
				$subservicename = '';
				
				if ($subserviceid!="" && $subserviceid != null) {
					// Sub-service is not empty so grab the name
					$result2 = mysqli_query($link, "SELECT $nameField,sds,contactid FROM tblsubservices WHERE id = '$subserviceid'");
					$row2 = mysqli_fetch_array($result2);
					if (!empty($row2)){
						$subservicename = $row2[0];
						$sla = $row2[1];
						$dsla = $sla * 2;
						$tarraycontactid = $row2[2];
					}
				}
				
				if (!empty($serviceid)) {
					// Sub-service is not empty so grab the name
					$result2 = mysqli_query($link, "SELECT $nameField,sds,contactid FROM tblservices WHERE id = '$serviceid'");
					$row2 = mysqli_fetch_array($result2);
					$servicename = $row2 ? $row2[0] : '';
					if ($sla == 0) {
						if ($serviceid == 21 || $serviceid == 22 || $serviceid == 23 || $serviceid == 24) {
							$sla = 15;
							$dsla = $sla * 2;
						} else {
							$sla = $row2 ? $row2[1] : 0;
							$dsla = $sla * 2;
						}
					}
					if (empty($tarraycontactid)) {
						$tarraycontactid = $row2 ? $row2[2] : 0;
					}
				}
				
				// Sub-service is not empty so grab the name
				$result2 = mysqli_query($link, "SELECT $nameField FROM tblcatalogue WHERE id = '$catalogueid'");
				$row2 = mysqli_fetch_array($result2);
				$cataloguename = $row2 ? $row2[0] : '';

				// Grab the date it was received
				$slatimer = $row['slatimer'];
				if ($slatimer == "" or is_null($slatimer)) {
					$datereceived = $row['datereceived'];
				} else {
					$datereceived = $slatimer;
				}
				$ndatereceived = date('Y-m-d H:i:s', strtotime($datereceived . ' +1 day'));
				
				// Calculate the business days
				$cBdays = calculateSLA($link, $row['requestid'], $ndatereceived);
				
				// Check if ticket is close to SLA or past SLA
				$suppressSlaWarning = in_array((int)$statusid, array(4, 5, 6));
				if ($cBdays > $dsla) {
					$doverdue = true;
				}
				if ($cBdays > $sla) {
					$overdue = true;
				}
				if ($cBdays == $sla) {
					$closedue = true;
				}
				
				// Determine panel color based on SLA status
				$panelClass = 'panel-default';
				if (!$suppressSlaWarning) {
					if ($doverdue) {
						$panelClass = 'panel-danger';
					} elseif ($overdue || $closedue) {
						$panelClass = 'panel-warning';
					}
				}

				// Get status name
				$result2 = mysqli_query($link, "SELECT $nameField FROM tblstatus WHERE id = '$statusid'");
				$row2 = mysqli_fetch_array($result2);
				$statusname = $row2 ? $row2[0] : '';
		?>
		<div class="col-sm-6 col-md-4 mrgn-bttm-md">
			<div class="panel <?= $panelClass ?> hght-inhrt">
				<div class="panel-heading">
					<h3 class="h5 mrgn-tp-sm mrgn-bttm-sm">
						<a href="viewrequest.php?lang=<?= $_SESSION['lang'] ?>&erid=<?php echo base64_encode($row['id']);?>&reqid=<?php echo urlencode('a11y-' . $row['requestid']);?>">a11y-<?php echo htmlspecialchars($row['requestid']);?></a>
					</h3>
					<p class="mrgn-bttm-0"><?php echo htmlspecialchars($row['title'] ?? '');?></p>
				</div>
				<div class="panel-body">
					<?php if (!$suppressSlaWarning) { ?>
						<?php if ($doverdue) { ?>
						<div class="alert alert-danger mrgn-bttm-md" role="alert">
							<span class="glyphicon glyphicon-warning-sign"></span> <?= htmlspecialchars($langFile['asearch_escalation_required']) ?>
						</div>
						<?php } elseif ($overdue) { ?>
						<div class="alert alert-warning mrgn-bttm-md" role="alert">
							<span class="glyphicon glyphicon-warning-sign"></span> <?= htmlspecialchars($langFile['asearch_request_past_sla']) ?>
						</div>
						<?php } elseif ($closedue) { ?>
						<div class="alert alert-warning mrgn-bttm-md" role="alert">
							<span class="glyphicon glyphicon-warning-sign"></span> <?= htmlspecialchars($langFile['asearch_request_close_sla']) ?>
						</div>
						<?php } ?>
					<?php } ?>
					<?php if (!empty($_SESSION['pid'])) { ?>
					<dl>
						<dt><?= htmlspecialchars($langFile['asearch_table_client']) ?></dt>
						<dd><?php echo htmlspecialchars($clientname);?></dd>
					<?php } ?>
						<dt><?= htmlspecialchars($langFile['asearch_table_service']) ?></dt>
						<dd>
							<?php echo htmlspecialchars($cataloguename); ?><br />
							<?php echo htmlspecialchars($servicename); ?>
							<?php if (!empty($subservicename)) { echo "<br />" . htmlspecialchars($subservicename); } ?>
						</dd>
						<dt><?= htmlspecialchars($langFile['asearch_table_status']) ?></dt>
						<dd><?php echo htmlspecialchars($statusname); ?></dd>
					<?php if (!empty($_SESSION['pid'])) { ?>
					</dl>
					<?php } ?>
				</div>
				<div class="panel-footer">
					<div class="row">
						<div class="col-xs-6">
							<?php
								// Check if the account is admin level to show this option 
								if ($_SESSION['atype']==1 OR $_SESSION['atype']==2 OR $_SESSION['atype']==3 OR $_SESSION['atype']==4) {
									// Now that we know the user is logged in we need to check if this ticket is assigned to them except for atype 1 and 2
									if ($_SESSION['atype']==1 OR $_SESSION['atype']==2) {	
							?>
									<a class="btn btn-sm btn-default btn-block" href="editrequest.php?lang=<?= $_SESSION['lang'] ?>&erid=<?php echo base64_encode($row['id']);?>&reqid=<?php echo urlencode('a11y-' . $row['requestid']); ?>"><?= htmlspecialchars($langFile['asearch_edit']) ?></a>
							<?php
									} else  {
										// User is 3 (Manager) or 4 (Team Leader) so check if they have permission to edit this request
										// First grab any existing teams
										$userid = $_SESSION['pid'];
										$result2 = mysqli_query($link, "SELECT team FROM tblusers WHERE id = '$userid'");
										$row2 = mysqli_fetch_array($result2);
										$teams = $row2[0];
										$tarray = explode(",",$teams);
											if(in_array($tarraycontactid, $tarray)) {
									?>
									<a class="btn btn-sm btn-default btn-block" href="editrequest.php?lang=<?= $_SESSION['lang'] ?>&erid=<?php echo base64_encode($row['id']);?>&reqid=<?php echo urlencode('a11y-' . $row['requestid']); ?>"><?= htmlspecialchars($langFile['asearch_edit']) ?></a>
									<?php 
											} else {
									?>
									<span class="text-muted"><?= htmlspecialchars($langFile['asearch_na']) ?></span>
									<?php
											}
										}
									}
							?>
						</div>
						<div class="col-xs-6">
							<?php if ($_SESSION['atype']=='1') { ?>
							<a class="wb-lbx btn btn-sm btn-danger btn-block" href="includes/delete-request.php?id=<?php echo $row['id'];?>"><?= htmlspecialchars($langFile['asearch_delete']) ?></a>
							<?php } elseif(in_array('1', $_SESSION['team'])){?>
							<a class="btn btn-sm btn-default btn-block" href="clonerequest.php?lang=<?= $_SESSION['lang'] ?>&erid=<?php echo base64_encode($row['id']);?>&toClose=2"><?= htmlspecialchars($langFile['asearch_clone']) ?></a>
							<?php if(!$isResolvedStatus){?><a class="btn btn-sm btn-default btn-block" href="clonerequest.php?lang=<?= $_SESSION['lang'] ?>&erid=<?php echo base64_encode($row['id']);?>&toClose=1"><?= htmlspecialchars($langFile['asearch_clone_close']) ?></a><?php }?>
							<?php }?>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php } ?>
		</div>
			<?php
			} else {
			?>
			<script>location.replace("/asearch.php?lang=<?= $_SESSION['lang'] ?>&status=noresults"); </script>
			<?php
			}
			}
			?>
			<div id="def-preFooter">
			</div>
<?php include 'includes/template/page-details.php'; ?>
	</main>
	
	<?php include 'includes/template/footer.php'; include 'includes/template/scripts.php'; ?>

	<script src="/public/js/ajax-dropdowns.js"></script>
</body>
</html>
<?php
// Close connection
mysqli_close($link);
?>
