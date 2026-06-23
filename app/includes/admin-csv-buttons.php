<?php

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

$effectiveAtype = isset($_SESSION['atype']) ? (int)$_SESSION['atype'] : 0;
if (isset($_SESSION['real_atype']) && (int)$_SESSION['real_atype'] === 1) {
	$effectiveAtype = 1;
}

// Only show CSV buttons to super admin
if ($effectiveAtype !== 1) {
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

	<form method="post" action="/includes/admin-csv-import.php" enctype="multipart/form-data" style="display:inline;">
		<input type="hidden" name="table" value="<?= htmlspecialchars($tableName) ?>">
		<input type="hidden" name="lang" value="<?= htmlspecialchars($lang) ?>">
		<input type="hidden" name="referrer" value="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">
		<input type="file" id="<?= $uniqueId ?>" name="csv_file" accept=".csv" style="display:none;" onchange="this.form.submit();">
		<button type="button" class="btn btn-primary" onclick="document.getElementById('<?= $uniqueId ?>').click();">
			<span class="glyphicon glyphicon-upload" aria-hidden="true"></span>
			<?= htmlspecialchars($langFile['admin_csv_import_heading'] ?? 'Import CSV') ?>
		</button>
	</form>

	<?php if (!empty($tableColumns)): ?>
		<div class="mrgn-tp-lg alert alert-warning">
			<p><strong><?= htmlspecialchars($langFile['admin_csv_columns_warning'] ?? 'Do not modify the column headings in the exported file. The import will fail if the headings are changed or reordered.') ?></strong></p>
			<p><?= htmlspecialchars($langFile['admin_csv_expected_columns_intro'] ?? 'The file header must exactly match this column order:') ?> <code><?= htmlspecialchars(implode(', ', $tableColumns)) ?></code></p>
		</div>
	<?php endif; ?>
</div>
<?php
