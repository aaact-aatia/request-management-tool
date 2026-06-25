<?php
// This is called through ajax on the product management page

// Start session
require_once __DIR__ . '/session_start.php';

// Set language
$lang_code = $_SESSION['lang'] ?? 'en';
$lang = require("../lang/{$lang_code}.php");

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
	$snameen = mysqli_real_escape_string($link,$_POST['snameen']);
	$snamefr = mysqli_real_escape_string($link,$_POST['snamefr']);
	$isResolved = isset($_POST['is_resolved']) ? (int)$_POST['is_resolved'] : 0;
	$status = 1;
	$noerror = false;
	
	// Custom form validation
	if ($snameen=="" OR $snamefr=="") {
		$noerror = true;
	}
	
	// If error detected send user back to modal dialog
	if ($noerror) {
		header("location:/status.php?lang={$lang_code}?status=failed"); 
		exit();
	}
	
	// Create SQL statement
	$sql = "INSERT INTO tblstatus(`nameen`, `namefr`, `is_resolved`, `status`) VALUES ('$snameen', '$snamefr', '$isResolved', '$status')";
	//echo $sql;
	//exit();
	rmt_admin_query($link,$sql);
	
	// Now redirect
	header("location:/status.php?lang={$lang_code}?status=success"); 
	exit();
}

// Translation keys
$translations = [
	'en' => [
		'modal_title' => 'Add new status',
		'name_en' => 'Name of status (english):',
		'name_fr' => 'Name of status (french):',
		'is_resolved' => 'Use this status as Resolved:',
		'no' => 'No',
		'yes' => 'Yes',
		'required' => '(required)',
		'add_button' => 'Add'
	],
	'fr' => [
		'modal_title' => 'Ajouter un nouveau statut',
		'name_en' => 'Nom du statut (anglais):',
		'name_fr' => 'Nom du statut (français):',
		'is_resolved' => 'Utiliser ce statut comme Résolu :',
		'no' => 'Non',
		'yes' => 'Oui',
		'required' => '(requis)',
		'add_button' => 'Ajouter'
	]
];

$t = $translations[$lang_code];
?>
<section id="filter-id" class="modal-dialog modal-content overlay-def">
	<header class="modal-header">
		<h2 class="modal-title"><?= htmlspecialchars($t['modal_title']) ?></h2>
	</header>
	<div class="modal-body">
		<form method="post" action="/includes/add-status.php">
		<div class="form-group">
			<label for="snameen"><span class="field-name"><?= htmlspecialchars($t['name_en']) ?> <strong><?= htmlspecialchars($t['required']) ?></strong></span></label>
			<input type="text" class="form-control" id="snameen" name="snameen" value="" required>
		</div>
		<div class="form-group">
			<label for="snamefr"><span class="field-name"><?= htmlspecialchars($t['name_fr']) ?> <strong><?= htmlspecialchars($t['required']) ?></strong></span></label>
			<input type="text" class="form-control" id="snamefr" name="snamefr" value="" required>
		</div>
		<div class="form-group">
			<label for="is_resolved"><span class="field-name"><?= htmlspecialchars($t['is_resolved']) ?></span></label>
			<select class="form-control" id="is_resolved" name="is_resolved">
				<option value="0"><?= htmlspecialchars($t['no']) ?></option>
				<option value="1"><?= htmlspecialchars($t['yes']) ?></option>
			</select>
		</div>
		<div class="form-group form-buttons">
			<button type="submit" class="btn btn-default"><?= htmlspecialchars($t['add_button']) ?></button>
			<button type="button" class="btn btn-default popup-modal-dismiss"><?= $lang_code === 'fr' ? 'Annuler' : 'Cancel' ?></button>
		</div>
		</form>
	</div>
</section>
<?php
// Close connection
mysqli_close($link);
?>
