<?php
// This is called through ajax on the product management page

// Start session
require_once __DIR__ . '/session_start.php';

// Set language from session
$lang_code = $_SESSION['lang'] ?? 'en';
require("../lang/{$lang_code}.php");

// Check if the user has the right priv's
if (!($_SESSION['is_superuser'] OR $_SESSION['is_admin'])) {
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
	$contactid = mysqli_real_escape_string($link,$_POST['contactid']);
	$status = 1;
	$noerror = false;
	
	// Custom form validation
	if ($nameen=="" OR $namefr=="" OR $contactid=="") {
		$noerror = true;
	}

	// If error detected send user back to modal dialog
	if ($noerror) {
		header("location:/catalogue.php?lang={$lang_code}&status=failed"); 
		exit();
	}
	
	// Create SQL statement
	$sql = "INSERT INTO tblcatalogue(`nameen`, `namefr`, `contactid`, `status`) VALUES ('$nameen', '$namefr', '$contactid', '$status')";
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
		<div class="form-group">
			<label for="contactid"><span class="field-name"><?= htmlspecialchars($lang['catalogue_contact_group_field'] ?? (($lang_code === 'fr') ? 'Groupe de contact' : 'Contact group')) ?>: <strong>(<?= htmlspecialchars($lang['required'] ?? 'required') ?>)</strong></span></label>
			<select class="form-control" id="contactid" name="contactid" required>
				<option value="" selected disabled><?= $lang_code === 'fr' ? 'Selectionnez un groupe de contact' : 'Select contact group' ?></option>
				<?php
				$sortField = $lang_code === 'fr' ? 'namefr' : 'nameen';
				$teamsSql = "SELECT * FROM tblteams WHERE status='1' ORDER BY {$sortField} ASC";
				$teamsResult = rmt_admin_query($link, $teamsSql, $lang_code);
				while ($teamRow = rmt_result_fetch_array($teamsResult)) {
					$teamName = $lang_code === 'fr' ? $teamRow['namefr'] : $teamRow['nameen'];
				?>
					<option value="<?= htmlspecialchars($teamRow['id']) ?>"><?= htmlspecialchars($teamName) ?></option>
				<?php } ?>
			</select>
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
