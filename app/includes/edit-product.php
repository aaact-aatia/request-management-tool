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
require_once('csrf.php');
require_once('helpers.php');

// Now first get the ID
$productid = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 0;
$csrfToken = rmt_csrf_token('products');

// Process the edit product form
if ($_SERVER['REQUEST_METHOD']=='POST'){
	if (!rmt_csrf_token_is_valid('products', (string) ($_POST['csrf_token'] ?? ''))) {
		header("location:/products.php?lang={$lang_code}&status=failed");
		exit();
	}

	$pnameen = trim((string) ($_POST['pnameen'] ?? ''));
	$pnamefr = trim((string) ($_POST['pnamefr'] ?? ''));
	$date_now = date("Y-m-d H:i:s");
	$updatedby = filter_var($_SESSION['pid'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 0;
	
	if ($productid <= 0 || $pnameen === '' || $pnamefr === '' || $updatedby <= 0) {
		header("location:/products.php?lang={$lang_code}&status=failed"); 
		exit();
	}
	
	$statement = rmt_db_execute(
		$link,
		'UPDATE tblproducts SET nameen = ?, namefr = ?, dateupdated = ?, updatedby = ? WHERE id = ?',
		'sssii',
		[$pnameen, $pnamefr, $date_now, $updatedby, $productid]
	);
	mysqli_stmt_close($statement);
	
	// Now redirect
	header("location:/products.php?lang={$lang_code}&status=success"); 
	exit();
}

// Load the selected product for the modal.
$product = $productid > 0
	? rmt_db_fetch_one($link, 'SELECT id, nameen, namefr FROM tblproducts WHERE id = ?', 'i', [$productid])
	: null;

if ($product) {
	$row2 = $product;
	$display_name = $lang_code === 'fr' ? $row2['namefr'] : $row2['nameen'];
?>
<section id="filter-id" class="modal-dialog modal-content overlay-def">
	<header class="modal-header">
		<h2 class="modal-title"><?php echo $lang_code === 'en' ? 'Edit' : 'Modifier le produit'; ?> <?php echo htmlspecialchars($display_name); ?><?php echo $lang_code === 'en' ? ' product' : ''; ?></h2>
	</header>
	<div class="modal-body">
		<form method="post" action="/includes/edit-product.php?id=<?php echo (int) $row2['id']; ?>">
		<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
		<div class="form-group">
			<label for="pnameen"><span class="field-name"><?php echo $lang_code === 'en' ? 'Name of product (english)' : 'Nom du produit (anglais)'; ?>: <strong>(<?php echo $lang_code === 'en' ? 'required' : 'requis'; ?>)</strong></span></label>
			<input type="text" class="form-control full-width" id="pnameen" name="pnameen" value="<?php echo htmlspecialchars($row2['nameen']); ?>" required>
		</div>
		<div class="form-group">
			<label for="pnamefr"><span class="field-name"><?php echo $lang_code === 'en' ? 'Name of product (french)' : 'Nom du produit (français)'; ?>: <strong>(<?php echo $lang_code === 'en' ? 'required' : 'requis'; ?>)</strong></span></label>
			<input type="text" class="form-control full-width" id="pnamefr" name="pnamefr" value="<?php echo htmlspecialchars($row2['namefr']); ?>" required>
		</div>
		<div class="form-group form-buttons">
			<button type="submit" class="btn btn-default"><?php echo $lang_code === 'en' ? 'Save' : 'Sauvegarder'; ?></button>
			<button type="button" class="btn btn-default popup-modal-dismiss"><?= $lang_code === 'fr' ? 'Annuler' : 'Cancel' ?></button>
		</div>
		</form>
	</div>
</section>
<?php
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
