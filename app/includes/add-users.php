<?php
// This is called through ajax on the product management page

// Start session
if (session_status() != PHP_SESSION_ACTIVE)
{
	session_start();
}

// Set language
$lang_code = $_SESSION['lang'] ?? 'en';
$lang = require("../lang/{$lang_code}.php");

// Check if the user has the right priv's
if ($_SESSION['atype'] != 1) {
	header("location:/openrequest.php?lang={$lang_code}&status=accessdenied"); 
	exit();
}

// Grab MySQL connection
require('../sql.php');
/** @var mysqli $link */

// Process the add product form
if ($_SERVER['REQUEST_METHOD']=='POST'){
	
	// Grab form elements
	$firstname = mysqli_real_escape_string($link,$_POST['firstname']);
	$lastname = mysqli_real_escape_string($link,$_POST['lastname']);
	$email = strtolower(mysqli_real_escape_string($link,$_POST['email']));
	$password = mysqli_real_escape_string($link,$_POST['password']);
	$accounttype = mysqli_real_escape_string($link,$_POST['accounttype']);
	$selectedTeams = [];
	if (!empty($_POST['teams']) && is_array($_POST['teams'])) {
		foreach ($_POST['teams'] as $teamid) {
			$teamid = (int)$teamid;
			if ($teamid > 0) {
				$selectedTeams[] = $teamid;
			}
		}
	}
	$selectedTeams = array_values(array_unique($selectedTeams));
	$teamstring = "";
	//exit();
	$date_now = date("Y-m-d H:i:s");
	$updatedby = $_SESSION['pid'];
	$status = 1;
	$noerror = false;
	
	// Custom form validation
	if ($firstname=="" OR $lastname=="" OR $email=="" OR $password=="" OR $accounttype=="") {
		$noerror = true;
	}

	if ($accounttype == '1' || $accounttype == '2' || $accounttype == '6') {
		$teamstring = "";
	} elseif ($accounttype == '5') {
		if (count($selectedTeams) !== 1) {
			$noerror = true;
		} else {
			$teamstring = (string)$selectedTeams[0];
			$teamLeadCheck = rmt_admin_query($link, "SELECT id FROM tblteams WHERE id='" . (int)$selectedTeams[0] . "' AND status='1' AND team_lead_user_id IS NOT NULL LIMIT 1");
			if (!rmt_result_num_rows($teamLeadCheck)) {
				$noerror = true;
			}
		}
	} elseif ($accounttype == '4') {
		if (count($selectedTeams) < 1) {
			$noerror = true;
		} else {
			$teamstring = implode(',', $selectedTeams);
		}
	} elseif ($accounttype == '3') {
		if (count($selectedTeams) < 1) {
			$noerror = true;
		} else {
			$teamstring = implode(',', $selectedTeams);
		}
	} else {
		$noerror = true;
	}

	// If error detected send user back to modal dialog
	if ($noerror) {
		header("location:/users.php?lang={$lang_code}&status=failed"); 
		exit();
	}
	
	$npassword = password_hash($password, PASSWORD_DEFAULT);
	
	// Create SQL statement
	$sql = "INSERT INTO tblusers(`firstname`, `lastname`, `email`, `password`, `atype`, `manager_id`, `team`, `status`, `environment`) VALUES ('$firstname', '$lastname', '$email', '$npassword', '$accounttype', NULL, '$teamstring', '$status', 1)";
	//echo $sql;
	//exit();
	rmt_admin_query($link,$sql);
	
	// Now redirect
	header("location:/users.php?lang={$lang_code}&status=success"); 
	exit();
}

// Translation keys
$translations = [
	'en' => [
		'modal_title' => 'Add new user',
		'first_name' => 'First name:',
		'last_name' => 'Last name:',
		'email' => 'Email:',
		'password' => 'Password:',
		'account_type' => 'Account type:',
		'teams' => 'Team(s):',
		'required' => '(required)',
		'add_button' => 'Add',
		'account_sort_field' => 'nameen',
		'team_sort_field' => 'nameen',
		'team_none_hint' => 'No team is assigned for Admin, Super Admin, and External accounts.',
		'team_single_hint' => 'Employee must have exactly one team. Team Lead and Manager can have multiple teams.'
	],
	'fr' => [
		'modal_title' => 'Ajouter un nouvel utilisateur',
		'first_name' => 'Prénom:',
		'last_name' => 'Nom:',
		'email' => 'Courriel:',
		'password' => 'Mot de passe:',
		'account_type' => 'Type de compte:',
		'teams' => 'Équipe(s):',
		'required' => '(requis)',
		'add_button' => 'Ajouter',
		'account_sort_field' => 'namefr',
		'team_sort_field' => 'namefr',
		'team_none_hint' => 'Aucune équipe n\'est assignée aux comptes Administrateur, Super administrateur et Externe.',
		'team_single_hint' => 'Un Employé doit avoir exactement une équipe. Un Chef d\'équipe et un Gestionnaire peuvent avoir plusieurs équipes.'
	]
];

$t = $translations[$lang_code];
?>
<section id="filter-id" class="modal-dialog modal-content overlay-def">
	<header class="modal-header">
		<h2 class="modal-title"><?= htmlspecialchars($t['modal_title']) ?></h2>
	</header>
	<div class="modal-body">
		<form method="post" action="/includes/add-users.php">
		<div class="form-group">
			<label for="firstname"><span class="field-name"><?= htmlspecialchars($t['first_name']) ?> <strong><?= htmlspecialchars($t['required']) ?></strong></span></label>
			<input type="text" class="form-control" id="firstname" name="firstname" value="" required>
		</div>
		<div class="form-group">
			<label for="lastname"><span class="field-name"><?= htmlspecialchars($t['last_name']) ?> <strong><?= htmlspecialchars($t['required']) ?></strong></span></label>
			<input type="text" class="form-control" id="lastname" name="lastname" value="" required>
		</div>
		<div class="form-group">
			<label for="email"><span class="field-name"><?= htmlspecialchars($t['email']) ?> <strong><?= htmlspecialchars($t['required']) ?></strong></span></label>
			<input type="email" class="form-control" id="email" name="email" value="" required>
		</div>
		<div class="form-group">
			<label for="password"><span class="field-name"><?= htmlspecialchars($t['password']) ?> <strong><?= htmlspecialchars($t['required']) ?></strong></span></label>
			<input type="password" class="form-control" id="password" name="password" value="" required>
		</div>
		<div class="form-group">
			<label for="accounttype"><span class="field-name"><?= htmlspecialchars($t['account_type']) ?> <strong><?= htmlspecialchars($t['required']) ?></strong></span></label>
			<select class="form-control" id="accounttype" name="accounttype" required>
				<?php 
				$sql2 = "SELECT * FROM tblaccounttype WHERE status='1' ORDER BY {$t['account_sort_field']} ASC";
				$result2 = rmt_admin_query($link,$sql2);	
				while($row2 = rmt_result_fetch_array($result2)){
					$accountname = ($lang_code === 'fr') ? $row2['namefr'] : $row2['nameen'];
				?>
					<option value="<?= htmlspecialchars($row2['id']) ?>"><?= htmlspecialchars($accountname) ?></option>
				<?php
				}
				?>
			</select>
		</div>
		<div class="form-group">
			<fieldset class="gc-chckbxrdio">
				<legend><?= htmlspecialchars($t['teams']) ?></legend>
				<p class="small"><?= htmlspecialchars($t['team_none_hint']) ?><br><?= htmlspecialchars($t['team_single_hint']) ?></p>
				<ul class="list-unstyled lst-spcd-2">
				<?php
				$sql3 = "SELECT * FROM tblteams ORDER BY {$t['team_sort_field']} ASC";
				$result3 = rmt_admin_query($link,$sql3);	
				while($row3 = rmt_result_fetch_array($result3)){
					$teamname = ($lang_code === 'fr') ? $row3['namefr'] : $row3['nameen'];
				?>
					<li class="checkbox">
						<input type="checkbox" class="team-option" name="teams[]" value="<?= htmlspecialchars($row3['id']) ?>" id="team-<?= htmlspecialchars($row3['id']) ?>" />
						<label for="team-<?= htmlspecialchars($row3['id']) ?>"><?= htmlspecialchars($teamname) ?></label>
					</li>
				<?php
				}
				?>
				</ul>
			</fieldset>
		</div>
		<div class="form-group form-buttons">
			<button type="submit" class="btn btn-default"><?= htmlspecialchars($t['add_button']) ?></button>
			<button type="button" class="btn btn-default popup-modal-dismiss"><?= $lang_code === 'fr' ? 'Annuler' : 'Cancel' ?></button>
		</div>
		<script src="/public/js/user-teams.js"></script>
		</form>
	</div>
</section>
<?php
// Close connection
mysqli_close($link);
?>
