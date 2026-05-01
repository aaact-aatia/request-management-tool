<?php
if (session_status() != PHP_SESSION_ACTIVE)
{
	session_start();
}
// Grab MySQL connection
require('sql.php');

// Get language from session
$lang = $_SESSION['lang'] ?? 'en';

// Grab the catalogue id
if(!empty($_GET['v1']))
{
	$subserviceid2 = mysqli_real_escape_string($link,$_GET['v1']);
}
else
{
	$subserviceid2 = "";
}

// Translation arrays
$translations = [
	'continue' => [
		'en' => 'Continue',
		'fr' => 'Continuer'
	],
	'complete_checklist_warning' => [
		'en' => 'Please complete the checklist or correct all previous failures before continuing.',
		'fr' => 'Veuillez compléter la liste de contrôle ou corriger tous les échecs précédents avant de continuer.'
	]
];

if ($subserviceid2=='6:1:1:1' OR $subserviceid2=='6:2:1:1' OR $subserviceid2=='6:5:1:1' OR $subserviceid2=='6:5:2:1' OR $subserviceid2=='8:1:1:1' OR $subserviceid2=='8:1:2:1' OR $subserviceid2=='8:2:2:1:1' OR $subserviceid2=='8:2:2:2:1') {
?>
				<div class="form-group form-buttons">
					<button type="submit" class="btn btn-primary"><?php echo $translations['continue'][$lang]; ?></button>
				</div>
<?php	
} elseif ($subserviceid2=='6:1:1:2' OR $subserviceid2=='6:2:1:2' OR $subserviceid2=='6:5:1:2' OR $subserviceid2=='6:5:2:2' OR $subserviceid2=='8:1:1:2' OR $subserviceid2=='8:1:2:2' OR $subserviceid2=='8:2:2:1:2' OR $subserviceid2=='8:2:2:2:2') {
?>
				<div class="alert alert-warning">
					<p tabindex="0"><?php echo $translations['complete_checklist_warning'][$lang]; ?></p>
				</div>
				
<?php
}
// Close connection
mysqli_close($link);
?>
