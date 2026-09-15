<?php
// This is called through ajax on the product management page

// Start session
require_once __DIR__ . '/session_start.php';

// Set language from session
$lang_code = $_SESSION['lang'] ?? 'en';
$lang = require("../lang/{$lang_code}.php");

// Check if the user has the right priv's
if (!rmt_has_admin_access()) {
	header("location:/openrequest.php?lang={$lang_code}&status=accessdenied"); 
	exit();
}

// Grab MySQL connection
require('../sql.php');
require_once('csrf.php');
require_once('helpers.php');

$csrfToken = rmt_csrf_token('catalogue');

// Process the add product form
if ($_SERVER['REQUEST_METHOD']=='POST'){
	if (!rmt_csrf_token_is_valid('catalogue', (string) ($_POST['csrf_token'] ?? ''))) {
		header("location:/catalogue.php?lang={$lang_code}&status=failed");
		exit();
	}

	$nameen = trim((string) ($_POST['nameen'] ?? ''));
	$namefr = trim((string) ($_POST['namefr'] ?? ''));
	$contactid = filter_var($_POST['contactid'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 0;
	$status = isset($_POST['status']) ? 1 : 0;

	if ($nameen === '' || $namefr === '' || $contactid <= 0) {
		header("location:/catalogue.php?lang={$lang_code}&status=failed"); 
		exit();
	}
	
	$statement = rmt_db_execute(
		$link,
		'INSERT INTO tblcatalogue (nameen, namefr, contactid, status) VALUES (?, ?, ?, ?)',
		'ssii',
		[$nameen, $namefr, $contactid, $status]
	);
	mysqli_stmt_close($statement);
	
	// Now redirect
	header("location:/catalogue.php?lang={$lang_code}&status=success"); 
	exit();
}
?>
<section id="filter-id" class="modal-dialog modal-content overlay-def">
	<header class="modal-header">
		<h2 class="modal-title"><?= htmlspecialchars($lang['add_catalogue_title'] ?? 'Add new catalogue item') ?></h2>
	</header>
	<div class="modal-body">
		<form method="post" action="/includes/add-catalogue.php">
		<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
		<div class="form-group">
			<label for="nameen"><span class="field-name"><?= htmlspecialchars($lang['add_catalogue_name_en'] ?? 'Name (english)') ?>: <strong>(<?= htmlspecialchars($lang['required'] ?? 'required') ?>)</strong></span></label>
			<input type="text" class="form-control" id="nameen" name="nameen" value="" required>
		</div>
		<div class="form-group">
			<label for="namefr"><span class="field-name"><?= htmlspecialchars($lang['add_catalogue_name_fr'] ?? 'Name (french)') ?>: <strong>(<?= htmlspecialchars($lang['required'] ?? 'required') ?>)</strong></span></label>
			<input type="text" class="form-control" id="namefr" name="namefr" value="" required>
		</div>
		<div class="form-group">
			<label for="contactid"><span class="field-name"><?= htmlspecialchars($lang['catalogue_contact_group_field'] ?? (($lang_code === 'fr') ? 'Équipe' : 'Team')) ?>: <strong>(<?= htmlspecialchars($lang['required'] ?? 'required') ?>)</strong></span></label>
			<select class="form-control" id="contactid" name="contactid" required>
				<option value="" selected disabled><?= $lang_code === 'fr' ? 'Sélectionnez une équipe' : 'Select team' ?></option>
				<?php
				$sortField = $lang_code === 'fr' ? 'namefr' : 'nameen';
				$teamsSql = "SELECT * FROM tblteams WHERE status='1' ORDER BY {$sortField} ASC";
				$teamsResult = rmt_admin_query($link, $teamsSql, $lang_code);
				while ($teamRow = rmt_result_fetch_array($teamsResult)) {
					$teamName = $lang_code === 'fr' ? $teamRow['namefr'] : $teamRow['nameen'];
				?>
					<option value="<?= htmlspecialchars($teamRow['id']) ?>"><?= htmlspecialchars($teamName) ?></option>
				<?php } ?>
			</select>
		</div>
		<div class="checkbox">
			<label for="status"><input type="checkbox" id="status" name="status" value="1" checked> <?= htmlspecialchars($lang['active_label']) ?></label>
		</div>
		<div class="form-group form-buttons">
			<button type="submit" class="btn btn-default"><?= htmlspecialchars($lang['add_button'] ?? 'Add') ?></button>
			<button type="button" class="btn btn-default popup-modal-dismiss"><?= $lang_code === 'fr' ? 'Annuler' : 'Cancel' ?></button>
		</div>
		</form>
	</div>
</section>
<?php
// Close connection
mysqli_close($link);
?>
