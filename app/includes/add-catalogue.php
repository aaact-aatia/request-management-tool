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

// Process the add product form
if ($_SERVER['REQUEST_METHOD']=='POST'){
	
	// Grab form elements
	$nameen = mysqli_real_escape_string($link,$_POST['nameen']);
	$namefr = mysqli_real_escape_string($link,$_POST['namefr']);
	$status = 1;
	$noerror = false;
	
	// Custom form validation
	if ($nameen=="" OR $namefr=="") {
		$noerror = true;
	}

	// If error detected send user back to modal dialog
	if ($noerror) {
		header("location:/catalogue.php?lang={$lang_code}&status=failed"); 
		exit();
	}
	
	// Create SQL statement
	$sql = "INSERT INTO tblcatalogue(`nameen`, `namefr`, `status`) VALUES ('$nameen', '$namefr', '$status')";
	//echo $sql;
	//exit();
	rmt_admin_query($link,$sql);
	
	// Now redirect
	header("location:/catalogue.php?lang={$lang_code}&status=success"); 
	exit();
}
?>
<section id="filter-id" class="modal-dialog modal-content overlay-def">
	<header class="modal-header">
		<h2 class="modal-title"><?= htmlspecialchars($lang['add_catalogue_title'] ?? 'Add new catalogue item') ?></h2>
	</header>
	<div class="modal-body">
		<form method="post" action="/includes/add-catalogue.php">
		<div class="form-group">
			<label for="nameen"><span class="field-name"><?= htmlspecialchars($lang['add_catalogue_name_en'] ?? 'Name (english)') ?>: <strong>(<?= htmlspecialchars($lang['required'] ?? 'required') ?>)</strong></span></label>
			<input type="text" class="form-control" id="nameen" name="nameen" value="" required>
		</div>
		<div class="form-group">
			<label for="namefr"><span class="field-name"><?= htmlspecialchars($lang['add_catalogue_name_fr'] ?? 'Name (french)') ?>: <strong>(<?= htmlspecialchars($lang['required'] ?? 'required') ?>)</strong></span></label>
			<input type="text" class="form-control" id="namefr" name="namefr" value="" required>
		</div>
		<div class="form-group form-buttons">
			<button type="submit" class="btn btn-default"><?= htmlspecialchars($lang['add_button'] ?? 'Add') ?></button>
			<button type="button" class="btn btn-default popup-modal-dismiss"><?= $lang_code === 'fr' ? 'Annuler' : 'Cancel' ?></button>
		</div>
		</form>
	</div>
</section>
<?php
// Close connection
mysqli_close($link);
?>
