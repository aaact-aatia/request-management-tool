<?php
// Grab MySQL connection
require_once __DIR__ . '/includes/session_start.php';

require('sql.php');
/** @var mysqli $link */

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

	$sql = "SELECT id, $nameColumn AS name FROM tblservices WHERE catalogueid='$catalogueid' AND status='1' ORDER BY $orderBy ASC";
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
