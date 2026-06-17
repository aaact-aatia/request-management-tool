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
$catalogueid = $_GET['id'];

// Process the edit product form
if ($_SERVER['REQUEST_METHOD']=='POST'){
	
	// Grab form elements
	$nameen = mysqli_real_escape_string($link,$_POST['nameen']);
	$namefr = mysqli_real_escape_string($link,$_POST['namefr']);
	$survey = mysqli_real_escape_string($link,$_POST['survey']);
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
	$sql = "UPDATE `tblcatalogue` SET `nameen` = '$nameen', `namefr` = '$namefr', `survey` = '$survey' WHERE id='$catalogueid'";
	//echo $sql;
	rmt_admin_query($link,$sql);
	
	// Now redirect
	header("location:/catalogue.php?lang={$lang_code}&status=success"); 
	exit();
}

// Construct SQL statement
$sql2 = "SELECT * FROM tblcatalogue WHERE id='$catalogueid'";

$result2 = rmt_admin_query($link,$sql2);
//List it
if(rmt_result_num_rows($result2)>0){
	while($row2 = rmt_result_fetch_array($result2)){
		$display_name = $lang_code === 'fr' ? $row2['namefr'] : $row2['nameen'];
?>
<section id="filter-id" class="modal-dialog modal-content overlay-def">
	<header class="modal-header">
		<h2 class="modal-title"><?php echo $lang_code === 'en' ? 'Edit' : 'Modifier l\'élément de catalogue'; ?> <?php echo htmlspecialchars($display_name); ?><?php echo $lang_code === 'en' ? ' catalogue item' : ''; ?></h2>
	</header>
	<div class="modal-body">
		<form method="post" action="/includes/edit-catalogue.php?id=<?php echo $row2['id']; ?>">
		<div class="form-group">
			<label for="nameen"><span class="field-name"><?php echo $lang_code === 'en' ? 'Name (english)' : 'Nom (anglais)'; ?>: <strong>(<?php echo $lang_code === 'en' ? 'required' : 'requis'; ?>)</strong></span></label>
			<input type="text" class="form-control" id="nameen" name="nameen" value="<?php echo htmlspecialchars($row2['nameen']); ?>" required>
		</div>
		<div class="form-group">
			<label for="namefr"><span class="field-name"><?php echo $lang_code === 'en' ? 'Name (french)' : 'Nom (français)'; ?>: <strong>(<?php echo $lang_code === 'en' ? 'required' : 'requis'; ?>)</strong></span></label>
			<input type="text" class="form-control" id="namefr" name="namefr" value="<?php echo htmlspecialchars($row2['namefr']); ?>" required>
		</div>
		<div class="form-group">
			<label for="survey"><span class="field-name"><?php echo $lang_code === 'en' ? 'Send survey' : 'Envoyer le sondage'; ?>: <strong>(<?php echo $lang_code === 'en' ? 'required' : 'requis'; ?>)</strong></span></label>
			<select class="form-control" id="survey" name="survey" required>
				<option value="0"<?php if($row2['survey'] == 0) echo " selected"; ?>><?php echo $lang_code === 'en' ? 'No' : 'Non'; ?></option>
				<option value="1"<?php if($row2['survey'] == 1) echo " selected"; ?>><?php echo $lang_code === 'en' ? 'Yes' : 'Oui'; ?></option>
			</select>
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
