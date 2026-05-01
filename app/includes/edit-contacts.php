<?php
// This is called through ajax on the product management page

// Start session
if (session_status() != PHP_SESSION_ACTIVE)
{
	session_start();
}

// Set language from session
$lang_code = $_SESSION['lang'] ?? 'en';
require("../lang/{$lang_code}.php");

// Check if the user has the right priv's
if ($_SESSION['atype'] != 1) {
	header("location:/openrequest.php?lang={$lang_code}&status=accessdenied"); 
	exit();
}

// Grab MySQL connection
require('../sql.php');

// Now first get the ID
$contactid = $_GET['id'];

// Process the edit product form
if ($_SERVER['REQUEST_METHOD']=='POST'){
	
	// Grab form elements
	$teamnameen = mysqli_real_escape_string($link,$_POST['teamnameen']);
	$teamnamefr = mysqli_real_escape_string($link,$_POST['teamnamefr']);
	$teamemail = mysqli_real_escape_string($link,$_POST['teamemail']);
	$contactname = mysqli_real_escape_string($link,$_POST['contactname']);
	$contactemail = mysqli_real_escape_string($link,$_POST['contactemail']);
	$escalationcontactname = mysqli_real_escape_string($link,$_POST['escalationcontactname']);
	$escalationcontactemail = mysqli_real_escape_string($link,$_POST['escalationcontactemail']);
	$date_now = date("Y-m-d H:i:s");
	$updatedby = $_SESSION['pid'];
	$noerror = false;
	
	// Custom form validation
	if ($teamnameen=="" OR $teamnamefr=="" OR $teamemail=="" OR $contactname=="" OR $contactemail=="" OR $escalationcontactname=="" OR $escalationcontactemail=="") {
		$noerror = true;
	}
	
	// If error detected send user back to modal dialog
	if ($noerror) {
		header("location:/contacts.php?lang={$lang_code}&status=failed"); 
		exit();
	}
	
	// Create SQL statement
	$sql = "UPDATE `tblcontacts` SET `teamnameen` = '$teamnameen', `teamnamefr` = '$teamnamefr', `teamemail` = '$teamemail', `contactname` = '$contactname', `contactemail` = '$contactemail', `escalationcontactname` = '$escalationcontactname', `escalationcontactemail` = '$escalationcontactemail', `dateupdated` = '$date_now', `updatedby` = '$updatedby' WHERE id='$contactid'";
	//echo $sql;
	mysqli_query($link,$sql);
	
	// Now redirect
	header("location:/contacts.php?lang={$lang_code}&status=success"); 
	exit();
}

// Construct SQL statement
$sql2 = "SELECT * FROM tblcontacts WHERE id='$contactid'";

$result2 = mysqli_query($link,$sql2);
//List it
if(mysqli_num_rows($result2)>0){
	while($row2 = mysqli_fetch_array($result2)){
		$display_name = $lang_code === 'fr' ? $row2['teamnamefr'] : $row2['teamnameen'];
?>
<section id="filter-id" class="modal-dialog modal-content overlay-def">
	<header class="modal-header">
		<h2 class="modal-title"><?php echo $lang_code === 'en' ? 'Edit' : 'Modifier le contact'; ?> <?php echo htmlspecialchars($display_name); ?><?php echo $lang_code === 'en' ? ' contact' : ''; ?></h2>
	</header>
	<div class="modal-body">
		<form method="post" action="/includes/edit-contacts.php?id=<?php echo $row2['id']; ?>">
		<div class="form-group">
			<label for="teamnameen"><span class="field-name"><?php echo $lang_code === 'en' ? 'Team name (english)' : 'Nom de l\'équipe (anglais)'; ?>: <strong>(<?php echo $lang_code === 'en' ? 'required' : 'requis'; ?>)</strong></span></label>
			<input type="text" class="form-control" id="teamnameen" name="teamnameen" value="<?php echo htmlspecialchars($row2['teamnameen']); ?>" required>
		</div>
		<div class="form-group">
			<label for="teamnamefr"><span class="field-name"><?php echo $lang_code === 'en' ? 'Team name (french)' : 'Nom de l\'équipe (français)'; ?>: <strong>(<?php echo $lang_code === 'en' ? 'required' : 'requis'; ?>)</strong></span></label>
			<input type="text" class="form-control" id="teamnamefr" name="teamnamefr" value="<?php echo htmlspecialchars($row2['teamnamefr']); ?>" required>
		</div>
		<div class="form-group">
			<label for="teamemail"><span class="field-name"><?php echo $lang_code === 'en' ? 'Team email' : 'Courriel de l\'équipe'; ?>: <strong>(<?php echo $lang_code === 'en' ? 'required' : 'requis'; ?>)</strong></span></label>
			<input type="email" class="form-control" id="teamemail" name="teamemail" value="<?php echo htmlspecialchars($row2['teamemail']); ?>" required>
		</div>
		<div class="form-group">
			<label for="contactname"><span class="field-name"><?php echo $lang_code === 'en' ? 'Contact name' : 'Nom du contact'; ?>: <strong>(<?php echo $lang_code === 'en' ? 'required' : 'requis'; ?>)</strong></span></label>
			<input type="text" class="form-control" id="contactname" name="contactname" value="<?php echo htmlspecialchars($row2['contactname']); ?>" required>
		</div>
		<div class="form-group">
			<label for="contactemail"><span class="field-name"><?php echo $lang_code === 'en' ? 'Contact email' : 'Courriel du contact'; ?>: <strong>(<?php echo $lang_code === 'en' ? 'required' : 'requis'; ?>)</strong></span></label>
			<input type="email" class="form-control" id="contactemail" name="contactemail" value="<?php echo htmlspecialchars($row2['contactemail']); ?>" required>
		</div>
		<div class="form-group">
			<label for="escalationcontactname"><span class="field-name"><?php echo $lang_code === 'en' ? 'Escalation contact name' : 'Nom du contact d\'escalade'; ?>: <strong>(<?php echo $lang_code === 'en' ? 'required' : 'requis'; ?>)</strong></span></label>
			<input type="text" class="form-control" id="escalationcontactname" name="escalationcontactname" value="<?php echo htmlspecialchars($row2['escalationcontactname']); ?>" required>
		</div>
		<div class="form-group">
			<label for="escalationcontactemail"><span class="field-name"><?php echo $lang_code === 'en' ? 'Escalation contact email' : 'Courriel du contact d\'escalade'; ?>: <strong>(<?php echo $lang_code === 'en' ? 'required' : 'requis'; ?>)</strong></span></label>
			<input type="email" class="form-control" id="escalationcontactemail" name="escalationcontactemail" value="<?php echo htmlspecialchars($row2['escalationcontactemail']); ?>" required>
		</div>
		<div class="form-group form-buttons">
			<button type="submit" class="btn btn-default"><?php echo $lang_code === 'en' ? 'Save' : 'Sauvegarder'; ?></button>
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
		<h2 class="modal-title"><?php echo $lang_code === 'en' ? 'Oops something went wrong!' : 'Oups, quelque chose s\'est mal passé!'; ?></h2>
	</header>
	<div class="modal-body">
		<p><?php echo $lang_code === 'en' ? 'Sorry something went wrong with your request, please try again!' : 'Désolé, une erreur s\'est produite avec votre demande, veuillez réessayer!'; ?></p>
	</div>
</section>
<?php
}
// Close connection
mysqli_close($link);
?>
