<?php
// This is called through ajax on the product management page

// Start session
require_once __DIR__ . '/session_start.php';

// Detect language
$lang = isset($_GET['lang']) ? $_GET['lang'] : (isset($_SESSION['lang']) ? $_SESSION['lang'] : 'en');
$is_french = ($lang === 'fr');

// Check if the user has the right priv's
if (!($_SESSION['is_superuser'] OR $_SESSION['is_admin'])) {
	header("location:/openrequest-" . $lang . ".php?status=accessdenied"); 
	exit();
}

// Grab MySQL connection
require('../sql.php');

// Now first get the ID
$subserviceid = $_GET['id'];
$serviceid = $_GET['sid'];
$catalogueid = $_GET['cid'];

// Process the edit product form
if ($_SERVER['REQUEST_METHOD']=='POST'){
	
	// Grab form elements
	$nameen    = mysqli_real_escape_string($link, $_POST['nameen']);
	$namefr    = mysqli_real_escape_string($link, $_POST['namefr']);
	$sds       = mysqli_real_escape_string($link, $_POST['sds']);
	$contactid = isset($_POST['contactid']) && $_POST['contactid'] !== '' ? (int)$_POST['contactid'] : 'NULL';
	$is_guidance_only    = isset($_POST['is_guidance_only']) ? 1 : 0;
	$alert_text_en       = mysqli_real_escape_string($link, $_POST['alert_text_en'] ?? '');
	$alert_text_fr       = mysqli_real_escape_string($link, $_POST['alert_text_fr'] ?? '');
	$needs_checklist     = isset($_POST['needs_checklist']) ? 1 : 0;
	$checklist_name_en   = mysqli_real_escape_string($link, $_POST['checklist_name_en'] ?? '');
	$checklist_name_fr   = mysqli_real_escape_string($link, $_POST['checklist_name_fr'] ?? '');
	$checklist_url_en    = mysqli_real_escape_string($link, $_POST['checklist_url_en'] ?? '');
	$checklist_url_fr    = mysqli_real_escape_string($link, $_POST['checklist_url_fr'] ?? '');
	$needs_sprint_fields = isset($_POST['needs_sprint_fields']) ? 1 : 0;
	$noerror = false;
	
	// Custom form validation
	if ($nameen=='' OR $namefr=='' OR $sds=='' OR $subserviceid=='') {
		$noerror = true;
	}
	
	// If error detected send user back to modal dialog
	if ($noerror) {
		header('location:/catalogue-sub-mgmt.php?lang=' . $lang . '?id=$serviceid&cid=$catalogueid&status=failed'); 
		exit();
	}
	
	// Create SQL statement
	$contactVal = is_int($contactid) ? $contactid : 'NULL';
	$sql = "UPDATE `tblsubservices` SET `nameen`='$nameen', `namefr`='$namefr', `sds`='$sds',
		`contactid`=$contactVal,
		`is_guidance_only`='$is_guidance_only',
		`alert_text_en`='$alert_text_en', `alert_text_fr`='$alert_text_fr',
		`needs_checklist`='$needs_checklist',
		`checklist_name_en`='$checklist_name_en', `checklist_name_fr`='$checklist_name_fr',
		`checklist_url_en`='$checklist_url_en', `checklist_url_fr`='$checklist_url_fr',
		`needs_sprint_fields`='$needs_sprint_fields'
		WHERE id='$subserviceid'";
	//echo $sql;
	rmt_admin_query($link,$sql);
	
	// Now redirect
	header("location:/catalogue-sub-mgmt.php?lang=" . $lang . "?id=$serviceid&cid=$catalogueid&status=success"); 
	exit();
}

// Construct SQL statement
$sql2 = "SELECT * FROM tblsubservices WHERE id='$subserviceid'";

$result2 = rmt_admin_query($link,$sql2);
//List it
if(rmt_result_num_rows($result2)>0){
	while($row2 = rmt_result_fetch_array($result2)){
		$title = $is_french ? ('Modifier l\'élément de sous-service ' . $row2['namefr']) : ('Edit ' . $row2['nameen'] . ' sub-service item');
		$label_en = $is_french ? 'Nom (anglais):' : 'Name (english):';
		$label_fr = $is_french ? 'Nom (français):' : 'Name (french):';
		$label_sds = $is_french ? 'Norme de prestation de services:' : 'Service delivery standard:';
		$required_label = $is_french ? 'requis' : 'required';
		$save_btn = $is_french ? 'Sauvegarder' : 'Save';
		$days_label = $is_french ? 'jours' : 'days';
?>
<section id="filter-id" class="modal-dialog modal-content overlay-def">
	<header class="modal-header">
		<h2 class="modal-title"><?php echo $title ?></h2>
	</header>
	<div class="modal-body">
		<form method="post" action="/includes/edit-subservice.php?id=<?php echo $subserviceid ?>&sid=<?php echo $serviceid ?>&cid=<?php echo $catalogueid ?>&lang=<?php echo $lang ?>">
		<div class="form-group">
			<label for="nameen"><span class="field-name"><?php echo $label_en ?> <strong>(<?php echo $required_label ?>)</strong></span></label>
			<input type="text" class="form-control" id="nameen" name="nameen" value="<?php echo $row2['nameen'] ?>" required>
		</div>
		<div class="form-group">
			<label for="namefr"><span class="field-name"><?php echo $label_fr ?> <strong>(<?php echo $required_label ?>)</strong></span></label>
			<input type="text" class="form-control" id="namefr" name="namefr" value="<?php echo $row2['namefr'] ?>" required>
		</div>
		<div class="form-group">
			<label for="sds"><span class="field-name"><?php echo $label_sds ?> <strong>(<?php echo $required_label ?>)</strong></span></label>
			<select class="form-control" id="sds" name="sds" required>
				<?php
				// Create range for SDS
				$range = range(1,30);
				foreach ($range as $sdsv) {
				?>
				<option value='<?php echo $sdsv ?>'<?php if($sdsv == $row2['sds']) echo " selected"; ?>><?php echo $sdsv ?> <?php echo $days_label ?></option>
				<?php
				}
				?>
			</select>
		</div>
		<div class="form-group">
			<label for="sub_contactid"><span class="field-name"><?= $is_french ? 'Équipe responsable (remplace le défaut du service)' : 'Responsible team (overrides service default)' ?>:</span></label>
			<select class="form-control" id="sub_contactid" name="contactid">
				<option value=""><?= $is_french ? '— Hériter du service —' : '— Inherit from service —' ?></option>
				<?php
				$sortF2 = $is_french ? 'namefr' : 'nameen';
				$tr2 = rmt_admin_query($link, "SELECT id, nameen, namefr FROM tblteams WHERE status=1 ORDER BY $sortF2 ASC");
				while ($tr2row = rmt_result_fetch_array($tr2)) {
					$tn2 = $is_french ? $tr2row['namefr'] : $tr2row['nameen'];
					$sel2 = ((int)$tr2row['id'] === (int)($row2['contactid'] ?? 0)) ? ' selected' : '';
					echo '<option value="' . $tr2row['id'] . '"' . $sel2 . '>' . htmlspecialchars($tn2) . '</option>';
				}
				?>
			</select>
		</div>
		<div class="form-group">
			<div class="checkbox">
				<label>
					<input type="checkbox" name="is_guidance_only" value="1"<?= !empty($row2['is_guidance_only']) ? ' checked' : '' ?>>
					<?= $is_french ? 'Orientation seulement' : 'Guidance only (no request form)' ?>
				</label>
			</div>
		</div>
		<div class="form-group">
			<label for="sub_alert_en"><span class="field-name"><?= $is_french ? 'Texte d\'alerte (anglais)' : 'Alert text (English)' ?>:</span></label>
			<textarea class="form-control" id="sub_alert_en" name="alert_text_en" rows="3"><?= htmlspecialchars($row2['alert_text_en'] ?? '') ?></textarea>
		</div>
		<div class="form-group">
			<label for="sub_alert_fr"><span class="field-name"><?= $is_french ? 'Texte d\'alerte (français)' : 'Alert text (French)' ?>:</span></label>
			<textarea class="form-control" id="sub_alert_fr" name="alert_text_fr" rows="3"><?= htmlspecialchars($row2['alert_text_fr'] ?? '') ?></textarea>
		</div>
		<div class="form-group">
			<div class="checkbox">
				<label>
					<input type="checkbox" name="needs_checklist" value="1"<?= !empty($row2['needs_checklist']) ? ' checked' : '' ?>>
					<?= $is_french ? 'Exige une liste de contrôle avant la soumission' : 'Requires checklist before submit' ?>
				</label>
			</div>
		</div>
		<div class="form-group">
			<label for="ck_name_en"><span class="field-name"><?= $is_french ? 'Nom de la liste de contrôle (anglais)' : 'Checklist name (English)' ?>:</span></label>
			<input type="text" class="form-control" id="ck_name_en" name="checklist_name_en" value="<?= htmlspecialchars($row2['checklist_name_en'] ?? '') ?>">
		</div>
		<div class="form-group">
			<label for="ck_name_fr"><span class="field-name"><?= $is_french ? 'Nom de la liste de contrôle (français)' : 'Checklist name (French)' ?>:</span></label>
			<input type="text" class="form-control" id="ck_name_fr" name="checklist_name_fr" value="<?= htmlspecialchars($row2['checklist_name_fr'] ?? '') ?>">
		</div>
		<div class="form-group">
			<label for="ck_url_en"><span class="field-name"><?= $is_french ? 'URL de la liste de contrôle (anglais)' : 'Checklist URL (English)' ?>:</span></label>
			<input type="url" class="form-control" id="ck_url_en" name="checklist_url_en" value="<?= htmlspecialchars($row2['checklist_url_en'] ?? '') ?>">
		</div>
		<div class="form-group">
			<label for="ck_url_fr"><span class="field-name"><?= $is_french ? 'URL de la liste de contrôle (français)' : 'Checklist URL (French)' ?>:</span></label>
			<input type="url" class="form-control" id="ck_url_fr" name="checklist_url_fr" value="<?= htmlspecialchars($row2['checklist_url_fr'] ?? '') ?>">
		</div>
		<div class="form-group">
			<div class="checkbox">
				<label>
					<input type="checkbox" name="needs_sprint_fields" value="1"<?= !empty($row2['needs_sprint_fields']) ? ' checked' : '' ?>>
					<?= $is_french ? 'Afficher les champs de dates de sprint dans le formulaire' : 'Show sprint date fields in request form' ?>
				</label>
			</div>
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
