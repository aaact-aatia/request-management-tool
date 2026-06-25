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

// Add reference legend comment rows at the top
fputcsv($output, ['# RMT Export Reference Legend']);
fputcsv($output, ['# ID values and their meanings:']);

// Table-specific legends
$legends = [
	'tblusers' => [
		'# atype: 1=Super Admin, 2=Admin, 3=Manager, 4=Team Lead, 5=Employee, 6=External',
		'# is_superuser: 0=No, 1=Yes',
		'# is_admin: 0=No, 1=Yes',
		'# team: Comma-separated Team IDs (e.g., 1,3,5)',
		'# status: 0=Inactive, 1=Active',
	],
	'tblteams' => [
		'# (Team names reference lookup: id → nameen in this table)',
		'# status: 0=Inactive, 1=Active',
	],
	'tblservices' => [
		'# catalogueid: Service category ID (reference tblcatalogue)',
		'# contactid: Contact person ID (reference tblcontacts)',
		'# status: 0=Inactive, 1=Active',
	],
	'tblsubservices' => [
		'# serviceid: Parent Service ID (reference tblservices)',
		'# contactid: Contact person ID (reference tblcontacts)',
		'# status: 0=Inactive, 1=Active',
	],
	'tblcatalogue' => [
		'# survey: 0=No survey, 1=Send customer satisfaction survey when request is resolved',
		'# status: 0=Inactive, 1=Active',
	],
	'tblsources' => [
		'# status: 0=Inactive, 1=Active',
	],
	'tblstatus' => [
		'# is_resolved: 0=Not a resolved status, 1=Marks request as resolved',
		'# status: 0=Inactive, 1=Active',
	],
	'tblholidays' => [
		'# (Holiday dates: each row is one holiday)',
		'# recurring: 0=One-time, 1=Recurring annually',
		'# status: 0=Inactive, 1=Active',
	],
];

if (isset($legends[$table])) {
	foreach ($legends[$table] as $legend) {
		fputcsv($output, [$legend]);
	}
}

// Add reference lists for foreign key IDs
if ($table === 'tblusers') {
	fputcsv($output, ['']);  // Empty line
	fputcsv($output, ['# Manager ID Reference:']);
	$managerSql = "SELECT id, CONCAT(firstname, ' ', lastname) as name FROM tblusers WHERE atype = 3 ORDER BY lastname, firstname";
	$managerResult = mysqli_query($link, $managerSql);
	if ($managerResult) {
		while ($mgr = mysqli_fetch_assoc($managerResult)) {
			fputcsv($output, ['# ' . $mgr['id'] . ' = ' . $mgr['name']]);
		}
	}
	fputcsv($output, ['']);  // Empty line
	fputcsv($output, ['# Team ID Reference (use comma-separated IDs in team column):']);
	$teamSql = "SELECT id, nameen FROM tblteams ORDER BY nameen";
	$teamResult = mysqli_query($link, $teamSql);
	if ($teamResult) {
		while ($team = mysqli_fetch_assoc($teamResult)) {
			fputcsv($output, ['# ' . $team['id'] . ' = ' . $team['nameen']]);
		}
	}
}

if ($table === 'tblteams') {
	fputcsv($output, ['']);  // Empty line
	fputcsv($output, ['# Team Lead ID Reference:']);
	$leadSql = "SELECT id, CONCAT(firstname, ' ', lastname) as name FROM tblusers WHERE atype = 4 ORDER BY lastname, firstname";
	$leadResult = mysqli_query($link, $leadSql);
	if ($leadResult) {
		while ($lead = mysqli_fetch_assoc($leadResult)) {
			fputcsv($output, ['# ' . $lead['id'] . ' = ' . $lead['name']]);
		}
	}
}

fputcsv($output, ['']);  // Empty line for readability

// Output column headers
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
