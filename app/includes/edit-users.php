<?php
// This is called through ajax on the product management page

// Start session
require_once __DIR__ . '/session_start.php';

// Detect language
$lang = isset($_GET['lang']) ? $_GET['lang'] : (isset($_SESSION['lang']) ? $_SESSION['lang'] : 'en');
$is_french = ($lang === 'fr');

// Check if the user has the right priv's
if (!rmt_has_admin_access()) {
	header("location:/openrequest-" . $lang . ".php?status=accessdenied"); 
	exit();
}

// Grab MySQL connection
require('../sql.php');
/** @var mysqli $link */
require_once('helpers.php');
require_once('csrf.php');

// Now first get the ID
$userid = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 0;
$csrfToken = rmt_csrf_token('users');
$existingUser = $userid > 0
	? rmt_db_fetch_one($link, 'SELECT * FROM tblusers WHERE id = ?', 'i', [$userid])
	: null;

// Process the edit product form
if ($_SERVER['REQUEST_METHOD']=='POST'){
	if (!rmt_csrf_token_is_valid('users', (string) ($_POST['csrf_token'] ?? ''))) {
		header("location:/users.php?lang=" . $lang . "&status=failed");
		exit();
	}

	$firstname = trim((string) ($_POST['firstname'] ?? ''));
	$lastname = trim((string) ($_POST['lastname'] ?? ''));
	$email = strtolower(trim((string) ($_POST['email'] ?? '')));
	$password = (string) ($_POST['password'] ?? '');
	$password2 = (string) ($_POST['password2'] ?? '');
	$accounttype = filter_var($_POST['accounttype'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 3, 'max_range' => 6]]);
	$reportsToManager = !empty($_POST['reports_to_manager']);
	$managerId = 0;
	$isSuperuserRole = !empty($_POST['is_superuser_role']) ? 1 : 0;
	$isAdminRole = !empty($_POST['is_admin_role']) ? 1 : 0;
	if ($isSuperuserRole === 1) {
		$isAdminRole = 1;
	}
	if (!isSuperAdmin()) {
		$isSuperuserRole = (int) ($existingUser['is_superuser'] ?? 0);
		$isAdminRole = (int) ($existingUser['is_admin'] ?? 0);
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
	$updatedby = filter_var($_SESSION['pid'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 0;
	$npassword = '';
	
	$noerror = $existingUser === null || $userid <= 0 || $updatedby <= 0 || $firstname === '' || $lastname === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $accounttype === false;
	
	if ($password!="") {
		if ($password!=$password2) {
			$noerror = true;
		}
	}

	// Team assignment logic by account type
	// Note: Superuser role doesn't prevent team assignments; it's an additional privilege
	if ($accounttype == '6') {
		// Director: no teams
		$teamstring = "";
	} elseif ($accounttype == '5') {
		// Employee: 0 or 1 team
		if (count($selectedTeams) > 1) {
			$noerror = true;
		} else {
			$teamstring = !empty($selectedTeams) ? (string)$selectedTeams[0] : "";
		}
	} elseif ($accounttype == '4') {
		// Team Lead: must have at least 1 team
		if (count($selectedTeams) < 1) {
			$noerror = true;
		} else {
			$teamstring = implode(',', $selectedTeams);
		}
	} elseif ($accounttype == '3') {
		// Manager: can optionally have multiple teams
		// (If superuser role is set, they can manage globally or per-team)
		$teamstring = !empty($selectedTeams) ? implode(',', $selectedTeams) : "";
	} else {
		$noerror = true;
	}

	if ($accounttype !== 5) {
		$reportsToManager = false;
	} elseif ($reportsToManager) {
		$teamId = (int) ($teamstring ?: 0);
		$manager = $teamId > 0
			? rmt_db_fetch_one($link, 'SELECT id FROM tblusers WHERE atype = 3 AND status = 1 AND FIND_IN_SET(?, team) > 0 ORDER BY firstname ASC, lastname ASC, id ASC LIMIT 1', 'i', [$teamId])
			: null;
		if ($manager === null || (int) $manager['id'] === $userid) {
			$noerror = true;
		} else {
			$managerId = (int) $manager['id'];
		}
	}
	
	// If error detected send user back to modal dialog
	if ($noerror) {
		header("location:/users.php?lang=" . $lang . "&status=failed"); 
		exit();
	}
	if ($password!="") {
		$npassword = password_hash($password, PASSWORD_DEFAULT);
	}
	
	// Create SQL statement
	$hasSuperRoleColumn = rmt_db_column_exists($link, 'tblusers', 'is_superuser');
	$hasAdminRoleColumn = rmt_db_column_exists($link, 'tblusers', 'is_admin');
	$setClauses = ['firstname = ?', 'lastname = ?', 'email = ?', 'atype = ?', 'team = ?'];
	$types = 'sss is';
	$params = [$firstname, $lastname, $email, $accounttype, $teamstring];
	if ($accounttype === 5) {
		$setClauses[] = 'manager_id = ?';
		$types .= 'i';
		$params[] = $managerId > 0 ? $managerId : null;
	} elseif (in_array($accounttype, [3, 6], true)) {
		$setClauses[] = 'manager_id = NULL';
	}
	if ($password !== '') {
		$setClauses[] = 'password = ?';
		$types .= 's';
		$params[] = $npassword;
	}
	if ($hasSuperRoleColumn) {
		$setClauses[] = 'is_superuser = ?';
		$types .= 'i';
		$params[] = $isSuperuserRole;
	}
	if ($hasAdminRoleColumn) {
		$setClauses[] = 'is_admin = ?';
		$types .= 'i';
		$params[] = $isAdminRole;
	}
	$types .= 'i';
	$params[] = $userid;
	$statement = rmt_db_execute(
		$link,
		'UPDATE tblusers SET ' . implode(', ', $setClauses) . ' WHERE id = ?',
		str_replace(' ', '', $types),
		$params
	);
	mysqli_stmt_close($statement);
	
	// Now redirect
	header("location:/users.php?lang=" . $lang . "&status=success"); 
	exit();
}


if ($existingUser) {
	$row2 = $existingUser;
		$title = $is_french ? ('Modifier l\'utilisateur ' . $row2['firstname'] . ' ' . $row2['lastname']) : ('Edit user ' . $row2['firstname'] . ' ' . $row2['lastname']);
		$label_firstname = $is_french ? 'Prénom:' : 'First name:';
		$label_lastname = $is_french ? 'Nom:' : 'Last name:';
		$label_email = $is_french ? 'Courriel:' : 'Email:';
		$label_password = $is_french ? 'Mot de passe:' : 'Password:';
		$label_password_note = $is_french ? 'remplissez uniquement pour changer le mot de passe' : 'only fill in to change the password';
		$label_password2 = $is_french ? 'Confirmation du mot de passe:' : 'Confirm password:';
		$label_accounttype = $is_french ? 'Role:' : 'Role:';
		$label_teams = $is_french ? 'Équipe(s):' : 'Team(s):';
		$label_extra_roles = $is_french ? 'Permissions supplémentaires:' : 'Extra permissions:';
		$label_superuser = $is_french ? 'Privilèges de Super administrateur' : 'Super Admin privileges';
		$label_admin = $is_french ? 'Privilèges d\'administrateur' : 'Admin privileges';
		$label_extra_roles_hint = $is_french
			? 'Seul un Super administrateur peut attribuer ces privilèges. Super administrateur remplace administrateur.'
			: 'Only a Super Admin can assign these privileges. Super Admin overrides Admin.';
		$required_label = $is_french ? 'requis' : 'required';
		$save_btn = $is_french ? 'Sauvegarder' : 'Save';
		$sort_field = $is_french ? 'namefr' : 'nameen';
		$name_field = $is_french ? 'namefr' : 'nameen';
		$team_sort = $is_french ? 'namefr' : 'nameen';
		$team_name = $is_french ? 'namefr' : 'nameen';
		$hint_none = $is_french ? 'Aucune équipe n\'est assignée aux comptes Administrateur, Super administrateur et Externe.' : 'No team is assigned for Admin, Super Admin, and External accounts.';
		$hint_single = $is_french ? 'Un Employé peut avoir zero ou une équipe. Un Chef d\'équipe et un Gestionnaire peuvent avoir plusieurs équipes.' : 'Employee can have zero or one team. Team Lead and Manager can have multiple teams.';
	$currentIsSuperRole = ((int)($row2['is_superuser'] ?? 0) === 1);
	$currentIsAdminRole = ((int)($row2['is_admin'] ?? 0) === 1);
?>
<section id="filter-id" class="modal-dialog modal-content overlay-def">
	<header class="modal-header">
		<h2 class="modal-title"><?php echo $title ?></h2>
	</header>
	<div class="modal-body">
		<form method="post" action="/includes/edit-users.php?id=<?php echo $row2['id'] ?>&lang=<?php echo $lang ?>">
		<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
		<div class="form-group">
			<label for="firstname"><span class="field-name"><?php echo $label_firstname ?> <strong>(<?php echo $required_label ?>)</strong></span></label>
			<input type="text" class="form-control full-width" id="firstname" name="firstname" value="<?php echo $row2['firstname'] ?>" required>
		</div>
		<div class="form-group">
			<label for="lastname"><span class="field-name"><?php echo $label_lastname ?> <strong>(<?php echo $required_label ?>)</strong></span></label>
			<input type="text" class="form-control full-width" id="lastname" name="lastname" value="<?php echo $row2['lastname'] ?>" required>
		</div>
		<div class="form-group">
			<label for="email"><span class="field-name"><?php echo $label_email ?> <strong>(<?php echo $required_label ?>)</strong></span></label>
			<input type="email" class="form-control full-width" id="email" name="email" value="<?php echo $row2['email'] ?>" required>
		</div>
		<div class="form-group">
			<label for="password"><span class="field-name"><?php echo $label_password ?> <strong>(<?php echo $label_password_note ?>)</strong></span></label>
			<input type="password" class="form-control full-width" id="password" name="password" value="">
		</div>
		<div class="form-group">
			<label for="password2"><span class="field-name"><?php echo $label_password2 ?> <strong>(<?php echo $required_label ?>)</strong></span></label>
			<input type="password" class="form-control full-width" id="password2" name="password2" value="">
		</div>
		<div class="form-group">
			<label for="accounttype"><span class="field-name"><?php echo $label_accounttype ?> <strong>(<?php echo $required_label ?>)</strong></span></label>
			<select class="form-control full-width" id="accounttype" name="accounttype" required>
				<?php 
				$sql3 = "SELECT * FROM tblaccounttype WHERE status='1' AND id BETWEEN 3 AND 6 ORDER BY $sort_field ASC";
				$result3 = rmt_admin_query($link,$sql3);	
				while($row3 = rmt_result_fetch_array($result3)){
				?>
					<option value="<?php echo $row3['id']; ?>"<?php if($row3['id'] == $row2['atype']) echo " selected"; ?>><?php echo $row3[$name_field]; ?></option>
				<?php
				}
				?>
			</select>
		</div>
		<div class="form-group">
			<fieldset class="gc-chckbxrdio">
				<legend><?php echo htmlspecialchars($label_extra_roles); ?></legend>
				<p class="small"><?php echo htmlspecialchars($label_extra_roles_hint); ?></p>
				<ul class="list-unstyled lst-spcd-2">
					<li class="checkbox">
						<input type="checkbox" name="is_superuser_role" value="1" id="is-superuser-role"<?php echo $currentIsSuperRole ? ' checked="checked"' : ''; ?> />
						<label for="is-superuser-role"><?php echo htmlspecialchars($label_superuser); ?></label>
					</li>
					<li class="checkbox">
						<input type="checkbox" name="is_admin_role" value="1" id="is-admin-role"<?php echo ($currentIsAdminRole || $currentIsSuperRole) ? ' checked="checked"' : ''; ?> />
						<label for="is-admin-role"><?php echo htmlspecialchars($label_admin); ?></label>
					</li>
				</ul>
			</fieldset>
		</div>
		<div class="form-group gc-chckbxrdio">
			<div class="checkbox">
				<input type="checkbox" id="reports-to-manager" name="reports_to_manager" value="1"<?php echo !empty($row2['manager_id']) ? ' checked="checked"' : ''; ?> />
				<label for="reports-to-manager"><?php echo htmlspecialchars($is_french ? 'Relève du gestionnaire' : 'Reports to manager'); ?></label>
			</div>
			<p class="small"><?php echo htmlspecialchars($is_french ? 'L’employé relève du gestionnaire affecté à son équipe. Le chef d’équipe demeure la personne par défaut si cette option n’est pas sélectionnée.' : 'The employee reports to the manager assigned to their team. The team lead remains the fallback when this is not selected.'); ?></p>
		</div>
		<div class="form-group">
			<fieldset class="gc-chckbxrdio">
				<legend><?php echo $label_teams ?></legend>
				<p class="small"><?php echo htmlspecialchars($hint_none); ?><br><?php echo htmlspecialchars($hint_single); ?></p>
				<ul class="list-unstyled lst-spcd-2">
				<?php
				// First grab any existing teams
				$teams = $row2['team'];
				$tarray = explode(",",$teams);
				
				$sql3 = "SELECT * FROM tblteams ORDER BY $team_sort ASC";
				$result3 = rmt_admin_query($link,$sql3);	
				while($row3 = rmt_result_fetch_array($result3)){
				?>
					<li class="checkbox">
						<input type="checkbox" class="team-option" name="teams[]" value="<?php echo $row3['id']; ?>" id="team-<?php echo $row3['id']; ?>"<?php if(in_array((string)$row3['id'], $tarray)) {?> checked="checked"<?php } ?> />
						<label for="team-<?php echo $row3['id']; ?>"><?php echo htmlspecialchars($row3[$team_name]); ?></label>
					</li>
				<?php
				}
				?>
				</ul>
			</fieldset>
		</div>
		<div class="form-group form-buttons">
			<button type="submit" class="btn btn-default"><?php echo $save_btn ?></button>
			<button type="button" class="btn btn-default popup-modal-dismiss"><?= $is_french ? 'Annuler' : 'Cancel' ?></button>
		</div>
		<script src="/public/js/user-teams.js"></script>
		</form>
	</div>
</section>
<?php
} else {
// Wrong ID so display an error message
	$error_title = $is_french ? 'Oups, quelque chose s\'est mal passé!' : 'Oops something went wrong!';
	$error_message = $is_french ? 'Désolé, une erreur s\'est produite avec votre demande, veuillez réessayer!' : 'Sorry something went wrong with your request, please try again!';
?>
<section id="filter-id" class="modal-dialog modal-content overlay-def">
	<header class="modal-header">
		<h2 class="modal-title"><?php echo $error_title ?></h2>
	</header>
	<div class="modal-body">
		<p><?php echo $error_message ?></p>
	</div>
</section>
<?php
}
// Close connection
mysqli_close($link);
?>
