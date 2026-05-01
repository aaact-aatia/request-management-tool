<?php
// This is called through ajax on the product management page

// Start session
if (session_status() != PHP_SESSION_ACTIVE)
{
	session_start();
}

// Determine language
$lang = $_SESSION['lang'] ?? 'en';
$translations = require(__DIR__ . '/../lang/' . $lang . '.php');

// Grab MySQL connection
require('../sql.php');

// Now first get the ID
$requestid = $_GET['id'];
$nrequestid = base64_encode($requestid);

// Process the delete product form
if ($_SERVER['REQUEST_METHOD']=='POST'){
	// Grab the current value
	$result3 = mysqli_query($link, "SELECT cssurvey FROM tbltriage WHERE id = '$requestid'");
	$row3 = mysqli_fetch_array($result3);
	$cssurvey = $row3[0];
	if ($cssurvey==0 OR is_null($cssurvey)) {
		$cssurveyval = 1;
	} else {
		$cssurveyval = $cssurvey + 1;
	}
	// Now set the css data entry to 1
	$sql = "UPDATE `tbltriage` SET `cssurvey` = '$cssurveyval' WHERE id='$requestid'";
	mysqli_query($link,$sql);
	
	// Now redirect
	$viewrequest_page = $lang === 'fr' ? '/viewrequest-fr.php' : '/viewrequest-en.php';
	header("location:" . $viewrequest_page . "?erid=".$nrequestid."&status=csssent"); 
	exit();
}

// Construct SQL statement
$sql2 = "SELECT id FROM tbltriage WHERE id='$requestid'";

$result2 = mysqli_query($link,$sql2);
//List it
if(mysqli_num_rows($result2)>0){
	while($row2 = mysqli_fetch_array($result2)){
?>
<section id="filter-id" class="modal-dialog modal-content overlay-def">
	<header class="modal-header">
		<h2 class="modal-title"><?php echo $translations['css_sent_title'] ?? ($lang === 'fr' ? 'Marquer le sondage comme envoyée' : 'Mark survey as sent'); ?></h2>
	</header>
	<div class="modal-body">
		<form method="post" action="/includes/css-sent.php?id=<?php echo $row2['id'] ?>">
		<p tabindex="0"><?php echo $translations['css_sent_confirm'] ?? ($lang === 'fr' ? 'Voulez-vous vraiment marquer le sondage comme envoyée?' : 'Are you sure you want to mark this survey as sent?'); ?></p>
		<div class="form-group form-buttons">
			<button type="submit" class="btn btn-default"><?php echo $translations['yes'] ?? ($lang === 'fr' ? 'Oui' : 'Yes'); ?></button>
		</div>
		</form>
	</div>
</section>
<?php
	}
} else { 
// Wrong ID so display an error message
?>
<section id="filter-id" class="modal-dialog modal-content overlay-def">
	<header class="modal-header">
		<h2 class="modal-title"><?php echo $translations['error_heading'] ?? ($lang === 'fr' ? 'Oups, quelque chose s\'est mal passé!' : 'Oops something went wrong!'); ?></h2>
	</header>
	<div class="modal-body">
		<p><?php echo $translations['error_message'] ?? ($lang === 'fr' ? 'Désolé, une erreur s\'est produite avec votre demande, veuillez réessayer!' : 'Sorry something went wrong with your request, please try again!'); ?></p>
	</div>
</section>
<?php
}
// Close connection
mysqli_close($link);
?>
