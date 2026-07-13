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

// Process the add product form
if ($_SERVER['REQUEST_METHOD']=='POST'){
	
	// Grab form elements
	$firstname = mysqli_real_escape_string($link,$_POST['firstname']);
	$lastname = mysqli_real_escape_string($link,$_POST['lastname']);
	$email = strtolower(mysqli_real_escape_string($link,$_POST['email']));
	$password = mysqli_real_escape_string($link,$_POST['password']);
	$accounttype = mysqli_real_escape_string($link,$_POST['accounttype']);
	$isSuperuserRole = !empty($_POST['is_superuser_role']) ? 1 : 0;
	$isAdminRole = !empty($_POST['is_admin_role']) ? 1 : 0;
	if ($isSuperuserRole === 1) {
		$isAdminRole = 1;
	}

	// Legacy compatibility: treat primary atype 1/2 as manager with elevated role flags.
	if ($accounttype === '1') {
		$accounttype = '3';
		$isSuperuserRole = 1;
		$isAdminRole = 1;
	} elseif ($accounttype === '2') {
		$accounttype = '3';
		$isAdminRole = 1;
	}

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

	// Team assignment logic by account type
	// Note: Superuser role doesn't prevent team assignments; it's an additional privilege
	if ($accounttype == '1' || $accounttype == '2' || $accounttype == '6') {
		// Super Admin, Admin, External: no teams
		$teamstring = "";
	} elseif ($accounttype == '5') {
		// Employee: 0 or 1 team
		if (count($selectedTeams) > 1) {
			$noerror = true;
		} else {
			$teamstring = !empty($selectedTeams) ? (string)$selectedTeams[0] : "";
		}
	} elseif ($accounttype == '4') {
		// Team Lead: teams are optional and may include multiple values
		$teamstring = !empty($selectedTeams) ? implode(',', $selectedTeams) : "";
	} elseif ($accounttype == '3') {
		// Manager: can optionally have multiple teams
		// (If superuser role is set, they can manage globally or per-team)
		$teamstring = !empty($selectedTeams) ? implode(',', $selectedTeams) : "";
	} else {
		$noerror = true;
	}

	// If error detected send user back to modal dialog
	if ($noerror) {
		header("location:/users.php?lang={$lang_code}&status=failed"); 
		exit();
	}

	// Prevent fatal database errors on duplicate user email.
	$existingEmailSql = "SELECT id FROM tblusers WHERE email='$email' LIMIT 1";
	$existingEmailResult = rmt_admin_query($link, $existingEmailSql);
	if (rmt_result_num_rows($existingEmailResult) > 0) {
		header("location:/users.php?lang={$lang_code}&status=duplicate_email");
		exit();
	}
	
	$npassword = password_hash($password, PASSWORD_DEFAULT);
	
	// Create SQL statement
	$hasSuperRoleColumn = rmt_db_column_exists($link, 'tblusers', 'is_superuser');
	$hasAdminRoleColumn = rmt_db_column_exists($link, 'tblusers', 'is_admin');

	$insertColumns = "`firstname`, `lastname`, `email`, `password`, `atype`, `manager_id`, `team`, `status`";
	$insertValues = "'$firstname', '$lastname', '$email', '$npassword', '$accounttype', NULL, '$teamstring', '$status'";
	if ($hasSuperRoleColumn) {
		$insertColumns .= ", `is_superuser`";
		$insertValues .= ", '$isSuperuserRole'";
	}
	if ($hasAdminRoleColumn) {
		$insertColumns .= ", `is_admin`";
		$insertValues .= ", '$isAdminRole'";
	}

	$sql = "INSERT INTO tblusers($insertColumns) VALUES ($insertValues)";
	//echo $sql;
	//exit();
	try {
		rmt_admin_query($link,$sql);
	} catch (mysqli_sql_exception $e) {
		if ((int)$e->getCode() === 1062) {
			header("location:/users.php?lang={$lang_code}&status=duplicate_email");
			exit();
		}

		throw $e;
	}
	
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
		'account_type' => 'Role:',
		'teams' => 'Team(s):',
		'required' => '(required)',
		'add_button' => 'Add',
		'add_in_progress_label' => 'Adding...',
		'add_in_progress_status' => 'Adding user, please wait...',
		'account_sort_field' => 'nameen',
		'team_sort_field' => 'nameen',
		'team_none_hint' => 'No team is assigned for Admin, Super Admin, and External accounts.',
		'team_single_hint' => 'Employee can have zero or one team. Team Lead and Manager can have multiple teams.',
		'extra_roles' => 'Extra permissions:',
		'extra_superuser' => 'Super Admin privileges',
		'extra_admin' => 'Admin privileges',
		'extra_roles_hint' => 'Only a Super Admin can assign these privileges. Super Admin overrides Admin.'
	],
	'fr' => [
		'modal_title' => 'Ajouter un nouvel utilisateur',
		'first_name' => 'Prénom:',
		'last_name' => 'Nom:',
		'email' => 'Courriel:',
		'password' => 'Mot de passe:',
		'account_type' => 'Role:',
		'teams' => 'Équipe(s):',
		'required' => '(requis)',
		'add_button' => 'Ajouter',
		'add_in_progress_label' => 'Ajout...',
		'add_in_progress_status' => 'Ajout de l\'utilisateur, veuillez patienter...',
		'account_sort_field' => 'namefr',
		'team_sort_field' => 'namefr',
		'team_none_hint' => 'Aucune équipe n\'est assignée aux comptes Administrateur, Super administrateur et Externe.',
		'team_single_hint' => 'Un Employé peut avoir zero ou une équipe. Un Chef d\'équipe et un Gestionnaire peuvent avoir plusieurs équipes.',
		'extra_roles' => 'Permissions supplémentaires :',
		'extra_superuser' => 'Privilèges de Super administrateur',
		'extra_admin' => 'Privilèges d\'administrateur',
		'extra_roles_hint' => 'Seul un Super administrateur peut attribuer ces privileges. Super administrateur remplace administrateur.'
	]
];

$t = $translations[$lang_code];
?>
<section id="filter-id" class="modal-dialog modal-content overlay-def">
	<header class="modal-header">
		<h2 class="modal-title"><?= htmlspecialchars($t['modal_title']) ?></h2>
	</header>
	<div class="modal-body">
		<form method="post" action="/includes/add-users.php" data-busy-label="<?= htmlspecialchars($t['add_in_progress_label']) ?>" data-busy-status="<?= htmlspecialchars($t['add_in_progress_status']) ?>">
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
				<legend><?= htmlspecialchars($t['extra_roles']) ?></legend>
				<p class="small"><?= htmlspecialchars($t['extra_roles_hint']) ?></p>
				<ul class="list-unstyled lst-spcd-2">
					<li class="checkbox">
						<input type="checkbox" name="is_superuser_role" value="1" id="is-superuser-role" />
						<label for="is-superuser-role"><?= htmlspecialchars($t['extra_superuser']) ?></label>
					</li>
					<li class="checkbox">
						<input type="checkbox" name="is_admin_role" value="1" id="is-admin-role" />
						<label for="is-admin-role"><?= htmlspecialchars($t['extra_admin']) ?></label>
					</li>
				</ul>
			</fieldset>
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
		<div class="form-group">
			<p class="small wb-inv" data-add-user-status role="status" aria-live="polite" aria-atomic="true"></p>
		</div>
		<script src="/public/js/user-teams.js"></script>
		</form>
	</div>
</section>
<?php
// Close connection
mysqli_close($link);
?>
