<?php
require_once __DIR__ . '/session_start.php';

require('../sql.php');
/** @var mysqli $link */
require_once('admin-csv-tables.php');

$lang = (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'fr'], true)) ? $_GET['lang'] : ($_SESSION['lang'] ?? 'en');
$_SESSION['lang'] = $lang;

if (!($_SESSION['is_superuser'] OR $_SESSION['is_admin'])) {
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

// Ensure import expects headers compatible with the live schema.
// Some environments may still have legacy NOT NULL columns.
$schemaColumns = [];
$requiredExtraColumns = [];
$requiredNoDefaultColumns = [];
$columnsResult = mysqli_query($link, "SHOW COLUMNS FROM `$table`");
if ($columnsResult) {
	while ($columnMeta = mysqli_fetch_assoc($columnsResult)) {
		$field = (string)($columnMeta['Field'] ?? '');
		if ($field === '') {
			continue;
		}

		$schemaColumns[] = $field;
		$isAutoIncrement = stripos((string)($columnMeta['Extra'] ?? ''), 'auto_increment') !== false;
		$isRequired = strtoupper((string)($columnMeta['Null'] ?? 'YES')) === 'NO'
			&& $columnMeta['Default'] === null
			&& !$isAutoIncrement;

		if ($isRequired) {
			$requiredNoDefaultColumns[$field] = true;
		}

		if ($isRequired && !in_array($field, $columns, true)) {
			$requiredExtraColumns[] = $field;
		}
	}
}

if (!empty($schemaColumns)) {
	$columns = array_values(array_filter($columns, function ($column) use ($schemaColumns) {
		return in_array($column, $schemaColumns, true);
	}));
}

if (!empty($requiredExtraColumns)) {
	// Legacy tblteams contact columns are auto-populated below when present in schema.
	if ($table === 'tblteams') {
		$requiredExtraColumns = array_values(array_filter($requiredExtraColumns, function ($column) {
			return !in_array($column, ['contactname', 'contactemail'], true);
		}));
	}

	$columns = array_values(array_unique(array_merge($columns, $requiredExtraColumns)));
}

// Skip comment rows and UTF-8 BOM
$header = null;
while (($header = fgetcsv($handle)) !== false) {
	if (!empty($header)) {
		// Trim and remove UTF-8 BOM from first cell
		$firstCell = trim((string)($header[0] ?? ''));
		$firstCell = preg_replace('/^\xEF\xBB\xBF/', '', $firstCell);
		
		// Skip comment rows (start with #)
		if (strpos($firstCell, '#') === 0) {
			continue;
		}
		
		// Skip completely empty rows
		if (empty($firstCell)) {
			continue;
		}
		
		// Found the header row
		$header[0] = $firstCell;
		break;
	}
}

if ($header === false) {
	fclose($handle);
	$sep = (strpos($referrer, '?') === false) ? '?' : '&';
	header("location:$referrer{$sep}lang=" . urlencode($lang) . "&status=header_mismatch");
	exit();
}

$header = array_map('trim', $header);

// Check that all required columns are present (allow extra descriptive columns)
$missingColumns = array_diff($columns, $header);
if (!empty($missingColumns)) {
	fclose($handle);
	$sep = (strpos($referrer, '?') === false) ? '?' : '&';
	header("location:$referrer{$sep}lang=" . urlencode($lang) . "&status=header_mismatch");
	exit();
}

$okCount = 0;
$failCount = 0;
$errorDetails = [];

while (($row = fgetcsv($handle)) !== false) {
	// Skip empty rows and comment rows
	if (count($row) === 1 && trim((string)$row[0]) === '') {
		continue;
	}
	if (!empty($row[0]) && strpos(trim((string)$row[0]), '#') === 0) {
		continue;
	}
	
	// Allow rows with extra columns (descriptive columns), but require at least the required columns
	if (count($row) < count($columns)) {
		$failCount++;
		$errorDetails[] = "Row has " . count($row) . " columns, expected at least " . count($columns);
		continue;
	}

	// Extract only the required columns from the row
	$rowValues = array_slice($row, 0, count($columns));
	$assoc = array_combine($columns, $rowValues);
	if ($assoc === false) {
		$failCount++;
		$errorDetails[] = "Failed to combine columns with values";
		continue;
	}

	// Legacy schema compatibility for tblteams.
	// If old required contact fields exist in DB, derive sensible defaults from team data.
	if ($table === 'tblteams') {
		$nameEn = trim((string)($assoc['nameen'] ?? ''));
		$nameFr = trim((string)($assoc['namefr'] ?? ''));
		$teamEmail = trim((string)($assoc['email'] ?? ''));

		if (!array_key_exists('contactname', $assoc) || trim((string)$assoc['contactname']) === '') {
			$assoc['contactname'] = ($nameEn !== '') ? $nameEn : (($nameFr !== '') ? $nameFr : 'Team Contact');
		}

		if (!array_key_exists('contactemail', $assoc) || trim((string)$assoc['contactemail']) === '') {
			$assoc['contactemail'] = $teamEmail;
		}
	}

	$idRaw = isset($assoc['id']) ? trim((string)$assoc['id']) : '';
	$idValue = ($idRaw !== '' && ctype_digit($idRaw)) ? (int)$idRaw : null;

	$insertColumnList = $columns;
	if ($table === 'tblteams') {
		if (isset($requiredNoDefaultColumns['contactname']) && !in_array('contactname', $insertColumnList, true)) {
			$insertColumnList[] = 'contactname';
		}
		if (isset($requiredNoDefaultColumns['contactemail']) && !in_array('contactemail', $insertColumnList, true)) {
			$insertColumnList[] = 'contactemail';
		}
	}

	$insertColumns = [];
	$insertValues = [];
	$updateParts = [];
	$missingRequiredColumn = null;

	foreach ($insertColumnList as $column) {
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
			if ($column !== 'id' && isset($requiredNoDefaultColumns[$column])) {
				$missingRequiredColumn = $column;
				break;
			}
			$insertValues[] = 'NULL';
		} else {
			$insertValues[] = "'" . mysqli_real_escape_string($link, $value) . "'";
		}

		if ($column !== 'id') {
			$updateParts[] = "`$column` = VALUES(`$column`)";
		}
	}

	if ($missingRequiredColumn !== null) {
		$failCount++;
		$errorDetails[] = "Missing required value for column: " . $missingRequiredColumn;
		continue;
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

	try {
		if (mysqli_query($link, $sql)) {
			$okCount++;
		} else {
			$failCount++;
			$errorDetails[] = "SQL Error: " . mysqli_error($link);
		}
	} catch (mysqli_sql_exception $e) {
		$failCount++;
		$errorDetails[] = "SQL Exception: " . $e->getMessage();
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
