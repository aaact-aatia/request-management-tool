<?php
// This is called through ajax on the product management page

// Start session
if (session_status() != PHP_SESSION_ACTIVE)
{
	session_start();
}

// Get language
$lang = $_GET['lang'] ?? 'en';

// Check if the user has the right priv's
if ($_SESSION['atype'] != 1) {
	header("location:/openrequest-$lang.php?status=accessdenied"); 
	exit();
}

// Grab MySQL connection
require('../sql.php');

// Now first get the ID
$triageid = $_GET['rid'];
$commentid = $_GET['id'];
$type = $_GET['t'];

// Process the delete product form
if ($_SERVER['REQUEST_METHOD']=='POST'){
	
	// Create SQL statement
	if ($type=="c") {
		$sql = "UPDATE `tblcommlog` SET `status` = '0' WHERE id='$commentid'";
	} elseif ($type=="a") {
		$sql = "UPDATE `tbladminlog` SET `status` = '0' WHERE id='$commentid'";
	}
	//echo $sql;
	rmt_admin_query($link,$sql);
	
	// Now redirect
	header("location:/viewrequest-$lang.php?rid=$triageid"); 
	exit();
}

// Check if there is an ID
if ($commentid!="") {
	$title = ($lang == 'fr') ? "Supprimer le commentaire" : "Delete comment";
	$question = ($lang == 'fr') ? "Êtes-vous sûr de vouloir supprimer ce commentaire?" : "Are you sure you wish to delete this comment?";
	$buttonText = ($lang == 'fr') ? "Oui" : "Yes";
?>
<section id="filter-id" class="modal-dialog modal-content overlay-def">
	<header class="modal-header">
		<h2 class="modal-title"><?php echo $title ?></h2>
	</header>
	<div class="modal-body">
		<form method="post" action="/includes/delete-comms.php?lang=<?php echo $lang ?>&t=<?php echo $type ?>&id=<?php echo $commentid ?>&rid=<?php echo $triageid ?>">
		<p tabindex="0"><?php echo $question ?></p>
		<div class="form-group form-buttons">
			<button type="submit" class="btn btn-default"><?php echo $buttonText ?></button>
		</div>
		</form>
	</div>
</section>
<?php
} else { 
// Wrong ID so display an error message
	$errorTitle = ($lang == 'fr') ? "Oups, quelque chose s'est mal passé!" : "Oops something went wrong!";
	$errorMsg = ($lang == 'fr') ? "Désolé, une erreur s'est produite avec votre demande, veuillez réessayer!" : "Sorry something went wrong with your request, please try again!";
?>
<section id="filter-id" class="modal-dialog modal-content overlay-def">
	<header class="modal-header">
		<h2 class="modal-title"><?php echo $errorTitle ?></h2>
	</header>
	<div class="modal-body">
		<p><?php echo $errorMsg ?></p>
	</div>
</section>
<?php
}
// Close connection
mysqli_close($link);
?>
