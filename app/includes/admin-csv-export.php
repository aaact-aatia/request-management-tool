<?php
require_once __DIR__ . '/session_start.php';

require('../sql.php');
/** @var mysqli $link */
require_once('admin-csv-tables.php');

$lang = (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'fr'], true)) ? $_GET['lang'] : ($_SESSION['lang'] ?? 'en');
$_SESSION['lang'] = $lang;

$effectiveAtype = isset($_SESSION['atype']) ? (int)$_SESSION['atype'] : 0;
if (isset($_SESSION['real_atype']) && (int)$_SESSION['real_atype'] === 1) {
	$effectiveAtype = 1;
}
if ($effectiveAtype !== 1) {
	header("location:/openrequest.php?lang=$lang&status=accessdenied");
	exit();
}

// Load language strings
$langFile = require("../lang/{$lang}.php");

$table = isset($_GET['table']) ? $_GET['table'] : '';
$csvTables = rmt_get_admin_csv_tables();
if (!isset($csvTables[$table])) {
	header("location:/openrequest.php?lang=$lang&status=invalid_table");
	exit();
}

$columns = $csvTables[$table]['columns'];
$orderBy = $csvTables[$table]['order_by'];

$selectColumns = [];
foreach ($columns as $column) {
	$selectColumns[] = "`$column`";
}

$sql = "SELECT " . implode(', ', $selectColumns) . " FROM `$table` ORDER BY $orderBy";
$result = mysqli_query($link, $sql);
if (!$result) {
	header("location:/openrequest.php?lang=$lang&status=import_failed");
	exit();
}

$filename = $table . '-export-' . date('Ymd-His') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

// Add UTF-8 BOM for proper encoding in Excel/CSV readers
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

// Output headers - use actual column names (not translated) for import compatibility
fputcsv($output, $columns);

// Output data rows
while ($row = mysqli_fetch_assoc($result)) {
	$line = [];
	foreach ($columns as $column) {
		$line[] = $row[$column];
	}
	fputcsv($output, $line);
}

fclose($output);
mysqli_close($link);
