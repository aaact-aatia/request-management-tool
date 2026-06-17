<?php
/**
 * Delete Request - Bilingual
 * Modal dialog to confirm request deletion
 */

// Database connection
require_once '../sql.php';

// Language detection
$lang = $_SESSION['lang'] ?? 'en';

// Check if the user has the right privileges
if (!isset($_SESSION['atype']) || $_SESSION['atype'] != 1) {
	header("location:/openrequest.php?lang=$lang&status=accessdenied"); 
	exit();
}

// Check if ID parameter exists
if (!isset($_GET['id']) || empty($_GET['id'])) {
	header("location:/index.php?lang=$lang&status=error"); 
	exit();
}

// Get the ID
$requestuid = mysqli_real_escape_string($link, $_GET['id']);

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
	// Create SQL statement
	$sql = "UPDATE `tbltriage` SET `status` = '0' WHERE id='$requestuid'";
	rmt_admin_query($link, $sql);
	
	// Now redirect
	header("location:/index.php?lang=$lang&status=dsuccess"); 
	exit();
}

// Construct SQL statement
$sql2 = "SELECT * FROM tbltriage WHERE id='$requestuid'";
$result2 = rmt_admin_query($link, $sql2);

// List it
if (rmt_result_num_rows($result2) > 0) {
	while ($row2 = rmt_result_fetch_array($result2)) {
		$requestid = $row2['requestid'];
?>
<section id="filter-id" class="modal-dialog modal-content overlay-def">
	<header class="modal-header">
		<h2 class="modal-title"><?php echo htmlspecialchars($t['delete_title']); ?> - a11y-<?php echo htmlspecialchars($requestid); ?></h2>
	</header>
	<div class="modal-body">
		<form method="post" action="/includes/delete-request.php?id=<?php echo htmlspecialchars($row2['id']); ?>">
		<p tabindex="0"><?php echo htmlspecialchars($t['confirm_message']); ?></p>
		<div class="form-group form-buttons">
			<button type="submit" class="btn btn-default"><?php echo htmlspecialchars($t['yes']); ?></button>
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
		<h2 class="modal-title"><?php echo htmlspecialchars($t['error_title']); ?></h2>
	</header>
	<div class="modal-body">
		<p><?php echo htmlspecialchars($t['error_message']); ?></p>
	</div>
</section>
<?php
}
?>
