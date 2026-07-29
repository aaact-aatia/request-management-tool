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
/** @var mysqli $link */
require_once('helpers.php');

// Now first get the ID
$subserviceid = $_GET['id'];
$serviceid = $_GET['sid'];
$catalogueid = $_GET['cid'];

// Process the edit product form
if ($_SERVER['REQUEST_METHOD']=='POST'){
	
	// Grab form elements
	$nameen = mysqli_real_escape_string($link,$_POST['nameen']);
	$namefr = mysqli_real_escape_string($link,$_POST['namefr']);
	$sds = mysqli_real_escape_string($link,$_POST['sds']);
	$contactId = (int) ($_POST['contactid'] ?? 0);
	$requestSubjectType = rmt_normalize_request_subject_type($_POST['request_subject_type'] ?? '', true);
	$status = isset($_POST['status']) ? 1 : 0;
	$noerror = false;
	
	// Custom form validation
	if ($nameen=="" OR $namefr=="" OR $sds=="" OR $subserviceid=="" || ($contactId > 0 && !rmt_db_fetch_one($link, 'SELECT id FROM tblteams WHERE id = ? AND status = 1', 'i', [$contactId]))) {
		$noerror = true;
	}
	$contactId = $contactId > 0 ? $contactId : null;
	
	// If error detected send user back to modal dialog
	if ($noerror) {
		header("location:/catalogue-sub-mgmt.php?lang=" . $lang . "&id=$serviceid&cid=$catalogueid&status=failed");
		exit();
	}
	
	$statement = rmt_db_execute(
		$link,
		'UPDATE tblsubservices SET nameen = ?, namefr = ?, sds = ?, contactid = ?, request_subject_type = ?, status = ? WHERE id = ?',
		'ssiisii',
		[$nameen, $namefr, $sds, $contactId, $requestSubjectType, $status, $subserviceid]
	);
	mysqli_stmt_close($statement);
	
	// Now redirect
	header("location:/catalogue-sub-mgmt.php?lang=" . $lang . "&id=$serviceid&cid=$catalogueid&status=success");
	exit();
}

// Construct SQL statement
$sql2 = "SELECT * FROM tblsubservices WHERE id='$subserviceid'";

$result2 = rmt_admin_query($link,$sql2);
//List it
if(rmt_result_num_rows($result2)>0){
	while($row2 = rmt_result_fetch_array($result2)){
		$parentRow = rmt_db_fetch_one(
			$link,
			'SELECT COALESCE(s.request_subject_type, c.request_subject_type, \'subject\') AS resolved_type FROM tblservices s INNER JOIN tblcatalogue c ON c.id = s.catalogueid WHERE s.id = ?',
			'i',
			[(int) $row2['serviceid']]
		);
		$parentType = rmt_normalize_request_subject_type($parentRow['resolved_type'] ?? 'subject') ?? 'subject';
		$parentText = rmt_request_subject_text($parentType, $lang)['label'];
		$parentTeamId = rmt_resolve_responsible_team_id($link, (int) $catalogueid, (int) $row2['serviceid']);
		$parentTeam = $parentTeamId > 0
			? rmt_db_fetch_one($link, 'SELECT nameen, namefr FROM tblteams WHERE id = ?', 'i', [$parentTeamId])
			: null;
		$parentTeamName = $parentTeam[$is_french ? 'namefr' : 'nameen'] ?? ($is_french ? 'aucune équipe' : 'no team');
		$teams = rmt_get_active_teams($link);
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
			<input type="text" class="form-control full-width" id="nameen" name="nameen" value="<?php echo $row2['nameen'] ?>" required>
		</div>
		<div class="form-group">
			<label for="namefr"><span class="field-name"><?php echo $label_fr ?> <strong>(<?php echo $required_label ?>)</strong></span></label>
			<input type="text" class="form-control full-width" id="namefr" name="namefr" value="<?php echo $row2['namefr'] ?>" required>
		</div>
		<div class="form-group">
			<label for="sds"><span class="field-name"><?php echo $label_sds ?> <strong>(<?php echo $required_label ?>)</strong></span></label>
			<select class="form-control full-width" id="sds" name="sds" required>
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
			<label for="contactid"><span class="field-name"><?= $is_french ? 'Équipe responsable' : 'Responsible team' ?></span></label>
			<select class="form-control full-width" id="contactid" name="contactid">
				<option value=""<?= empty($row2['contactid']) ? ' selected' : '' ?>><?= htmlspecialchars(($is_french ? 'Hériter du service' : 'Inherit from service') . ' (' . $parentTeamName . ')') ?></option>
				<?php foreach ($teams as $team): ?>
				<option value="<?= (int) $team['id'] ?>"<?= (int) $row2['contactid'] === (int) $team['id'] ? ' selected' : '' ?>><?= htmlspecialchars($team[$is_french ? 'namefr' : 'nameen']) ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="form-group">
			<label for="request_subject_type"><span class="field-name"><?= $is_french ? 'Type d’objet de la demande' : 'Request subject type' ?></span></label>
			<select class="form-control full-width" id="request_subject_type" name="request_subject_type">
				<option value=""<?= empty($row2['request_subject_type']) ? ' selected' : '' ?>><?= htmlspecialchars(($is_french ? 'Hériter du service' : 'Inherit from service') . ' (' . $parentText . ')') ?></option>
				<option value="system"<?= $row2['request_subject_type'] === 'system' ? ' selected' : '' ?>><?= $is_french ? 'Nom du système' : 'System name' ?></option>
				<option value="document"<?= $row2['request_subject_type'] === 'document' ? ' selected' : '' ?>><?= $is_french ? 'Titre du document' : 'Document title' ?></option>
				<option value="subject"<?= $row2['request_subject_type'] === 'subject' ? ' selected' : '' ?>><?= $is_french ? 'Objet' : 'Subject' ?></option>
			</select>
		</div>
		<div class="checkbox">
			<label for="status"><input type="checkbox" id="status" name="status" value="1"<?php if ((int)$row2['status'] === 1) echo ' checked'; ?>> <?= $is_french ? 'Actif' : 'Active' ?></label>
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
