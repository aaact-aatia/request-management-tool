<?php
/**
 * Delete Request - Bilingual
 * Modal dialog to confirm request deletion
 * This is a lightbox endpoint accessed directly via GET/POST requests
 */

// Database connection
require_once '../sql.php';
require_once 'helpers.php';
require_once 'csrf.php';

// Language detection
$lang = $_SESSION['lang'] ?? 'en';

// Check if the user has the right privileges
if (!isset($_SESSION['is_superuser']) || (!$_SESSION['is_superuser'] && !$_SESSION['is_admin'])) {
	header("location:/openrequest.php?lang=$lang&status=accessdenied"); 
	exit();
}

// Check if ID parameter exists
if (!isset($_GET['id']) || empty($_GET['id'])) {
	header("location:/requests.php?lang=$lang&status=error");
	exit();
}

// Get the ID
$requestuid = filter_var($_GET['id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 0;
$csrfToken = rmt_csrf_token('request-delete');

// Translations
$translations = [
	'en' => [
		'delete_title' => 'Delete request',
		'confirm_message' => 'Are you sure you wish to delete this request?',
		'yes' => 'Yes',
		'error_title' => 'Oops something went wrong!',
		'error_message' => 'Sorry something went wrong with your request, please try again!'
	],
	'fr' => [
		'delete_title' => 'Supprimer la demande',
		'confirm_message' => 'Voulez-vous vraiment supprimer cette demande?',
		'yes' => 'Oui',
		'error_title' => 'Oups, quelque chose s\'est mal passé!',
		'error_message' => 'Désolé, une erreur s\'est produite avec votre demande, veuillez réessayer!'
	]
];

$t = $translations[$lang];

// Process the delete form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	if (!rmt_csrf_token_is_valid('request-delete', (string) ($_POST['csrf_token'] ?? '')) || $requestuid <= 0) {
		header("location:/requests.php?lang=$lang&status=error");
		exit();
	}

	$statement = rmt_db_execute($link, 'UPDATE tbltriage SET status = 0 WHERE id = ?', 'i', [$requestuid]);
	mysqli_stmt_close($statement);
	
	// Now redirect
	header("location:/requests.php?lang=$lang&status=dsuccess");
	exit();
}

// Construct SQL statement
$request = $requestuid > 0
	? rmt_db_fetch_one($link, 'SELECT id, requestid FROM tbltriage WHERE id = ?', 'i', [$requestuid])
	: null;

// List it
if ($request) {
		$requestid = $request['requestid'];
?>
<section id="filter-id" class="modal-dialog modal-content overlay-def">
	<header class="modal-header">
		<h2 class="modal-title"><?php echo htmlspecialchars($t['delete_title']); ?> - a11y-<?php echo htmlspecialchars($requestid); ?></h2>
	</header>
	<div class="modal-body">
		<form method="post" action="/includes/delete-request.php?id=<?php echo (int) $request['id']; ?>">
		<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
		<p tabindex="0"><?php echo htmlspecialchars($t['confirm_message']); ?></p>
		<div class="form-group form-buttons">
			<button type="submit" class="btn btn-default"><?php echo htmlspecialchars($t['yes']); ?></button>
			<button type="button" class="btn btn-default popup-modal-dismiss"><?= $lang === 'fr' ? 'Non' : 'No' ?></button>
		</div>
		</form>
	</div>
</section>
<?php
} else {
	// Wrong ID so display an error message
?>
<section id="filter-id" class="modal-dialog modal-content overlay-def">
	<header class="modal-header">
		<h2 class="modal-title"><?php echo htmlspecialchars($t['error_title']); ?></h2>
	</header>
	<div class="modal-body">
		<p><?php echo htmlspecialchars($t['error_message']); ?></p>
	</div>
</section>
<?php
}
?>
