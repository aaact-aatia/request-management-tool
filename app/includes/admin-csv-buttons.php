<?php
if (isset($_SERVER['SCRIPT_FILENAME']) && realpath(__FILE__) === realpath((string) $_SERVER['SCRIPT_FILENAME'])) {
    http_response_code(404);
    exit();
}


/**
 * Helper: Render CSV export/import for admin pages
 * 
 * Usage: 
 * $tableName = 'tblteams';
 * $langFile = require("lang/{$_SESSION['lang']}.php");
 * include('includes/admin-csv-buttons.php');
 */

if (!isset($tableName)) {
	return;
}

// Only show CSV buttons to super admin
if (!($_SESSION['is_superuser'] OR $_SESSION['is_admin'])) {
	return;
}

$lang = $_SESSION['lang'] ?? 'en';

// Use provided langFile or load it
if (!isset($langFile)) {
	$langFile = require("lang/{$lang}.php");
}

$uniqueId = uniqid('csv_');

require_once('admin-csv-tables.php');
$csvTables = rmt_get_admin_csv_tables();
$tableColumns = isset($csvTables[$tableName]) ? $csvTables[$tableName]['columns'] : [];
?>
<div class="mrgn-tp-md">
	<form method="get" action="/includes/admin-csv-export.php" style="display:inline;">
		<input type="hidden" name="table" value="<?= htmlspecialchars($tableName) ?>">
		<input type="hidden" name="lang" value="<?= htmlspecialchars($lang) ?>">
		<button type="submit" class="btn btn-primary mrgn-rght-sm">
			<span class="glyphicon glyphicon-download" aria-hidden="true"></span>
			<?= htmlspecialchars($langFile['admin_csv_export_heading'] ?? 'Export CSV') ?>
		</button>
	</form>

	<div class="mrgn-tp-lg alert alert-info" id="<?= $uniqueId ?>_instructions">
		<p><strong><?= htmlspecialchars($langFile['admin_csv_import_info'] ?? 'Import Tips:') ?></strong></p>
		<ul class="mrgn-bttm-0">
			<li><?= htmlspecialchars($langFile['admin_csv_import_tip_columns'] ?? 'All required columns must be present in the file') ?></li>
			<li><?= htmlspecialchars($langFile['admin_csv_import_tip_order'] ?? 'Keep the exported column names and order unchanged') ?></li>
			<li><?= htmlspecialchars($langFile['admin_csv_import_tip_comments'] ?? 'Comment rows (lines starting with #) are automatically skipped') ?></li>
		</ul>
	</div>

	<form method="post" action="/includes/admin-csv-import.php" enctype="multipart/form-data">
		<input type="hidden" name="table" value="<?= htmlspecialchars($tableName) ?>">
		<input type="hidden" name="lang" value="<?= htmlspecialchars($lang) ?>">
		<input type="hidden" name="referrer" value="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">
		<div class="form-group">
			<label for="<?= $uniqueId ?>"><?= htmlspecialchars($langFile['admin_csv_choose_file'] ?? 'CSV file') ?></label>
			<input type="file" class="form-control" id="<?= $uniqueId ?>" name="csv_file" accept=".csv,text/csv" aria-describedby="<?= $uniqueId ?>_instructions" required>
		</div>
		<button type="submit" class="btn btn-primary">
			<span class="glyphicon glyphicon-upload" aria-hidden="true"></span>
			<?= htmlspecialchars($langFile['admin_csv_import_heading'] ?? 'Import CSV') ?>
		</button>
	</form>
</div>
<?php
