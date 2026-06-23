<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
	session_start();
}

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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	header("location:/openrequest.php?lang=$lang");
	exit();
}

// Get referrer page (the page the user was importing from)
$referrer = isset($_POST['referrer']) ? $_POST['referrer'] : '/openrequest.php';
// Validate referrer is from our app (basic safety check)
if (empty($referrer) || !preg_match('#^/[a-z0-9\-\.]+\.php#i', $referrer)) {
	$referrer = '/openrequest.php';
}

$table = isset($_POST['table']) ? $_POST['table'] : '';
$csvTables = rmt_get_admin_csv_tables();
if (!isset($csvTables[$table])) {
	$sep = (strpos($referrer, '?') === false) ? '?' : '&';
	header("location:$referrer{$sep}lang=" . urlencode($lang) . "&status=invalid_table");
	exit();
}

if (empty($_FILES['csv_file']) || !isset($_FILES['csv_file']['tmp_name']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
	$sep = (strpos($referrer, '?') === false) ? '?' : '&';
	header("location:$referrer{$sep}lang=" . urlencode($lang) . "&status=no_file");
	exit();
}

$handle = fopen($_FILES['csv_file']['tmp_name'], 'r');
if ($handle === false) {
	$sep = (strpos($referrer, '?') === false) ? '?' : '&';
	header("location:$referrer{$sep}lang=" . urlencode($lang) . "&status=import_failed");
	exit();
}

$columns = $csvTables[$table]['columns'];
$header = fgetcsv($handle);
if ($header === false) {
	fclose($handle);
	$sep = (strpos($referrer, '?') === false) ? '?' : '&';
	header("location:$referrer{$sep}lang=" . urlencode($lang) . "&status=header_mismatch");
	exit();
}

$header = array_map('trim', $header);
if (!empty($header[0])) {
	$header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
}

if ($header !== $columns) {
	fclose($handle);
	$sep = (strpos($referrer, '?') === false) ? '?' : '&';
	header("location:$referrer{$sep}lang=" . urlencode($lang) . "&status=header_mismatch");
	exit();
}

$okCount = 0;
$failCount = 0;
$errorDetails = [];

while (($row = fgetcsv($handle)) !== false) {
	if (count($row) === 1 && trim((string)$row[0]) === '') {
		continue;
	}
	if (count($row) !== count($columns)) {
		$failCount++;
		$errorDetails[] = "Row has " . count($row) . " columns, expected " . count($columns);
		continue;
	}

	$assoc = array_combine($columns, $row);
	if ($assoc === false) {
		$failCount++;
		$errorDetails[] = "Failed to combine columns with values";
		continue;
	}

	$idRaw = isset($assoc['id']) ? trim((string)$assoc['id']) : '';
	$idValue = ($idRaw !== '' && ctype_digit($idRaw)) ? (int)$idRaw : null;

	$insertColumns = [];
	$insertValues = [];
	$updateParts = [];

	foreach ($columns as $column) {
		if ($column === 'id' && $idValue === null) {
			continue;
		}

		$insertColumns[] = "`$column`";
		if ($column === 'id' && $idValue !== null) {
			$insertValues[] = (string)$idValue;
			continue;
		}

		$value = trim((string)($assoc[$column] ?? ''));
		if ($value === '') {
			$insertValues[] = 'NULL';
		} else {
			$insertValues[] = "'" . mysqli_real_escape_string($link, $value) . "'";
		}

		if ($column !== 'id') {
			$updateParts[] = "`$column` = VALUES(`$column`)";
		}
	}

	if (empty($insertColumns)) {
		$failCount++;
		$errorDetails[] = "No columns to insert";
		continue;
	}

	$sql = "INSERT INTO `$table` (" . implode(', ', $insertColumns) . ") VALUES (" . implode(', ', $insertValues) . ")";
	if ($idValue !== null && !empty($updateParts)) {
		$sql .= " ON DUPLICATE KEY UPDATE " . implode(', ', $updateParts);
	}

	if (mysqli_query($link, $sql)) {
		$okCount++;
	} else {
		$failCount++;
		$errorDetails[] = "SQL Error: " . mysqli_error($link);
	}
}

fclose($handle);
mysqli_close($link);

$sep = (strpos($referrer, '?') === false) ? '?' : '&';

if ($okCount > 0 && $failCount === 0) {
	header("location:$referrer{$sep}lang=" . urlencode($lang) . "&status=import_success&count=$okCount");
	exit();
}

if ($okCount > 0 && $failCount > 0) {
	header("location:$referrer{$sep}lang=" . urlencode($lang) . "&status=import_partial&ok=$okCount&fail=$failCount");
	exit();
}

// All rows failed or no rows - provide diagnostic info
$errorMsg = '';
if (!empty($errorDetails)) {
	$errorMsg = '&error=' . urlencode($errorDetails[0]);
}

header("location:$referrer{$sep}lang=" . urlencode($lang) . "&status=import_failed" . $errorMsg);
exit();
