<?php
// Grab MySQL connection (includes session management)
require('sql.php');
/** @var mysqli $link */

// Handle language from query string or session
if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'fr'])) {
    $_SESSION['lang'] = $_GET['lang'];
}
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'en';
}

// Load language file
$lang = require("lang/{$_SESSION['lang']}.php");

// Construct SQL statement
$sql = "SELECT * FROM tbltriage WHERE (catalogueid='1' OR catalogueid='2' OR catalogueid='3' OR catalogueid='4')";
//echo $sql;

$result = mysqli_query($link,$sql);
//List it
if(mysqli_num_rows($result)>0){
	while($row = mysqli_fetch_array($result)){
		$requestid = $row['id'];
		$nsd = $row['nsd'];
		$nsdnote = "";
		// Skip the WS CoE services
		$serviceid = $row['serviceid'];
		if ($serviceid!='46') {
			// Now that we're here we need to update the triage ticket information
			if ($nsd==0) {
				$nsdnote = $lang['batch_ace_no_details'];
			} else {
				$nsdnote = $lang['batch_ace_see_ticket'] . $nsd . $lang['batch_ace_for_details'];
			}
			
			// Create SQL statement to update the request information
			$sql2 = "UPDATE `tbltriage` SET `clientlname` = 'CLIENT', `clientfname` = 'ACE', `clientemail` = 'ace-cea@hrsdc-rhdcc.gc.ca', `clientphone` = '' WHERE id='$requestid'";
			mysqli_query($link,$sql2);
			
			// Now update the original description
			$sql3 = "UPDATE `tblcommlog` SET `notes` = '$nsdnote' WHERE triageid='$requestid'";
			mysqli_query($link,$sql3);
		}
	}
}

// Now redirect
header("location:/index-{$_SESSION['lang']}.php?status=batchsuccess"); 
exit();

// Close connection
mysqli_close($link);
?>
