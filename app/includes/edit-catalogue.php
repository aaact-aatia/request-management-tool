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
$catalogueid = $_GET['id'];

// Process the edit product form
if ($_SERVER['REQUEST_METHOD']=='POST'){
	
	$nameen  = mysqli_real_escape_string($link, $_POST['nameen']);
	$namefr  = mysqli_real_escape_string($link, $_POST['namefr']);
	$contactid = mysqli_real_escape_string($link, $_POST['contactid']);
	$survey  = mysqli_real_escape_string($link, $_POST['survey']);
	$show_in_openrequest = isset($_POST['show_in_openrequest']) ? 1 : 0;
	$openrequest_order   = (int) ($_POST['openrequest_order'] ?? 99);
	$is_guidance_only    = isset($_POST['is_guidance_only']) ? 1 : 0;
	$guidance_text_en    = mysqli_real_escape_string($link, $_POST['guidance_text_en'] ?? '');
	$guidance_text_fr    = mysqli_real_escape_string($link, $_POST['guidance_text_fr'] ?? '');
	$noerror = false;
	
	// Custom form validation
	if ($nameen=='' OR $namefr=='' OR $contactid=='') {
		$noerror = true;
	}
	
	// If error detected send user back to modal dialog
	if ($noerror) {
		header("location:/catalogue.php?lang={$lang_code}&status=failed"); 
		exit();
	}
	
	// Create SQL statement
	$sql = "UPDATE `tblcatalogue` SET `nameen` = '$nameen', `namefr` = '$namefr', `contactid` = '$contactid', `survey` = '$survey', `show_in_openrequest` = '$show_in_openrequest', `openrequest_order` = '$openrequest_order', `is_guidance_only` = '$is_guidance_only', `guidance_text_en` = '$guidance_text_en', `guidance_text_fr` = '$guidance_text_fr' WHERE id='$catalogueid'";
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
			<label for="contactid"><span class="field-name"><?php echo $lang_code === 'en' ? 'Contact group' : 'Groupe de contact'; ?>: <strong>(<?php echo $lang_code === 'en' ? 'required' : 'requis'; ?>)</strong></span></label>
			<select class="form-control" id="contactid" name="contactid" required>
				<option value=""<?php if (empty($row2['contactid'])) echo " selected"; ?> disabled><?php echo $lang_code === 'en' ? 'Select contact group' : 'Selectionnez un groupe de contact'; ?></option>
				<?php
				$sortField = $lang_code === 'fr' ? 'namefr' : 'nameen';
				$teamsSql = "SELECT * FROM tblteams WHERE status='1' ORDER BY {$sortField} ASC";
				$teamsResult = rmt_admin_query($link, $teamsSql, $lang_code);
				while ($teamRow = rmt_result_fetch_array($teamsResult)) {
					$teamName = $lang_code === 'fr' ? $teamRow['namefr'] : $teamRow['nameen'];
				?>
					<option value="<?php echo $teamRow['id']; ?>"<?php if((int)$teamRow['id'] === (int)($row2['contactid'] ?? 0)) echo " selected"; ?>><?php echo htmlspecialchars($teamName); ?></option>
				<?php } ?>
			</select>
		</div>
		<div class="form-group">
			<label for="survey"><span class="field-name"><?php echo $lang_code === 'en' ? 'Send survey' : 'Envoyer le sondage'; ?>: <strong>(<?php echo $lang_code === 'en' ? 'required' : 'requis'; ?>)</strong></span></label>
			<select class="form-control" id="survey" name="survey" required>
				<option value="0"<?php if($row2['survey'] == 0) echo " selected"; ?>><?php echo $lang_code === 'en' ? 'No' : 'Non'; ?></option>
				<option value="1"<?php if($row2['survey'] == 1) echo " selected"; ?>><?php echo $lang_code === 'en' ? 'Yes' : 'Oui'; ?></option>
			</select>
		</div>
		<div class="form-group">
			<div class="checkbox">
				<label>
					<input type="checkbox" name="show_in_openrequest" value="1"<?= !empty($row2['show_in_openrequest']) ? ' checked' : '' ?>>
					<?= $lang_code === 'en' ? 'Show in open request dropdown' : 'Afficher dans la liste déroulante des nouvelles demandes' ?>
				</label>
			</div>
		</div>
		<div class="form-group">
			<div class="checkbox">
				<label>
					<input type="checkbox" name="is_guidance_only" value="1"<?= !empty($row2['is_guidance_only']) ? ' checked' : '' ?>>
					<?= $lang_code === 'en' ? 'Guidance only (no request form — shows info panel instead)' : 'Orientation seulement (pas de formulaire — affiche un panneau d\'information)' ?>
				</label>
			</div>
		</div>
		<div class="form-group">
			<label for="guidance_text_en"><span class="field-name"><?= $lang_code === 'en' ? 'Guidance text (English)' : 'Texte d\'orientation (anglais)' ?>:</span></label>
			<textarea class="form-control" id="guidance_text_en" name="guidance_text_en" rows="6" style="font-family:monospace;"><?= htmlspecialchars($row2['guidance_text_en'] ?? '') ?></textarea>
			<p class="small text-muted"><?= $lang_code === 'en' ? 'Shown when this catalogue is guidance-only. Supports Markdown: **bold**, [link text](url), - lists.' : 'Affiché quand ce catalogue est « orientation seulement ». Prend en charge Markdown : **gras**, [texte](url), - listes.' ?></p>
		</div>
		<div class="form-group">
			<label for="guidance_text_fr"><span class="field-name"><?= $lang_code === 'en' ? 'Guidance text (French)' : 'Texte d\'orientation (français)' ?>:</span></label>
			<textarea class="form-control" id="guidance_text_fr" name="guidance_text_fr" rows="6" style="font-family:monospace;"><?= htmlspecialchars($row2['guidance_text_fr'] ?? '') ?></textarea>
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
