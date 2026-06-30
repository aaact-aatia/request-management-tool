<?php
/**
 * Client Survey Pending - Pending Customer Satisfaction Surveys
 */

// Grab MySQL connection (includes session management)
require('sql.php');
/** @var mysqli $link */

require('includes/helpers.php');

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
$pageTitle = $langFile['client_survey_pending_page_title'];
$pageDescription = '';

include 'includes/template/head.php';
include 'includes/template/header.php';
?>
		<main role="main" property="mainContentOfPage" class="container">
			<h1 property="name" id="wb-cont"><?= htmlspecialchars($langFile['client_survey_pending_heading']) ?></h1>
			
			<?php
			// Grab only the last 500 requests
			// Get the latest id first
			$result = mysqli_query($link, "SELECT MAX(id) FROM tbltriage");
			$row = mysqli_fetch_array($result);
			$latestid = $row[0];
			$chkid = $latestid - 500;
			$resolvedStatusIds = rmt_get_resolved_status_ids($link);
			$resolvedStatusList = !empty($resolvedStatusIds) ? implode(',', array_map('intval', $resolvedStatusIds)) : '0';
			
			// Construct SQL statement
			$sql = "SELECT tr.*
			        FROM tbltriage tr
			        WHERE tr.id > '$chkid'
			          AND tr.statusid IN ($resolvedStatusList)
			          AND (tr.cssurvey IS NULL OR tr.cssurvey = 0)
			        ORDER BY tr.id DESC LIMIT 250";
			
			$result = mysqli_query($link,$sql);
			//List it
			if(mysqli_num_rows($result)>0){
			?>
			<table class="wb-tables table table-striped table-hover table-sm" data-wb-tables='{"paging": false, "columnDefs": [{ "type": "html-num", "targets": 0 }]}'>
				<thead>
				<tr>
					<th><?= htmlspecialchars($langFile['client_survey_pending_col_request']) ?></th>
					<th><?= htmlspecialchars($langFile['client_survey_pending_col_title']) ?></th>
					<th><?= htmlspecialchars($langFile['client_survey_pending_col_actions']) ?></th>
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
				?>
				<tr>
					<td><a href="viewrequest.php?lang=<?= $_SESSION['lang'] ?>&erid=<?php echo base64_encode($requestid);?>&reqid=<?php echo urlencode('a11y-' . $requesttid);?>">a11y-<?php echo $requesttid ?> <span class="glyphicon glyphicon-eye-open"></span><span class="wb-inv"><?= htmlspecialchars($langFile['client_survey_pending_details']) ?></span></a></td>
					<td><?php echo $title ?></td>
					<td><a class="btn btn-primary mrgn-bttm-sm" href="/client-survey-link.php?lang=<?= htmlspecialchars($_SESSION['lang']) ?>&erid=<?php echo $nrequestid; ?>"><?= htmlspecialchars($langFile['client_survey_pending_view_links']) ?></a></td>
				</tr>
			<?php 
					}
				} 
			?>
				</tbody>
			</table>
			
			<?php } else { ?>
			<p><strong><?= htmlspecialchars($langFile['client_survey_pending_no_surveys']) ?></strong></p>
			<?php } ?>
			
<?php include 'includes/template/page-details.php'; ?>
		</main>
<?php 
include 'includes/template/footer.php';
include 'includes/template/scripts.php';

// Close connection
mysqli_close($link);
