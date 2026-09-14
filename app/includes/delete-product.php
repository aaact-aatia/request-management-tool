<?php
// This is called through ajax on the product management page

// Start session
require_once __DIR__ . '/session_start.php';
require_once('csrf.php');
require_once('helpers.php');

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
$productid = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 0;
$csrfToken = rmt_csrf_token('products');

// Process the delete product form
if ($_SERVER['REQUEST_METHOD']=='POST'){
	if (!rmt_csrf_token_is_valid('products', (string) ($_POST['csrf_token'] ?? '')) || $productid <= 0) {
		header("location:/products.php?lang={$lang}&status=failed");
		exit();
	}

	$statement = rmt_db_execute($link, 'UPDATE tblproducts SET status = 0 WHERE id = ?', 'i', [$productid]);
	mysqli_stmt_close($statement);
	
	// Now redirect
	header("location:/products.php?lang=$lang&status=success"); 
	exit();
}


$product = $productid > 0
	? rmt_db_fetch_one($link, 'SELECT id, nameen, namefr FROM tblproducts WHERE id = ?', 'i', [$productid])
	: null;

if ($product) {
	$row2 = $product;
	$name = ($lang == 'fr') ? $row2['namefr'] : $row2['nameen'];
		$title = ($lang == 'fr') ? "Supprimer le produit $name" : "Delete $name product";
		$question = ($lang == 'fr') ? "Êtes-vous sûr de vouloir supprimer ce produit?" : "Are you sure you wish to delete this product?";
		$buttonText = ($lang == 'fr') ? "Oui" : "Yes";
?>
<section id="filter-id" class="modal-dialog modal-content overlay-def">
	<header class="modal-header">
		<h2 class="modal-title"><?php echo $title ?></h2>
	</header>
	<div class="modal-body">
		<form method="post" action="/includes/delete-product.php?lang=<?php echo $lang ?>&id=<?php echo $row2['id'] ?>">
		<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
		<p tabindex="0"><?php echo $question ?></p>
		<div class="form-group form-buttons">
			<button type="submit" class="btn btn-default"><?php echo $buttonText ?></button>
			<button type="button" class="btn btn-default popup-modal-dismiss"><?= $lang === 'fr' ? 'Non' : 'No' ?></button>
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
