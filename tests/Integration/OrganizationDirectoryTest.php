<?php

require_once '/var/www/html/sql.php';
/** @var mysqli $link */
require_once '/var/www/html/includes/department-directory.php';
require_once '/var/www/html/includes/admin-csv-tables.php';

$passed = 0;
$failed = 0;

function checkOrganizationDirectory(bool $condition, string $message): void
{
    global $passed, $failed;

    if ($condition) {
        $passed++;
        echo "PASS: {$message}\n";
        return;
    }

    $failed++;
    echo "FAIL: {$message}\n";
}

$countRow = mysqli_fetch_assoc(mysqli_query(
    $link,
    'SELECT COUNT(*) AS total, SUM(source_part = 1) AS part1, SUM(source_part = 2) AS part2 FROM tblorganizations'
));
checkOrganizationDirectory((int) ($countRow['total'] ?? 0) === 132, 'official organization snapshot contains 132 unique titles');
checkOrganizationDirectory((int) ($countRow['part1'] ?? 0) === 101, 'organization snapshot contains 101 Part 1 titles');
checkOrganizationDirectory((int) ($countRow['part2'] ?? 0) === 31, 'organization snapshot contains 31 Part 2 titles');

$organizationCsvConfig = rmt_get_admin_csv_tables()['tblorganizations'] ?? [];
$organizationCsvColumns = $organizationCsvConfig['columns'] ?? [];
checkOrganizationDirectory(
    !in_array('source_part', $organizationCsvColumns, true),
    'organization CSV administration excludes source provenance'
);
checkOrganizationDirectory(
    in_array('source_part', $organizationCsvConfig['forbidden_columns'] ?? [], true),
    'organization CSV imports reject the protected source column'
);

$englishDirectory = rmt_get_department_directory($link, 'en');
$frenchDirectory = rmt_get_department_directory($link, 'fr');
checkOrganizationDirectory(
    in_array(
        ['name' => 'Treasury Board of Canada Secretariat', 'label' => 'Treasury Board of Canada Secretariat (TBS)'],
        $englishDirectory,
        true
    ),
    'English directory includes the official title and acronym'
);
checkOrganizationDirectory(
    in_array(
        ['name' => 'Secrétariat du Conseil du Trésor du Canada', 'label' => 'Secrétariat du Conseil du Trésor du Canada (SCT)'],
        $frenchDirectory,
        true
    ),
    'French directory includes the official title and acronym'
);

mysqli_begin_transaction($link);
mysqli_query(
    $link,
    "INSERT INTO tblorganizations (nameen, namefr, status)
     VALUES ('Manual provenance test organization', 'Organisation de test de provenance manuelle', 1)"
);
$manualOrganizationId = mysqli_insert_id($link);
$manualSourceRow = mysqli_fetch_assoc(mysqli_query(
    $link,
    "SELECT source_part FROM tblorganizations WHERE id = {$manualOrganizationId}"
));
checkOrganizationDirectory(
    (int) ($manualSourceRow['source_part'] ?? -1) === 0,
    'new organizations default to manually maintained provenance'
);

mysqli_query(
    $link,
    "INSERT INTO tblorganizations (nameen, namefr, abbreviationen, abbreviationfr, source_part, status)
     VALUES ('Inactive test organization', 'Organisation de test inactive', 'ITO', 'OTI', 0, 0)"
);
$directoryWithInactiveRecord = rmt_get_department_directory($link, 'en');
checkOrganizationDirectory(
    !in_array('Inactive test organization', array_column($directoryWithInactiveRecord, 'name'), true),
    'inactive organizations are excluded from public intake'
);
mysqli_rollback($link);

mysqli_close($link);
echo "Passed: {$passed}; Failed: {$failed}\n";
exit($failed === 0 ? 0 : 1);
