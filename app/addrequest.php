<?php
/**
 * Consolidated Bilingual Add Request Page
 * 
 * This page replaces the separate addrequest-en.php and addrequest-fr.php files
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

// Grab HTTPS check
require('includes/httpscheck.php');

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
$lang = require("lang/{$_SESSION['lang']}.php");

// Security check (using session language)
if ($_SESSION['lang'] === 'fr') {
	require('includes/loggedincheck.php');
} else {
	require('includes/loggedincheck.php');
}

// Process the add product form
if ($_SERVER['REQUEST_METHOD']=='POST'){
	// Set error to false
	$noerror = false;
	
	// Grab form elements
	$requestid = mysqli_real_escape_string($link,$_POST['requestid']);
	// We need to do some validation to ensure the ID doesn't already exist
	$sql2 = "SELECT id FROM tbltriage WHERE requestid = '$requestid'";
	$result2 = mysqli_query($link,$sql2);
	if(mysqli_num_rows($result2)>0){
		header("location:/addrequest.php?lang={$_SESSION['lang']}&status=duplicate"); 
		exit();
	}

	if (!empty($_POST['requesttitle']))
	{
		$requesttitle = mysqli_real_escape_string($link,$_POST['requesttitle']);
	}
	else
	{
		$requesttitle = "";
	}
	
	if (!empty($_POST['clientlname']))
	{
		$clientlname = mysqli_real_escape_string($link,$_POST['clientlname']);
	}
	else
	{
		$clientlname = "";
	}
	
	if (!empty($_POST['clientfname']))
	{
		$clientfname = mysqli_real_escape_string($link,$_POST['clientfname']);
	}
	else
	{
		$clientfname = "";
	}
	
	if (!empty($_POST['clientemail']))
	{
		$clientemail = mysqli_real_escape_string($link,$_POST['clientemail']);
	}
	else
	{
		$clientemail = "";
	}
	
	if (!empty($_POST['clientphone']))
	{
		$clientphone = mysqli_real_escape_string($link,$_POST['clientphone']);
	}
	else
	{
		$clientphone = "";
	}
	
	if (!empty($_POST['sourceid']))
	{
		$sourceid = mysqli_real_escape_string($link,$_POST['sourceid']);
	}
	else
	{
		$sourceid = "";
	}
	
	if (!empty($_POST['datereceived']))
	{
		$datereceived = mysqli_real_escape_string($link,$_POST['datereceived']);
	}
	else
	{
		$datereceived = "";
	}

	$dateupdatedu = FALSE;
	$daterequiredu = FALSE;
	$dateresolvedu = FALSE;
	
	if (!empty($_POST['dateupdated']))
	{
		$dateupdated = mysqli_real_escape_string($link,$_POST['dateupdated']);
	}
	else
	{
		$dateupdatedu = TRUE;
		$dateupdated = "1900-01-01";
	}
	
	if (!empty($_POST['daterequired']))
	{
		$daterequired = mysqli_real_escape_string($link,$_POST['daterequired']);
	}
	else
	{
		$daterequiredu = TRUE;
		$daterequired = "1900-01-01";
	}

	if (!empty($_POST['dateresolved']))
	{
		$dateresolved = mysqli_real_escape_string($link,$_POST['dateresolved']);
	}
	else
	{
		$dateresolvedu = TRUE;
		$dateresolved = "1900-01-01";
	}

	if (!empty($_POST['statusid']))
	{
		$statusid = mysqli_real_escape_string($link,$_POST['statusid']);
	}
	else
	{
		$statusid = "";
	}
	
	if (!empty($_POST['nsd']))
	{
		$nsd = mysqli_real_escape_string($link,$_POST['nsd']);
	}
	else
	{
		$nsd = 0;
	}
	
	if (!empty($_POST['catalogueid']))
	{
		$catalogueid = mysqli_real_escape_string($link,$_POST['catalogueid']);
	}
	else
	{
		$catalogueid = 0;
	}

	if (!empty($_POST['serviceid']))
	{
		$serviceid = mysqli_real_escape_string($link,$_POST['serviceid']);
	}
	else
	{
		$serviceid = 0;
	}

	if (!empty($_POST['subserviceid']))
	{
		$subserviceid = mysqli_real_escape_string($link,$_POST['subserviceid']);
	}
	else
	{
		$subserviceid = 0;
	}
	
	if (!empty($_POST['clientnotes']))
	{
		$clientnotes = mysqli_real_escape_string($link,$_POST['clientnotes']);
	}
	else
	{
		$clientnotes = "";
	}

	if (!empty($_POST['adminnotes']))
	{
		$adminnotes = mysqli_real_escape_string($link,$_POST['adminnotes']);
	}
	else
	{
		$adminnotes = "";
	}
	
	$creatorid = $_SESSION['pid'];
	$updaterid = $_SESSION['pid'];
	$status = 1;
		
	// Custom form validation
	if ($requestid=="" OR $requesttitle=="" OR $sourceid=="" OR $datereceived=="" OR $statusid=="" OR $catalogueid=="" OR $serviceid=="") {
		$noerror = true;
	}

	// If error detected send user back to modal dialog
	if ($noerror) {
		header("location:/addrequest.php?lang={$_SESSION['lang']}&status=failed"); 
		exit();
	}
	
	// Create SQL statement
	$sql = "INSERT INTO tbltriage(`requestid`, `title`, `clientlname`, `clientfname`, `clientemail`, `clientphone`, `sourceid`, `datereceived`, `dateupdated`, `daterequired`, `dateresolved`, `statusid`, `nsd`, `catalogueid`, `serviceid`, `subserviceid`, `creatorid`, `updaterid`, `status`) VALUES ('$requestid', '$requesttitle', '$clientlname', '$clientfname', '$clientemail', '$clientphone', '$sourceid', '$datereceived', '$dateupdated', '$daterequired', '$dateresolved', '$statusid', '$nsd', '$catalogueid', '$serviceid', '$subserviceid', '$creatorid', '$updaterid', '$status')";
	//echo $sql;
	//exit();
	mysqli_query($link,$sql);
	
	// Update the date fields
	// Get the latest id first
	$result = mysqli_query($link, "SELECT MAX(id) FROM tbltriage");
	$row = mysqli_fetch_array($result);
	$latestid = $row[0];
	
	// Check if notes has anything and add to table
	if ($clientnotes!="") {
		$sql = "INSERT INTO tblcommlog(`triageid`, `dateadded`, `notes`, `creatorid`, `status`) VALUES ('$latestid', '$datereceived', '$clientnotes', '$creatorid', '$status')";
		mysqli_query($link,$sql);
	}
	
	// Check if notes has anything and add to table
	if ($adminnotes!="") {
		$sql = "INSERT INTO tbladminlog(`triageid`, `dateadded`, `notes`, `creatorid`, `status`) VALUES ('$latestid', '$datereceived', '$adminnotes', '$creatorid', '$status')";
		mysqli_query($link,$sql);
	}
	
	// Now update to set the dates to null
	if ($dateupdatedu) {
		$sql = "UPDATE `tbltriage` SET `dateupdated` = NULL WHERE id='$latestid'";
		mysqli_query($link,$sql);
	}
	
	if ($daterequiredu) {
		$sql = "UPDATE `tbltriage` SET `daterequired` = NULL WHERE id='$latestid'";
		mysqli_query($link,$sql);
	}
	
	if ($dateresolvedu) {
		$sql = "UPDATE `tbltriage` SET `dateresolved` = NULL WHERE id='$latestid'";
		mysqli_query($link,$sql);
	}
	
	// Now redirect
	header("location:/addrequest.php?lang={$_SESSION['lang']}&status=success"); 
	exit();
}

// Check if there is a status
if (!empty($_GET['status'])){
	$status = $_GET['status'];
}
else{
	$status = "";
}
?>
<!DOCTYPE html>
<!--[if lt IE 9]><html class="no-js lt-ie9" lang="<?= $_SESSION['lang'] ?>" dir="ltr"><![endif]-->
<!--[if gt IE 8]><!--><html class="no-js" lang="<?= $_SESSION['lang'] ?>" dir="ltr"><!--<![endif]-->
	<head>
		<meta charset="utf-8">
		<!-- Web Experience Toolkit (WET) / Boîte à outils de l'expérience Web (BOEW) wet-boew.github.io/wet-boew/License-en.html / wet-boew.github.io/wet-boew/Licence-fr.html -->
		<title><?= htmlspecialchars($lang['page_title']) ?></title>
		<meta content="width=device-width,initial-scale=1" name="viewport">
		<!-- Meta data -->
		<meta name="description" content="<?= htmlspecialchars($lang['page_description']) ?>">
		<!-- Meta data-->
		<?php 
		include 'includes/refTop.php';
		?>
	</head>
	<body vocab="https://schema.org/" typeof="WebPage">
		<div id="def-top">
		</div>
		<?php 
		if ($_SESSION['lang'] == 'fr') {
			include 'includes/appTop-fr.php';
		} else {
			include 'includes/appTop.php';
		}
		?>
		<main role="main" property="mainContentOfPage" class="container">
			<h1 property="name" id="wb-cont"><?= htmlspecialchars($lang['main_heading']) ?></h1>
			<?php 
			if ($status == 'success') {
			?>
			<section class="alert alert-success">
				<h2><?= htmlspecialchars($lang['success_heading']) ?></h2>
				<ul>
					<li><?= htmlspecialchars($lang['success_message']) ?></li>
				</ul>
			</section>
			<?php
			} elseif ($status == 'failed') {
			?>
			<section class="alert alert-danger">
				<h2><?= htmlspecialchars($lang['failed_heading']) ?></h2>
				<ul>
					<li><?= htmlspecialchars($lang['failed_message']) ?></li>
				</ul>
			</section>
			<?php
			} elseif ($status == 'duplicate') {
			?>
			<section class="alert alert-danger">
				<h2><?= htmlspecialchars($lang['failed_heading']) ?></h2>
				<ul>
					<li><?= htmlspecialchars($lang['duplicate_message']) ?></li>
				</ul>
			</section>
			<?php
			}
			?>
			
			<form method="post" action="/addrequest.php?lang=<?= $_SESSION['lang'] ?>">
			<div class="form-group">
				<label for="requestid"><span class="field-name"><?= htmlspecialchars($lang['request_id']) ?> <strong><?= htmlspecialchars($lang['required']) ?></strong></span></label>
				<input type="text" class="form-control" id="requestid" name="requestid" value="" required>
			</div>
			<div class="form-group">
				<label for="requesttitle"><span class="field-name"><?= htmlspecialchars($lang['request_title']) ?> <strong><?= htmlspecialchars($lang['required']) ?></strong></span></label>
				<input type="text" class="form-control" id="requesttitle" name="requesttitle" value="" required>
			</div>
			<div class="form-group">
				<label for="clientlname"><span class="field-name"><?= htmlspecialchars($lang['client_lname']) ?></span></label>
				<input type="text" class="form-control" id="clientlname" name="clientlname" value="">
			</div>
			<div class="form-group">
				<label for="clientfname"><span class="field-name"><?= htmlspecialchars($lang['client_fname']) ?></span></label>
				<input type="text" class="form-control" id="clientfname" name="clientfname" value="">
			</div>
			<div class="form-group">
				<label for="clientemail"><span class="field-name"><?= htmlspecialchars($lang['client_email']) ?></span></label>
				<input type="email" class="form-control" id="clientemail" name="clientemail" value="">
			</div>
			<div class="form-group">
				<label for="clientphone"><span class="field-name"><?= htmlspecialchars($lang['client_phone']) ?></span></label>
				<input type="tel" data-rule-phoneUS="true" class="form-control" id="clientphone" name="clientphone" value="">
			</div>
			<div class="form-group">
				<label for="sourceid"><span class="field-name"><?= htmlspecialchars($lang['request_source']) ?> <strong><?= htmlspecialchars($lang['required']) ?></strong></span></label>
				<select class="form-control" id="sourceid" name="sourceid" required>
					<option value=""><?= htmlspecialchars($lang['select_source']) ?></option>
					<?php 
					$nameField = $_SESSION['lang'] == 'fr' ? 'namefr' : 'nameen';
					$orderBy = $_SESSION['lang'] == 'fr' ? 'namefr' : 'nameen';
					$sql2 = "SELECT * FROM tblsources WHERE status='1' ORDER BY $orderBy ASC";
					$result2 = mysqli_query($link,$sql2);	
					while($row2 = mysqli_fetch_array($result2)){
					?>
						<option value="<?php echo $row2['id']; ?>"><?php echo $row2[$nameField]; ?></option>
					<?php
					}
					?>
				</select>
			</div>
			<div class="form-group">
				<label for="datereceived"><span class="field-name"><?= htmlspecialchars($lang['date_received']) ?> <strong><?= htmlspecialchars($lang['required']) ?></strong></span></label>
				<input type="date" class="form-control" id="datereceived" name="datereceived" min="<?php echo date('Y-m-d', strtotime('-1 years'));?>" max="<?php echo date('Y-m-d', strtotime('+1 years'));?>" required />
			</div>
			<div class="form-group">
				<label for="dateupdated"><span class="field-name"><?= htmlspecialchars($lang['date_updated']) ?></span></label>
				<input type="date" class="form-control" id="dateupdated" name="dateupdated" min="<?php echo date('Y-m-d', strtotime('-1 years'));?>" max="<?php echo date('Y-m-d', strtotime('+1 years'));?>" />
			</div>
			<div class="form-group">
				<label for="daterequired"><span class="field-name"><?= htmlspecialchars($lang['date_required']) ?></span></label>
				<input type="date" class="form-control" id="daterequired" name="daterequired" min="<?php echo date('Y-m-d', strtotime('-1 years'));?>" max="<?php echo date('Y-m-d', strtotime('+1 years'));?>" />
			</div>
			<div class="form-group">
				<label for="dateresolved"><span class="field-name"><?= htmlspecialchars($lang['date_resolved']) ?></span></label>
				<input type="date" class="form-control" id="dateresolved" name="dateresolved" min="<?php echo date('Y-m-d', strtotime('-1 years'));?>" max="<?php echo date('Y-m-d', strtotime('+1 years'));?>" />
			</div>
			<div class="form-group">
				<label for="statusid"><span class="field-name"><?= htmlspecialchars($lang['status']) ?> <strong><?= htmlspecialchars($lang['required']) ?></strong></span></label>
				<select class="form-control" id="statusid" name="statusid" required>
					<option value=""><?= htmlspecialchars($lang['select_status']) ?></option>
					<?php 
					$nameField = $_SESSION['lang'] == 'fr' ? 'namefr' : 'nameen';
					$orderBy = $_SESSION['lang'] == 'fr' ? 'namefr' : 'nameen';
					$sql2 = "SELECT * FROM tblstatus WHERE status='1' ORDER BY $orderBy ASC";
					$result2 = mysqli_query($link,$sql2);	
					while($row2 = mysqli_fetch_array($result2)){
					?>
						<option value="<?php echo $row2['id']; ?>"><?php echo $row2[$nameField]; ?></option>
					<?php
					}
					?>
				</select>
			</div>
			<div class="form-group">
				<label for="nsd"><span class="field-name"><?= htmlspecialchars($lang['nsd_ticket']) ?></span></label>
				<input type="text" class="form-control" id="nsd" name="nsd" value="">
			</div>
			<div class="form-group">
				<label for="catalogueid"><span class="field-name"><?= htmlspecialchars($lang['catalogue_name']) ?> <strong><?= htmlspecialchars($lang['required']) ?></strong></span></label>
				<select class="form-control" id="catalogueid" name="catalogueid" onchange="ajax1(this.value)" required>
					<option value=""><?= htmlspecialchars($lang['select_catalogue']) ?></option>
					<?php 
					$nameField = $_SESSION['lang'] == 'fr' ? 'namefr' : 'nameen';
					$orderBy = $_SESSION['lang'] == 'fr' ? 'namefr' : 'nameen';
					$sql2 = "SELECT * FROM tblcatalogue WHERE status='1' ORDER BY $orderBy ASC";
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
			<div class="form-group">
				<label for="clientnotes"><span class="field-name"><?= htmlspecialchars($lang['client_notes']) ?></span></label>
				<textarea class="form-control" id="clientnotes" name="clientnotes" cols="50" rows="10"></textarea>
			</div>
			<div class="form-group">
				<label for="adminnotes"><span class="field-name"><?= htmlspecialchars($lang['admin_notes']) ?></span></label>
				<textarea class="form-control" id="adminnotes" name="adminnotes" cols="50" rows="10"></textarea>
			</div>
			<div class="form-group form-buttons">
				<button type="submit" class="btn btn-default"><?= htmlspecialchars($lang['add_request']) ?></button>
			</div>
			</form>			
			
			<div id="def-preFooter">
			</div>
			<?php include 'includes/preFooter.php';?>
		</main>
		<div id="def-footer">
		</div>
		<?php include 'includes/appFooter.php';?>
	</body>
	<script>
	// Get current language from session
	var currentLang = '<?= $_SESSION['lang'] ?>';
	
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
</html>
<?php
// Close connection
mysqli_close($link);
?>
