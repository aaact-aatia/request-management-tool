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

// Now first get the ID
$serviceid = $_GET['id'];
$catalogueid = $_GET['cid'];

// Process the add product form
if ($_SERVER['REQUEST_METHOD']=='POST'){
	
	// Grab form elements
	$nameen = mysqli_real_escape_string($link,$_POST['nameen']);
	$namefr = mysqli_real_escape_string($link,$_POST['namefr']);
	$contactid = mysqli_real_escape_string($link,$_POST['contactid']);
	$sds = mysqli_real_escape_string($link,$_POST['sds']);
	$status = 1;
	$noerror = false;
	
	// Custom form validation
	if ($nameen=="" OR $namefr=="" OR $contactid=="" OR $sds=="" OR $serviceid=="") {
		$noerror = true;
	}

	// If error detected send user back to modal dialog
	if ($noerror) {
		header("location:/catalogue-sub-mgmt.php?lang={$lang_code}?id=$serviceid&cid=$catalogueid&status=failed"); 
		exit();
	}
	
	// Create SQL statement
	$sql = "INSERT INTO tblsubservices(`nameen`, `namefr`, `serviceid`, `contactid`, `sds`, `status`) VALUES ('$nameen', '$namefr', '$serviceid', '$contactid', '$sds', '$status')";
	//echo $sql;
	//exit();
	mysqli_query($link,$sql);
	
	// Now redirect
	header("location:/catalogue-sub-mgmt.php?lang={$lang_code}?id=$serviceid&cid=$catalogueid&status=success"); 
	exit();
}

// Grab the catalogue name
$sql = "SELECT * FROM tblcatalogue WHERE id='$catalogueid'";
$result = mysqli_query($link,$sql);
if(mysqli_num_rows($result)>0) {
	while($row = mysqli_fetch_array($result)) {
		$cataloguename = ($lang_code === 'fr') ? $row['namefr'] : $row['nameen'];
	}
}

// Grab the service name
$sql = "SELECT * FROM tblservices WHERE id='$serviceid'";
$result = mysqli_query($link,$sql);
if(mysqli_num_rows($result)>0) {
	while($row = mysqli_fetch_array($result)) {
		$servicename = ($lang_code === 'fr') ? $row['namefr'] : $row['nameen'];
	}
}

// Translation keys
$translations = [
	'en' => [
		'modal_title' => 'Add new sub-service item for',
		'name_en' => 'Name (english):',
		'name_fr' => 'Name (french):',
		'contact_group' => 'Contact group:',
		'sds' => 'Service delivery standard:',
		'days' => 'days',
		'required' => '(required)',
		'add_button' => 'Add',
		'team_sort_field' => 'teamnameen'
	],
	'fr' => [
		'modal_title' => 'Ajouter un nouvel élément de sous-service pour',
		'name_en' => 'Nom (anglais):',
		'name_fr' => 'Nom (français):',
		'contact_group' => 'Groupe de contact:',
		'sds' => 'Norme de prestation de services:',
		'days' => 'jours',
		'required' => '(requis)',
		'add_button' => 'Ajouter',
		'team_sort_field' => 'teamnamefr'
	]
];

$t = $translations[$lang_code];
?>
<section id="filter-id" class="modal-dialog modal-content overlay-def">
	<header class="modal-header">
		<h2 class="modal-title"><?= htmlspecialchars($t['modal_title']) ?> <?= htmlspecialchars($servicename) ?></h2>
	</header>
	<div class="modal-body">
		<form method="post" action="/includes/add-subservice.php?id=<?= htmlspecialchars($serviceid) ?>&cid=<?= htmlspecialchars($catalogueid) ?>">
		<div class="form-group">
			<label for="nameen"><span class="field-name"><?= htmlspecialchars($t['name_en']) ?> <strong><?= htmlspecialchars($t['required']) ?></strong></span></label>
			<input type="text" class="form-control" id="nameen" name="nameen" value="" required>
		</div>
		<div class="form-group">
			<label for="namefr"><span class="field-name"><?= htmlspecialchars($t['name_fr']) ?> <strong><?= htmlspecialchars($t['required']) ?></strong></span></label>
			<input type="text" class="form-control" id="namefr" name="namefr" value="" required>
		</div>
		<div class="form-group">
			<label for="contactid"><span class="field-name"><?= htmlspecialchars($t['contact_group']) ?> <strong><?= htmlspecialchars($t['required']) ?></strong></span></label>
			<select class="form-control" id="contactid" name="contactid" required>
				<?php 
				$sql2 = "SELECT * FROM tblcontacts WHERE status='1' ORDER BY {$t['team_sort_field']} ASC";
				$result2 = mysqli_query($link,$sql2);	
				while($row2 = mysqli_fetch_array($result2)){
					$teamname = ($lang_code === 'fr') ? $row2['teamnamefr'] : $row2['teamnameen'];
				?>
					<option value="<?= htmlspecialchars($row2['id']) ?>"><?= htmlspecialchars($teamname) ?></option>
				<?php
				}
				?>
			</select>
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
