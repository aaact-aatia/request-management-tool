<?php
// Start session
require_once __DIR__ . '/session_start.php';

// Check if Super Admin
if (!($_SESSION['is_superuser'] OR $_SESSION['is_admin'])) {
	header("Location: ../requests.php");
	exit();
}

// HTTPS check
require('../includes/httpscheck.php');

// Database connection
require('../sql.php');
require_once('../includes/helpers.php');
require_once('../includes/csrf.php');

// Get language
$lang = isset($_GET['lang']) && $_GET['lang'] === 'fr' ? 'fr' : 'en';

// Translations
$translations = [
	'en' => [
		'page_title' => 'Edit Holiday',
		'heading' => 'Edit Holiday',
		'holiday_date' => 'Holiday Date',
		'name_en' => 'Name (English)',
		'name_fr' => 'Name (French)',
		'recurring' => 'Recurring',
		'recurring_help' => 'Check if this holiday repeats annually (e.g., Christmas, Canada Day)',
		'active' => 'Active',
		'required' => 'required',
		'update_button' => 'Update Holiday',
		'cancel' => 'Cancel',
	],
	'fr' => [
		'page_title' => 'Modifier le jour férié',
		'heading' => 'Modifier le jour férié',
		'holiday_date' => 'Date du jour férié',
		'name_en' => 'Nom (anglais)',
		'name_fr' => 'Nom (français)',
		'recurring' => 'Récurrent',
		'recurring_help' => 'Cochez si ce jour férié se répète chaque année (par ex., Noël, fête du Canada)',
		'active' => 'Actif',
		'required' => 'obligatoire',
		'update_button' => 'Mettre à jour le jour férié',
		'cancel' => 'Annuler',
	]
];

$t = $translations[$lang];

// Get holiday ID
$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 0;
$csrfToken = rmt_csrf_token('holidays');

if (empty($id)) {
	header("Location: ../holidays-mgmt.php?lang=$lang");
	exit();
}

// Fetch holiday
$holiday = $id > 0 ? rmt_db_fetch_one($link, 'SELECT * FROM tblholidays WHERE id = ?', 'i', [$id]) : null;
if ($holiday === null) {
	header("Location: ../holidays-mgmt.php?lang=$lang");
	exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	if (!rmt_csrf_token_is_valid('holidays', (string) ($_POST['csrf_token'] ?? ''))) {
		header("Location: ../holidays-mgmt.php?lang=$lang&status=error");
		exit();
	}
	$holiday_date = trim((string) ($_POST['holiday_date'] ?? ''));
	$name_en = trim((string) ($_POST['name_en'] ?? ''));
	$name_fr = trim((string) ($_POST['name_fr'] ?? ''));
	$recurring = isset($_POST['recurring']) ? 1 : 0;
	$status = isset($_POST['status']) ? 1 : 0;
	$dateObject = DateTime::createFromFormat('!Y-m-d', $holiday_date);
	if ($dateObject === false || $dateObject->format('Y-m-d') !== $holiday_date || $name_en === '' || $name_fr === '') {
		header("Location: ../holidays-mgmt.php?lang=$lang&status=error");
		exit();
	}

	$statement = rmt_db_execute($link, 'UPDATE tblholidays SET holiday_date = ?, name_en = ?, name_fr = ?, recurring = ?, status = ? WHERE id = ?', 'sssiii', [$holiday_date, $name_en, $name_fr, $recurring, $status, $id]);
	mysqli_stmt_close($statement);
	// Log admin action
	$adminNote = ($lang == 'fr' ? "Mis à jour le jour férié : " : "Updated holiday: ") . "$name_en / $name_fr " . ($lang == 'fr' ? "le " : "on ") . "$holiday_date";
	$userId = (int) ($_SESSION['pid'] ?? 0);
	$logStatement = rmt_db_execute($link, 'INSERT INTO tbladminlog (triageid, dateadded, notes, creatorid, status) VALUES (0, NOW(), ?, ?, 1)', 'si', [$adminNote, $userId]);
	mysqli_stmt_close($logStatement);

	echo '<script>window.parent.location.href = "../holidays-mgmt.php?lang=' . $lang . '&status=updated";</script>';
	exit();
}
?>
<section id="edit-holiday-modal" class="modal-dialog modal-content overlay-def">
	<header class="modal-header">
		<h2 class="modal-title"><?= $t['heading'] ?></h2>
	</header>
	<div class="modal-body">
		<form method="post" action="/includes/edit-holiday.php?id=<?= $id ?>&lang=<?= $lang ?>">
			<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
			<div class="form-group">
				<label for="holiday_date"><?= $t['holiday_date'] ?> <strong class="required">(<?= $t['required'] ?>)</strong></label>
				<input type="date" class="form-control" id="holiday_date" name="holiday_date"
					value="<?= htmlspecialchars($holiday['holiday_date']) ?>" required>
			</div>
			<div class="form-group">
				<label for="name_en"><?= $t['name_en'] ?> <strong class="required">(<?= $t['required'] ?>)</strong></label>
				<input type="text" class="form-control full-width" id="name_en" name="name_en"
					value="<?= htmlspecialchars($holiday['name_en']) ?>" required>
			</div>
			<div class="form-group">
				<label for="name_fr"><?= $t['name_fr'] ?> <strong class="required">(<?= $t['required'] ?>)</strong></label>
				<input type="text" class="form-control full-width" id="name_fr" name="name_fr"
					value="<?= htmlspecialchars($holiday['name_fr']) ?>" required>
			</div>
			<div class="form-group">
				<input aria-describedby="holiday-help" type="checkbox" id="recurring-holiday-edit" name="recurring" value="1"
					<?= $holiday['recurring'] ? 'checked' : '' ?>>
				<label for="recurring-holiday-edit"><?= $t['recurring'] ?></label>
				<p id="holiday-help" class="help-block"><?= $t['recurring_help'] ?></p>
			</div>
			<div class="form-group">
				<input type="checkbox" id="active-holiday-edit" name="status" value="1"
					<?= $holiday['status'] ? 'checked' : '' ?>>
				<label for="active-holiday-edit"><?= $t['active'] ?></label>
			</div>
			<div class="form-group">
				<button type="submit" class="btn btn-primary"><?= $t['update_button'] ?></button>
				<button type="button" class="btn btn-default popup-modal-dismiss"><?= $t['cancel'] ?></button>
			</div>
		</form>
	</div>
</section>
<?php mysqli_close($link); ?>