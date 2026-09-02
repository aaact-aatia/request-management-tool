<?php
require_once '/var/www/html/sql.php';
/** @var mysqli $link */
require_once '/var/www/html/includes/helpers.php';
require_once '/var/www/html/includes/catalogue-delete.php';

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
check(rmt_get_sla_days_required_for_request($link, 201, 0) === 5, 'leaf service uses its service-level SLA');
check(rmt_get_sla_days_required_for_request($link, 202, 0) === 0, 'service with active subservices does not use its service-level SLA');
check(rmt_get_sla_days_required_for_request($link, 202, 301) === 3, 'selected subservice uses its own SLA');
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

check(rmt_resolve_request_subject_type($link, 102, 201, 0) === 'system', 'service inherits catalogue subject type');
check(rmt_resolve_request_subject_type($link, 103, 202, 0) === 'document', 'service overrides catalogue subject type');
check(rmt_resolve_request_subject_type($link, 103, 202, 301) === 'subject', 'subservice overrides service subject type');
check(rmt_resolve_request_subject_type($link, 101, 0, 0) === 'subject', 'null hierarchy falls back to subject');
check(rmt_resolve_responsible_team_id($link, 101, 0, 0) === 1, 'terminal catalogue uses catalogue team');
check(rmt_resolve_responsible_team_id($link, 102, 201, 0) === 1, 'service inherits catalogue team');
check(rmt_resolve_responsible_team_id($link, 103, 202, 0) === 2, 'service overrides catalogue team');
check(rmt_resolve_responsible_team_id($link, 103, 202, 301) === 3, 'subservice overrides service team');

$originalSession = $_SESSION;
$_SESSION = [];
check(!rmt_can_access_request($link, ['workerid' => 1]), 'anonymous user cannot access request attachments');
$_SESSION = ['pid' => 10, 'atype' => 3];
check(rmt_can_access_request($link, ['workerid' => 20]), 'manager can access request attachments');
$_SESSION = ['pid' => 10, 'atype' => 5];
check(rmt_can_access_request($link, ['workerid' => 10]), 'assigned employee can access request attachments');
check(!rmt_can_access_request($link, ['workerid' => 20]), 'unassigned employee cannot access request attachments');
$_SESSION = ['pid' => 10, 'atype' => 5, 'primary_atype' => 1, 'is_superuser' => 1, 'is_admin' => 1];
check(!rmt_can_access_request($link, ['workerid' => 20]), 'role-test employee cannot inherit administrative attachment access');
$_SESSION = $originalSession;

$systemText = rmt_request_subject_text('system', 'en');
$documentText = rmt_request_subject_text('document', 'en');
$subjectText = rmt_request_subject_text('subject', 'fr');
check($systemText['label'] === 'System name', 'system type uses the System name label');
check($documentText['label'] === 'Document title', 'document type uses the Document title label');
check($subjectText['label'] === 'Objet', 'subject type has a French label');
check(
    rmt_generate_request_title('REQ-26-123', 'SSC', 'Accessibility testing tool')
        === 'REQ-26-123 - SSC - Accessibility testing tool',
    'request title uses ticket, organization, and request subject'
);

$openStatus = rmt_db_fetch_one($link, 'SELECT id FROM tblstatus WHERE COALESCE(is_resolved, 0) = 0 ORDER BY id LIMIT 1');
$resolvedStatus = rmt_db_fetch_one($link, 'SELECT id FROM tblstatus WHERE is_resolved = 1 ORDER BY id LIMIT 1');
check($openStatus !== null && $resolvedStatus !== null, 'catalogue deletion test statuses are available');

if ($openStatus !== null && $resolvedStatus !== null) {
    mysqli_query($link, "INSERT INTO tblcatalogue (nameen, namefr, status) VALUES ('Delete test catalogue', 'Catalogue test suppression', 1)");
    $deleteCatalogueId = mysqli_insert_id($link);
    mysqli_query($link, "INSERT INTO tblservices (catalogueid, nameen, namefr, status) VALUES ($deleteCatalogueId, 'Delete test service', 'Service test suppression', 1)");
    $deleteServiceId = mysqli_insert_id($link);
    mysqli_query($link, "INSERT INTO tblsubservices (serviceid, nameen, namefr, status) VALUES ($deleteServiceId, 'Old sub-service', 'Ancien sous-service', 1)");
    $oldSubserviceId = mysqli_insert_id($link);
    mysqli_query($link, "INSERT INTO tblsubservices (serviceid, nameen, namefr, status) VALUES ($deleteServiceId, 'Replacement sub-service', 'Sous-service de remplacement', 1)");
    $replacementSubserviceId = mysqli_insert_id($link);

    mysqli_query($link, "INSERT INTO tbltriage (requestid, catalogueid, serviceid, subserviceid, statusid, status) VALUES ('DELETE-OPEN-KEEP', $deleteCatalogueId, $deleteServiceId, $oldSubserviceId, {$openStatus['id']}, 1)");
    $openKeepRequestId = mysqli_insert_id($link);
    mysqli_query($link, "INSERT INTO tbltriage (requestid, catalogueid, serviceid, subserviceid, statusid, status) VALUES ('DELETE-CLOSED-KEEP', $deleteCatalogueId, $deleteServiceId, $oldSubserviceId, {$resolvedStatus['id']}, 1)");
    $closedKeepRequestId = mysqli_insert_id($link);

    check(rmt_catalogue_unresolved_request_count($link, 'subservice', $oldSubserviceId) === 1, 'only unresolved requests are offered for reassignment');
    rmt_delete_catalogue_hierarchy($link, 'subservice', $oldSubserviceId, 0, $deleteServiceId);
    $deletedSubservice = rmt_db_fetch_one($link, 'SELECT id FROM tblsubservices WHERE id = ?', 'i', [$oldSubserviceId]);
    $openKeepRequest = rmt_db_fetch_one($link, 'SELECT * FROM tbltriage WHERE id = ?', 'i', [$openKeepRequestId]);
    $closedKeepRequest = rmt_db_fetch_one($link, 'SELECT * FROM tbltriage WHERE id = ?', 'i', [$closedKeepRequestId]);
    check($deletedSubservice === null, 'sub-service row is permanently deleted');
    check((int) $openKeepRequest['subserviceid'] === $oldSubserviceId, 'default deletion leaves open request assignment unchanged');
    check((int) $closedKeepRequest['subserviceid'] === $oldSubserviceId, 'closed request assignment remains unchanged');
    check(
        $closedKeepRequest['subservicenameen'] === 'Old sub-service'
            && $closedKeepRequest['subservicenamefr'] === 'Ancien sous-service',
        'closed request keeps bilingual archived sub-service names'
    );

    mysqli_query($link, "INSERT INTO tblsubservices (serviceid, nameen, namefr, status) VALUES ($deleteServiceId, 'Second old sub-service', 'Deuxieme ancien sous-service', 1)");
    $secondOldSubserviceId = mysqli_insert_id($link);
    mysqli_query($link, "INSERT INTO tbltriage (requestid, catalogueid, serviceid, subserviceid, statusid, status) VALUES ('DELETE-OPEN-MOVE', $deleteCatalogueId, $deleteServiceId, $secondOldSubserviceId, {$openStatus['id']}, 1)");
    $openMoveRequestId = mysqli_insert_id($link);
    mysqli_query($link, "INSERT INTO tbltriage (requestid, catalogueid, serviceid, subserviceid, statusid, status) VALUES ('DELETE-CLOSED-STAY', $deleteCatalogueId, $deleteServiceId, $secondOldSubserviceId, {$resolvedStatus['id']}, 1)");
    $closedStayRequestId = mysqli_insert_id($link);

    rmt_delete_catalogue_hierarchy($link, 'subservice', $secondOldSubserviceId, $replacementSubserviceId, $deleteServiceId);
    $openMoveRequest = rmt_db_fetch_one($link, 'SELECT * FROM tbltriage WHERE id = ?', 'i', [$openMoveRequestId]);
    $closedStayRequest = rmt_db_fetch_one($link, 'SELECT * FROM tbltriage WHERE id = ?', 'i', [$closedStayRequestId]);
    check((int) $openMoveRequest['subserviceid'] === $replacementSubserviceId, 'selected replacement reassigns the open request');
    check($openMoveRequest['subservicenameen'] === 'Replacement sub-service', 'reassigned request snapshot uses the replacement name');
    check((int) $closedStayRequest['subserviceid'] === $secondOldSubserviceId, 'selected replacement does not alter a closed request');
    check($closedStayRequest['subservicenameen'] === 'Second old sub-service', 'closed request archives the deleted name during reassignment');

    mysqli_query($link, "DELETE FROM tbltriage WHERE id IN ($openKeepRequestId, $closedKeepRequestId, $openMoveRequestId, $closedStayRequestId)");
    rmt_delete_catalogue_hierarchy($link, 'catalogue', $deleteCatalogueId, 0, 0);
    $deletedCatalogue = rmt_db_fetch_one($link, 'SELECT id FROM tblcatalogue WHERE id = ?', 'i', [$deleteCatalogueId]);
    $deletedService = rmt_db_fetch_one($link, 'SELECT id FROM tblservices WHERE id = ?', 'i', [$deleteServiceId]);
    check($deletedCatalogue === null && $deletedService === null, 'catalogue deletion permanently removes its hierarchy');
}

mysqli_close($link);
echo "Passed: {$passed}; Failed: {$failed}\n";
exit($failed === 0 ? 0 : 1);