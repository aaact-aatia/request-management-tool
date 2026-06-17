<?php
// This is called through ajax on the product management page

// Start session
if (session_status() != PHP_SESSION_ACTIVE)
{
	session_start();
}

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
	$pnameen = mysqli_real_escape_string($link,$_POST['pnameen']);
	$pnamefr = mysqli_real_escape_string($link,$_POST['pnamefr']);
	$date_now = date("Y-m-d H:i:s");
	$updatedby = $_SESSION['pid'];
	$status = 1;
	$noerror = false;
	
	// Custom form validation
	if ($pnameen=="" OR $pnamefr=="") {
		$noerror = true;
	}
	
	// If error detected send user back to modal dialog
	if ($noerror) {
		header("location:/products.php?lang={$lang_code}&status=failed"); 
		exit();
	}
	
	// Create SQL statement
	$sql = "INSERT INTO tblproducts(`nameen`, `namefr`, `dateadded`, `dateupdated`, `updatedby`, `status`) VALUES ('$pnameen', '$pnamefr', '$date_now', '$date_now', '$updatedby', '$status')";
	//echo $sql;
	//exit();
	rmt_admin_query($link,$sql);
	
	// Now redirect
	header("location:/products.php?lang={$lang_code}&status=success"); 
	exit();
}

// Translation keys
$translations = [
	'en' => [
		'modal_title' => 'Add new product',
		'name_en' => 'Name of product (english):',
		'name_fr' => 'Name of product (french):',
		'required' => '(required)',
		'add_button' => 'Add'
	],
	'fr' => [
		'modal_title' => 'Ajouter un nouveau produit',
		'name_en' => 'Nom du produit (anglais):',
		'name_fr' => 'Nom du produit (français):',
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
		<form method="post" action="/includes/add-product.php">
		<div class="form-group">
			<label for="pnameen"><span class="field-name"><?= htmlspecialchars($t['name_en']) ?> <strong><?= htmlspecialchars($t['required']) ?></strong></span></label>
			<input type="text" class="form-control" id="pnameen" name="pnameen" value="" required>
		</div>
		<div class="form-group">
			<label for="pnamefr"><span class="field-name"><?= htmlspecialchars($t['name_fr']) ?> <strong><?= htmlspecialchars($t['required']) ?></strong></span></label>
			<input type="text" class="form-control" id="pnamefr" name="pnamefr" value="" required>
		</div>
		<div class="form-group form-buttons">
			<button type="submit" class="btn btn-default"><?= htmlspecialchars($t['add_button']) ?></button>
		</div>
		</form>
	</div>
</section>
<?php
// Close connection
mysqli_close($link);
?>
