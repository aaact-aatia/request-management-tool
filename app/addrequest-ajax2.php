<?php
// Grab MySQL connection
if (session_status() != PHP_SESSION_ACTIVE)
{
	session_start();
}

require('sql.php');
/** @var mysqli $link */

// Determine language from session
$lang = $_SESSION['lang'] ?? 'en';
$nameColumn = ($lang === 'fr') ? 'namefr' : 'nameen';
$orderBy = ($lang === 'fr') ? 'namefr' : 'nameen';

// Load language file for translations
$translations = require("lang/{$lang}.php");

// Grab the service id
if(!empty($_GET['v1']))
{
	$serviceid = mysqli_real_escape_string($link,$_GET['v1']);
}
else
{
	$serviceid = "";
}

// Check if results otherwise return empty result
$sql = "SELECT * FROM tblsubservices WHERE serviceid='$serviceid' AND status='1' ORDER BY $orderBy ASC";

$result = mysqli_query($link,$sql);
//List it
if(mysqli_num_rows($result)>0){
?>
				<label for="subserviceid"><span class="field-name"><?= htmlspecialchars($translations['subservice_name'] ?? 'Sub-service name:') ?></span></label>
				<select class="form-control" id="subserviceid" name="subserviceid">
					<option value=""><?= htmlspecialchars($translations['select_subservice'] ?? 'Select a sub-service name') ?></option>
					<?php 
					$sql2 = "SELECT * FROM tblsubservices WHERE serviceid='$serviceid' AND status='1' ORDER BY $orderBy ASC";
					$result2 = mysqli_query($link,$sql2);	
					while($row2 = mysqli_fetch_array($result2)){
					?>
					<option value="<?php echo $row2['id']; ?>"><?php echo htmlspecialchars($row2[$nameColumn]); ?></option>
					<?php
					}
					?>
				</select>
<?php
}
// Close connection
mysqli_close($link);
?>
