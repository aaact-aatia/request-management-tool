<?php
/**
 * CSS Pending - Pending Customer Satisfaction Surveys
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
$lang = $_SESSION['lang'];
$langFile = require("lang/{$_SESSION['lang']}.php");

// Grab HTTPS check
require('includes/httpscheck.php');

// Security check
require('includes/loggedincheck.php');

// Load config
require_once 'includes/config.php';

// Page-specific metadata
$pageTitle = $langFile['css_pending_page_title'];
$pageDescription = '';

include 'includes/template/head.php';
include 'includes/template/header.php';
?>
		<main role="main" property="mainContentOfPage" class="container">
			<h1 property="name" id="wb-cont"><?= htmlspecialchars($langFile['css_pending_heading']) ?></h1>
			
			<?php
			// Grab only the last 500 requests
			// Get the latest id first
			$result = mysqli_query($link, "SELECT MAX(id) FROM tbltriage");
			$row = mysqli_fetch_array($result);
			$latestid = $row[0];
			$chkid = $latestid - 500;
			
			// Construct SQL statement
			$sql = "SELECT * FROM tbltriage WHERE id > '$chkid' AND statusid = '2' AND cssurvey IS NULL ORDER BY id DESC LIMIT 250";
			
			$result = mysqli_query($link,$sql);
			//List it
			if(mysqli_num_rows($result)>0){
			?>
			<table class="wb-tables table table-striped table-hover table-sm" data-wb-tables='{"paging": false, "columnDefs": [{ "type": "html-num", "targets": 0 }]}'>
				<thead>
				<tr>
					<th><?= htmlspecialchars($langFile['css_pending_col_request']) ?></th>
					<th><?= htmlspecialchars($langFile['css_pending_col_title']) ?></th>
					<th><?= htmlspecialchars($langFile['css_pending_col_actions']) ?></th>
				</tr>
				</thead>
				<tbody>
				<?php
				while($row = mysqli_fetch_array($result)){
					$requestid = $row['id'];
					// Create encoded request ID
					$nrequestid = base64_encode($requestid);
					$requesttid = $row['requestid'];
					$title = $row['title'];
					
					// First let's check if we need to send the survey
					$catalogueid = $row['catalogueid'];
					$result2 = mysqli_query($link, "SELECT survey FROM tblcatalogue WHERE id = '$catalogueid'");
					$row2 = mysqli_fetch_array($result2);
					$survey = $row2['survey'];
					// If survey is 1 then send it
					if ($survey==1) {
						// Prepare email
						$erequestnum = $row['requestid'];
						$eclientemail = $row['clientemail'];
						$esubject = "Sondage sur la satisfaction de la clientèle pour / Client satisfaction survey for a11y-".$erequestnum;
						$ebody = "Bonjour,%0d%0a%0d%0aVotre demande d'accessibilité a été complété par un membre de notre équipe, serait-il possible pour vous de compléter sondage sur la satisfaction de la clientèle? Ce sondage nous aidera à mieux servir nos clients et ne prendra que 30 secondes à remplir.%0d%0a%0d%0ahttps://a11y.itaormt-batiogd-int.service.cloud-nuage.canada.ca/css.php?lang=fr&erid=".$nrequestid."%0d%0a%0d%0a**********************************************************%0d%0a%0d%0aHello,%0d%0a%0d%0aYour accessibility request has now been completed by one of our team members, could you please fill out the following client satisfaction survey? This survey will help us serve our clients better and will only take 30 seconds to complete.%0d%0a%0d%0ahttps://a11y.itaormt-batiogd-int.service.cloud-nuage.canada.ca/css.php?lang=en&erid=".$nrequestid."%0d%0a%0d%0aMerci / Thank you"
				?>
				<tr>
					<td><a href="viewrequest.php?lang=<?= $_SESSION['lang'] ?>&erid=<?php echo base64_encode($requestid);?>">a11y-<?php echo $requesttid ?> <span class="glyphicon glyphicon-eye-open"></span><span class="wb-inv"><?= htmlspecialchars($langFile['css_pending_details']) ?></span></a></td>
					<td><?php echo $title ?></td>
					<td><a class="btn btn-primary mrgn-bttm-sm" href="mailto:<?php echo $eclientemail ?>?subject=<?php echo $esubject ?>&body=<?php echo $ebody ?>"><?= htmlspecialchars($langFile['css_pending_generate_email']) ?></a><br /><a class="wb-lbx btn btn-primary" href="includes/css-sent.php?id=<?php echo $row['id'];?>"><?= htmlspecialchars($langFile['css_pending_mark_sent']) ?></a></td>
				</tr>
			<?php 
					}
				} 
			?>
				</tbody>
			</table>
			
			<?php } else { ?>
			<p><strong><?= htmlspecialchars($langFile['css_pending_no_surveys']) ?></strong></p>
			<?php } ?>
			
<?php include 'includes/template/page-details.php'; ?>
		</main>
<?php 
include 'includes/template/footer.php';
include 'includes/template/scripts.php';

// Close connection
mysqli_close($link);
