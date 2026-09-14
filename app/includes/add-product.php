<?php
// This is called through ajax on the product management page

// Start session
require_once __DIR__ . '/session_start.php';

// Set language
$lang_code = $_SESSION['lang'] ?? 'en';
$lang = require("../lang/{$lang_code}.php");

// Check if the user has the right priv's
if (!($_SESSION['is_superuser'] OR $_SESSION['is_admin'])) {
	header("location:/openrequest.php?lang={$lang_code}&status=accessdenied"); 
	exit();
}

// Grab MySQL connection
require('../sql.php');

require_once('csrf.php');
require_once('helpers.php');
$csrfToken = rmt_csrf_token('products');

// Process the add product form
if ($_SERVER['REQUEST_METHOD']=='POST'){
	if (!rmt_csrf_token_is_valid('products', (string) ($_POST['csrf_token'] ?? ''))) {
		header("location:/products.php?lang={$lang_code}&status=failed");
		exit();
	}

	$pnameen = trim((string) ($_POST['pnameen'] ?? ''));
	$pnamefr = trim((string) ($_POST['pnamefr'] ?? ''));
	$date_now = date("Y-m-d H:i:s");
	$updatedby = filter_var($_SESSION['pid'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 0;
	$status = 1;
	
	if ($pnameen === '' || $pnamefr === '' || $updatedby <= 0) {
		header("location:/products.php?lang={$lang_code}&status=failed"); 
		exit();
	}
	
	$statement = rmt_db_execute(
		$link,
		'INSERT INTO tblproducts (nameen, namefr, dateadded, dateupdated, updatedby, status) VALUES (?, ?, ?, ?, ?, ?)',
		'ssssii',
		[$pnameen, $pnamefr, $date_now, $date_now, $updatedby, $status]
	);
	mysqli_stmt_close($statement);
	
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
		<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
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
			<button type="button" class="btn btn-default popup-modal-dismiss"><?= $lang_code === 'fr' ? 'Annuler' : 'Cancel' ?></button>
		</div>
		</form>
	</div>
</section>
<?php
// Close connection
mysqli_close($link);
?>
