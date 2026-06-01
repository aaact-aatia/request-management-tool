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
if (session_status() != PHP_SESSION_ACTIVE)
{
	session_start();
}
require('includes/sla-calculator.php');

// Grab HTTPS check
require('includes/httpscheck.php');

// Include file for calculating business days
require('includes/calculate-bdays.php');

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
	$nsd = mysqli_real_escape_string($link,$_POST['nsd']);
	if ($nsd!="") {
		$nosearch = false;
		$SQLSV .= " nsd = '$nsd' AND";
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
				<div class="col-xs-6">
					<div class="form-group">
						<label for="nsd"><span class="field-name"><?= htmlspecialchars($langFile['asearch_nsd_ticket']) ?></span></label>
						<input type="text" class="form-control" id="nsd" name="nsd" value="">
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
			?>
			<h1 property="name" id="wb-cont"><?= htmlspecialchars($langFile['asearch_results_heading']) ?><?php if ($nosearch) { ?> - <?= htmlspecialchars($langFile['asearch_last_1000']) ?><?php } ?></h1>
			
			<table class="wb-tables table table-striped table-hover" data-wb-tables='{"columnDefs": [{ "type": "html-num", "targets": 0 }]}'>
				<thead>
				<tr>
					<th><?= htmlspecialchars($langFile['asearch_table_request']) ?></th>
					<th><?= htmlspecialchars($langFile['asearch_table_title']) ?></th>
					<?php if($_SESSION['pid']!=""){ ?><th><?= htmlspecialchars($langFile['asearch_table_client']) ?></th><?php } ?>
					<th><?= htmlspecialchars($langFile['asearch_table_service']) ?></th>
					<th><?= htmlspecialchars($langFile['asearch_table_status']) ?></th>
					<?php
					// Check if the account is admin level to show this option 
					if ($_SESSION['atype']=='1' OR $_SESSION['atype']=='2' OR $_SESSION['atype']=='3' OR $_SESSION['atype']=='4') {
					?>
					<th><?= htmlspecialchars($langFile['asearch_table_actions']) ?></th>
					<?php } ?>
				</tr>
				</thead>
				<tbody>
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

					

					if ($serviceid!="" && $serviceid != null) {
						// Sub-service is not empty so grab the name
						$result2 = mysqli_query($link, "SELECT $nameField,sds,contactid FROM tblservices WHERE id = '$serviceid'");
						$row2 = mysqli_fetch_array($result2);
						
						if($row2 != null && is_array($row2)){
							$servicename = $row2[0];
						if (empty($sla) and !empty($row2)) {
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
					}
					
					if ($catalogueid!="" && !empty($catalogueid)) {
						// Sub-service is not empty so grab the name
						$result2 = mysqli_query($link, "SELECT $nameField FROM tblcatalogue WHERE id = '$catalogueid'");
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
					if (!empty($datereceived) && strtotime($datereceived) !== false) {
						$ndatereceived = date('Y-m-d H:i:s', strtotime($datereceived . ' +1 day'));
					} else {
						$ndatereceived = date('Y-m-d H:i:s');
					}
					
					// Check if request is resolved and calculate from that day
					// Grab the status id
					$statusid = $row['statusid'];
					if ($statusid=='2') {
						// Get the date resolved
						$dateresolved = $row['dateresolved'];
						if (!empty($dateresolved) && strtotime($dateresolved) !== false) {
							$ndateresolved = date('Y-m-d H:i:s', strtotime($dateresolved));
							// Calculate the business days (request completed)
							//$cBdays = getWorkingDays($ndatereceived,$ndateresolved,$holidays);
							$cBdays = calculateSLA($link, $row['requestid'], $ndatereceived,$ndateresolved);
						} else {
							// Missing resolve date: fall back to open-request SLA calculation.
							$cBdays = calculateSLA($link, $row['requestid'], $ndatereceived);
						}

					} else {
						// Calculate the business days (request still open)
						//$cBdays = getWorkingDays($ndatereceived,date('Y-m-d'),$holidays);
						$cBdays = calculateSLA($link, $row['requestid'], $ndatereceived);

					}
						
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
			?>
				<?php if ($statusid == 2) { ?>
				<tr <?php if ($doverdue OR $overdue) { ?> style="background-color: #e87d88;"<?php } ?>>
				<?php } else { ?>
				<tr <?php if ($doverdue) { ?> style="background-color: #e87d88;"<?php } elseif($overdue) { ?> style="background-color: #f5c6cb;"<?php } elseif ($closedue) { ?> style="background-color: #ffeeba;"<?php } ?>>
				<?php } ?>
				<td>
						<a href="viewrequest.php?lang=<?= $_SESSION['lang'] ?>&erid=<?php echo base64_encode($row['id']);?>">a11y-<?php echo $row['requestid'];?> <span class="glyphicon glyphicon-eye-open"></span><span class="wb-inv"><?= htmlspecialchars($langFile['asearch_details']) ?></span></a>
						<?php if (!empty($row['nsd']) && !empty($_SESSION['pid'])) { ?>
							<br />
							<?php if(preg_match('/^[0-9]+$/', $row['nsd'])){?>
								<a href="http://arweb.prv/SRMIS.htm?Ticket=<?php echo $row['nsd'];?>" target="_blank"># NSD<?php echo $row['nsd'];?><span class="glyphicon glyphicon-new-window"></span><span class="wb-inv"><?= htmlspecialchars($langFile['asearch_details_new_window']) ?></span></a>
							<?php }else{?>
								<a href="https://smartitesdc.service.gc.ca/smartit/app/#/search/<?php echo $row['nsd'];?>" target="_blank"># Smart IT <?php echo $row['nsd'];?><span class="glyphicon glyphicon-eye-open"></span><span class="wb-inv"><?= htmlspecialchars($langFile['asearch_details_new_window']) ?></span></a>
							<?php } ?>
							<?php }?>
					</td>					
					<td><?php echo htmlspecialchars ($row['title'] ?? '');?></td>
					<?php if(!empty($_SESSION['pid'])){ ?><td><?php echo $clientname;?></td><?php } ?>
					<td>
						<?php echo $cataloguename; ?><?php echo "<br />" . $servicename; ?><?php if (!empty($subservicename)) { echo "<br />" . $subservicename; } ?>
					</td>					
					<td>
					<?php 
					
					$result2 = mysqli_query($link, "SELECT $nameField FROM tblstatus WHERE id = '$statusid'");
					$row2 = mysqli_fetch_array($result2);
					$statusname = $row2[0];
					?>
						<?php echo $statusname; ?>
						
						<?php if ($statusid=='2') { ?>
						<?php if ($doverdue OR $overdue) { ?><br /><span class="glyphicon glyphicon-warning-sign"></span> <?= htmlspecialchars($langFile['asearch_request_closed_past_sla']) ?><?php } ?>
						<?php } else { ?>
						<?php if ($doverdue) { ?><br /><span class="glyphicon glyphicon-warning-sign"></span> <?= htmlspecialchars($langFile['asearch_escalation_required']) ?><?php } elseif ($overdue) { ?><br /><span class="glyphicon glyphicon-warning-sign"></span> <?= htmlspecialchars($langFile['asearch_request_past_sla']) ?><?php } elseif ($closedue) { ?><br /><span class="glyphicon glyphicon-warning-sign"></span> <?= htmlspecialchars($langFile['asearch_request_close_sla']) ?><?php } ?>
						<?php } ?>
					</td>
					<?php
					// Check if the account is admin level to show this option 
					if ($_SESSION['atype']==1 OR $_SESSION['atype']==2 OR $_SESSION['atype']==3 OR $_SESSION['atype']==4) {
					?>
					<td>
					<?php
						// Now that we know the user is logged in we need to check if this ticket is assigned to them except for atype 1 and 2
						if ($_SESSION['atype']==1 OR $_SESSION['atype']==2) {	
					?>
					
						<a class="btn btn-primary btn-block" href="editrequest.php?erid=<?php echo base64_encode($row['id']);?>"><?= htmlspecialchars($langFile['asearch_edit']) ?> &nbsp;<span class="wb-inv"> a11y-<?php echo $row['requestid'];?> <?= htmlspecialchars($langFile['asearch_request']) ?></span></a><?php if ($_SESSION['atype']=='1') { ?> <a class="wb-lbx btn btn-primary btn-block" href="includes/delete-request.php?id=<?php echo $row['id'];?>"><?= htmlspecialchars($langFile['asearch_delete']) ?> &nbsp;<span class="wb-inv"> a11y-<?php echo $row['requestid'];?> <?= htmlspecialchars($langFile['asearch_request']) ?></span></a><?php } ?>
						<?php if(in_array('1', $_SESSION['team'])){?><a class="btn btn-primary btn-block" href="clonerequest.php?lang=<?= $_SESSION['lang'] ?>&erid=<?php echo base64_encode($row['id']);?>&toClose=2"><?= htmlspecialchars($langFile['asearch_clone']) ?> &nbsp;<span class="wb-inv"> a11y-<?php echo $row['requestid'];?> <?= htmlspecialchars($langFile['asearch_request']) ?></span></a>
						<?php if($statusid != 2 and $statusid != '2'){?><a class="btn btn-primary btn-block" href="clonerequest.php?lang=<?= $_SESSION['lang'] ?>&erid=<?php echo base64_encode($row['id']);?>&toClose=1"><?= htmlspecialchars($langFile['asearch_clone_close']) ?> &nbsp;<span class="wb-inv"> a11y-<?php echo $row['requestid'];?> <?= htmlspecialchars($langFile['asearch_request']) ?></span></a><?php }}?>
					</td>
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
						<a class="btn btn-primary btn-block" href="editrequest.php?erid=<?php echo base64_encode($row['id']);?>"><?= htmlspecialchars($langFile['asearch_edit']) ?> &nbsp;<span class="wb-inv"> a11y-<?php echo $row['requestid'];?> <?= htmlspecialchars($langFile['asearch_request']) ?></span></a><?php if ($_SESSION['atype']=='1') { ?> <a class="wb-lbx btn btn-primary btn-block" href="includes/delete-request.php?id=<?php echo $row['id'];?>"><?= htmlspecialchars($langFile['asearch_delete']) ?> &nbsp;<span class="wb-inv"> a11y-<?php echo $row['requestid'];?> <?= htmlspecialchars($langFile['asearch_request']) ?></span></a><?php } ?>
						<?php if(in_array('1', $_SESSION['team'])){?><a class="btn btn-primary btn-block" href="clonerequest.php?lang=<?= $_SESSION['lang'] ?>&erid=<?php echo base64_encode($row['id']);?>&toClose=2"><?= htmlspecialchars($langFile['asearch_clone']) ?> &nbsp;<span class="wb-inv"> a11y-<?php echo $row['requestid'];?> <?= htmlspecialchars($langFile['asearch_request']) ?></span></a>
						<?php if($statusid != 2 and $statusid != '2'){?><a class="btn btn-primary btn-block" href="clonerequest.php?lang=<?= $_SESSION['lang'] ?>&erid=<?php echo base64_encode($row['id']);?>&toClose=1"><?= htmlspecialchars($langFile['asearch_clone_close']) ?> &nbsp;<span class="wb-inv"> a11y-<?php echo $row['requestid'];?> <?= htmlspecialchars($langFile['asearch_request']) ?></span></a><?php }}?>
					<?php 
							} else {
					?>
						<?= htmlspecialchars($langFile['asearch_na']) ?>
					<?php
							}
						}
					?>
					</td>
					<?php 
					} 
					?>
				</tr>
			<?php } ?>
				</tbody>
			</table>
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

	<script>
	function ajax1(val1){
		$.ajax({url:"addrequest-ajax1.php?v1="+val1,success:function(result){
			$(".divservice").html(result);
		}});
		$(".divsubservice").hide();
	}
	function ajax2(val1){
		$.ajax({url:"addrequest-ajax2.php?v1="+val1,success:function(result){
			$(".divsubservice").html(result);
		}});
		$(".divsubservice").show();
	}
	</script>
</body>
</html>
<?php
// Close connection
mysqli_close($link);
?>
