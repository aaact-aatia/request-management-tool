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

// Now first get the ID
$serviceid = $_GET['id'];
$catalogueid = $_GET['cid'];

// Process the edit product form
if ($_SERVER['REQUEST_METHOD']=='POST'){
	
	// Grab form elements
	$nameen  = mysqli_real_escape_string($link, $_POST['nameen']);
	$namefr  = mysqli_real_escape_string($link, $_POST['namefr']);
	$sds     = mysqli_real_escape_string($link, $_POST['sds']);
	$contactid = isset($_POST['contactid']) && $_POST['contactid'] !== ''
		? (int) $_POST['contactid'] : 'NULL';
	$is_guidance_only  = isset($_POST['is_guidance_only']) ? 1 : 0;
	$has_other_option  = isset($_POST['has_other_option']) ? 1 : 0;
	$alert_text_en     = mysqli_real_escape_string($link, $_POST['alert_text_en'] ?? '');
	$alert_text_fr     = mysqli_real_escape_string($link, $_POST['alert_text_fr'] ?? '');
	$needs_checklist   = isset($_POST['needs_checklist']) ? 1 : 0;
	$checklist_name_en = mysqli_real_escape_string($link, $_POST['checklist_name_en'] ?? '');
	$checklist_name_fr = mysqli_real_escape_string($link, $_POST['checklist_name_fr'] ?? '');
	$checklist_url_en  = mysqli_real_escape_string($link, $_POST['checklist_url_en'] ?? '');
	$checklist_url_fr  = mysqli_real_escape_string($link, $_POST['checklist_url_fr'] ?? '');
	$noerror = false;
	
	// Custom form validation
	if ($nameen=='' OR $namefr=='' OR $sds=='' OR $catalogueid=='') {
		$noerror = true;
	}
	
	// If error detected send user back to modal dialog
	if ($noerror) {
		header("location:/catalogue-mgmt.php?lang={$lang_code}&id=$catalogueid&status=failed"); 
		exit();
	}
	
	// Create SQL statement
	$contactVal = is_int($contactid) ? $contactid : 'NULL';
	$sql = "UPDATE `tblservices` SET `nameen` = '$nameen', `namefr` = '$namefr', `sds` = '$sds',
		`contactid` = $contactVal,
		`is_guidance_only` = '$is_guidance_only',
		`has_other_option` = '$has_other_option',
		`alert_text_en` = '$alert_text_en', `alert_text_fr` = '$alert_text_fr',
		`needs_checklist` = '$needs_checklist',
		`checklist_name_en` = '$checklist_name_en', `checklist_name_fr` = '$checklist_name_fr',
		`checklist_url_en` = '$checklist_url_en', `checklist_url_fr` = '$checklist_url_fr'
		WHERE id='$serviceid'";
	//echo $sql;
	rmt_admin_query($link,$sql);
	
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
?>
<section id="filter-id" class="modal-dialog modal-content overlay-def">
	<header class="modal-header">
		<h2 class="modal-title"><?php echo $lang_code === 'en' ? 'Edit' : 'Modifier l\'itème de service'; ?> <?php echo htmlspecialchars($display_name); ?><?php echo $lang_code === 'en' ? ' service item' : ''; ?></h2>
	</header>
	<div class="modal-body">
		<form method="post" action="/includes/edit-service.php?id=<?php echo $serviceid; ?>&cid=<?php echo $catalogueid; ?>">
		<div class="form-group">
			<label for="nameen"><span class="field-name"><?php echo $lang_code === 'en' ? 'Name (english)' : 'Nom (anglais)'; ?>: <strong>(<?php echo $lang_code === 'en' ? 'required' : 'requis'; ?>)</strong></span></label>
			<input type="text" class="form-control" id="nameen" name="nameen" value="<?php echo htmlspecialchars($row2['nameen']); ?>" required>
		</div>
		<div class="form-group">
			<label for="namefr"><span class="field-name"><?php echo $lang_code === 'en' ? 'Name (french)' : 'Nom (français)'; ?>: <strong>(<?php echo $lang_code === 'en' ? 'required' : 'requis'; ?>)</strong></span></label>
			<input type="text" class="form-control" id="namefr" name="namefr" value="<?php echo htmlspecialchars($row2['namefr']); ?>" required>
		</div>
		<div class="form-group">
			<label for="sds"><span class="field-name"><?php echo $lang_code === 'en' ? 'Service delivery standard' : 'Norme de prestation de services'; ?>: <strong>(<?php echo $lang_code === 'en' ? 'required' : 'requis'; ?>)</strong></span></label>
			<select class="form-control" id="sds" name="sds" required>
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
			<label for="service_contactid"><span class="field-name"><?= $lang_code === 'en' ? 'Responsible team (overrides catalogue default)' : 'Équipe responsable (remplace le défaut du catalogue)' ?>:</span></label>
			<select class="form-control" id="service_contactid" name="contactid">
				<option value=""><?= $lang_code === 'en' ? '— Inherit from catalogue —' : '— Hériter du catalogue —' ?></option>
				<?php
				$sortF = $lang_code === 'fr' ? 'namefr' : 'nameen';
				$tr = rmt_admin_query($link, "SELECT id, nameen, namefr FROM tblteams WHERE status=1 ORDER BY $sortF ASC");
				while ($teamRow = rmt_result_fetch_array($tr)) {
					$tn = $lang_code === 'fr' ? $teamRow['namefr'] : $teamRow['nameen'];
					$sel = ((int)$teamRow['id'] === (int)($row2['contactid'] ?? 0)) ? ' selected' : '';
					echo '<option value="' . $teamRow['id'] . '"' . $sel . '>' . htmlspecialchars($tn) . '</option>';
				}
				?>
			</select>
		</div>
		<div class="form-group">
			<div class="checkbox">
				<label>
					<input type="checkbox" name="is_guidance_only" value="1"<?= !empty($row2['is_guidance_only']) ? ' checked' : '' ?>>
					<?= $lang_code === 'en' ? 'Guidance only (shows info panel; does not create a request)' : 'Orientation seulement (affiche un panneau d\'info; ne crée pas de demande)' ?>
				</label>
			</div>
		</div>
		<div class="form-group">
			<label for="alert_text_en"><span class="field-name"><?= $lang_code === 'en' ? 'Guidance text (English)' : 'Texte d\'orientation (anglais)' ?>:</span></label>
			<textarea class="form-control" id="alert_text_en" name="alert_text_en" rows="5" style="font-family:monospace;"><?= htmlspecialchars($row2['alert_text_en'] ?? '') ?></textarea>
			<p class="small text-muted"><?= $lang_code === 'en' ? 'Supports Markdown: **bold**, [link text](url), - bullet lists. Shown as an info panel above the form.' : 'Prend en charge Markdown : **gras**, [texte du lien](url), - listes à puces. Affiché sous forme de panneau d\'information au-dessus du formulaire.' ?></p>
		</div>
		<div class="form-group">
			<label for="alert_text_fr"><span class="field-name"><?= $lang_code === 'en' ? 'Guidance text (French)' : 'Texte d\'orientation (français)' ?>:</span></label>
			<textarea class="form-control" id="alert_text_fr" name="alert_text_fr" rows="5" style="font-family:monospace;"><?= htmlspecialchars($row2['alert_text_fr'] ?? '') ?></textarea>
		</div>
		<div class="form-group">
			<div class="checkbox">
				<label>
					<input type="checkbox" name="has_other_option" value="1"<?= !empty($row2['has_other_option']) ? ' checked' : '' ?>>
					<?= $lang_code === 'en' ? 'Add "Other" option to sub-service dropdown (shows a freeform text field when selected)' : 'Ajouter une option « Autre » à la liste des sous-services (affiche un champ de texte libre lors de la sélection)' ?>
				</label>
			</div>
		</div>
		<div class="form-group">
			<div class="checkbox">
				<label>
					<input type="checkbox" name="needs_checklist" value="1"<?= !empty($row2['needs_checklist']) ? ' checked' : '' ?>>
					<?= $lang_code === 'en' ? 'Requires checklist before submit (for services with no sub-services)' : 'Exige une liste de contrôle avant la soumission (pour les services sans sous-services)' ?>
				</label>
			</div>
		</div>
		<div class="form-group">
			<label for="checklist_name_en"><span class="field-name"><?= $lang_code === 'en' ? 'Checklist name (English)' : 'Nom de la liste de contrôle (anglais)' ?>:</span></label>
			<input type="text" class="form-control" id="checklist_name_en" name="checklist_name_en" value="<?= htmlspecialchars($row2['checklist_name_en'] ?? '') ?>">
		</div>
		<div class="form-group">
			<label for="checklist_name_fr"><span class="field-name"><?= $lang_code === 'en' ? 'Checklist name (French)' : 'Nom de la liste de contrôle (français)' ?>:</span></label>
			<input type="text" class="form-control" id="checklist_name_fr" name="checklist_name_fr" value="<?= htmlspecialchars($row2['checklist_name_fr'] ?? '') ?>">
		</div>
		<div class="form-group">
			<label for="checklist_url_en"><span class="field-name"><?= $lang_code === 'en' ? 'Checklist URL (English)' : 'URL de la liste de contrôle (anglais)' ?>:</span></label>
			<input type="url" class="form-control" id="checklist_url_en" name="checklist_url_en" value="<?= htmlspecialchars($row2['checklist_url_en'] ?? '') ?>">
		</div>
		<div class="form-group">
			<label for="checklist_url_fr"><span class="field-name"><?= $lang_code === 'en' ? 'Checklist URL (French)' : 'URL de la liste de contrôle (français)' ?>:</span></label>
			<input type="url" class="form-control" id="checklist_url_fr" name="checklist_url_fr" value="<?= htmlspecialchars($row2['checklist_url_fr'] ?? '') ?>">
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
