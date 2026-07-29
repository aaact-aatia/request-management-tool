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
/** @var mysqli $link */
require_once('helpers.php');

// Now first get the ID
$serviceid = $_GET['id'];
$catalogueid = $_GET['cid'];

// Process the edit product form
if ($_SERVER['REQUEST_METHOD']=='POST'){
	
	// Grab form elements
	$nameen = mysqli_real_escape_string($link,$_POST['nameen']);
	$namefr = mysqli_real_escape_string($link,$_POST['namefr']);
	$sds = mysqli_real_escape_string($link,$_POST['sds']);
	$requestSubjectType = rmt_normalize_request_subject_type($_POST['request_subject_type'] ?? '', true);
	$status = isset($_POST['status']) ? 1 : 0;
	$noerror = false;
	
	// Custom form validation
	if ($nameen=="" OR $namefr=="" OR $sds=="" OR $catalogueid=="") {
		$noerror = true;
	}
	
	// If error detected send user back to modal dialog
	if ($noerror) {
		header("location:/catalogue-mgmt.php?lang={$lang_code}&id=$catalogueid&status=failed"); 
		exit();
	}
	
	$statement = rmt_db_execute(
		$link,
		'UPDATE tblservices SET nameen = ?, namefr = ?, sds = ?, request_subject_type = ?, status = ? WHERE id = ?',
		'ssisii',
		[$nameen, $namefr, $sds, $requestSubjectType, $status, $serviceid]
	);
	mysqli_stmt_close($statement);
	
	// Now redirect
	header("location:/catalogue-mgmt.php?lang={$lang_code}&id=$catalogueid&status=success"); 
	exit();
}

// Construct SQL statement
$sql2 = "SELECT * FROM tblservices WHERE id='$serviceid'";

$result2 = rmt_admin_query($link,$sql2);
//List it
if(rmt_result_num_rows($result2)>0){
	while($row2 = rmt_result_fetch_array($result2)){
		$display_name = $lang_code === 'fr' ? $row2['namefr'] : $row2['nameen'];
		$parentRow = rmt_db_fetch_one($link, 'SELECT request_subject_type FROM tblcatalogue WHERE id = ?', 'i', [(int) $row2['catalogueid']]);
		$parentType = rmt_normalize_request_subject_type($parentRow['request_subject_type'] ?? 'subject') ?? 'subject';
		$parentText = rmt_request_subject_text($parentType, $lang_code)['label'];
?>
<section id="filter-id" class="modal-dialog modal-content overlay-def">
	<header class="modal-header">
		<h2 class="modal-title"><?php echo $lang_code === 'en' ? 'Edit' : 'Modifier l\'itème de service'; ?> <?php echo htmlspecialchars($display_name); ?><?php echo $lang_code === 'en' ? ' service item' : ''; ?></h2>
	</header>
	<div class="modal-body">
		<form method="post" action="/includes/edit-service.php?id=<?php echo $serviceid; ?>&cid=<?php echo $catalogueid; ?>">
		<div class="form-group">
			<label for="nameen"><span class="field-name"><?php echo $lang_code === 'en' ? 'Name (english)' : 'Nom (anglais)'; ?>: <strong>(<?php echo $lang_code === 'en' ? 'required' : 'requis'; ?>)</strong></span></label>
			<input type="text" class="form-control full-width" id="nameen" name="nameen" value="<?php echo htmlspecialchars($row2['nameen']); ?>" required>
		</div>
		<div class="form-group">
			<label for="namefr"><span class="field-name"><?php echo $lang_code === 'en' ? 'Name (french)' : 'Nom (français)'; ?>: <strong>(<?php echo $lang_code === 'en' ? 'required' : 'requis'; ?>)</strong></span></label>
			<input type="text" class="form-control full-width" id="namefr" name="namefr" value="<?php echo htmlspecialchars($row2['namefr']); ?>" required>
		</div>
		<div class="form-group">
			<label for="sds"><span class="field-name"><?php echo $lang_code === 'en' ? 'Service delivery standard' : 'Norme de prestation de services'; ?>: <strong>(<?php echo $lang_code === 'en' ? 'required' : 'requis'; ?>)</strong></span></label>
			<select class="form-control full-width" id="sds" name="sds" required>
				<?php
				// Create range for SDS
				$range = range(1,30);
				$days_text = $lang_code === 'en' ? 'days' : 'jours';
				foreach ($range as $sdsv) {
				?>
				<option value='<?php echo $sdsv; ?>'<?php if($sdsv == $row2['sds']) echo " selected"; ?>><?php echo $sdsv; ?> <?php echo $days_text; ?></option>
				<?php
				}
				?>
			</select>
		</div>
		<div class="form-group">
			<label for="request_subject_type"><span class="field-name"><?= $lang_code === 'fr' ? 'Type d’objet de la demande' : 'Request subject type' ?></span></label>
			<select class="form-control full-width" id="request_subject_type" name="request_subject_type">
				<option value=""<?= empty($row2['request_subject_type']) ? ' selected' : '' ?>><?= htmlspecialchars(($lang_code === 'fr' ? 'Hériter du catalogue' : 'Inherit from catalogue') . ' (' . $parentText . ')') ?></option>
				<option value="system"<?= $row2['request_subject_type'] === 'system' ? ' selected' : '' ?>><?= $lang_code === 'fr' ? 'Nom du système' : 'System name' ?></option>
				<option value="document"<?= $row2['request_subject_type'] === 'document' ? ' selected' : '' ?>><?= $lang_code === 'fr' ? 'Titre du document' : 'Document title' ?></option>
				<option value="subject"<?= $row2['request_subject_type'] === 'subject' ? ' selected' : '' ?>><?= $lang_code === 'fr' ? 'Objet' : 'Subject' ?></option>
			</select>
		</div>
		<div class="checkbox">
			<label for="status"><input type="checkbox" id="status" name="status" value="1"<?php if ((int)$row2['status'] === 1) echo ' checked'; ?>> <?= $lang_code === 'fr' ? 'Actif' : 'Active' ?></label>
		</div>
		<div class="form-group form-buttons">
			<button type="submit" class="btn btn-default"><?php echo $lang_code === 'en' ? 'Save' : 'Sauvegarder'; ?></button>
			<button type="button" class="btn btn-default popup-modal-dismiss"><?= $lang_code === 'fr' ? 'Annuler' : 'Cancel' ?></button>
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
