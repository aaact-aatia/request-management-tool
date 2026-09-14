<?php
// This is called through ajax on the product management page

// Start session
require_once __DIR__ . '/session_start.php';

// Get language
$lang = $_GET['lang'] ?? 'en';

// Check if the user has the right priv's
if (!($_SESSION['is_superuser'] OR $_SESSION['is_admin'])) {
	header("location:/openrequest.php?lang=$lang&status=accessdenied"); 
	exit();
}

// Grab MySQL connection
require('../sql.php');
require_once('helpers.php');
require_once('csrf.php');

// Now first get the ID
$triageid = filter_var($_GET['rid'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 0;
$commentid = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 0;
$type = (string) ($_GET['t'] ?? '');
$csrfToken = rmt_csrf_token('communications');

// Process the delete product form
if ($_SERVER['REQUEST_METHOD']=='POST'){
	if (!rmt_csrf_token_is_valid('communications', (string) ($_POST['csrf_token'] ?? '')) || $triageid <= 0 || $commentid <= 0 || !in_array($type, ['a', 'c'], true)) {
		header("location:/viewrequest.php?lang=$lang&rid=$triageid");
		exit();
	}

	$table = $type === 'c' ? 'tblcommlog' : 'tbladminlog';
	$log = rmt_db_fetch_one($link, "SELECT id FROM {$table} WHERE id = ? AND triageid = ? AND status = 1", 'ii', [$commentid, $triageid]);
	if ($log === null) {
		header("location:/viewrequest.php?lang=$lang&rid=$triageid");
		exit();
	}
	$statement = rmt_db_execute($link, "UPDATE {$table} SET status = 0 WHERE id = ? AND triageid = ?", 'ii', [$commentid, $triageid]);
	mysqli_stmt_close($statement);
	
	// Now redirect
	header("location:/viewrequest.php?lang=$lang&rid=$triageid"); 
	exit();
}

// Check if there is an ID
if ($commentid > 0 && $triageid > 0 && in_array($type, ['a', 'c'], true)) {
	$title = ($lang == 'fr') ? "Supprimer le commentaire" : "Delete comment";
	$question = ($lang == 'fr') ? "Êtes-vous sûr de vouloir supprimer ce commentaire?" : "Are you sure you wish to delete this comment?";
	$buttonText = ($lang == 'fr') ? "Oui" : "Yes";
?>
<section id="filter-id" class="modal-dialog modal-content overlay-def">
	<header class="modal-header">
		<h2 class="modal-title"><?php echo $title ?></h2>
	</header>
	<div class="modal-body">
		<form method="post" action="/includes/delete-comms.php?lang=<?php echo $lang ?>&t=<?php echo $type ?>&id=<?php echo $commentid ?>&rid=<?php echo $triageid ?>">
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
