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
	} elseif ($accounttype == '4' || $accounttype == '5') {
		if (count($selectedTeams) !== 1) {
			$noerror = true;
		} else {
			$teamstring = (string)$selectedTeams[0];
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
	$sql = "INSERT INTO tblusers(`firstname`, `lastname`, `email`, `password`, `atype`, `team`, `status`, `environment`) VALUES ('$firstname', '$lastname', '$email', '$npassword', '$accounttype', '$teamstring', '$status', 1)";
	//echo $sql;
	//exit();
	mysqli_query($link,$sql);
	
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
		'team_single_hint' => 'Team Lead and Employee must have exactly one team. Manager can have multiple teams.'
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
		'team_single_hint' => 'Un Chef d\'équipe et un Employé doivent avoir exactement une équipe. Un Gestionnaire peut avoir plusieurs équipes.'
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
				$result2 = mysqli_query($link,$sql2);	
				while($row2 = mysqli_fetch_array($result2)){
					$accountname = ($lang_code === 'fr') ? $row2['namefr'] : $row2['nameen'];
				?>
					<option value="<?= htmlspecialchars($row2['id']) ?>"><?= htmlspecialchars($accountname) ?></option>
				<?php
				}
				?>
			</select>
		</div>
		<div class="form-group">
			<fieldset class="chkbxrdio-grp">
				<legend><span class="field-name"><?= htmlspecialchars($t['teams']) ?></span></legend>
				<p class="small"><?= htmlspecialchars($t['team_none_hint']) ?><br><?= htmlspecialchars($t['team_single_hint']) ?></p>
				<?php
				$sql3 = "SELECT * FROM tblteams WHERE status='1' ORDER BY {$t['team_sort_field']} ASC";
				$result3 = mysqli_query($link,$sql3);	
				while($row3 = mysqli_fetch_array($result3)){
					$teamname = ($lang_code === 'fr') ? $row3['namefr'] : $row3['nameen'];
				?>
				<div class="checkbox">
					<label><input type="checkbox" class="team-option" name="teams[]" value="<?= htmlspecialchars($row3['id']) ?>" id="team-<?= htmlspecialchars($row3['id']) ?>" />&#160;&#160;<?= htmlspecialchars($teamname) ?></label>
				</div>
				<?php
				}
				?>
			</fieldset>
		</div>
		<div class="form-group form-buttons">
			<button type="submit" class="btn btn-default"><?= htmlspecialchars($t['add_button']) ?></button>
		</div>
		<script>
		(function () {
			var accountType = document.getElementById('accounttype');
			var teamBoxes = document.querySelectorAll('.team-option');
			function updateTeamSelectionRules() {
				var role = accountType.value;
				var noTeamRoles = ['1', '2', '6'];
				var singleTeamRoles = ['4', '5'];

				if (noTeamRoles.indexOf(role) !== -1) {
					teamBoxes.forEach(function (cb) {
						cb.checked = false;
						cb.disabled = true;
					});
					return;
				}

				teamBoxes.forEach(function (cb) {
					cb.disabled = false;
				});

				if (singleTeamRoles.indexOf(role) !== -1) {
					var checked = Array.prototype.filter.call(teamBoxes, function (cb) { return cb.checked; });
					if (checked.length > 1) {
						checked.slice(1).forEach(function (cb) { cb.checked = false; });
					}
				}
			}

			teamBoxes.forEach(function (cb) {
				cb.addEventListener('change', function () {
					if (['4', '5'].indexOf(accountType.value) !== -1) {
						teamBoxes.forEach(function (other) {
							if (other !== cb) {
								other.checked = false;
							}
						});
					}
				});
			});

			accountType.addEventListener('change', updateTeamSelectionRules);
			updateTeamSelectionRules();
		})();
		</script>
		</form>
	</div>
</section>
<?php
// Close connection
mysqli_close($link);
?>
