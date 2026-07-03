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

if (!empty($_GET['clientlname'] ))
{
	$clientlname = mysqli_real_escape_string($link,strtolower($_GET['clientlname']));
}
else
{
	$clientlname = "";
}

if (!empty($_GET['clientfname'] ))
{
	$clientfname = mysqli_real_escape_string($link,strtolower($_GET['clientfname']));
}
else
{
	$clientfname = "";
}

if (!empty($_GET['clientemail'] ))
{
	$clientemail = mysqli_real_escape_string($link,strtolower($_GET['clientemail']));
}
else
{
	$clientemail = "";
}

if (!empty($_GET['clientphone'] ))
{
	$clientphone = mysqli_real_escape_string($link,$_GET['clientphone']);
}
else
{
	$clientphone = "";
}

if (!empty($_GET['serviceid'] ))
{
	$serviceid = mysqli_real_escape_string($link,$_GET['serviceid']);
}
else
{
	$serviceid = "";
}

if (!empty($_GET['subserviceid'] ))
{
	$subserviceid = mysqli_real_escape_string($link,$_GET['subserviceid']);
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

$effectiveAtype = (int)($_SESSION['atype'] ?? 0);
$isTeamLeadAccount = ($effectiveAtype === 4);
$searchScope = $isTeamLeadAccount ? (($_GET['searchscope'] ?? 'team') === 'all' ? 'all' : 'team') : 'all';
$userTeamIds = [];
if ($isTeamLeadAccount) {
	$teamResult = mysqli_query($link, "SELECT team FROM tblusers WHERE id = '" . (int)($_SESSION['pid'] ?? 0) . "' LIMIT 1");
	$teamRow = $teamResult ? mysqli_fetch_assoc($teamResult) : null;
	$userTeamIds = array_values(array_filter(array_map('trim', explode(',', (string)($teamRow['team'] ?? '')))));
}

// Process search if any parameters are submitted
$hasSearchParams = !empty($_GET['requestid']) || !empty($_GET['requesttitle']) || !empty($clientlname) || !empty($clientfname) || 
                    !empty($clientemail) || !empty($clientphone) || !empty($_GET['sourceid']) || !empty($_GET['datereceived']) || 
                    !empty($_GET['datereceived2']) || !empty($_GET['dateupdated']) || !empty($_GET['dateupdated2']) || 
                    !empty($_GET['daterequired']) || !empty($_GET['daterequired2']) || !empty($_GET['dateresolved']) || 
                    !empty($_GET['dateresolved2']) || !empty($_GET['statusid']) || !empty($_GET['catalogueid']) || 
					!empty($serviceid) || !empty($subserviceid) || ($isTeamLeadAccount && isset($_GET['searchscope']));

if ($hasSearchParams){
	// Set no search value
	$showform = false;
	$nosearch = true;
	$SQLSV = "";
	
	// Grab form elements	
	if (!empty($_GET['requestid'])) {
		$requestid = mysqli_real_escape_string($link,$_GET['requestid']);
		$nosearch = false;
		$SQLSV .= " requestid = '$requestid' AND";
	}
	
	if (!empty($_GET['requesttitle'])) {
		$requesttitle = mysqli_real_escape_string($link,strtolower($_GET['requesttitle']));
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
	
	if (!empty($_GET['sourceid'])) {
		$sourceid = mysqli_real_escape_string($link,$_GET['sourceid']);
		$nosearch = false;
		$SQLSV .= " sourceid = '$sourceid' AND";
	}
	$datereceived = mysqli_real_escape_string($link, $_GET['datereceived'] ?? '');
	$datereceived2 = mysqli_real_escape_string($link, $_GET['datereceived2'] ?? '');
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
	$dateupdated = mysqli_real_escape_string($link, $_GET['dateupdated'] ?? '');
	$dateupdated2 = mysqli_real_escape_string($link, $_GET['dateupdated2'] ?? '');
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
	$daterequired = mysqli_real_escape_string($link, $_GET['daterequired'] ?? '');
	$daterequired2 = mysqli_real_escape_string($link, $_GET['daterequired2'] ?? '');
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
	$dateresolved = mysqli_real_escape_string($link, $_GET['dateresolved'] ?? '');
	$dateresolved2 = mysqli_real_escape_string($link, $_GET['dateresolved2'] ?? '');
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
	$statusid = mysqli_real_escape_string($link, $_GET['statusid'] ?? '');
		if ($statusid!="") {
		$nosearch = false;
		$SQLSV .= " statusid = '$statusid' AND";
	}
	$catalogueid = mysqli_real_escape_string($link, $_GET['catalogueid'] ?? '');
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

	$teamScopeClause = "";
	if ($isTeamLeadAccount && $searchScope !== 'all') {
		if (empty($userTeamIds)) {
			$teamScopeClause = " AND 1=0";
		} else {
			$teamIdCsv = implode(',', array_map('intval', $userTeamIds));
			$teamScopeClause = " AND ((serviceid IN (SELECT id FROM tblservices WHERE contactid IN ($teamIdCsv))) OR (subserviceid IN (SELECT id FROM tblsubservices WHERE contactid IN ($teamIdCsv))))";
		}
	}

	// Create SQL statement
	if ($nosearch) {
		$sql = "SELECT * FROM tbltriage WHERE status = '1'" . $teamScopeClause . " ORDER BY requestid DESC LIMIT 1000";
	} else {
		$sql = "SELECT * FROM tbltriage WHERE $SQLSV AND status = '1'" . $teamScopeClause . " ORDER BY requestid DESC";
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
		
			<form method="get" action="/asearch.php" onsubmit="removeEmptyFields(event)">
			<input type="hidden" name="lang" value="<?= $_SESSION['lang'] ?>">
			<div class="row">
				<div class="col-xs-6">
					<div class="form-group">
						<label for="requestid"><span class="field-name"><?= htmlspecialchars($langFile['asearch_request_id']) ?></span></label>
						<input type="text" class="form-control" id="requestid" name="requestid" value="<?= htmlspecialchars($_GET['requestid'] ?? '') ?>">
					</div>
				</div>
				<div class="col-xs-6">
					<div class="form-group">
						<label for="requesttitle"><span class="field-name"><?= htmlspecialchars($langFile['request_title']) ?></span></label>
						<input type="text" class="form-control" id="requesttitle" name="requesttitle" value="<?= htmlspecialchars($_GET['requesttitle'] ?? '') ?>">
					</div>
				</div>
			</div>
			<?php if($_SESSION['pid']!=""){ ?>
			<div class="row">
				<div class="col-xs-6">				
					<div class="form-group">
						<label for="clientlname"><span class="field-name"><?= htmlspecialchars($langFile['client_lname']) ?></span></label>
						<input type="text" class="form-control" id="clientlname" name="clientlname" value="<?= htmlspecialchars($_GET['clientlname'] ?? '') ?>">
					</div>
				</div>
				<div class="col-xs-6">				
					<div class="form-group">
						<label for="clientfname"><span class="field-name"><?= htmlspecialchars($langFile['client_fname']) ?></span></label>
						<input type="text" class="form-control" id="clientfname" name="clientfname" value="<?= htmlspecialchars($_GET['clientfname'] ?? '') ?>">
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-xs-6">
					<div class="form-group">
						<label for="clientemail"><span class="field-name"><?= htmlspecialchars($langFile['client_email']) ?></span></label>
						<input type="email" class="form-control" id="clientemail" name="clientemail" value="<?= htmlspecialchars($_GET['clientemail'] ?? '') ?>">
					</div>
				</div>
				<div class="col-xs-6">
					<div class="form-group">
						<label for="clientphone"><span class="field-name"><?= htmlspecialchars($langFile['asearch_client_phone']) ?></span></label>
						<input type="tel" data-rule-phoneUS="true" class="form-control" id="clientphone" name="clientphone" value="<?= htmlspecialchars($_GET['clientphone'] ?? '') ?>">
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
						<input type="date" class="form-control" id="datereceived" name="datereceived" value="<?= htmlspecialchars($_GET['datereceived'] ?? '') ?>" max="<?php echo date('Y-m-d', strtotime('+1 years'));?>" />
					</div>
				</div>
				<div class="col-xs-6">
					<div class="form-group">
						<label for="datereceived2"><span class="field-name"><?= htmlspecialchars($langFile['asearch_date_received_to']) ?></span></label>
						<input type="date" class="form-control" id="datereceived2" name="datereceived2" value="<?= htmlspecialchars($_GET['datereceived2'] ?? '') ?>" max="<?php echo date('Y-m-d', strtotime('+1 years'));?>" />
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-xs-6">
					<div class="form-group">
						<label for="dateupdated"><span class="field-name"><?= htmlspecialchars($langFile['asearch_date_updated_from']) ?></span></label>
						<input type="date" class="form-control" id="dateupdated" name="dateupdated" value="<?= htmlspecialchars($_GET['dateupdated'] ?? '') ?>" max="<?php echo date('Y-m-d', strtotime('+1 years'));?>" />
					</div>
				</div>
				<div class="col-xs-6">
					<div class="form-group">
						<label for="dateupdated2"><span class="field-name"><?= htmlspecialchars($langFile['asearch_date_updated_to']) ?></span></label>
						<input type="date" class="form-control" id="dateupdated2" name="dateupdated2" value="<?= htmlspecialchars($_GET['dateupdated2'] ?? '') ?>" max="<?php echo date('Y-m-d', strtotime('+1 years'));?>" />
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-xs-6">
					<div class="form-group">
						<label for="daterequired"><span class="field-name"><?= htmlspecialchars($langFile['asearch_date_required_from']) ?></span></label>
						<input type="date" class="form-control" id="daterequired" name="daterequired" value="<?= htmlspecialchars($_GET['daterequired'] ?? '') ?>" max="<?php echo date('Y-m-d', strtotime('+1 years'));?>" />
					</div>
				</div>
				<div class="col-xs-6">
					<div class="form-group">
						<label for="daterequired2"><span class="field-name"><?= htmlspecialchars($langFile['asearch_date_required_to']) ?></span></label>
						<input type="date" class="form-control" id="daterequired2" name="daterequired2" value="<?= htmlspecialchars($_GET['daterequired2'] ?? '') ?>" max="<?php echo date('Y-m-d', strtotime('+1 years'));?>" />
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-xs-6">
					<div class="form-group">
						<label for="dateresolved"><span class="field-name"><?= htmlspecialchars($langFile['asearch_date_resolved_from']) ?></span></label>
						<input type="date" class="form-control" id="dateresolved" name="dateresolved" value="<?= htmlspecialchars($_GET['dateresolved'] ?? '') ?>" max="<?php echo date('Y-m-d', strtotime('+1 years'));?>" />
					</div>
				</div>
				<div class="col-xs-6">
					<div class="form-group">
						<label for="dateresolved2"><span class="field-name"><?= htmlspecialchars($langFile['asearch_date_resolved_to']) ?></span></label>
						<input type="date" class="form-control" id="dateresolved2" name="dateresolved2" value="<?= htmlspecialchars($_GET['dateresolved2'] ?? '') ?>" max="<?php echo date('Y-m-d', strtotime('+1 years'));?>" />
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
				<?php if ($isTeamLeadAccount) { ?>
				<div class="form-group">
					<label for="searchscope"><span class="field-name"><?php echo ($_SESSION['lang'] === 'fr') ? 'Portée de la recherche' : 'Search scope'; ?></span></label>
					<select class="form-control" id="searchscope" name="searchscope">
						<option value="team" <?php echo $searchScope === 'team' ? 'selected' : ''; ?>><?php echo ($_SESSION['lang'] === 'fr') ? 'Demandes liées à mon équipe' : 'Requests related to my team'; ?></option>
						<option value="all" <?php echo $searchScope === 'all' ? 'selected' : ''; ?>><?php echo ($_SESSION['lang'] === 'fr') ? 'Toutes les demandes' : 'All requests'; ?></option>
					</select>
				</div>
				<?php } ?>
				<button type="submit" class="btn btn-default"><?= htmlspecialchars($langFile['asearch_button']) ?></button>			<button type="reset" class="btn btn-default"><?= htmlspecialchars($langFile['asearch_clear']) ?></button>			</div>
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

				// Determine action availability for this request.
				$canEditThisRequest = false;
				if (canEditRequests()) {
					if (isSuperAdmin() || (isset($_SESSION['is_admin']) && $_SESSION['is_admin']) || (int)($_SESSION['atype'] ?? 0) === 3) {
						$canEditThisRequest = true;
					} else {
						$userid = $_SESSION['pid'];
						$result2 = mysqli_query($link, "SELECT team FROM tblusers WHERE id = '$userid'");
						$row2 = mysqli_fetch_array($result2);
						$teams = $row2[0] ?? '';
						$tarray = explode(",", $teams);
						if (in_array($tarraycontactid, $tarray)) {
							$canEditThisRequest = true;
						}
					}
				}

				$canDeleteThisRequest = canDeleteRequests();
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
				<?php if ($canEditThisRequest || $canDeleteThisRequest) { ?>
				<div class="panel-footer">
					<div class="row">
						<?php if ($canEditThisRequest) { ?>
						<div class="<?= $canDeleteThisRequest ? 'col-xs-6' : 'col-xs-12' ?>">
							<a class="btn btn-default btn-block" href="editrequest.php?lang=<?= $_SESSION['lang'] ?>&erid=<?php echo base64_encode($row['id']);?>&reqid=<?php echo urlencode('a11y-' . $row['requestid']); ?>"><span class="glyphicon glyphicon-pencil" aria-hidden="true"></span><span class="mrgn-lft-sm"><?= htmlspecialchars($langFile['asearch_edit']) ?></span></a>
						</div>
						<?php } ?>
						<?php if ($canDeleteThisRequest) { ?>
						<div class="<?= $canEditThisRequest ? 'col-xs-6' : 'col-xs-12' ?>">
							<a class="wb-lbx btn btn-danger btn-block" href="includes/delete-request.php?id=<?php echo $row['id'];?>"><span class="glyphicon glyphicon-trash" aria-hidden="true"></span><span class="mrgn-lft-sm"><?= htmlspecialchars($langFile['asearch_delete']) ?></span></a>
						</div>
						<?php } ?>
					</div>
				</div>
				<?php } ?>
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

	<script src="/public/js/remove-empty-fields.js"></script>
	<script src="/public/js/ajax-dropdowns.js"></script>
</body>
</html>
<?php
// Close connection
mysqli_close($link);
?>
