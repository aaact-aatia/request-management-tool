<?php
// This is called through ajax on the product management page

// Start session
require_once __DIR__ . '/session_start.php';

// Get language
$lang = $_GET['lang'] ?? 'en';

// Check if the user has the right priv's
if (!rmt_has_admin_access()) {
	$redirect = ($lang == 'en') ? "/index-en.php?status=accessdenied" : "/openrequest-$lang.php?status=accessdenied";
	header("location:$redirect"); 
	exit();
}

// Grab MySQL connection
require('../sql.php');
require_once('helpers.php');
require_once('csrf.php');

// Now first get the ID
$userid = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 0;
$csrfToken = rmt_csrf_token('users');

// Process the delete product form
if ($_SERVER['REQUEST_METHOD']=='POST'){
	if (!rmt_csrf_token_is_valid('users', (string) ($_POST['csrf_token'] ?? '')) || $userid <= 0 || $userid === (int) ($_SESSION['pid'] ?? 0)) {
		header("location:/users.php?lang={$lang}&status=failed");
		exit();
	}

	$statement = rmt_db_execute($link, 'DELETE FROM tblusers WHERE id = ?', 'i', [$userid]);
	mysqli_stmt_close($statement);
	
	// Now redirect
	header("location:/users.php?lang=$lang&status=success");
	exit();
}


$row2 = $userid > 0
	? rmt_db_fetch_one($link, 'SELECT id, firstname, lastname FROM tblusers WHERE id = ?', 'i', [$userid])
	: null;
if ($row2) {
		$title = ($lang == 'fr') 
			? "Supprimer l'utilisateur {$row2['firstname']} {$row2['lastname']}" 
			: "Delete user {$row2['firstname']} {$row2['lastname']}";
		$question = ($lang == 'fr') ? "Voulez-vous vraiment supprimer cet utilisateur?" : "Are you sure you wish to delete this user?";
		$buttonText = ($lang == 'fr') ? "Oui" : "Yes";
?>
<section id="filter-id" class="modal-dialog modal-content overlay-def">
	<header class="modal-header">
		<h2 class="modal-title"><?php echo $title ?></h2>
	</header>
	<div class="modal-body">
		<form method="post" action="/includes/delete-users.php?lang=<?php echo $lang ?>&id=<?php echo $row2['id'] ?>">
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
