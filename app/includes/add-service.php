<?php
// This is called through ajax on the product management page

// Start session
require_once __DIR__ . '/session_start.php';

// Set language
$lang_code = $_SESSION['lang'] ?? 'en';
$lang = require("../lang/{$lang_code}.php");

// Check if the user has the right priv's
if (!($_SESSION['is_superuser'] OR $_SESSION['is_admin'])) {
	header("location:/openrequest.php?lang={$lang_code}&status=accessdenied"); 
	exit();
}

// Grab MySQL connection
require('../sql.php');
require_once('helpers.php');

// Now first get the ID
$catalogueid = $_GET['id'];

// Process the add product form
if ($_SERVER['REQUEST_METHOD']=='POST'){
	
	// Grab form elements
	$nameen = mysqli_real_escape_string($link,$_POST['nameen']);
	$namefr = mysqli_real_escape_string($link,$_POST['namefr']);
	$sds = mysqli_real_escape_string($link,$_POST['sds']);
	$contactId = (int) ($_POST['contactid'] ?? 0);
	$status = isset($_POST['status']) ? 1 : 0;
	$noerror = false;
	
	// Custom form validation
	if ($nameen=="" OR $namefr=="" OR $sds=="" OR $catalogueid=="" || ($contactId > 0 && !rmt_db_fetch_one($link, 'SELECT id FROM tblteams WHERE id = ? AND status = 1', 'i', [$contactId]))) {
		$noerror = true;
	}
	$contactId = $contactId > 0 ? $contactId : null;

	// If error detected send user back to modal dialog
	if ($noerror) {
		header("location:/catalogue-mgmt.php?lang={$lang_code}&id=$catalogueid&status=failed");
		exit();
	}
	
	// Create SQL statement
	$statement = rmt_db_execute(
		$link,
		'INSERT INTO tblservices (nameen, namefr, catalogueid, sds, contactid, status) VALUES (?, ?, ?, ?, ?, ?)',
		'ssiiii',
		[$nameen, $namefr, $catalogueid, $sds, $contactId, $status]
	);
	mysqli_stmt_close($statement);
	
	// Now redirect
	header("location:/catalogue-mgmt.php?lang={$lang_code}&id=$catalogueid&status=success");
	exit();
}

// Grab the catalogue name
$parentTeamId = 0;
$sql = "SELECT * FROM tblcatalogue WHERE id='$catalogueid'";
$result = rmt_admin_query($link,$sql);
if(rmt_result_num_rows($result)>0) {
	while($row = rmt_result_fetch_array($result)) {
		$cataloguename = ($lang_code === 'fr') ? $row['namefr'] : $row['nameen'];
		$parentTeamId = rmt_resolve_responsible_team_id($link, (int) $row['id']);
	}
}
$parentTeam = $parentTeamId > 0 ? rmt_db_fetch_one($link, 'SELECT nameen, namefr FROM tblteams WHERE id = ?', 'i', [$parentTeamId]) : null;
$parentTeamName = $parentTeam[$lang_code === 'fr' ? 'namefr' : 'nameen'] ?? ($lang_code === 'fr' ? 'aucune équipe' : 'no team');
$teams = rmt_get_active_teams($link);

// Translation keys
$translations = [
	'en' => [
		'modal_title' => 'Add new service item for',
		'name_en' => 'Name (english):',
		'name_fr' => 'Name (french):',
		'sds' => 'Service delivery standard:',
		'days' => 'days',
		'required' => '(required)',
		'active' => 'Active',
		'responsible_team' => 'Responsible team',
		'inherit_team' => 'Inherit from catalogue',
		'add_button' => 'Add'
	],
	'fr' => [
		'modal_title' => 'Ajouter un nouvel élément de service pour',
		'name_en' => 'Nom (anglais):',
		'name_fr' => 'Nom (français):',
		'sds' => 'Norme de prestation de services:',
		'days' => 'jours',
		'required' => '(requis)',
		'active' => 'Actif',
		'responsible_team' => 'Équipe responsable',
		'inherit_team' => 'Hériter du catalogue',
		'add_button' => 'Ajouter'
	]
];

$t = $translations[$lang_code];
?>
<section id="filter-id" class="modal-dialog modal-content overlay-def">
	<header class="modal-header">
		<h2 class="modal-title"><?= htmlspecialchars($t['modal_title']) ?> <?= htmlspecialchars($cataloguename) ?></h2>
	</header>
	<div class="modal-body">
		<form method="post" action="/includes/add-service.php?id=<?= htmlspecialchars($catalogueid) ?>">
		<div class="form-group">
			<label for="nameen"><span class="field-name"><?= htmlspecialchars($t['name_en']) ?> <strong><?= htmlspecialchars($t['required']) ?></strong></span></label>
			<input type="text" class="form-control" id="nameen" name="nameen" value="" required>
		</div>
		<div class="form-group">
			<label for="namefr"><span class="field-name"><?= htmlspecialchars($t['name_fr']) ?> <strong><?= htmlspecialchars($t['required']) ?></strong></span></label>
			<input type="text" class="form-control" id="namefr" name="namefr" value="" required>
		</div>
		<div class="form-group">
			<label for="sds"><span class="field-name"><?= htmlspecialchars($t['sds']) ?> <strong><?= htmlspecialchars($t['required']) ?></strong></span></label>
			<select class="form-control" id="sds" name="sds" required>
				<?php
				// Create range for SDS
				$range = range(1,30);
				foreach ($range as $sdsv) {
				echo "<option value='$sdsv'>$sdsv {$t['days']}</option>";
				}
				?>
			</select>
		</div>
		<div class="form-group">
			<label for="contactid"><span class="field-name"><?= htmlspecialchars($t['responsible_team']) ?></span></label>
			<select class="form-control" id="contactid" name="contactid">
				<option value=""><?= htmlspecialchars($t['inherit_team'] . ' (' . $parentTeamName . ')') ?></option>
				<?php foreach ($teams as $team): ?>
				<option value="<?= (int) $team['id'] ?>"><?= htmlspecialchars($team[$lang_code === 'fr' ? 'namefr' : 'nameen']) ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="checkbox">
			<label for="status"><input type="checkbox" id="status" name="status" value="1" checked> <?= htmlspecialchars($t['active']) ?></label>
		</div>
		<div class="form-group form-buttons">
			<button type="submit" class="btn btn-default"><?= htmlspecialchars($t['add_button']) ?></button>
			<button type="button" class="btn btn-default popup-modal-dismiss"><?= $lang_code === 'fr' ? 'Annuler' : 'Cancel' ?></button>
		</div>
		</form>
	</div>
</section>
<?php
// Close connection
mysqli_close($link);
?>
