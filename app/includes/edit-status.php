<?php
// This is called through ajax on the product management page

// Start session
if (session_status() != PHP_SESSION_ACTIVE)
{
	session_start();
}

// Detect language
$lang = isset($_GET['lang']) ? $_GET['lang'] : (isset($_SESSION['lang']) ? $_SESSION['lang'] : 'en');
$is_french = ($lang === 'fr');

// Check if the user has the right priv's
if ($_SESSION['atype'] != 1) {
	header("location:/openrequest-" . $lang . ".php?status=accessdenied"); 
	exit();
}

// Grab MySQL connection
require('../sql.php');

// Now first get the ID
$productid = $_GET['id'];

// Process the edit product form
if ($_SERVER['REQUEST_METHOD']=='POST'){
	
	// Grab form elements
	$snameen = mysqli_real_escape_string($link,$_POST['snameen']);
	$snamefr = mysqli_real_escape_string($link,$_POST['snamefr']);
	$isResolved = isset($_POST['is_resolved']) ? (int)$_POST['is_resolved'] : 0;
	$noerror = false;
	
	// Custom form validation
	if ($snameen=="" OR $snamefr=="") {
		$noerror = true;
	}
	
	// If error detected send user back to modal dialog
	if ($noerror) {
		header("location:/status.php?lang=" . $lang . "?status=failed"); 
		exit();
	}
	
	// Create SQL statement
	$sql = "UPDATE `tblstatus` SET `nameen` = '$snameen', `namefr` = '$snamefr', `is_resolved` = '$isResolved' WHERE id='$productid'";
	//echo $sql;
	rmt_admin_query($link,$sql);
	
	// Now redirect
	header("location:/status.php?lang=" . $lang . "?status=success"); 
	exit();
}

// Construct SQL statement
$sql2 = "SELECT * FROM tblstatus WHERE id='$productid'";

$result2 = rmt_admin_query($link,$sql2);
//List it
if(rmt_result_num_rows($result2)>0){
	while($row2 = rmt_result_fetch_array($result2)){
		$title = $is_french ? ('Modifier le statut ' . $row2['namefr']) : ('Edit ' . $row2['nameen'] . ' status');
		$label_en = $is_french ? 'Nom du statut (anglais):' : 'Name of status (english):';
		$label_fr = $is_french ? 'Nom du statut (français):' : 'Name of status (french):';
		$required_label = $is_french ? 'requis' : 'required';
		$is_resolved_label = $is_french ? 'Utiliser ce statut comme Résolu :' : 'Use this status as Resolved:';
		$no_label = $is_french ? 'Non' : 'No';
		$yes_label = $is_french ? 'Oui' : 'Yes';
		$currentIsResolved = isset($row2['is_resolved']) ? (int)$row2['is_resolved'] : 0;
		$save_btn = $is_french ? 'Sauvegarder' : 'Save';
?>
<section id="filter-id" class="modal-dialog modal-content overlay-def">
	<header class="modal-header">
		<h2 class="modal-title"><?php echo $title ?></h2>
	</header>
	<div class="modal-body">
		<form method="post" action="/includes/edit-status.php?id=<?php echo $row2['id'] ?>&lang=<?php echo $lang ?>">
		<div class="form-group">
			<label for="pnameen"><span class="field-name"><?php echo $label_en ?> <strong>(<?php echo $required_label ?>)</strong></span></label>
			<input type="text" class="form-control" id="snameen" name="snameen" value="<?php echo $row2['nameen'] ?>" required>
		</div>
		<div class="form-group">
			<label for="pnamefr"><span class="field-name"><?php echo $label_fr ?> <strong>(<?php echo $required_label ?>)</strong></span></label>
			<input type="text" class="form-control" id="snamefr" name="snamefr" value="<?php echo $row2['namefr'] ?>" required>
		</div>
		<div class="form-group">
			<label for="is_resolved"><span class="field-name"><?php echo $is_resolved_label ?></span></label>
			<select class="form-control" id="is_resolved" name="is_resolved">
				<option value="0"<?php if($currentIsResolved === 0) echo " selected"; ?>><?php echo $no_label; ?></option>
				<option value="1"<?php if($currentIsResolved === 1) echo " selected"; ?>><?php echo $yes_label; ?></option>
			</select>
		</div>
		<div class="form-group form-buttons">
			<button type="submit" class="btn btn-default"><?php echo $save_btn ?></button>
			<button type="button" class="btn btn-default popup-modal-dismiss"><?= $is_french ? 'Annuler' : 'Cancel' ?></button>
		</div>
		</form>
	</div>
</section>
<?php
	}
} else { 
// Wrong ID so display an error message
	$error_title = $is_french ? 'Oups, quelque chose s\'est mal passé!' : 'Oops something went wrong!';
	$error_message = $is_french ? 'Désolé, une erreur s\'est produite avec votre demande, veuillez réessayer!' : 'Sorry something went wrong with your request, please try again!';
?>
<section id="filter-id" class="modal-dialog modal-content overlay-def">
	<header class="modal-header">
		<h2 class="modal-title"><?php echo $error_title ?></h2>
	</header>
	<div class="modal-body">
		<p><?php echo $error_message ?></p>
	</div>
</section>
<?php
}
// Close connection
mysqli_close($link);
?>
