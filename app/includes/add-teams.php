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
/** @var mysqli $link */

// Process the add team form
if ($_SERVER['REQUEST_METHOD']=='POST'){
	
	// Grab form elements
	$teamnameen = mysqli_real_escape_string($link,$_POST['nameen']);
	$teamnamefr = mysqli_real_escape_string($link,$_POST['namefr']);
	$teamemail = mysqli_real_escape_string($link,$_POST['email']);
	$teamLeadUserId = !empty($_POST['team_lead_user_id']) ? (int)$_POST['team_lead_user_id'] : 0;
	$date_now = date("Y-m-d H:i:s");
	$updatedby = $_SESSION['pid'];
	$status = 1;
	$noerror = false;
	
	// Custom form validation - require team name and email
	if (empty($teamnameen) || empty($teamnamefr) || empty($teamemail)) {
		$noerror = true;
	}

	// Validate team lead if provided
	if (!$noerror && $teamLeadUserId > 0) {
		$leadCheckSql = "SELECT id FROM tblusers WHERE id='" . $teamLeadUserId . "' AND atype='4' AND status='1' LIMIT 1";
		$leadCheckResult = rmt_admin_query($link, $leadCheckSql);
		if (!rmt_result_num_rows($leadCheckResult)) {
			$noerror = true;
		}
	}

	// If error detected send user back to modal dialog
	if ($noerror) {
		header("location:/teams.php?lang={$lang_code}&status=failed"); 
		exit();
	}
	
	// Create SQL statement
	$teamLeadSqlValue = ($teamLeadUserId > 0) ? (string)$teamLeadUserId : "NULL";
	$sql = "INSERT INTO tblteams(`nameen`, `namefr`, `email`, `team_lead_user_id`, `dateadded`, `dateupdated`, `updatedby`, `status`) VALUES ('$teamnameen', '$teamnamefr', '$teamemail', $teamLeadSqlValue, '$date_now', '$date_now', '$updatedby', '$status')";
	rmt_admin_query($link,$sql);
	
	// Now redirect
	header("location:/teams.php?lang={$lang_code}&status=success"); 
	exit();
}

// Translation keys
$translations = [
	'en' => [
		'modal_title' => 'Add new team',
		'team_name_en' => 'Team name (english):',
		'team_name_fr' => 'Team name (french):',
		'team_email' => 'Team email:',
		'team_lead' => 'Team Lead:',
		'team_lead_hint' => 'Optional: The person responsible for day-to-day team operations.',
		'none_assigned' => 'None assigned',
		'required' => '(required)',
		'add_button' => 'Add'
	],
	'fr' => [
		'modal_title' => 'Ajouter une nouvelle équipe',
		'team_name_en' => 'Nom de l\'équipe (anglais):',
		'team_name_fr' => 'Nom de l\'équipe (français):',
		'team_email' => 'Courriel de l\'équipe:',
		'team_lead' => 'Chef d\'équipe:',
		'team_lead_hint' => 'Optionnel: La personne responsable des opérations quotidiennes de l\'équipe.',
		'none_assigned' => 'Aucun assigné',
		'required' => '(requis)',
		'add_button' => 'Ajouter'
	]
];

$t = $translations[$lang_code];
?>
<section id="filter-id" class="modal-dialog modal-content overlay-def">
	<header class="modal-header">
		<h2 class="modal-title"><?= htmlspecialchars($t['modal_title']) ?></h2>
	</header>
	<div class="modal-body">
		<form method="post" action="/includes/add-teams.php">
		<div class="form-group">
			<label for="nameen"><span class="field-name"><?= htmlspecialchars($t['team_name_en']) ?> <strong><?= htmlspecialchars($t['required']) ?></strong></span></label>
				<input type="text" class="form-control" id="nameen" name="nameen" value="" required>
		</div>
		<div class="form-group">
			<label for="namefr"><span class="field-name"><?= htmlspecialchars($t['team_name_fr']) ?> <strong><?= htmlspecialchars($t['required']) ?></strong></span></label>
				<input type="text" class="form-control" id="namefr" name="namefr" value="" required>
		</div>
		<div class="form-group">
			<label for="email"><span class="field-name"><?= htmlspecialchars($t['team_email']) ?> <strong><?= htmlspecialchars($t['required']) ?></strong></span></label>
				<input type="email" class="form-control" id="email" name="email" value="" required>
		</div>
		<div class="form-group">
			<label for="team_lead_user_id"><span class="field-name"><?= htmlspecialchars($t['team_lead']) ?></span></label>
			<select class="form-control" id="team_lead_user_id" name="team_lead_user_id">
				<option value=""><?= htmlspecialchars($t['none_assigned']) ?></option>
				<?php
				$leadSql = "SELECT id, firstname, lastname FROM tblusers WHERE atype='4' AND status='1' ORDER BY firstname ASC, lastname ASC";
				$leadResult = rmt_admin_query($link, $leadSql);
				while ($leadRow = rmt_result_fetch_array($leadResult)) {
				?>
					<option value="<?= (int)$leadRow['id'] ?>"><?= htmlspecialchars($leadRow['firstname'] . ' ' . $leadRow['lastname']) ?></option>
				<?php
				}
				?>
			</select>
			<p class="small"><?= htmlspecialchars($t['team_lead_hint']) ?></p>
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
