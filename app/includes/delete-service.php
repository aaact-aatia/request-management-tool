<?php
// This is called through ajax on the product management page

// Start session
require_once __DIR__ . '/session_start.php';

// Get language
$lang = $_GET['lang'] ?? 'en';

// Check if the user has the right priv's
if (!($_SESSION['is_superuser'] OR $_SESSION['is_admin'])) {
	header("location:/openrequest-$lang.php?status=accessdenied"); 
	exit();
}

// Grab MySQL connection
require('../sql.php');

// Now first get the ID
$serviceid = $_GET['id'];
$catalogueid = $_GET['cid'];

// Process the delete product form
if ($_SERVER['REQUEST_METHOD']=='POST'){
	
	// Now we have a service id we need to check if we have sub services
	$sql = "UPDATE `tblsubservices` SET `status` = '0' WHERE serviceid='$serviceid'";
	rmt_admin_query($link,$sql);
	
	// Now set all the services to status = 0
	$sql = "UPDATE `tblservices` SET `status` = '0' WHERE id='$serviceid'";
	rmt_admin_query($link,$sql);
	
	// Now redirect
	header("location:/catalogue-mgmt.php?lang=$lang?id=$catalogueid&status=success"); 
	exit();
}

// Construct SQL statement
$sql2 = "SELECT * FROM tblservices WHERE id='$serviceid'";

$result2 = rmt_admin_query($link,$sql2);
// List it
if(rmt_result_num_rows($result2)>0){
	while($row2 = rmt_result_fetch_array($result2)){
		$name = ($lang == 'fr') ? $row2['namefr'] : $row2['nameen'];
		$title = ($lang == 'fr') ? "Supprimer l'élément service $name" : "Delete $name service item";
		$question = ($lang == 'fr') ? "Voulez-vous vraiment supprimer cet élément de service?" : "Are you sure you wish to delete this service item?";
		$buttonText = ($lang == 'fr') ? "Oui" : "Yes";
?>
<section id="filter-id" class="modal-dialog modal-content overlay-def">
	<header class="modal-header">
		<h2 class="modal-title"><?php echo $title ?></h2>
	</header>
	<div class="modal-body">
		<form method="post" action="/includes/delete-service.php?lang=<?php echo $lang ?>&id=<?php echo $serviceid ?>&cid=<?php echo $catalogueid ?>">
		<p tabindex="0"><?php echo $question ?></p>
		<div class="form-group form-buttons">
			<button type="submit" class="btn btn-default"><?php echo $buttonText ?></button>
			<button type="button" class="btn btn-default popup-modal-dismiss"><?= $lang === 'fr' ? 'Non' : 'No' ?></button>
		</div>
		</form>
	</div>
</section>
<?php
	}
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
