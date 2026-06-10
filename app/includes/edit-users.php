<?php
// This is called through ajax on the product management page

// Start session
if (session_status() != PHP_SESSION_ACTIVE)
{
	session_start();
}

// Detect language
$lang = isset($_GET['lang']) ? $_GET['lang'] : (isset($_SESSION['lang']) ? $_SESSION['lang'] : 'en');
$is_french = ($lang === 'fr');

// Check if the user has the right priv's
if ($_SESSION['atype'] != 1) {
	header("location:/openrequest-" . $lang . ".php?status=accessdenied"); 
	exit();
}

// Grab MySQL connection
require('../sql.php');
/** @var mysqli $link */

// Now first get the ID
$userid = $_GET['id'];

// Process the edit product form
if ($_SERVER['REQUEST_METHOD']=='POST'){
	
	// Grab form elements
	$firstname = mysqli_real_escape_string($link,$_POST['firstname']);
	$lastname = mysqli_real_escape_string($link,$_POST['lastname']);
	$email = strtolower(mysqli_real_escape_string($link,$_POST['email']));
	$password = mysqli_real_escape_string($link,$_POST['password']);
	$password2 = mysqli_real_escape_string($link,$_POST['password2']);
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
	$noerror = false;
	$npassword = '';
	
	// Custom form validation
	if ($firstname=="" OR $lastname=="" OR $email=="" OR $accounttype=="") {
		$noerror = true;
	}
	
	if ($password!="") {
		if ($password!=$password2) {
			$noerror = true;
		}
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
		header("location:/users.php?lang=" . $lang . "&status=failed"); 
		exit();
	}
	if ($password!="") {
		$npassword = password_hash($password, PASSWORD_DEFAULT);
	}
	
	// Create SQL statement
	if ($password!="") {
		$sql = "UPDATE `tblusers` SET `firstname` = '$firstname', `lastname` = '$lastname', `email` = '$email', `password` = '$npassword', `atype` = '$accounttype', `team` = '$teamstring' WHERE id='$userid'";
	} else {
		$sql = "UPDATE `tblusers` SET `firstname` = '$firstname', `lastname` = '$lastname', `email` = '$email', `atype` = '$accounttype', `team` = '$teamstring' WHERE id='$userid'";
	}		
	//echo $sql;
	mysqli_query($link,$sql);
	
	// Now redirect
	header("location:/users.php?lang=" . $lang . "&status=success"); 
	exit();
}

// Construct SQL statement
$sql2 = "SELECT * FROM tblusers WHERE id='$userid'";

$result2 = mysqli_query($link,$sql2);
//List it
if(mysqli_num_rows($result2)>0){
	while($row2 = mysqli_fetch_array($result2)){
		$title = $is_french ? ('Modifier l\'utilisateur ' . $row2['firstname'] . ' ' . $row2['lastname']) : ('Edit user ' . $row2['firstname'] . ' ' . $row2['lastname']);
		$label_firstname = $is_french ? 'Prénom:' : 'First name:';
		$label_lastname = $is_french ? 'Nom:' : 'Last name:';
		$label_email = $is_french ? 'Courriel:' : 'Email:';
		$label_password = $is_french ? 'Mot de passe:' : 'Password:';
		$label_password_note = $is_french ? 'remplissez uniquement pour changer le mot de passe' : 'only fill in to change the password';
		$label_password2 = $is_french ? 'Confirmation du mot de passe:' : 'Confirm password:';
		$label_accounttype = $is_french ? 'Type de compte:' : 'Account type:';
		$label_teams = $is_french ? 'Équipe(s):' : 'Team(s):';
		$required_label = $is_french ? 'requis' : 'required';
		$save_btn = $is_french ? 'Sauvegarder' : 'Save';
		$sort_field = $is_french ? 'namefr' : 'nameen';
		$name_field = $is_french ? 'namefr' : 'nameen';
		$team_sort = $is_french ? 'namefr' : 'nameen';
		$team_name = $is_french ? 'namefr' : 'nameen';
		$hint_none = $is_french ? 'Aucune équipe n\'est assignée aux comptes Administrateur, Super administrateur et Externe.' : 'No team is assigned for Admin, Super Admin, and External accounts.';
		$hint_single = $is_french ? 'Un Chef d\'équipe et un Employé doivent avoir exactement une équipe. Un Gestionnaire peut avoir plusieurs équipes.' : 'Team Lead and Employee must have exactly one team. Manager can have multiple teams.';
?>
<section id="filter-id" class="modal-dialog modal-content overlay-def">
	<header class="modal-header">
		<h2 class="modal-title"><?php echo $title ?></h2>
	</header>
	<div class="modal-body">
		<form method="post" action="/includes/edit-users.php?id=<?php echo $row2['id'] ?>&lang=<?php echo $lang ?>">
		<div class="form-group">
			<label for="firstname"><span class="field-name"><?php echo $label_firstname ?> <strong>(<?php echo $required_label ?>)</strong></span></label>
			<input type="text" class="form-control" id="firstname" name="firstname" value="<?php echo $row2['firstname'] ?>" required>
		</div>
		<div class="form-group">
			<label for="lastname"><span class="field-name"><?php echo $label_lastname ?> <strong>(<?php echo $required_label ?>)</strong></span></label>
			<input type="text" class="form-control" id="lastname" name="lastname" value="<?php echo $row2['lastname'] ?>" required>
		</div>
		<div class="form-group">
			<label for="email"><span class="field-name"><?php echo $label_email ?> <strong>(<?php echo $required_label ?>)</strong></span></label>
			<input type="email" class="form-control" id="email" name="email" value="<?php echo $row2['email'] ?>" required>
		</div>
		<div class="form-group">
			<label for="password"><span class="field-name"><?php echo $label_password ?> <strong>(<?php echo $label_password_note ?>)</strong></span></label>
			<input type="password" class="form-control" id="password" name="password" value="">
		</div>
		<div class="form-group">
			<label for="password2"><span class="field-name"><?php echo $label_password2 ?> <strong>(<?php echo $required_label ?>)</strong></span></label>
			<input type="password" class="form-control" id="password2" name="password2" value="">
		</div>
		<div class="form-group">
			<label for="accounttype"><span class="field-name"><?php echo $label_accounttype ?> <strong>(<?php echo $required_label ?>)</strong></span></label>
			<select class="form-control" id="accounttype" name="accounttype" required>
				<?php 
				$sql3 = "SELECT * FROM tblaccounttype WHERE status='1' ORDER BY $sort_field ASC";
				$result3 = mysqli_query($link,$sql3);	
				while($row3 = mysqli_fetch_array($result3)){
				?>
					<option value="<?php echo $row3['id']; ?>"<?php if($row3['id'] == $row2['atype']) echo " selected"; ?>><?php echo $row3[$name_field]; ?></option>
				<?php
				}
				?>
			</select>
		</div>
		<div class="form-group">
			<fieldset class="chkbxrdio-grp">
				<legend><span class="field-name"><?php echo $label_teams ?></span></legend>
				<p class="small"><?php echo htmlspecialchars($hint_none); ?><br><?php echo htmlspecialchars($hint_single); ?></p>
				<?php
				// First grab any existing teams
				$teams = $row2['team'];
				$tarray = explode(",",$teams);
				
				$sql3 = "SELECT * FROM tblteams WHERE status='1' ORDER BY $team_sort ASC";
				$result3 = mysqli_query($link,$sql3);	
				while($row3 = mysqli_fetch_array($result3)){
				?>
				<div class="checkbox">
					<label><input type="checkbox" class="team-option" name="teams[]" value="<?php echo $row3['id']; ?>" id="team-<?php echo $row3['id']; ?>"<?php if(in_array((string)$row3['id'], $tarray)) {?> checked="checked"<?php } ?> />&#160;&#160;<?php echo htmlspecialchars($row3[$team_name]); ?></label>
				</div>
				<?php
				}
				?>
			</fieldset>
		</div>
		<div class="form-group form-buttons">
			<button type="submit" class="btn btn-default"><?php echo $save_btn ?></button>
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
	}
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
