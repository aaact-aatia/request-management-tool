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

// Process the add product form
if ($_SERVER['REQUEST_METHOD']=='POST'){
	
	// Grab form elements
	$firstname = mysqli_real_escape_string($link,$_POST['firstname']);
	$lastname = mysqli_real_escape_string($link,$_POST['lastname']);
	$email = strtolower(mysqli_real_escape_string($link,$_POST['email']));
	$password = mysqli_real_escape_string($link,$_POST['password']);
	$accounttype = mysqli_real_escape_string($link,$_POST['accounttype']);
	$teamstring = "";
	if(!empty($_POST['teams'])) {
		foreach($_POST['teams'] as $teamid) {
			$teamstring .= $teamid . ",";
		}
		$teamstring = rtrim($teamstring, ',');
		//echo "Selected teams : " . $teamstring;
	} else {
			$teamstring = "";
		//echo "You did not choose a team.";
	}
	//exit();
	$date_now = date("Y-m-d H:i:s");
	$updatedby = $_SESSION['pid'];
	$status = 1;
	$noerror = false;
	
	// Custom form validation
	if ($firstname=="" OR $lastname=="" OR $email=="" OR $password=="" OR $accounttype=="") {
		$noerror = true;
	}

	// If error detected send user back to modal dialog
	if ($noerror) {
		header("location:/users.php?lang={$lang_code}?status=failed"); 
		exit();
	}
	
	$npassword = password_hash($password, PASSWORD_DEFAULT);
	
	// Create SQL statement
	$sql = "INSERT INTO tblusers(`firstname`, `lastname`, `email`, `password`, `accounttype`, `team`, `dateadded`, `dateupdated`, `updatedby`, `status`, `environment`) VALUES ('$firstname', '$lastname', '$email', '$npassword', '$accounttype', '$teamstring', '$date_now', '$date_now', '$updatedby', '$status', 1)";
	//echo $sql;
	//exit();
	mysqli_query($link,$sql);
	
	// Now redirect
	header("location:/users.php?lang={$lang_code}?status=success"); 
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
		'team_sort_field' => 'teamnameen'
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
		'team_sort_field' => 'teamnamefr'
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
				<?php
				$sql3 = "SELECT * FROM tblcontacts WHERE status='1' ORDER BY {$t['team_sort_field']} ASC";
				$result3 = mysqli_query($link,$sql3);	
				while($row3 = mysqli_fetch_array($result3)){
					$teamname = ($lang_code === 'fr') ? $row3['teamnamefr'] : $row3['teamnameen'];
				?>
				<div class="checkbox">
					<label><input type="checkbox" name="teams[]" value="<?= htmlspecialchars($row3['id']) ?>" id="<?= htmlspecialchars($row3['id']) ?>" />&#160;&#160;<?= htmlspecialchars($teamname) ?></label>
				</div>
				<?php
				}
				?>
			</fieldset>
		</div>
		<div class="form-group form-buttons">
			<button type="submit" class="btn btn-default"><?= htmlspecialchars($t['add_button']) ?></button>
		</div>
		</form>
	</div>
</section>
<?php
// Close connection
mysqli_close($link);
?>
