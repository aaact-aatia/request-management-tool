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
	$redirect = ($lang == 'en') ? "/index-en.php?status=accessdenied" : "/openrequest-$lang.php?status=accessdenied";
	header("location:$redirect"); 
	exit();
}

// Grab MySQL connection
require('../sql.php');

// Now first get the ID
$userid = $_GET['id'];

// Process the delete product form
if ($_SERVER['REQUEST_METHOD']=='POST'){
	
	// Hard-delete the user record
	$sql = "DELETE FROM `tblusers` WHERE id='$userid'";
	//echo $sql;
	rmt_admin_query($link,$sql);
	
	// Now redirect
	header("location:/users.php?lang=$lang?status=success"); 
	exit();
}

// Construct SQL statement
$sql2 = "SELECT * FROM tblusers WHERE id='$userid'";

$result2 = rmt_admin_query($link,$sql2);
//List it
if(rmt_result_num_rows($result2)>0){
	while($row2 = rmt_result_fetch_array($result2)){
		$title = ($lang == 'fr') 
			? "Supprimer l'utilisateur {$row2['firstname']} {$row2['lastname']}" 
			: "Delete user {$row2['firstname']} {$row2['lastname']}";
		$question = ($lang == 'fr') ? "Voulez-vous vraiment supprimer cet utilisateur?" : "Are you sure you wish to delete this user?";
		$buttonText = ($lang == 'fr') ? "Oui" : "Yes";
?>
<section id="filter-id" class="modal-dialog modal-content overlay-def">
	<header class="modal-header">
		<h2 class="modal-title"><?php echo $title ?></h2>
	</header>
	<div class="modal-body">
		<form method="post" action="/includes/delete-users.php?lang=<?php echo $lang ?>&id=<?php echo $row2['id'] ?>">
		<p tabindex="0"><?php echo $question ?></p>
		<div class="form-group form-buttons">
			<button type="submit" class="btn btn-default"><?php echo $buttonText ?></button>
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
