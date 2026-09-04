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

// Grab the catalogue id
if(!empty($_GET['v1']))
{
	$catalogueid = mysqli_real_escape_string($link,$_GET['v1']);
}
else
{
	$catalogueid = "";
}

	$serviceScopeFilter = '';
	if ((int)($_SESSION['atype'] ?? 0) === 5) {
		$teamIds = array_values(array_filter(array_map('intval', getEffectiveEmployeeTeamIds($link)), static function ($teamId) {
			return $teamId > 0;
		}));
		$serviceScopeFilter = empty($teamIds)
			? ' AND 1 = 0'
			: " AND (s.contactid IN (" . implode(',', $teamIds) . ") OR c.contactid IN (" . implode(',', $teamIds) . ") OR EXISTS (SELECT 1 FROM tblsubservices ss WHERE ss.serviceid = s.id AND ss.status = 1 AND COALESCE(NULLIF(ss.contactid, 0), NULLIF(s.contactid, 0), NULLIF(c.contactid, 0)) IN (" . implode(',', $teamIds) . ")))";
	}
	$sql = "SELECT s.id, s.$nameColumn AS name FROM tblservices s INNER JOIN tblcatalogue c ON c.id = s.catalogueid WHERE s.catalogueid='$catalogueid' AND s.status='1'" . $serviceScopeFilter . " ORDER BY s.$orderBy ASC";
	$result = mysqli_query($link, $sql);
	if ($result && mysqli_num_rows($result) > 0) {
	?>
				<label for="serviceid"><span class="field-name"><?= htmlspecialchars($translations['service_name'] ?? 'Service name:') ?></span></label>
				<select class="form-control full-width" id="serviceid" name="serviceid" onchange="ajax2(this.value)">
					<option value=""><?= htmlspecialchars($translations['select_service'] ?? 'Select a service name') ?></option>
					<?php 
					while($row2 = mysqli_fetch_array($result)){
					?>
					<option value="<?php echo $row2['id']; ?>"><?php echo htmlspecialchars($row2['name']); ?></option>
					<?php
					}
					?>
				</select>
<?php
}
// Close connection
mysqli_close($link);
?>
