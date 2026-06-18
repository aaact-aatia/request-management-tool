<?php
/**
 * Status Report Page
 */

// Grab HTTPS check
require('includes/httpscheck.php');

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

require('includes/sla-calculator.php');

// Include file for calculating business days
require('includes/calculate-bdays.php');

// Check login
require('includes/loggedincheck.php');

if (!empty($_GET['status'])){
	$status = $_GET['status'];
}
else{
	$status = "";
}

// Initialize date variables
$sdate = "";
$edate = "";

// Process the add product form
if ($_SERVER['REQUEST_METHOD']=='POST'){
	// Set no search value
	$cSearch = false;
	$SQLSV = "";
	$strCat = "";
	
	$sdate = mysqli_real_escape_string($link,$_POST['sdate']);
	$edate = mysqli_real_escape_string($link,$_POST['edate']);
	// Check if catalogue name was specified and store variables
	if(!empty($_POST['catalogueid'])) {
		$cSearch = true;
		$catalogueidstring = "";
		foreach($_POST['catalogueid'] as $catalogueid) {
			$catalogueidstring .= $catalogueid . ",";
		}
		$catalogueidstring = rtrim($catalogueidstring, ',');
		$strCat = "catalogueid IN ($catalogueidstring)";
		//echo "Selected teams : " . $teamstring;
		$cataloguename = $strCat;
	}
	
	// Double check if someone got here without posting the form
	if ($sdate=="" || $edate=="") {
		header("location:/reports.php?lang=" . $_SESSION['lang'] . "&status=statuserror"); 
		exit();
	}
}

// Double check if someone got here without posting the form
if ($sdate=="" || $edate=="") {
	header("location:/reports.php?lang=" . $_SESSION['lang'] . "&status=statuserror"); 
	exit();
}

// Determine which database column to use for name field
$nameColumn = ($_SESSION['lang'] === 'fr') ? 'namefr' : 'nameen';

// Load config
require_once 'includes/config.php';

// Page-specific metadata
$pageTitle = $langFile['report_status_page_title'];
$pageDescription = '';

include 'includes/template/head.php';
include 'includes/template/header.php';
?>
		<main role="main" property="mainContentOfPage" class="container">
			<h1 property="name" id="wb-cont"><?= htmlspecialchars($langFile['report_status_heading']) ?> <?php echo $sdate;?> <?= htmlspecialchars($langFile['report_status_date_separator']) ?> <?php echo $edate;?></h1>
			
			<dl>
				<dt><?= htmlspecialchars($langFile['report_status_current_open']) ?></dt>
				<?php
				// Get current number of open tickets
				if ($cSearch) {
					$sql = "SELECT * FROM tbltriage WHERE $strCat AND status = '1' AND (statusid='1' OR statusid='3' OR statusid='5' OR statusid='6' OR statusid='7' OR statusid='8' OR statusid='10' OR statusid='11' OR statusid='12')";
				} else {
					$sql = "SELECT * FROM tbltriage WHERE status = '1' AND (statusid='1' OR statusid='3' OR statusid='5' OR statusid='6' OR statusid='7' OR statusid='8' OR statusid='10' OR statusid='11' OR statusid='12')";
				}
				//echo $sql;
				
				$result = mysqli_query($link,$sql);
				$num_rows = mysqli_num_rows($result);
				?>
				<dd><?php echo $num_rows; ?></dd>
				<dt><?= htmlspecialchars($langFile['report_status_current_onhold']) ?></dt>
				<?php
				// Get current number tickets On hold
				if ($cSearch) {
					$sql = "SELECT * FROM tbltriage WHERE $strCat AND status = '1' AND (statusid='5')";
				} else {
					$sql = "SELECT * FROM tbltriage WHERE status = '1' AND (statusid='5')";
				}
				$result = mysqli_query($link,$sql);
				$num_rows = mysqli_num_rows($result);
				?>
				<dd><?php echo $num_rows; ?></dd>
				<dt><?= htmlspecialchars($langFile['report_status_closed_total']) ?></dt>
				<?php
				// Get current number tickets closed total
				if ($cSearch) {
					$sql = "SELECT * FROM tbltriage WHERE $strCat AND status = '1' AND (dateresolved BETWEEN '$sdate' AND '$edate')";
				} else {
					$sql = "SELECT * FROM tbltriage WHERE status = '1' AND (dateresolved BETWEEN '$sdate' AND '$edate')";
				}
				$result = mysqli_query($link,$sql);
				$num_rows = mysqli_num_rows($result);
				?>
				<dd><?php echo $num_rows; ?></dd>
				<dt><?= htmlspecialchars($langFile['report_status_new_opened']) ?></dt>
				<?php
				// Get current number
				if ($cSearch) {
					$sql = "SELECT * FROM tbltriage WHERE $strCat AND status = '1' AND (datereceived BETWEEN '$sdate' AND '$edate')";
				} else {
					$sql = "SELECT * FROM tbltriage WHERE status = '1' AND (datereceived BETWEEN '$sdate' AND '$edate')";
				}
				$result = mysqli_query($link,$sql);
				$num_rows = mysqli_num_rows($result);
				?>
				<dd><?php echo $num_rows; ?></dd>
				<dt><?= htmlspecialchars($langFile['report_status_avg_response']) ?></dt>
				<?php
				// Get all the closed tickets
				// Set variables first
				$avgTime = 0;
				$countRequests = 0;
				$totalBdays = 0;
				
				// Construct SQL
				if ($cSearch) {
					$sql = "SELECT * FROM tbltriage WHERE $strCat AND status = '1' AND (dateresolved BETWEEN '$sdate' AND '$edate')";
				} else {
					$sql = "SELECT * FROM tbltriage WHERE status = '1' AND (dateresolved BETWEEN '$sdate' AND '$edate')";
				}
				$result = mysqli_query($link,$sql);
				if(mysqli_num_rows($result)>0){
					while($row = mysqli_fetch_array($result)){
						// Grab the start date
						$slatimer = $row['slatimer'];

						if ($slatimer=="" || is_null($slatimer)) {
							$datereceived = $row['datereceived'];
						} else {
							$datereceived = $slatimer;
						}						
						//echo "Date received = " . $datereceived . " /";
						$dateresolved = $row['dateresolved'];
						//echo "Date resolved = " . $dateresolved . " /";
						$cBdays = calculateSLA($link, $row['requestid'], $datereceived,$dateresolved);
                       
						// Add 1 to the count
						//echo "Business day count = " . $cBdays . " /";
						$countRequests = $countRequests + 1;
						//echo "Request count = " . $countRequests . " /";
						$totalBdays = $totalBdays + $cBdays;
						//echo "Row ID: ".$row['requestid']." Date recieved: ".$datereceived." Date resolved: ".$dateresolved." Count Request: ".$countRequests." Total Days so far ".$totalBdays." and sla timer " . $slatimer ."<br />";
						//echo "Total business days (acc) = " . $totalBdays . "<br />";
					}
					
					//echo "Total bdays = " . $totalBdays . " and total count is " . $countRequests. "<br />";
					$avgTime = $totalBdays / $countRequests;
					//echo "Average time = " . $avgtime;
				}
				?>
				<dd><?php echo round($avgTime, 3); ?> <?= htmlspecialchars($langFile['report_status_business_days']) ?></dd>			
			</dl>
				
			<?php
			//percentageOfTotalDays, avgDaysPerOccurence,avgOccurence,avgDays

			$result = calculateStatusAvg($link, $sdate, $edate, $strCat);
$statusAverages = $result["statusAverages"];
$overallAverages = $result["overallAverages"];
$requestDetailsJSON = $result["requestDetails"]; // This is in JSON format.

// Display the request details
//echo '<div id="request-details" style="margin-bottom: 20px;">';
//echo '<h4>Request Details:</h4>';//
//echo '<pre>' . htmlspecialchars($requestDetailsJSON) . '</pre>'; // safely render JSON
//echo '</div>';

// Start generating the table
echo '<table class="wb-tables table table-striped" data-ordering="false">';
echo '<thead><tr>
        <th data-orderable="false">' . htmlspecialchars($langFile['report_status_table_status']) . '</th>
        <th>' . htmlspecialchars($langFile['report_status_table_avg_time']) . '</th>
        <th>' . htmlspecialchars($langFile['report_status_table_avg_occurrence']) . '</th>
        <th>' . htmlspecialchars($langFile['report_status_table_occurrence_pct']) . '</th>
        <th>' . htmlspecialchars($langFile['report_status_table_total_days_pct']) . '</th>
      </tr></thead>';
echo '<tbody>';

// Specify the desired order explicitly
$preferredOrder = [
    "Information Gathering",
    "Unassigned",
    "Assigned",
    "Reassigned",
    "Escalated",
    "In progress",
    "Return to client",
    "On hold"
];

// Loop through the preferred order and output the averages
foreach ($preferredOrder as $statusName) {
    if (isset($statusAverages[$statusName])) {
        $avg = $statusAverages[$statusName];

        $averageTimeFormatted = number_format($avg["avgDays"], 3);
        $avgDaysPerOccurrenceFormatted = number_format($avg["avgDaysPerOccurrence"], 3);
        $avgOccurrenceFormatted = number_format($avg["avgOccurrence"], 3) . '%';
        $percentageOfTotalDaysFormatted = number_format($avg["percentageOfTotalDays"], 3) . '%';

        echo "<tr>
                <td>{$statusName}</td>
                <td>{$averageTimeFormatted}</td>
                <td>{$avgDaysPerOccurrenceFormatted}</td>
                <td>{$avgOccurrenceFormatted}</td>
                <td>{$percentageOfTotalDaysFormatted}</td>
              </tr>";
    }
}

echo '</tbody></table>';

// Optionally, echo an overall summary.
//echo '<div style="margin-top:20px;">Overall Average Days Per Request: ' 
//     . number_format($overallAverages["avgOverallDaysPerRequest"], 2)
//     . ' days (from ' . $overallAverages["totalRequests"] . ' requests).</div>';
?>
<script src="/public/js/report-status.js"></script>
			<?php
			// Display only if cSearch is false
			if ($cSearch==false) {
			?>
			<table class="wb-charts wb-charts-pie table" data-flot='{"series": { "pie": { "radius": 1,"label": { "radius": 1,"show": true } } } }'>
				<caption><?= htmlspecialchars($langFile['report_status_chart_caption']) ?></caption>
				<tr>
					<td></td>
					<?php
					// Now we need to display the amount of requests per service catalogue item
					// Construct SQL
					if ($cSearch) {
						$sql = "SELECT catalogueid, COUNT(*) FROM tbltriage WHERE $strCat AND status = '1' AND (datereceived BETWEEN '$sdate' AND '$edate')";
					} else {
						$sql = "SELECT catalogueid, COUNT(*) FROM tbltriage WHERE status = '1' AND (datereceived BETWEEN '$sdate' AND '$edate') GROUP BY catalogueid";
					}
					$result = mysqli_query($link,$sql);
					if(mysqli_num_rows($result)>0){
						while($row = mysqli_fetch_array($result)){
							// Get catalogueid name
							$catalogueid = $row['catalogueid'];
							$result2 = mysqli_query($link, "SELECT $nameColumn FROM tblcatalogue WHERE id = '$catalogueid'");
							$row2 = mysqli_fetch_array($result2);
							$cataloguename = $row2[0];
					?>
					<th><?php echo $cataloguename; ?></th>
					<?php
						}
					}
					?>
				</tr>
				<tr>
					<th><?= htmlspecialchars($langFile['report_status_total_count']) ?></th>
					<?php
					// Now we need to display the amount of requests per service catalogue item
					// Construct SQL
					if ($cSearch) {
						$sql = "SELECT catalogueid, COUNT(*) FROM tbltriage WHERE $strCat AND status = '1' AND (datereceived BETWEEN '$sdate' AND '$edate')";
					} else {
						$sql = "SELECT catalogueid, COUNT(*) FROM tbltriage WHERE status = '1' AND (datereceived BETWEEN '$sdate' AND '$edate') GROUP BY catalogueid";
					}
					$result = mysqli_query($link,$sql);
					if(mysqli_num_rows($result)>0){
						while($row = mysqli_fetch_array($result)){
							// Get catalogueid name
							$totalCount = $row[1];
					?>
					<td><?php echo $totalCount; ?></td>
					<?php
						}
					}
					?>
				</tr>
			</table>
			<?php
			}
			?>
<?php include 'includes/template/page-details.php'; ?>
		</main>
<?php 
include 'includes/template/footer.php';
include 'includes/template/scripts.php';

// Close connection
mysqli_close($link);
