<?php
// Start session
require_once __DIR__ . '/session_start.php';

// Check if Super Admin
if (!($_SESSION['is_superuser'] OR $_SESSION['is_admin'])) {
	header("Location: ../index.php");
	exit();
}

// HTTPS check
require('../includes/httpscheck.php');

// Database connection
require('../sql.php');

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
$id = isset($_GET['id']) ? mysqli_real_escape_string($link, $_GET['id']) : '';

if (empty($id)) {
	header("Location: ../holidays-mgmt.php?lang=$lang");
	exit();
}

// Fetch holiday
$sql = "SELECT * FROM tblholidays WHERE id = '$id'";
$result = rmt_admin_query($link, $sql);

if (rmt_result_num_rows($result) == 0) {
	header("Location: ../holidays-mgmt.php?lang=$lang");
	exit();
}

$holiday = mysqli_fetch_assoc($result);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	$holiday_date = mysqli_real_escape_string($link, $_POST['holiday_date']);
	$name_en = mysqli_real_escape_string($link, $_POST['name_en']);
	$name_fr = mysqli_real_escape_string($link, $_POST['name_fr']);
	$recurring = isset($_POST['recurring']) ? 1 : 0;
	$status = isset($_POST['status']) ? 1 : 0;

$updateSql = "UPDATE tblholidays
	                  SET holiday_date = '$holiday_date',
	                      name_en = '$name_en',
	                      name_fr = '$name_fr',
	                      recurring = $recurring,
	                      status = $status
                  WHERE id = '$id'";

	if (rmt_admin_query($link, $updateSql)) {
		// Log admin action
		$adminNote = ($lang == 'fr' ? "Mis à jour le jour férié : " : "Updated holiday: ") . "$name_en / $name_fr " . ($lang == 'fr' ? "le " : "on ") . "$holiday_date";
		$userId = $_SESSION['pid'];
		$logSql = "INSERT INTO tbladminlog (triageid, dateadded, notes, creatorid, status)
                   VALUES (0, NOW(), '$adminNote', $userId, 1)";
		rmt_admin_query($link, $logSql);

		echo '<script>window.parent.location.href = "../holidays-mgmt.php?lang=' . $lang . '&status=updated";</script>';
		exit();
	}
}
?>
<section id="edit-holiday-modal" class="modal-dialog modal-content overlay-def">
	<header class="modal-header">
		<h2 class="modal-title"><?= $t['heading'] ?></h2>
	</header>
	<div class="modal-body">
		<form method="post" action="/includes/edit-holiday.php?id=<?= $id ?>&lang=<?= $lang ?>">
			<div class="form-group">
				<label for="holiday_date"><?= $t['holiday_date'] ?> <strong class="required">(<?= $t['required'] ?>)</strong></label>
				<input type="date" class="form-control" id="holiday_date" name="holiday_date"
					value="<?= htmlspecialchars($holiday['holiday_date']) ?>" required>
			</div>
			<div class="form-group">
				<label for="name_en"><?= $t['name_en'] ?> <strong class="required">(<?= $t['required'] ?>)</strong></label>
				<input type="text" class="form-control" id="name_en" name="name_en"
					value="<?= htmlspecialchars($holiday['name_en']) ?>" required>
			</div>
			<div class="form-group">
				<label for="name_fr"><?= $t['name_fr'] ?> <strong class="required">(<?= $t['required'] ?>)</strong></label>
				<input type="text" class="form-control" id="name_fr" name="name_fr"
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