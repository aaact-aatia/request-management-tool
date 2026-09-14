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
require_once('helpers.php');
require_once('csrf.php');

// Now first get the ID
$productid = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 0;
$csrfToken = rmt_csrf_token('status');

// Process the edit product form
if ($_SERVER['REQUEST_METHOD']=='POST'){
	if (!rmt_csrf_token_is_valid('status', (string) ($_POST['csrf_token'] ?? ''))) {
		header("location:/status.php?lang={$lang}&status=failed");
		exit();
	}

	$snameen = trim((string) ($_POST['snameen'] ?? ''));
	$snamefr = trim((string) ($_POST['snamefr'] ?? ''));
	$isResolved = filter_var($_POST['is_resolved'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 1]]);
	
	if ($productid <= 0 || $snameen === '' || $snamefr === '' || $isResolved === false) {
		header("location:/status.php?lang={$lang}&status=failed");
		exit();
	}
	
	$statement = rmt_db_execute($link, 'UPDATE tblstatus SET nameen = ?, namefr = ?, is_resolved = ? WHERE id = ?', 'ssii', [$snameen, $snamefr, $isResolved, $productid]);
	mysqli_stmt_close($statement);
	
	// Now redirect
	header("location:/status.php?lang={$lang}&status=success");
	exit();
}

$row2 = $productid > 0
	? rmt_db_fetch_one($link, 'SELECT * FROM tblstatus WHERE id = ?', 'i', [$productid])
	: null;
if ($row2) {
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
		<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
		<div class="form-group">
			<label for="pnameen"><span class="field-name"><?php echo $label_en ?> <strong>(<?php echo $required_label ?>)</strong></span></label>
			<input type="text" class="form-control full-width" id="snameen" name="snameen" value="<?php echo $row2['nameen'] ?>" required>
		</div>
		<div class="form-group">
			<label for="pnamefr"><span class="field-name"><?php echo $label_fr ?> <strong>(<?php echo $required_label ?>)</strong></span></label>
			<input type="text" class="form-control full-width" id="snamefr" name="snamefr" value="<?php echo $row2['namefr'] ?>" required>
		</div>
		<div class="form-group">
			<label for="is_resolved"><span class="field-name"><?php echo $is_resolved_label ?></span></label>
			<select class="form-control full-width" id="is_resolved" name="is_resolved">
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
