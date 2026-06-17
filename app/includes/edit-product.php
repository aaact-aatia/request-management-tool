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
$productid = $_GET['id'];

// Process the edit product form
if ($_SERVER['REQUEST_METHOD']=='POST'){
	
	// Grab form elements
	$pnameen = mysqli_real_escape_string($link,$_POST['pnameen']);
	$pnamefr = mysqli_real_escape_string($link,$_POST['pnamefr']);
	$date_now = date("Y-m-d H:i:s");
	$updatedby = $_SESSION['pid'];
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
	$sql = "UPDATE `tblproducts` SET `nameen` = '$pnameen', `namefr` = '$pnamefr', `dateupdated` = '$date_now', `updatedby` = '$updatedby' WHERE id='$productid'";
	//echo $sql;
	rmt_admin_query($link,$sql);
	
	// Now redirect
	header("location:/products.php?lang={$lang_code}&status=success"); 
	exit();
}

// Construct SQL statement
$sql2 = "SELECT * FROM tblproducts WHERE id='$productid'";

$result2 = rmt_admin_query($link,$sql2);
//List it
if(rmt_result_num_rows($result2)>0){
	while($row2 = rmt_result_fetch_array($result2)){
		$display_name = $lang_code === 'fr' ? $row2['namefr'] : $row2['nameen'];
?>
<section id="filter-id" class="modal-dialog modal-content overlay-def">
	<header class="modal-header">
		<h2 class="modal-title"><?php echo $lang_code === 'en' ? 'Edit' : 'Modifier le produit'; ?> <?php echo htmlspecialchars($display_name); ?><?php echo $lang_code === 'en' ? ' product' : ''; ?></h2>
	</header>
	<div class="modal-body">
		<form method="post" action="/includes/edit-product.php?id=<?php echo $row2['id']; ?>">
		<div class="form-group">
			<label for="pnameen"><span class="field-name"><?php echo $lang_code === 'en' ? 'Name of product (english)' : 'Nom du produit (anglais)'; ?>: <strong>(<?php echo $lang_code === 'en' ? 'required' : 'requis'; ?>)</strong></span></label>
			<input type="text" class="form-control" id="pnameen" name="pnameen" value="<?php echo htmlspecialchars($row2['nameen']); ?>" required>
		</div>
		<div class="form-group">
			<label for="pnamefr"><span class="field-name"><?php echo $lang_code === 'en' ? 'Name of product (french)' : 'Nom du produit (français)'; ?>: <strong>(<?php echo $lang_code === 'en' ? 'required' : 'requis'; ?>)</strong></span></label>
			<input type="text" class="form-control" id="pnamefr" name="pnamefr" value="<?php echo htmlspecialchars($row2['namefr']); ?>" required>
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
