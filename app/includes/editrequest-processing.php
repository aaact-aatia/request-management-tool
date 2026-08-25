<?php
if (isset($_SERVER['SCRIPT_FILENAME']) && realpath(__FILE__) === realpath((string) $_SERVER['SCRIPT_FILENAME'])) {
    http_response_code(404);
    exit();
}

require_once __DIR__ . '/sla-calculator.php';

/**
 * Edit Request Form Processing
 * Extracted from main editrequest.php for better organization
 */

// Encode for redirect
$redirectid = base64_encode($requestuid);

// ============================================================================
// COLLECT FORM DATA
// ============================================================================

$inTestMode = isRoleTestMode();
$isManagerAccount = ((int)($_SESSION['atype'] ?? 0) === 3);
$isTeamLeadAccount = ((int)($_SESSION['atype'] ?? 0) === 4);
$isEmployeeAccount = ((int)($_SESSION['atype'] ?? 0) === 5);
$canFullFieldEdit = !$inTestMode && (!empty($_SESSION['is_superuser']) || !empty($_SESSION['is_admin']));
$canEditStatusAndWorker = in_array((int)($_SESSION['atype'] ?? 0), [3, 4, 5], true) || $canFullFieldEdit;
$canEditRequestSubject = $canFullFieldEdit || $isManagerAccount || $isTeamLeadAccount;
$canEditSlaTimer = $canFullFieldEdit || $isManagerAccount;
$canEditCommunicationLogs = $canFullFieldEdit || $isManagerAccount || $isTeamLeadAccount || $isEmployeeAccount;

$currentRequestResult = mysqli_query($link, "SELECT * FROM tbltriage WHERE id = '$requestuid' LIMIT 1");
$currentRequest = $currentRequestResult ? mysqli_fetch_assoc($currentRequestResult) : null;

if (!$currentRequest) {
    $langCode = $_SESSION['lang'] ?? 'en';
    header("location:/editrequest.php?lang=$langCode&id=$requestuid&status=failed&focus=update");
    exit();
}

if ($isTeamLeadAccount) {
    $teamIds = getEffectiveTeamIds($link);
    $requestContactId = rmt_resolve_responsible_team_id(
        $link,
        (int) ($currentRequest['catalogueid'] ?? 0),
        (int) ($currentRequest['serviceid'] ?? 0),
        (int) ($currentRequest['subserviceid'] ?? 0)
    );

    if ($requestContactId <= 0 || !in_array((string)$requestContactId, $teamIds, true)) {
        header("location:/index.php?lang=$lang&status=accessdenied");
        exit();
    }
}

if ($isEmployeeAccount) {
    $effectiveEmployeeId = getEffectiveEmployeeUserId($link);
    if ((int)($currentRequest['workerid'] ?? 0) !== $effectiveEmployeeId) {
        header("location:/indexonly.php?lang=$lang&status=accessdenied");
        exit();
    }
}

$requestid = (string) ($currentRequest['requestid'] ?? '');
$requesttitle = (string) ($currentRequest['title'] ?? '');
$requestSubject = trim((string) ($_POST['request_subject'] ?? ''));
$clientlname = getPostValue('clientlname');
$clientfname = getPostValue('clientfname');
$clientemail = getPostValue('clientemail');
$submittedDepartmentAgency = trim((string)($_POST['departmentagency'] ?? ''));
$clientphone = getPostValue('clientphone');
$statusid = getPostValue('statusid');
$datereceived = getPostValue('datereceived');
$dateupdated = getTodayDate();

// Handle nullable dates
$daterequired = getPostValue('daterequired');
$daterequiredu = empty($daterequired);
if ($daterequiredu) $daterequired = NULL;

$dateresolved = NULL;
$slatimer = getPostValue('slatimer');
$audienceid = getPostValue('audience', 0);
$bdm = getPostValue('bdm', 0);
$catalogueid = getPostValue('catalogueid');
$serviceid = (int)getPostValue('serviceid', 0);
$subserviceid = getPostValue('subserviceid', 0);
$workerid = getPostValue('workerid', 0);
$attach1 = getPostValue('attach1');
$attach2 = getPostValue('attach2');
$attach3 = getPostValue('attach3');
$sprintdefects = getPostValue('sprintdefects');
$sprintschedule = getPostValue('sprintschedule');
$firstsprintstartdate = getPostValue('firstsprintstartdate');
$firstsprintenddate = getPostValue('firstsprintenddate');
$adminnotes = getPostValue('adminnotes');
$updaterid = $_SESSION['pid'];
$todaydate = getTodayDate();
$lang = $_SESSION['lang'] ?? 'en';
$requestlang = app_normalize_language($lang);
$requestuidInt = (int) $requestuid;
$postedRequestLang = app_normalize_language(getPostValue('requestlang', ''), '');
$formAction = trim((string) getPostValue('form_action', 'update_request'));
if (!in_array($formAction, ['update_request', 'upload_files', 'add_log'], true)) {
    $formAction = 'update_request';
}

if (!$canEditStatusAndWorker) {
    $statusid = (string) ($currentRequest['statusid'] ?? '');
    $workerid = (string) ($currentRequest['workerid'] ?? 0);
}

if (!$canFullFieldEdit) {
    // Non-full-edit roles are restricted; manager gets approved exceptions.
    $clientlname = (string) ($currentRequest['clientlname'] ?? '');
    $clientfname = (string) ($currentRequest['clientfname'] ?? '');
    $clientemail = (string) ($currentRequest['clientemail'] ?? '');
    $clientphone = (string) ($currentRequest['clientphone'] ?? '');
    $datereceived = (string) ($currentRequest['datereceived'] ?? '');
    $daterequired = $currentRequest['daterequired'] ?? NULL;
    $bdm = (string) ($currentRequest['bdm'] ?? 0);
    $attach1 = (string) ($currentRequest['attach1'] ?? '');
    $attach2 = (string) ($currentRequest['attach2'] ?? '');
    $attach3 = (string) ($currentRequest['attach3'] ?? '');
    $catalogueid = (string) ($currentRequest['catalogueid'] ?? '');
    $serviceid = (int) ($currentRequest['serviceid'] ?? 0);
    $subserviceid = (string) ($currentRequest['subserviceid'] ?? 0);
    $sprintschedule = (string) ($currentRequest['sprintschedule'] ?? '');
    $sprintdefects = (string) ($currentRequest['sprintdefects'] ?? '');
    $audienceid = (string) ($currentRequest['audienceid'] ?? 0);
    $firstsprintstartdate = (string) ($currentRequest['firstsprintstartdate'] ?? '');
    $firstsprintenddate = (string) ($currentRequest['firstsprintenddate'] ?? '');

    if (!$canEditRequestSubject) {
        $requestSubject = (string) ($currentRequest['request_subject'] ?? '');
    }
    if (!$canEditSlaTimer) {
        $slatimer = (string) ($currentRequest['slatimer'] ?? '');
    }
}

$departmentResult = mysqli_query(
    $link,
    "SELECT id, notes FROM tblcommlog
     WHERE triageid = '$requestuid'
       AND status = '1'
       AND (notes LIKE 'Department/agency:%' OR notes LIKE 'Ministère/organisme:%')
     ORDER BY id ASC
     LIMIT 1"
);
$departmentRow = $departmentResult ? mysqli_fetch_assoc($departmentResult) : null;
$currentDepartmentAgency = '';
if ($departmentRow && preg_match('/^(Department\/agency|Ministère\/organisme):\s*(.+)$/miu', (string) $departmentRow['notes'], $matches)) {
    $currentDepartmentAgency = trim($matches[2]);
}

$departmentDirectory = rmt_get_department_directory($link, $lang);
$departmentAgency = $currentDepartmentAgency;
if ($canFullFieldEdit) {
    $submittedDepartmentOfficialTitle = rmt_department_directory_official_title($departmentDirectory, $submittedDepartmentAgency);
    $localizedCurrentDepartment = rmt_get_localized_department_name($link, $currentDepartmentAgency, $lang);
    if (rmt_department_directory_key($submittedDepartmentOfficialTitle) !== rmt_department_directory_key($localizedCurrentDepartment)) {
        $departmentAgency = $submittedDepartmentOfficialTitle;
    }
}
$requestLanguage = rmt_get_request_language($link, $requestuidInt, (string) ($currentRequest['requestlang'] ?? 'en'));
if ($requestSubject !== '') {
    $titleDepartment = rmt_department_title_component($link, $departmentAgency, $requestLanguage);
    $requesttitle = rmt_generate_request_title($requestid, $titleDepartment, $requestSubject);
}

$isTargetResolved = rmt_is_resolved_status_id($link, $statusid);
if ($isTargetResolved) {
    $dateresolved = !empty($currentRequest['dateresolved'])
        ? (string) $currentRequest['dateresolved']
        : getTodayDate();
} else {
    $dateresolvedu = true;
}

function rmt_audit_normalize_value($value) {
    if (is_null($value)) {
        return null;
    }

    $normalized = trim((string) $value);
    if ($normalized === '' || $normalized === '0000-00-00') {
        return null;
    }

    return $normalized;
}

function rmt_replace_department_agency_note($notes, $departmentValue, $lang) {
    $cleanedNotes = preg_replace('/^\s*(Department\/agency|Ministère\/organisme):\s*.*(?:\R|$)/miu', '', (string)$notes);
    $cleanedNotes = trim((string)$cleanedNotes);

    if (!hasValue($departmentValue)) {
        return $cleanedNotes;
    }

    $prefix = ($lang === 'fr') ? 'Ministère/organisme: ' : 'Department/agency: ';
    $departmentLine = $prefix . $departmentValue;

    return $cleanedNotes === '' ? $departmentLine : $departmentLine . "\n\n" . $cleanedNotes;
}

function rmt_audit_values_equal($oldValue, $newValue) {
    return rmt_audit_normalize_value($oldValue) === rmt_audit_normalize_value($newValue);
}

function rmt_append_request_change(array &$changes, string $fieldName, $oldValue, $newValue): void {
    if (rmt_audit_values_equal($oldValue, $newValue)) {
        return;
    }

    $changes[] = [
        'field' => $fieldName,
        'old' => rmt_audit_normalize_value($oldValue),
        'new' => rmt_audit_normalize_value($newValue),
    ];
}

function rmt_normalize_intish($value): string {
    $normalized = rmt_audit_normalize_value($value);
    if ($normalized === null) {
        return '0';
    }

    return (string) ((int) $normalized);
}

function rmt_lookup_label(array $labels, string $key): string {
    return $labels[$key] ?? $key;
}

function rmt_append_changed_label(array &$labels, array $localizedLabels, string $key, $oldValue, $newValue): void {
    if (rmt_audit_values_equal($oldValue, $newValue)) {
        return;
    }

    $labels[] = rmt_lookup_label($localizedLabels, $key);
}

// ============================================================================
// STATUS CHANGE TRACKING
// ============================================================================

$cstatusid = (string) ($currentRequest['statusid'] ?? '');
$previousWorkerIdForHistory = (int) ($currentRequest['workerid'] ?? 0);
$newWorkerIdForHistory = (int) $workerid;
$statusChanged = ((string) $cstatusid !== (string) $statusid);
$assignmentChanged = ($previousWorkerIdForHistory !== $newWorkerIdForHistory);
$isCurrentResolved = rmt_is_resolved_status_id($link, $cstatusid);

if ($formAction === 'update_request' && ($statusChanged || $assignmentChanged)) {
    $exactTime = date('Y-m-d H:i:s');
    $statusHistoryColumns = ['requestID', 'statusID', 'changeTimeStamp'];
    $statusHistoryValues = ["'$requestid'", "'$statusid'", "'$exactTime'"];

    $hasPreviousStatusColumn = rmt_table_has_column($link, 'StatusHistory', 'previousStatusID');
    $hasActorUserColumn = rmt_table_has_column($link, 'StatusHistory', 'actorUserID');
    $hasChangeTypeColumn = rmt_table_has_column($link, 'StatusHistory', 'changeType');
    $hasPreviousWorkerColumn = rmt_table_has_column($link, 'StatusHistory', 'previousWorkerID');
    $hasNewWorkerColumn = rmt_table_has_column($link, 'StatusHistory', 'newWorkerID');
    $hasSlaClockStartColumn = rmt_table_has_column($link, 'StatusHistory', 'slaClockStartDate');
    $hasSlaDueDateColumn = rmt_table_has_column($link, 'StatusHistory', 'slaDueDate');
    $hasSlaElapsedColumn = rmt_table_has_column($link, 'StatusHistory', 'slaElapsedBusinessDays');

    $slaClockStartDate = rmt_get_sla_clock_start_date($slatimer, $datereceived);
    $slaDueDate = '';
    $slaElapsedBusinessDays = null;

    if ($slaClockStartDate !== '') {
        $slaClockStartForCalculation = date('Y-m-d H:i:s', strtotime($slaClockStartDate . ' +1 day'));
        $slaElapsedBusinessDays = calculateSLA($link, $requestid, $slaClockStartForCalculation);

        $slaDaysRequired = rmt_get_sla_days_required_for_request($link, (int) $serviceid, (int) $subserviceid);
        if ($slaDaysRequired > 0) {
            $slaDueDate = addBusinessDays($slaClockStartDate, $slaDaysRequired, $link);
        }
    }

    if ($hasPreviousStatusColumn) {
        $statusHistoryColumns[] = 'previousStatusID';
        $statusHistoryValues[] = "'" . (int) $cstatusid . "'";
    }
    if ($hasActorUserColumn) {
        $statusHistoryColumns[] = 'actorUserID';
        $statusHistoryValues[] = "'" . (int) $updaterid . "'";
    }
    if ($hasChangeTypeColumn) {
        $statusHistoryColumns[] = 'changeType';
        $changeType = 'status_change';
        if ($statusChanged && $assignmentChanged) {
            $changeType = 'status_and_assignment_change';
        } elseif ($assignmentChanged) {
            $changeType = 'assignment_change';
        }
        $statusHistoryValues[] = "'" . mysqli_real_escape_string($link, $changeType) . "'";
    }
    if ($hasPreviousWorkerColumn) {
        $statusHistoryColumns[] = 'previousWorkerID';
        $statusHistoryValues[] = ($previousWorkerIdForHistory > 0)
            ? "'" . $previousWorkerIdForHistory . "'"
            : 'NULL';
    }
    if ($hasNewWorkerColumn) {
        $statusHistoryColumns[] = 'newWorkerID';
        $statusHistoryValues[] = ($newWorkerIdForHistory > 0)
            ? "'" . $newWorkerIdForHistory . "'"
            : 'NULL';
    }
    if ($hasSlaClockStartColumn) {
        $statusHistoryColumns[] = 'slaClockStartDate';
        $statusHistoryValues[] = ($slaClockStartDate !== '')
            ? "'" . mysqli_real_escape_string($link, $slaClockStartDate) . "'"
            : 'NULL';
    }
    if ($hasSlaDueDateColumn) {
        $statusHistoryColumns[] = 'slaDueDate';
        $statusHistoryValues[] = ($slaDueDate !== '')
            ? "'" . mysqli_real_escape_string($link, $slaDueDate) . "'"
            : 'NULL';
    }
    if ($hasSlaElapsedColumn) {
        $statusHistoryColumns[] = 'slaElapsedBusinessDays';
        $statusHistoryValues[] = ($slaElapsedBusinessDays !== null)
            ? "'" . (int) $slaElapsedBusinessDays . "'"
            : 'NULL';
    }

    $sql = "INSERT INTO StatusHistory(`" . implode('`,`', $statusHistoryColumns) . "`) VALUES (" . implode(', ', $statusHistoryValues) . ")";
    mysqli_query($link, $sql);

    // Update previous status duration
    if ($statusChanged) {
        $sqlSelect = "SELECT `id`, `ChangeTimestamp`, `statusID` FROM StatusHistory 
                      WHERE `requestID` = '$requestid' 
                      ORDER BY `id` DESC LIMIT 1";
        $result = mysqli_query($link, $sqlSelect);
        $row = mysqli_fetch_assoc($result);

        if ($row && $row['statusID'] != $statusid) {
            $cBdays = getWorkingDays($row['ChangeTimestamp'], $exactTime, $holidays);
            $prevId = $row['id'];
            $sqlUpdate = "UPDATE StatusHistory SET `DurationInDays` = $cBdays WHERE `id` = '$prevId'";
            mysqli_query($link, $sqlUpdate);
        }
    }
}

// ============================================================================
// FILE UPLOADS
// ============================================================================

$uploadedFileNames = [];

if (isset($_FILES['fileToUpload'])) {
    $validatedUploads = rmt_validate_uploaded_files($_FILES['fileToUpload'], $lang);
    if ($formAction === 'upload_files' && empty($validatedUploads['errors']) && empty($validatedUploads['files'])) {
        $_SESSION['upload_error_message'] = ($lang === 'fr')
            ? 'Veuillez choisir au moins un fichier a televerser.'
            : 'Please choose at least one file to upload.';
        $_SESSION['edit_section_status'] = ['status' => 'uploadfailed', 'focus' => 'upload'];
        header("location: /editrequest.php?lang=$lang&id=$requestuid&status=uploadfailed&focus=upload");
        exit();
    }

    if (!empty($validatedUploads['errors'])) {
        $_SESSION['upload_error_message'] = implode(' ', $validatedUploads['errors']);
        $_SESSION['edit_section_status'] = ['status' => 'uploadfailed', 'focus' => 'upload'];
        header("location: /editrequest.php?lang=$lang&id=$requestuid&status=uploadfailed&focus=upload");
        exit();
    }

    if (!empty($validatedUploads['files'])) {
        $storageManager = new AzureBlobStorageManager();
        foreach ($validatedUploads['files'] as $uploadFile) {
            $fileType = strtolower((string) ($uploadFile['extension'] ?? ''));
            $uploadedFileName = trim((string) ($uploadFile['name'] ?? ''));
            $fileName = mysqli_real_escape_string($link, (string) ($uploadFile['name'] ?? ''));
            $fileSize = (float) ($uploadFile['size_kb'] ?? 0.0);
            $fileTmpPath = (string) ($uploadFile['tmp_name'] ?? '');
            $randomCode = $requestid . "-" . bin2hex(random_bytes(16)) . "." . $fileType;
            $safeRandomCode = mysqli_real_escape_string($link, $randomCode);
            $safeFileType = mysqli_real_escape_string($link, $fileType);

            if ($storageManager->uploadFile($fileTmpPath, $randomCode)) {
                $uploadSql = "INSERT INTO tblfiles (`requestid`, `name`, `code`, `type`, `size`) VALUES ('$requestid', '$fileName', '$safeRandomCode', '$safeFileType', '$fileSize')";
                mysqli_query($link, $uploadSql);
                if ($uploadedFileName !== '') {
                    $uploadedFileNames[] = $uploadedFileName;
                }
            }
        }
    }
}

if ($formAction === 'upload_files') {
    if (empty($uploadedFileNames)) {
        $_SESSION['upload_error_message'] = ($lang === 'fr')
            ? 'Veuillez choisir au moins un fichier a televerser.'
            : 'Please choose at least one file to upload.';
        $_SESSION['edit_section_status'] = ['status' => 'uploadfailed', 'focus' => 'upload'];
        header("location:/editrequest.php?lang=$lang&id=$requestuid&status=uploadfailed&focus=upload");
        exit();
    }

    $touchDateUpdated = mysqli_real_escape_string($link, getTodayDate());
    mysqli_query($link, "UPDATE tbltriage SET dateupdated='$touchDateUpdated', updaterid='" . (int) $updaterid . "' WHERE id='$requestuid'");

    if (rmt_table_has_column($link, 'RequestFieldHistory', 'requestID')) {
        $auditChangeTime = date('Y-m-d H:i:s');
        $safeRequestId = mysqli_real_escape_string($link, (string) $requestid);
        foreach ($uploadedFileNames as $uploadedFileName) {
            $safeField = mysqli_real_escape_string($link, 'uploaded_file');
            $newValueSql = "'" . mysqli_real_escape_string($link, (string) $uploadedFileName) . "'";
            $sqlAudit = "INSERT INTO RequestFieldHistory(`requestID`, `fieldName`, `oldValue`, `newValue`, `actorUserID`, `changeTimeStamp`) VALUES ('$safeRequestId', '$safeField', NULL, $newValueSql, '" . (int) $updaterid . "', '$auditChangeTime')";
            mysqli_query($link, $sqlAudit);
        }
    }

    $_SESSION['edit_section_status'] = ['status' => 'uploadsuccess', 'focus' => 'upload'];
    header("location:/editrequest.php?lang=$lang&id=$requestuid&status=uploadsuccess&focus=upload");
    exit();
}

if ($formAction === 'add_log') {
    if (!$canEditCommunicationLogs) {
        $_SESSION['edit_section_status'] = ['status' => 'logfailed', 'focus' => 'log'];
        header("location:/editrequest.php?lang=$lang&id=$requestuid&status=logfailed&focus=log");
        exit();
    }

    $adminnotesTrimmed = trim((string) $adminnotes);
    if ($adminnotesTrimmed === '') {
        $_SESSION['edit_section_status'] = ['status' => 'logfailed', 'focus' => 'log'];
        header("location:/editrequest.php?lang=$lang&id=$requestuid&status=logfailed&focus=log");
        exit();
    }

    $safeAdminNotes = mysqli_real_escape_string($link, $adminnotesTrimmed);
    $sql = "INSERT INTO tbladminlog(`triageid`, `dateadded`, `notes`, `creatorid`, `status`) VALUES ('$requestuid', '$todaydate', '$safeAdminNotes', '$updaterid', '1')";
    mysqli_query($link, $sql);

    $touchDateUpdated = mysqli_real_escape_string($link, getTodayDate());
    mysqli_query($link, "UPDATE tbltriage SET dateupdated='$touchDateUpdated', updaterid='" . (int) $updaterid . "' WHERE id='$requestuid'");

    if (rmt_table_has_column($link, 'RequestFieldHistory', 'requestID')) {
        $auditChangeTime = date('Y-m-d H:i:s');
        $safeRequestId = mysqli_real_escape_string($link, (string) $requestid);
        $safeField = mysqli_real_escape_string($link, 'staff_note_added');
        $newValueSql = "'" . mysqli_real_escape_string($link, $adminnotesTrimmed) . "'";
        $sqlAudit = "INSERT INTO RequestFieldHistory(`requestID`, `fieldName`, `oldValue`, `newValue`, `actorUserID`, `changeTimeStamp`) VALUES ('$safeRequestId', '$safeField', NULL, $newValueSql, '" . (int) $updaterid . "', '$auditChangeTime')";
        mysqli_query($link, $sqlAudit);
    }

    $_SESSION['edit_section_status'] = ['status' => 'logsuccess', 'focus' => 'log'];
    header("location:/editrequest.php?lang=$lang&id=$requestuid&status=logsuccess&focus=log");
    exit();
}

// ============================================================================
// TEAM ASSIGNMENT & EMAIL NOTIFICATIONS
// ============================================================================

// Get previous values for reassignment checks.
$result2 = mysqli_query($link, "SELECT catalogueid, serviceid, subserviceid, workerid FROM tbltriage WHERE id = '$requestuid'");
$row2 = mysqli_fetch_assoc($result2);
$ccatalogueid = $row2['catalogueid'];
$cserviceid = $row2['serviceid'];
$csubserviceid = $row2['subserviceid'];
$prevWorkerid = $row2['workerid'];

// Always honor the request's original language preference for outbound client notifications.
$requestlang = in_array($postedRequestLang, ['en', 'fr'], true)
    ? $postedRequestLang
    : rmt_get_request_language($link, $requestuidInt, $requestlang);

// Determine team contact
$contactid = rmt_resolve_responsible_team_id(
    $link,
    (int) $catalogueid,
    (int) $serviceid,
    (int) $subserviceid
);
$teamname = "";
$teamemail = "";
$contactname = "";
$contactemail = "";

if ($contactid > 0) {
    $result = mysqli_query($link, "SELECT * FROM tblteams WHERE id = '$contactid'");
    $row = mysqli_fetch_assoc($result);
    if ($row) {
        $teamname = $row['nameen'];
        $teamemail = $row['email'];
        $contactname = $row['contactname'];
        $contactemail = $row['contactemail'];
    }
} else {
    // Default to AAACT Triage
    $teamname = "AAACT Triage";
    $teamemail = "daiu-anci@ssc-spc.gc.ca";
    $contactname = "Brad Souster";
    $contactemail = "Brad.Souster@ssc-spc.gc.ca";
}

// Prepare email personalization
$attachments = array_filter([$attach1, $attach2, $attach3]);
$attach = implode("\n", $attachments);

$nameField = $lang === 'fr' ? 'namefr' : 'nameen';
$result = mysqli_query($link, "SELECT $nameField FROM tblcatalogue WHERE id='$catalogueid'");
$row = mysqli_fetch_assoc($result);
$cataloguename = $row ? $row[$nameField] : "";

$result = mysqli_query($link, "SELECT $nameField FROM tblservices WHERE id='$serviceid'");
$row = mysqli_fetch_assoc($result);
$servicename = $row ? $row[$nameField] : "";

$result = mysqli_query($link, "SELECT nameen, namefr FROM tblstatus WHERE id='$statusid'");
$row = mysqli_fetch_assoc($result);
$statusEn = $row ? $row['nameen'] : "";
$statusFr = $row ? $row['namefr'] : "";

$domain = app_base_url();
$nrequestemailid = base64_encode($requestuid);

$personalisation = [
    "requestid" => $requestid,
    "nrequestid" => $requestid,
    "teamname" => $teamname,
    "team_email" => $teamemail,
    "requesttitle" => $requesttitle,
    "nrequestemailid" => $nrequestemailid,
    "nrequestemail" => $clientemail,
    "client_fname" => $clientfname,
    "client_lname" => $clientlname,
    "client_email" => $clientemail,
    "attach" => $attach,
    "client_communications" => "",
    "catalogue_name" => $cataloguename,
    "service_name" => $servicename,
    "status_en" => $statusEn,
    "status_fr" => $statusFr,
    "url" => app_url("viewrequest.php?lang=" . $requestlang . "&erid=" . $nrequestemailid . "&reqid=" . urlencode("a11y-" . $requestid))
];

// Send emails based on status changes
if (!$isCurrentResolved && $isTargetResolved) {
    // Queue one survey send for newly resolved requests only.
    mysqli_query($link, "UPDATE tbltriage SET cssurvey = 0 WHERE id = '$requestuid' AND (cssurvey IS NULL)");
} elseif ($cstatusid != $statusid) {
    // Status changed (not to resolved) - client notifications are manual only.
}

// Notify the newly responsible team when a hierarchy edit changes ownership.
$contactidold = rmt_resolve_responsible_team_id(
    $link,
    (int) $ccatalogueid,
    (int) $cserviceid,
    (int) $csubserviceid
);
if (($cserviceid != $serviceid || $csubserviceid != $subserviceid) && $contactid > 0 && $contactid !== $contactidold) {
    $result = mysqli_query($link, "SELECT * FROM tblteams WHERE id = '$contactid'");
    $row = mysqli_fetch_assoc($result);
    if ($row) {
        $personalisation['teamname'] = $row['nameen'];
        $personalisation['team_email'] = $row['email'];
        $newTeamEmail = $row['email'];

        $reassignedTemplate = app_notify_template_id('notification_generic');
        $reassignedCategory = rmt_notification_template_category('reassigned');
        $reassignedTeamPersonalisation = $personalisation + [
            'notification_event' => 'reassigned',
            'template_category_id' => $reassignedCategory['id'],
            'template_category_name_en' => $reassignedCategory['name_en'],
            'template_category_name_fr' => $reassignedCategory['name_fr'],
            'subject' => rmt_notification_subject('reassigned', 'internal', 'en', $personalisation, $link, $contactid, (int) $serviceid, (int) $subserviceid),
            'message' => rmt_notification_message('reassigned', 'internal', 'en', $personalisation, $link, $contactid, (int) $serviceid, (int) $subserviceid),
        ];
        sendEmail($newTeamEmail, $reassignedTemplate, json_encode($reassignedTeamPersonalisation), ['recipientType' => 'internal']);
    }
}

// Send notification when assigned worker changes.
$prevWorkerIdInt = (int) ($prevWorkerid ?? 0);
$workerIdInt = (int) ($workerid ?? 0);
if ($workerIdInt > 0 && $workerIdInt !== $prevWorkerIdInt) {
    $workerResult = mysqli_query($link, "SELECT firstname, lastname, email, atype FROM tblusers WHERE id = '$workerIdInt' AND status = '1' LIMIT 1");
    $workerRow = $workerResult ? mysqli_fetch_assoc($workerResult) : null;
    $workerEmail = trim((string) ($workerRow['email'] ?? ''));

    if ($workerEmail !== '') {
        $workerName = trim(((string) ($workerRow['firstname'] ?? '')) . ' ' . ((string) ($workerRow['lastname'] ?? '')));
        if ($workerName !== '') {
            $personalisation['teamname'] = $workerName;
        }
        $personalisation['team_email'] = $workerEmail;

        $workerRoleKey = 'assignee';
        $workerAtype = (int) ($workerRow['atype'] ?? 0);
        if ($workerAtype === 3) {
            $workerRoleKey = 'manager';
        } elseif ($workerAtype === 4) {
            $workerRoleKey = 'team_lead';
        } elseif ($workerAtype === 1) {
            $workerRoleKey = 'admin';
        }

        $reassignedTemplate = app_notify_template_id('notification_generic');
        $reassignedCategory = rmt_notification_template_category('reassigned');
        $reassignedWorkerPersonalisation = $personalisation + [
            'notification_event' => 'reassigned',
            'template_category_id' => $reassignedCategory['id'],
            'template_category_name_en' => $reassignedCategory['name_en'],
            'template_category_name_fr' => $reassignedCategory['name_fr'],
            'subject' => rmt_notification_subject('reassigned', 'internal', 'en', $personalisation, $link, $contactid, (int) $serviceid, (int) $subserviceid),
            'message' => rmt_notification_message('reassigned', 'internal', 'en', $personalisation, $link, $contactid, (int) $serviceid, (int) $subserviceid),
        ];
        sendEmail($workerEmail, $reassignedTemplate, json_encode($reassignedWorkerPersonalisation), ['recipientType' => 'internal', 'recipientRole' => $workerRoleKey]);
    }
}

// ============================================================================
// VALIDATION
// ============================================================================

$requestSubjectLength = function_exists('mb_strlen') ? mb_strlen($requestSubject, 'UTF-8') : strlen($requestSubject);
if (empty($requestid) || empty($requesttitle) || empty($datereceived) ||
    ($formAction === 'update_request' && $canEditRequestSubject
        && (empty($requestSubject) || $requestSubjectLength > 500)) ||
    empty($statusid) || empty($catalogueid)) {
    header("location: /editrequest.php?lang=$lang&id=$requestuid&status=failed&focus=update");
    exit();
}

// ============================================================================
// UPDATE DATABASE
// ============================================================================

$requestFieldHistoryEnabled = rmt_table_has_column($link, 'RequestFieldHistory', 'requestID');
$generalRequestChanges = [];
if ($requestFieldHistoryEnabled) {
    rmt_append_request_change(
        $generalRequestChanges,
        'request_subject',
        (string) ($currentRequest['request_subject'] ?? ''),
        $requestSubject
    );
    rmt_append_request_change(
        $generalRequestChanges,
        'client_last_name',
        (string) ($currentRequest['clientlname'] ?? ''),
        (string) $clientlname
    );
    rmt_append_request_change(
        $generalRequestChanges,
        'client_first_name',
        (string) ($currentRequest['clientfname'] ?? ''),
        (string) $clientfname
    );
    rmt_append_request_change(
        $generalRequestChanges,
        'client_email',
        (string) ($currentRequest['clientemail'] ?? ''),
        (string) $clientemail
    );
    rmt_append_request_change(
        $generalRequestChanges,
        'client_phone',
        (string) ($currentRequest['clientphone'] ?? ''),
        (string) $clientphone
    );
    rmt_append_request_change(
        $generalRequestChanges,
        'date_received',
        $currentRequest['datereceived'] ?? null,
        $datereceived
    );
    rmt_append_request_change(
        $generalRequestChanges,
        'date_updated',
        $currentRequest['dateupdated'] ?? null,
        $dateupdated
    );
    rmt_append_request_change(
        $generalRequestChanges,
        'date_required',
        $currentRequest['daterequired'] ?? null,
        $daterequired
    );
    rmt_append_request_change(
        $generalRequestChanges,
        'date_resolved',
        $currentRequest['dateresolved'] ?? null,
        $dateresolved
    );
    rmt_append_request_change(
        $generalRequestChanges,
        'sla_timer',
        $currentRequest['slatimer'] ?? null,
        $slatimer
    );
    rmt_append_request_change(
        $generalRequestChanges,
        'intended_audience',
        rmt_normalize_intish($currentRequest['audienceid'] ?? '0'),
        rmt_normalize_intish($audienceid ?? '0')
    );
    rmt_append_request_change(
        $generalRequestChanges,
        'catalogue_name',
        rmt_normalize_intish($currentRequest['catalogueid'] ?? '0'),
        rmt_normalize_intish($catalogueid ?? '0')
    );
    rmt_append_request_change(
        $generalRequestChanges,
        'service_name',
        rmt_normalize_intish($currentRequest['serviceid'] ?? '0'),
        rmt_normalize_intish($serviceid ?? '0')
    );
    rmt_append_request_change(
        $generalRequestChanges,
        'subservice_name',
        rmt_normalize_intish($currentRequest['subserviceid'] ?? '0'),
        rmt_normalize_intish($subserviceid ?? '0')
    );
    rmt_append_request_change(
        $generalRequestChanges,
        'assigned_team_member',
        rmt_normalize_intish($currentRequest['workerid'] ?? '0'),
        rmt_normalize_intish($workerid ?? '0')
    );
    rmt_append_request_change(
        $generalRequestChanges,
        'sprint_schedule',
        (string) ($currentRequest['sprintschedule'] ?? ''),
        (string) $sprintschedule
    );
    rmt_append_request_change(
        $generalRequestChanges,
        'sprint_defects',
        (string) ($currentRequest['sprintdefects'] ?? ''),
        (string) $sprintdefects
    );
    rmt_append_request_change(
        $generalRequestChanges,
        'first_sprint_start',
        $currentRequest['firstsprintstartdate'] ?? null,
        $firstsprintstartdate
    );
    rmt_append_request_change(
        $generalRequestChanges,
        'first_sprint_end',
        $currentRequest['firstsprintenddate'] ?? null,
        $firstsprintenddate
    );
    rmt_append_request_change(
        $generalRequestChanges,
        'attachment_1',
        (string) ($currentRequest['attach1'] ?? ''),
        (string) $attach1
    );
    rmt_append_request_change(
        $generalRequestChanges,
        'attachment_2',
        (string) ($currentRequest['attach2'] ?? ''),
        (string) $attach2
    );
    rmt_append_request_change(
        $generalRequestChanges,
        'attachment_3',
        (string) ($currentRequest['attach3'] ?? ''),
        (string) $attach3
    );

    foreach ($uploadedFileNames as $uploadedFileName) {
        rmt_append_request_change(
            $generalRequestChanges,
            'uploaded_file',
            null,
            $uploadedFileName
        );
    }
}

if ($requestFieldHistoryEnabled && $canFullFieldEdit) {
    rmt_append_request_change(
        $generalRequestChanges,
        'department_agency',
        $currentDepartmentAgency,
        $departmentAgency
    );
}

$safeRequestTitle = mysqli_real_escape_string($link, $requesttitle);
$safeRequestSubject = mysqli_real_escape_string($link, $requestSubject);
$sql = "UPDATE `tbltriage` SET
    `title` = '$safeRequestTitle',
    `request_subject` = " . ($requestSubject === '' ? 'NULL' : "'$safeRequestSubject'") . ",
    `clientlname` = '$clientlname',
    `clientfname` = '$clientfname',
    `clientemail` = '$clientemail',
    `clientphone` = '$clientphone',
    `datereceived` = '$datereceived',
    `dateupdated` = '$dateupdated',
    `slatimer` = '$slatimer',
    `statusid` = '$statusid',
    `bdm` = '$bdm',
    `attach1` = '$attach1',
    `attach2` = '$attach2',
    `attach3` = '$attach3',
    `catalogueid` = '$catalogueid',
    `serviceid` = '$serviceid',
    `subserviceid` = '$subserviceid',
    `updaterid` = '$updaterid',
    `workerid` = '$workerid',
    `sprintschedule` = '$sprintschedule',
    `sprintdefects` = '$sprintdefects',
    `audienceid` = '$audienceid'";

// Add optional sprint fields if they exist in the database
if (!empty($sprintschedule)) {
    $sql .= ", `sprintschedule` = '$sprintschedule'";
}

if (!empty($sprintdefects)) {
    $sql .= ", `sprintdefects` = '$sprintdefects'";
}

if (!empty($dateresolved)) {
    $sql .= ", `dateresolved` = '$dateresolved'";
}

if (!empty($daterequired)) {
    $sql .= ", `daterequired` = '$daterequired'";
}

if (!empty($firstsprintstartdate)) {
    $sql .= ", `firstsprintstartdate` = '$firstsprintstartdate'";
}

if (!empty($firstsprintenddate)) {
    $sql .= ", `firstsprintenddate` = '$firstsprintenddate'";
}

$sql .= " WHERE id='$requestuid'";
mysqli_query($link, $sql);

if ($canFullFieldEdit) {
    $departmentNoteLang = $requestlang;
    if ($departmentRow && stripos(trim((string)$departmentRow['notes']), 'Ministère/organisme:') === 0) {
        $departmentNoteLang = 'fr';
    } elseif ($departmentRow && stripos(trim((string)$departmentRow['notes']), 'Department/agency:') === 0) {
        $departmentNoteLang = 'en';
    }

    if ($departmentRow) {
        $departmentCommlogId = (int)$departmentRow['id'];
        $updatedDepartmentNotes = rmt_replace_department_agency_note($departmentRow['notes'], $departmentAgency, $departmentNoteLang);
        if ($updatedDepartmentNotes === '') {
            mysqli_query($link, "UPDATE tblcommlog SET status = '0' WHERE id = '$departmentCommlogId' AND triageid = '$requestuid'");
        } else {
            $safeDepartmentNotes = mysqli_real_escape_string($link, $updatedDepartmentNotes);
            mysqli_query($link, "UPDATE tblcommlog SET notes = '$safeDepartmentNotes' WHERE id = '$departmentCommlogId' AND triageid = '$requestuid'");
        }
    } elseif (hasValue($departmentAgency)) {
        $departmentNotes = rmt_replace_department_agency_note('', $departmentAgency, $departmentNoteLang);
        $safeDepartmentNotes = mysqli_real_escape_string($link, $departmentNotes);
        mysqli_query(
            $link,
            "INSERT INTO tblcommlog(`triageid`, `dateadded`, `notes`, `creatorid`, `status`)
             VALUES ('$requestuid', '$todaydate', '$safeDepartmentNotes', '$updaterid', '1')"
        );
    }
}

// Add communications notes
if ($canEditCommunicationLogs && !empty($adminnotes)) {
    if ($requestFieldHistoryEnabled) {
        rmt_append_request_change($generalRequestChanges, 'staff_note_added', null, $adminnotes);
    }
    $sql = "INSERT INTO tbladminlog(`triageid`, `dateadded`, `notes`, `creatorid`, `status`) 
            VALUES ('$requestuid', '$todaydate', '$adminnotes', '$updaterid', '1')";
    mysqli_query($link, $sql);
}

// Set NULL for empty dates
if (isset($dateupdatedu)) {
    mysqli_query($link, "UPDATE `tbltriage` SET `dateupdated` = NULL WHERE id='$requestuid'");
}
if ($daterequiredu) {
    mysqli_query($link, "UPDATE `tbltriage` SET `daterequired` = NULL WHERE id='$requestuid'");
}
if (isset($dateresolvedu)) {
    mysqli_query($link, "UPDATE `tbltriage` SET `dateresolved` = NULL WHERE id='$requestuid'");
}

$statusNameField = ($lang === 'fr') ? 'namefr' : 'nameen';
$statusFeedback = null;
$changedFieldLabels = [];

$feedbackFieldLabels = [
    'request_subject' => [
        'en' => 'Request subject',
        'fr' => 'Objet de la demande',
    ],
    'department_agency' => [
        'en' => 'Department/agency',
        'fr' => 'Ministère/organisme',
    ],
    'client_last_name' => [
        'en' => 'Last name',
        'fr' => 'Nom',
    ],
    'client_first_name' => [
        'en' => 'First name',
        'fr' => 'Prenom',
    ],
    'client_email' => [
        'en' => 'Client email',
        'fr' => 'Courriel du client',
    ],
    'client_phone' => [
        'en' => 'Client phone number',
        'fr' => 'Numero de telephone client',
    ],
    'status' => [
        'en' => 'Status',
        'fr' => 'Statut',
    ],
    'date_received' => [
        'en' => 'Date received',
        'fr' => 'Date de reception',
    ],
    'date_updated' => [
        'en' => 'Date updated',
        'fr' => 'Date de mise a jour',
    ],
    'date_required' => [
        'en' => 'Date required',
        'fr' => 'Date requise',
    ],
    'date_resolved' => [
        'en' => 'Date resolved',
        'fr' => 'Date de resolution',
    ],
    'sla_timer' => [
        'en' => 'SLA due date',
        'fr' => 'Date d echeance du SLA',
    ],
    'intended_audience' => [
        'en' => 'Audience',
        'fr' => 'Audience',
    ],
    'assigned_team_member' => [
        'en' => 'Assigned AAACT team member',
        'fr' => 'Membre assigne de l equipe AATIA',
    ],
    'catalogue_name' => [
        'en' => 'Catalogue name',
        'fr' => 'Nom du catalogue',
    ],
    'service_name' => [
        'en' => 'Service name',
        'fr' => 'Nom du service',
    ],
    'subservice_name' => [
        'en' => 'Sub-service name',
        'fr' => 'Nom du sous-service',
    ],
    'sprint_schedule' => [
        'en' => 'Sprint schedule',
        'fr' => 'Calendrier du sprint',
    ],
    'sprint_defects' => [
        'en' => 'Sprint defect',
        'fr' => 'Defauts du sprint',
    ],
    'first_sprint_start' => [
        'en' => 'Sprint Start Date',
        'fr' => 'Date de debut du sprint',
    ],
    'first_sprint_end' => [
        'en' => 'Sprint End Date',
        'fr' => 'Date de fin du sprint',
    ],
    'attachment_1' => [
        'en' => 'Attachment 1',
        'fr' => 'Piece jointe 1',
    ],
    'attachment_2' => [
        'en' => 'Attachment 2',
        'fr' => 'Piece jointe 2',
    ],
    'attachment_3' => [
        'en' => 'Attachment 3',
        'fr' => 'Piece jointe 3',
    ],
    'client_communication_log' => [
        'en' => 'Client communication log update',
        'fr' => 'Mise a jour du journal des communications client',
    ],
    'staff_communication_log' => [
        'en' => 'Staff communication log update',
        'fr' => 'Mise a jour du journal des communications du personnel',
    ],
    'staff_note_added' => [
        'en' => 'Staff note added',
        'fr' => 'Note du personnel ajoutee',
    ],
    'uploaded_file' => [
        'en' => 'Uploaded file',
        'fr' => 'Fichier televerse',
    ],
];

$labelsForLang = [];
foreach ($feedbackFieldLabels as $key => $localizedLabels) {
    $labelsForLang[$key] = $localizedLabels[$lang] ?? $localizedLabels['en'];
}

$oldStatusId = rmt_normalize_intish($currentRequest['statusid'] ?? '0');
$newStatusId = rmt_normalize_intish($statusid);
if ($oldStatusId !== $newStatusId) {
    $statusIdList = array_values(array_unique([$oldStatusId, $newStatusId]));
    $statusNamesById = [];

    if (!empty($statusIdList)) {
        $statusIdCsv = implode(',', array_map('intval', $statusIdList));
        $statusNameResult = mysqli_query($link, "SELECT id, $statusNameField AS statusName FROM tblstatus WHERE id IN ($statusIdCsv)");
        while ($statusNameResult && $statusNameRow = mysqli_fetch_assoc($statusNameResult)) {
            $statusNamesById[(string) ((int) $statusNameRow['id'])] = trim((string) ($statusNameRow['statusName'] ?? ''));
        }
    }

    $oldStatusName = $statusNamesById[$oldStatusId] ?? $oldStatusId;
    $newStatusName = $statusNamesById[$newStatusId] ?? $newStatusId;
    $statusFeedback = [
        'from' => $oldStatusName,
        'to' => $newStatusName,
    ];
}

$oldWorkerId = rmt_normalize_intish($currentRequest['workerid'] ?? '0');
$newWorkerId = rmt_normalize_intish($workerid);
if ($oldWorkerId !== $newWorkerId) {
    $changedFieldLabels[] = rmt_lookup_label($labelsForLang, 'assigned_team_member');
}

rmt_append_changed_label($changedFieldLabels, $labelsForLang, 'request_subject', $currentRequest['request_subject'] ?? '', $requestSubject);
rmt_append_changed_label($changedFieldLabels, $labelsForLang, 'client_last_name', $currentRequest['clientlname'] ?? '', $clientlname);
rmt_append_changed_label($changedFieldLabels, $labelsForLang, 'client_first_name', $currentRequest['clientfname'] ?? '', $clientfname);
rmt_append_changed_label($changedFieldLabels, $labelsForLang, 'client_email', $currentRequest['clientemail'] ?? '', $clientemail);
rmt_append_changed_label($changedFieldLabels, $labelsForLang, 'client_phone', $currentRequest['clientphone'] ?? '', $clientphone);
rmt_append_changed_label($changedFieldLabels, $labelsForLang, 'date_received', $currentRequest['datereceived'] ?? null, $datereceived);
rmt_append_changed_label($changedFieldLabels, $labelsForLang, 'date_updated', $currentRequest['dateupdated'] ?? null, $dateupdated);
rmt_append_changed_label($changedFieldLabels, $labelsForLang, 'date_required', $currentRequest['daterequired'] ?? null, $daterequired);
rmt_append_changed_label($changedFieldLabels, $labelsForLang, 'date_resolved', $currentRequest['dateresolved'] ?? null, $dateresolved);
rmt_append_changed_label($changedFieldLabels, $labelsForLang, 'sla_timer', $currentRequest['slatimer'] ?? null, $slatimer);
rmt_append_changed_label(
    $changedFieldLabels,
    $labelsForLang,
    'intended_audience',
    rmt_normalize_intish($currentRequest['audienceid'] ?? '0'),
    rmt_normalize_intish($audienceid ?? '0')
);
rmt_append_changed_label($changedFieldLabels, $labelsForLang, 'sprint_schedule', $currentRequest['sprintschedule'] ?? '', $sprintschedule);
rmt_append_changed_label($changedFieldLabels, $labelsForLang, 'sprint_defects', $currentRequest['sprintdefects'] ?? '', $sprintdefects);
rmt_append_changed_label($changedFieldLabels, $labelsForLang, 'first_sprint_start', $currentRequest['firstsprintstartdate'] ?? null, $firstsprintstartdate);
rmt_append_changed_label($changedFieldLabels, $labelsForLang, 'first_sprint_end', $currentRequest['firstsprintenddate'] ?? null, $firstsprintenddate);
rmt_append_changed_label($changedFieldLabels, $labelsForLang, 'attachment_1', $currentRequest['attach1'] ?? '', $attach1);
rmt_append_changed_label($changedFieldLabels, $labelsForLang, 'attachment_2', $currentRequest['attach2'] ?? '', $attach2);
rmt_append_changed_label($changedFieldLabels, $labelsForLang, 'attachment_3', $currentRequest['attach3'] ?? '', $attach3);

$oldCatalogueId = rmt_normalize_intish($currentRequest['catalogueid'] ?? '0');
$newCatalogueId = rmt_normalize_intish($catalogueid);
if ($oldCatalogueId !== $newCatalogueId) {
    $changedFieldLabels[] = rmt_lookup_label($labelsForLang, 'catalogue_name');
}

$oldServiceId = rmt_normalize_intish($currentRequest['serviceid'] ?? '0');
$newServiceId = rmt_normalize_intish($serviceid);
if ($oldServiceId !== $newServiceId) {
    $changedFieldLabels[] = rmt_lookup_label($labelsForLang, 'service_name');
}

$oldSubserviceId = rmt_normalize_intish($currentRequest['subserviceid'] ?? '0');
$newSubserviceId = rmt_normalize_intish($subserviceid);
if ($oldSubserviceId !== $newSubserviceId) {
    $changedFieldLabels[] = rmt_lookup_label($labelsForLang, 'subservice_name');
}

foreach ($generalRequestChanges as $change) {
    $fieldName = (string) ($change['field'] ?? '');
    if ($fieldName === '' || !isset($labelsForLang[$fieldName])) {
        continue;
    }
    $changedFieldLabels[] = rmt_lookup_label($labelsForLang, $fieldName);
}

$changedFieldLabels = array_values(array_unique($changedFieldLabels));

$_SESSION['request_update_feedback'] = [
    'status_change' => $statusFeedback,
    'changed_fields' => $changedFieldLabels,
];

if ($requestFieldHistoryEnabled && !empty($generalRequestChanges)) {
    $auditChangeTime = date('Y-m-d H:i:s');
    $safeRequestId = mysqli_real_escape_string($link, (string) $requestid);
    $safeActorId = (int) $updaterid;

    foreach ($generalRequestChanges as $change) {
        $safeField = mysqli_real_escape_string($link, (string) ($change['field'] ?? ''));
        if ($safeField === '') {
            continue;
        }

        $oldValueSql = is_null($change['old'])
            ? 'NULL'
            : "'" . mysqli_real_escape_string($link, (string) $change['old']) . "'";
        $newValueSql = is_null($change['new'])
            ? 'NULL'
            : "'" . mysqli_real_escape_string($link, (string) $change['new']) . "'";

        $sqlAudit = "INSERT INTO RequestFieldHistory(`requestID`, `fieldName`, `oldValue`, `newValue`, `actorUserID`, `changeTimeStamp`) VALUES ('$safeRequestId', '$safeField', $oldValueSql, $newValueSql, '$safeActorId', '$auditChangeTime')";
        mysqli_query($link, $sqlAudit);
    }
}

// Redirect on success.
// When a request is newly resolved, send staff directly to manual survey links.
if (!$isCurrentResolved && $isTargetResolved) {
    header("location:/client-survey-link.php?lang=$lang&erid=$redirectid");
    exit();
}

header("location:/editrequest.php?lang=$lang&id=$requestuid&status=success&focus=update");
exit();
?>
