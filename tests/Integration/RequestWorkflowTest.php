<?php
require_once '/var/www/html/sql.php';
/** @var mysqli $link */
require_once '/var/www/html/includes/helpers.php';

$passed = 0;
$failed = 0;

function check(bool $condition, string $message): void
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

echo "RMT intake hierarchy integration tests\n";

$boundValue = "O'Reilly ? --";
$boundRow = rmt_db_fetch_one($link, 'SELECT ? AS bound_value', 's', [$boundValue]);
check(($boundRow['bound_value'] ?? null) === $boundValue, 'prepared helper binds text as data');

$orphanServices = mysqli_fetch_assoc(mysqli_query(
    $link,
    'SELECT COUNT(*) AS total FROM tblservices s LEFT JOIN tblcatalogue c ON c.id = s.catalogueid WHERE c.id IS NULL'
));
check((int) $orphanServices['total'] === 0, 'services have valid catalogue parents');

$orphanSubservices = mysqli_fetch_assoc(mysqli_query(
    $link,
    'SELECT COUNT(*) AS total FROM tblsubservices ss LEFT JOIN tblservices s ON s.id = ss.serviceid WHERE s.id IS NULL'
));
check((int) $orphanSubservices['total'] === 0, 'subservices have valid service parents');

check(
    rmt_validate_intake_selection($link, 102, 201, 999) === [
        'catalogueid' => 102,
        'serviceid' => 201,
        'subserviceid' => 0
    ],
    'service without subservices normalizes the child ID to zero'
);
check(
    rmt_validate_intake_selection($link, 103, 202, 301) === [
        'catalogueid' => 103,
        'serviceid' => 202,
        'subserviceid' => 301
    ],
    'service with a valid required subservice is accepted'
);
check(
    rmt_validate_intake_selection($link, 101, 0, 0) === [
        'catalogueid' => 101,
        'serviceid' => 0,
        'subserviceid' => 0
    ],
    'catalogue without services is accepted as a terminal selection'
);
check(rmt_validate_intake_selection($link, 101, 0, 301) === null, 'terminal catalogue rejects a subservice');
check(rmt_validate_intake_selection($link, 102, 0, 0) === null, 'catalogue with active services requires a service');
check(rmt_validate_intake_selection($link, 103, 201, 0) === null, 'cross-catalogue service is rejected');
check(rmt_validate_intake_selection($link, 102, 201, 301) === [
    'catalogueid' => 102,
    'serviceid' => 201,
    'subserviceid' => 0
], 'leaf service ignores a cross-service subservice');
check(rmt_validate_intake_selection($link, 103, 202, 0) === null, 'missing required subservice is rejected');
check(rmt_validate_intake_selection($link, 103, 202, 302) === null, 'inactive subservice is rejected');
check(rmt_validate_intake_selection($link, 103, 203, 0) === null, 'inactive service is rejected');
check(rmt_validate_intake_selection($link, 104, 0, 0) === null, 'inactive catalogue is rejected');
check(rmt_validate_intake_selection($link, 99, 999, 0) === null, 'unknown IDs are rejected');

mysqli_close($link);
echo "Passed: {$passed}; Failed: {$failed}\n";
exit($failed === 0 ? 0 : 1);