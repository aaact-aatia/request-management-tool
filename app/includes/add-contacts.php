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
	$teamnameen = mysqli_real_escape_string($link,$_POST['teamnameen']);
	$teamnamefr = mysqli_real_escape_string($link,$_POST['teamnamefr']);
	$teamemail = mysqli_real_escape_string($link,$_POST['teamemail']);
	$contactname = mysqli_real_escape_string($link,$_POST['contactname']);
	$contactemail = mysqli_real_escape_string($link,$_POST['contactemail']);
	$escalationcontactname = mysqli_real_escape_string($link,$_POST['escalationcontactname']);
	$escalationcontactemail = mysqli_real_escape_string($link,$_POST['escalationcontactemail']);
	$date_now = date("Y-m-d H:i:s");
	$updatedby = $_SESSION['pid'];
	$status = 1;
	$noerror = false;
	
	// Custom form validation
	if ($teamnameen=="" OR $teamnamefr=="" OR $teamemail=="" OR $contactname=="" OR $contactemail=="" OR $escalationcontactname=="" OR $escalationcontactemail=="") {
		$noerror = true;
	}

	// If error detected send user back to modal dialog
	if ($noerror) {
		header("location:/contacts.php?lang={$lang_code}&status=failed"); 
		exit();
	}
	
	// Create SQL statement
	$sql = "INSERT INTO tblcontacts(`teamnameen`, `teamnamefr`, `teamemail`, `contactname`, `contactemail`, `escalationcontactname`, `escalationcontactemail`, `dateadded`, `dateupdated`, `updatedby`, `status`) VALUES ('$teamnameen', '$teamnamefr', '$teamemail', '$contactname', '$contactemail', '$escalationcontactname', '$escalationcontactemail', '$date_now', '$date_now', '$updatedby', '$status')";
	//echo $sql;
	//exit();
	mysqli_query($link,$sql);
	
	// Now redirect
	header("location:/contacts.php?lang={$lang_code}&status=success"); 
	exit();
}

// Translation keys
$translations = [
	'en' => [
		'modal_title' => 'Add new contact',
		'team_name_en' => 'Team name (english):',
		'team_name_fr' => 'Team name (french):',
		'team_email' => 'Team email:',
		'contact_name' => 'Contact name:',
		'contact_email' => 'Contact email:',
		'escalation_contact_name' => 'Escalation contact name:',
		'escalation_contact_email' => 'Escalation contact email:',
		'required' => '(required)',
		'add_button' => 'Add'
	],
	'fr' => [
		'modal_title' => 'Ajouter un nouveau contact',
		'team_name_en' => 'Nom de l\'équipe (anglais):',
		'team_name_fr' => 'Nom de l\'équipe (français):',
		'team_email' => 'Courriel de l\'équipe:',
		'contact_name' => 'Nom du contact:',
		'contact_email' => 'Courriel du contact:',
		'escalation_contact_name' => 'Nom du contact d\'escalade:',
		'escalation_contact_email' => 'Courriel du contact d\'escalade:',
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
		<form method="post" action="/includes/add-contacts.php">
		<div class="form-group">
			<label for="teamnameen"><span class="field-name"><?= htmlspecialchars($t['team_name_en']) ?> <strong><?= htmlspecialchars($t['required']) ?></strong></span></label>
			<input type="text" class="form-control" id="teamnameen" name="teamnameen" value="" required>
		</div>
		<div class="form-group">
			<label for="teamnamefr"><span class="field-name"><?= htmlspecialchars($t['team_name_fr']) ?> <strong><?= htmlspecialchars($t['required']) ?></strong></span></label>
			<input type="text" class="form-control" id="teamnamefr" name="teamnamefr" value="" required>
		</div>
		<div class="form-group">
			<label for="teamemail"><span class="field-name"><?= htmlspecialchars($t['team_email']) ?> <strong><?= htmlspecialchars($t['required']) ?></strong></span></label>
			<input type="email" class="form-control" id="teamemail" name="teamemail" value="" required>
		</div>
		<div class="form-group">
			<label for="contactname"><span class="field-name"><?= htmlspecialchars($t['contact_name']) ?> <strong><?= htmlspecialchars($t['required']) ?></strong></span></label>
			<input type="text" class="form-control" id="contactname" name="contactname" value="" required>
		</div>
		<div class="form-group">
			<label for="contactemail"><span class="field-name"><?= htmlspecialchars($t['contact_email']) ?> <strong><?= htmlspecialchars($t['required']) ?></strong></span></label>
			<input type="email" class="form-control" id="contactemail" name="contactemail" value="" required>
		</div>
		<div class="form-group">
			<label for="escalationcontactname"><span class="field-name"><?= htmlspecialchars($t['escalation_contact_name']) ?> <strong><?= htmlspecialchars($t['required']) ?></strong></span></label>
			<input type="text" class="form-control" id="escalationcontactname" name="escalationcontactname" value="" required>
		</div>
		<div class="form-group">
			<label for="escalationcontactemail"><span class="field-name"><?= htmlspecialchars($t['escalation_contact_email']) ?> <strong><?= htmlspecialchars($t['required']) ?></strong></span></label>
			<input type="email" class="form-control" id="escalationcontactemail" name="escalationcontactemail" value="" required>
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
