<?php
// Grab MySQL connection
require_once __DIR__ . '/includes/session_start.php';

require('sql.php');
/** @var mysqli $link */
require('includes/helpers.php');

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
$subserviceScopeFilter = '';
if ((int)($_SESSION['atype'] ?? 0) === 5) {
	$teamIds = array_values(array_filter(array_map('intval', getEffectiveEmployeeTeamIds($link)), static function ($teamId) {
		return $teamId > 0;
	}));
	$subserviceScopeFilter = empty($teamIds)
		? ' AND 1 = 0'
		: " AND COALESCE(NULLIF(tblsubservices.contactid, 0), NULLIF(s.contactid, 0), NULLIF(c.contactid, 0)) IN (" . implode(',', $teamIds) . ")";
}
$sql = "SELECT tblsubservices.* FROM tblsubservices INNER JOIN tblservices s ON s.id = tblsubservices.serviceid INNER JOIN tblcatalogue c ON c.id = s.catalogueid WHERE tblsubservices.serviceid='$serviceid' AND tblsubservices.status='1'" . $subserviceScopeFilter . " ORDER BY tblsubservices.$orderBy ASC";

$result = mysqli_query($link,$sql);
//List it
if(mysqli_num_rows($result)>0){
?>
				<label for="subserviceid"><span class="field-name"><?= htmlspecialchars($translations['subservice_name'] ?? 'Sub-service name:') ?></span></label>
				<select class="form-control full-width" id="subserviceid" name="subserviceid">
					<option value=""><?= htmlspecialchars($translations['select_subservice'] ?? 'Select a sub-service name') ?></option>
					<?php 
					$sql2 = $sql;
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
