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
if ($_SESSION['is_superuser'] OR $_SESSION['is_admin'] OR $_SESSION['atype'] == 3 OR $_SESSION['atype'] == 4 OR $_SESSION['atype'] == 6) 
{
	
}
else
{
	header("location:/newrequest.php?lang={$_SESSION['lang']}&status=accessdenied"); 
	exit();
}





if($_SERVER['REQUEST_METHOD'] == 'POST'){



    if (!empty($_POST['requestid']))
	{
		$requestid = mysqli_real_escape_string($link,$_POST['requestid']);
	}
	else
	{
		$requestid = "";
	}
    if (!empty($_POST['toclose']) ||  $_POST['toclose'] == "0" || $_POST['toclose'] == 0)
	{
		$toclose = mysqli_real_escape_string($link,$_POST['toclose']);
	}
	else
	{
		$toclose = "1";
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

    // New query to select the highest id
    $result_max_id = mysqli_query($link, "SELECT MAX(requestid) AS max_id FROM tbltriage");
    $row_max_id = mysqli_fetch_array($result_max_id);
    $max_id = $row_max_id['max_id'];

    // Increment the requestid by 1
    $new_requestid =  $max_id + 1;
   



    // Prepare the SQL statement to insert the new row
	$sql_insert = "INSERT INTO tbltriage (requestid, title, clientlname, clientfname, clientemail, clientphone, requestlang, sourceid, datereceived, dateupdated, daterequired, dateresolved, slatimer, statusid, bdm, catalogueid, serviceid, subserviceid, attach1, attach2, attach3, creatorid, updaterid, workerid, closesla, pastsla, cssurvey, project_id, audience_id, triage_population, conformance_id, triage_maturity, triage_management, tech_id, priority_score, status, isreaudit, ipaddress, exactTime, firstsprintenddate, firstsprintstartdate, sprintdefects, sprintschedule, navigation_data_id, technology_id, expectedUsers_id, technologySecVal)
		SELECT $new_requestid, title, clientlname, clientfname, clientemail, clientphone, COALESCE(requestlang, 'en'), COALESCE(sourceid, 0), CURDATE(), dateupdated, daterequired, dateresolved, CURDATE(), 1 , COALESCE(bdm, 0), COALESCE($catalogueid, 0), COALESCE($serviceid, 0), COALESCE($subserviceid, 0), attach1, attach2, attach3, COALESCE(creatorid, 0), COALESCE(updaterid, 0), COALESCE(workerid, 0), COALESCE(closesla, 0), COALESCE(pastsla, 0), COALESCE(cssurvey, 0), COALESCE(project_id, 0), COALESCE(audience_id, 0), COALESCE(triage_population, 0), COALESCE(conformance_id, 0), COALESCE(triage_maturity, 0), COALESCE(triage_management, 0), COALESCE(tech_id, 0), COALESCE(priority_score, 0), status, isreaudit, ipaddress, exactTime, firstsprintenddate, firstsprintstartdate, sprintdefects, sprintschedule, navigation_data_id, technology_id, expectedUsers_id, technologySecVal
    FROM tbltriage
    WHERE id = '$requestid';
    ";
    if (mysqli_query($link, $sql_insert)) {
        if($toclose == "1" || $toclose == 1){
            $sql_update = "UPDATE tbltriage SET statusid = 2 where id = '$requestid'";
            mysqli_query($link,$sql_update);
        }     
		$indexPage = ($_SESSION['lang'] === 'fr') ? 'index-fr.php' : 'index-en.php';
		header("location:/$indexPage");
		exit();
    } else {
		error_log('Request clone failed: ' . mysqli_error($link));
		$newrequestPage = ($_SESSION['lang'] === 'fr') ? 'newrequest-fr.php' : 'newrequest-en.php';
		header("location:/$newrequestPage?status=accessdenied");
		exit();
    }







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
			// Check if catalogueid is not empty
			if ($catalogueid!="") 
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
