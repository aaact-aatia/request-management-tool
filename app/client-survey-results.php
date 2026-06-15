<?php
/**
 * Client Survey Results - Customer Satisfaction Survey Results
 */

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
$lang = $_SESSION['lang'];
$langFile = require("lang/{$_SESSION['lang']}.php");

// Grab HTTPS check
require('includes/httpscheck.php');

// Check login
require('includes/loggedincheck.php');

// Load config
require_once 'includes/config.php';

// Page-specific metadata
$pageTitle = $langFile['client_survey_results_page_title'];
$pageDescription = '';

include 'includes/template/head.php';
include 'includes/template/header.php';
?>
		<main role="main" property="mainContentOfPage" class="container">
			<h1 property="name" id="wb-cont"><?= htmlspecialchars($langFile['client_survey_results_heading']) ?></h1>
			
			<?php
			// Construct SQL statement
			$sql = "SELECT * FROM tblcss WHERE status = '1' ORDER BY requestid DESC LIMIT 200";
			//echo $sql;
			
			$result = mysqli_query($link,$sql);
			//List it
			if(mysqli_num_rows($result)>0){
			?>
			<table class="wb-tables table table-striped table-hover table-sm" data-wb-tables='{"paging": false, "columnDefs": [{ "type": "html-num", "targets": 0 }]}'>
				<thead>
				<tr>
					<th><?= htmlspecialchars($langFile['client_survey_results_request_num']) ?></th>
					<th><?= htmlspecialchars($langFile['client_survey_results_title']) ?></th>
					<th><?= htmlspecialchars($langFile['client_survey_results_overall']) ?></th>
					<th><?= htmlspecialchars($langFile['client_survey_results_response']) ?></th>
					<th><?= htmlspecialchars($langFile['client_survey_results_comments']) ?></th>
				</tr>
				</thead>
				<tbody>
				<?php
				while($row = mysqli_fetch_array($result)){
					// Check if clientlname or clientfname is not empty
					$requestid = $row['requestid'];
					$overall = $row['overall'];
					$response = $row['response'];
					$comments = $row['comments'];
										
					// Get request title
					$result2 = mysqli_query($link, "SELECT requestid,title FROM tbltriage WHERE id = '$requestid'");
					$row2 = mysqli_fetch_array($result2);
					$requestidnum = $row2[0];
					$title = $row2[1];
					
					// Build link to viewrequest page
					$viewRequestLink = "viewrequest.php?erid=" . base64_encode($requestid) . "&lang=" . $_SESSION['lang'] . "&reqid=" . urlencode("a11y-" . $requestidnum);
				?>
				<tr>
					<td><a href="<?= $viewRequestLink ?>">a11y-<?php echo $requestidnum ?> <span class="glyphicon glyphicon-eye-open"></span><span class="wb-inv"><?= htmlspecialchars($langFile['client_survey_results_details']) ?></span></a></td>
					<td><?php echo $title ?></td>
					<td><?php echo $overall ?>/10</td>
					<td><?php echo $response ?>/10</td>
					<td><?php echo $comments ?></td>
				</tr>
			<?php } ?>
				</tbody>
			</table>
			
			<?php } else { ?>
			<p><strong><?= htmlspecialchars($langFile['client_survey_results_no_surveys']) ?></strong></p>
			<?php } ?>
			
<?php include 'includes/template/page-details.php'; ?>
		</main>
<?php 
include 'includes/template/footer.php';
include 'includes/template/scripts.php';

// Close connection
mysqli_close($link);
