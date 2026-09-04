<?php
/**
 * Consolidated Bilingual Clone Request Page
 * 
 * This page replaces the separate clonerequest-en.php and clonerequest-fr.php files
 * by using a language file system. The language is determined by $_SESSION['lang'].
 * 
 * @package RMT
 * @since 2.0.0
 */

require_once __DIR__ . '/includes/session_start.php';

// Grab HTTPS check
require('includes/httpscheck.php');

// Grab MySQL connection
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

// Check login
require('includes/loggedincheck.php');

// Check if the user has the right priv's
if (canCloneRequests())
{
	
}
else
{
	header("location:/newrequest.php?lang={$_SESSION['lang']}&status=accessdenied"); 
	exit();
}





if($_SERVER['REQUEST_METHOD'] == 'POST'){
	$requestid = filter_var($_POST['requestid'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'default' => 0]]);
	$toclose = (int) ($_POST['toclose'] ?? 1);
	$catalogueid = (int) ($_POST['catalogueid'] ?? 0);
	$serviceid = (int) ($_POST['serviceid'] ?? 0);
	$subserviceid = (int) ($_POST['subserviceid'] ?? 0);

	$selection = rmt_validate_intake_selection($link, $catalogueid, $serviceid, $subserviceid);
	if ($requestid <= 0 || $selection === null) {
		header("Location: /clonerequest.php?lang={$_SESSION['lang']}&status=failed");
		exit();
	}
	$sourceRequest = rmt_db_fetch_one($link, 'SELECT requestid FROM tbltriage WHERE id = ?', 'i', [$requestid]);
	if (!$sourceRequest) {
		header("Location: /clonerequest.php?lang={$_SESSION['lang']}&status=failed");
		exit();
	}

	$year = date('y');
	$requestPattern = "REQ-{$year}-%";
	$sequenceRow = rmt_db_fetch_one(
		$link,
		'SELECT MAX(CAST(SUBSTRING(requestid, 8) AS UNSIGNED)) AS max_seq
		 FROM tbltriage
		 WHERE requestid LIKE ?',
		's',
		[$requestPattern]
	);
	$sequence = ((int) ($sequenceRow['max_seq'] ?? 0)) + 1;
	$newRequestid = sprintf('REQ-%s-%03d', $year, $sequence);

	$sqlInsert = "INSERT INTO tbltriage (requestid, title, request_subject, clientlname, clientfname, clientemail, clientphone, requestlang, datereceived, dateupdated, daterequired, dateresolved, slatimer, statusid, bdm, catalogueid, serviceid, subserviceid, attach1, attach2, attach3, creatorid, updaterid, workerid, closesla, pastsla, cssurvey, project_id, audience_id, triage_population, conformance_id, triage_maturity, triage_management, tech_id, priority_score, status, ipaddress, exactTime, firstsprintenddate, firstsprintstartdate, sprintdefects, sprintschedule)
		SELECT ?, CASE WHEN INSTR(COALESCE(title, ''), ' - ') > 0 THEN CONCAT(?, SUBSTRING(title, INSTR(title, ' - '))) ELSE ? END, request_subject, clientlname, clientfname, clientemail, clientphone, COALESCE(requestlang, 'en'), CURDATE(), dateupdated, daterequired, NULL, CURDATE(), 1, COALESCE(bdm, 0), ?, ?, ?, attach1, attach2, attach3, COALESCE(creatorid, 0), COALESCE(updaterid, 0), COALESCE(workerid, 0), COALESCE(closesla, 0), COALESCE(pastsla, 0), COALESCE(cssurvey, 0), COALESCE(project_id, 0), COALESCE(audience_id, 0), COALESCE(triage_population, 0), COALESCE(conformance_id, 0), COALESCE(triage_maturity, 0), COALESCE(triage_management, 0), COALESCE(tech_id, 0), COALESCE(priority_score, 0), status, ipaddress, exactTime, firstsprintenddate, firstsprintstartdate, sprintdefects, sprintschedule
		FROM tbltriage
		WHERE id = ?";
	$insertStatement = rmt_db_execute(
		$link,
		$sqlInsert,
		'sssiiii',
		[$newRequestid, $newRequestid, $newRequestid, $selection['catalogueid'], $selection['serviceid'], $selection['subserviceid'], $requestid]
	);
	$newRequestId = mysqli_insert_id($link);
	mysqli_stmt_close($insertStatement);
	rmt_refresh_request_catalogue_snapshot($link, (int) $newRequestId);

	$cloneLogNote = ($_SESSION['lang'] === 'fr' ? 'Clonée à partir de la demande ' : 'Cloned from request ')
		. $sourceRequest['requestid'];
	$cloneLogStatement = rmt_db_execute(
		$link,
		'INSERT INTO tblcommlog (triageid, dateadded, notes, creatorid, status) VALUES (?, CURDATE(), ?, ?, 1)',
		'isi',
		[(int) $newRequestId, $cloneLogNote, (int) ($_SESSION['pid'] ?? 0)]
	);
	mysqli_stmt_close($cloneLogStatement);

	if ($toclose === 1) {
		$closeStatement = rmt_db_execute($link, 'UPDATE tbltriage SET statusid = 5 WHERE id = ?', 'i', [$requestid]);
		mysqli_stmt_close($closeStatement);
	}

	$editRequestUrl = '/editrequest.php?lang=' . urlencode($_SESSION['lang'])
		. '&erid=' . urlencode(base64_encode((string) $newRequestId))
		. '&reqid=' . urlencode('a11y-' . $newRequestid);
	header("Location: {$editRequestUrl}");
	exit();







}else{

    // Check if there was an email request ID
if (!empty($_GET['erid']))
{
	// There is a request email id so grab it
	$requestuid = base64_decode($_GET['erid']);
}
else
{
	// Now first get the ID
	$requestuid = $_GET['id'];
}

if (isset($_GET['toClose']) && (!empty($_GET['toClose']) || $_GET['toClose'] == "0"))
{
	$toclose = $_GET['toClose'];
}
else
{
	$toclose = 1;
}

// Make sure ID is not empty
if (empty($requestuid)) 
{
	header("location:/openrequest.php?lang={$_SESSION['lang']}&status=wrongid"); 
	exit();	
}

$result2 = mysqli_query($link, "SELECT catalogueid,serviceid,subserviceid FROM tbltriage WHERE id = '$requestuid'");
	$row2 = mysqli_fetch_array($result2);
	$catalogueid = $row2[0];
	$serviceid = $row2[1];
	$subserviceid = $row2[2];

// Load config
require_once 'includes/config.php';

// Page-specific metadata
$pageTitle = $langFile['clonerequest_page_title'];
$pageDescription = '';

include 'includes/template/head.php';
include 'includes/template/header.php';
?>
    <main role="main" property="mainContentOfPage" class="container">




        <form method="POST" action="/clonerequest.php?lang=<?= $_SESSION['lang'] ?>">
            <input type="hidden" name="requestid" value="<?php echo $requestuid; ?>">
            <input type="hidden" name="toclose" value="<?php echo $toclose; ?>">
            <!-- Add other form fields here -->

            <div class="form-group">
                <label for="catalogueid"><span class="field-name"><?= htmlspecialchars($langFile['catalogue_name']) ?>
                        <strong>(<?= htmlspecialchars($langFile['required']) ?>)</strong></span></label>
                <select class="form-control" id="catalogueid" name="catalogueid" onchange="ajax1(this.value)" required>
                    <option value=""><?= htmlspecialchars($langFile['select_catalogue']) ?></option>
                    <?php 
					// Determine which field to use based on language
					$nameField = ($_SESSION['lang'] === 'fr') ? 'namefr' : 'nameen';
					$orderField = ($_SESSION['lang'] === 'fr') ? 'namefr' : 'nameen';
					
					$sql2 = "SELECT * FROM tblcatalogue WHERE status='1' ORDER BY $orderField ASC";
					$result2 = mysqli_query($link,$sql2);	
					while($row2 = mysqli_fetch_array($result2))
					{
					?>
                    <option value="<?php echo $row2['id']; ?>" <?php if ($catalogueid==$row2['id']) { ?>
                        selected<?php } ?>><?php echo $row2[$nameField]; ?></option>
                    <?php
					}
					?>
                </select>
            </div>
            <?php 
			// Only require a service when the catalogue has active services.
			$catalogueHasServices = false;
			if ($catalogueid != "") {
				$serviceCheck = mysqli_query($link, "SELECT 1 FROM tblservices WHERE catalogueid='$catalogueid' AND status='1' LIMIT 1");
				$catalogueHasServices = $serviceCheck && mysqli_num_rows($serviceCheck) > 0;
			}

			if ($catalogueHasServices)
			{
				
				
			?>
            <div class="form-group divservice">
				<label for="serviceid"><span class="field-name"><?= htmlspecialchars($langFile['service_name']) ?> <strong>(<?= htmlspecialchars($langFile['required']) ?>)</strong></span></label>
                <select class="form-control" id="serviceid" name="serviceid" onchange="ajax2(this.value)" required>
                    <option value=""><?= htmlspecialchars($langFile['select_service']) ?></option>
                    <?php 
					$sql2 = "SELECT * FROM tblservices WHERE catalogueid='$catalogueid' AND status='1' ORDER BY $orderField ASC";
					$result2 = mysqli_query($link,$sql2);	
					while($row2 = mysqli_fetch_array($result2))
					{
					?>
                    <option value="<?php echo $row2['id']; ?>" <?php if ($serviceid==$row2['id']) { ?>
                        selected<?php } ?>><?php echo $row2[$nameField]; ?></option>
                    <?php
					}
					?>
                </select>
            </div>
            <?php
			} 
			else 
			{
			?>
            <div class="form-group divservice">
            </div>
            <?php
			}
			// Check if service id is not empty
			
			// Check if service id is not empty
			if ($serviceid!="") 
			{
				// Grab the catalogue id
				
			
				// Check if results otherwise return empty result
				$sql2 = "SELECT * FROM tblsubservices WHERE serviceid='$serviceid' AND status='1' ORDER BY $orderField ASC";

				$result2 = mysqli_query($link,$sql2);
				//List it
				if(mysqli_num_rows($result2)>0 && $subserviceid != 0)
				{
			?>
            <div class="form-group divsubservice">
                <label for="subserviceid"><span class="field-name"><?= htmlspecialchars($langFile['subservice_name']) ?>
                        <strong>(<?= htmlspecialchars($langFile['required']) ?>)</strong></span></label>
                <select class="form-control" id="subserviceid" name="subserviceid" required>
                    <option value=""><?= htmlspecialchars($langFile['select_subservice']) ?></option>
                    <?php 
							$sql3 = "SELECT * FROM tblsubservices WHERE serviceid='$serviceid' AND status='1' ORDER BY $orderField ASC";
							$result3 = mysqli_query($link,$sql3);
							while($row3 = mysqli_fetch_array($result3))
							{
							?>
                    <option value="<?php echo $row3['id']; ?>" <?php if ($subserviceid==$row3['id']) { ?>
                        selected<?php } ?>><?php echo $row3[$nameField]; ?></option>
                    <?php
							}
							?>
                </select>
            </div>
            <?php
				} 
				else 
				{
				?>
            <div class="form-group divsubservice">
            </div>

                        <?php }}?>
            <input type="submit" value="<?= htmlspecialchars($langFile['clonerequest_submit']) ?>">
        </form>

<?php include 'includes/template/page-details.php'; ?>
    </main>
<?php 
include 'includes/template/footer.php';
include 'includes/template/scripts.php';

}
?>
<script src="/public/js/ajax-dropdowns.js"></script>
<?php
// Close connection
mysqli_close($link);
?>
