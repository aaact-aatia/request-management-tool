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
$canEditTitle = $canFullFieldEdit || $isManagerAccount || $isTeamLeadAccount;
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

    $requestContactId = 0;
    $subserviceIdInt = (int)($currentRequest['subserviceid'] ?? 0);
    $serviceIdInt = (int)($currentRequest['serviceid'] ?? 0);
    if ($subserviceIdInt > 0) {
        $contactResult = mysqli_query($link, "SELECT contactid FROM tblsubservices WHERE id = '$subserviceIdInt' LIMIT 1");
        $contactRow = $contactResult ? mysqli_fetch_assoc($contactResult) : null;
        $requestContactId = (int)($contactRow['contactid'] ?? 0);
    }
    if ($requestContactId === 0 && $serviceIdInt > 0) {
        $contactResult = mysqli_query($link, "SELECT contactid FROM tblservices WHERE id = '$serviceIdInt' LIMIT 1");
        $contactRow = $contactResult ? mysqli_fetch_assoc($contactResult) : null;
        $requestContactId = (int)($contactRow['contactid'] ?? 0);
    }

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

$requestid = getPostValue('requestid');
$requesttitle = getPostValue('requesttitle');
$clientlname = getPostValue('clientlname');
$clientfname = getPostValue('clientfname');
$clientemail = getPostValue('clientemail');
$departmentagency = getPostValue('departmentagency');
$departmentagencyCommlogId = (int)getPostValue('departmentagency_commlogid', 0);
$clientphone = getPostValue('clientphone');
$sourceid = getPostValue('sourceid');
$statusid = getPostValue('statusid');
$datereceived = getPostValue('datereceived');
$dateupdated = !empty($_POST['dateupdated']) ? getPostValue('dateupdated') : getTodayDate();

// Handle nullable dates
$daterequired = getPostValue('daterequired');
$daterequiredu = empty($daterequired);
if ($daterequiredu) $daterequired = NULL;

$dateresolved = getPostValue('dateresolved');
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
$commlog1 = getPostValue('commlog1');
$commlogid1 = getPostValue('commlogid1');
$commlog2 = getPostValue('commlog2');
$commlogid2 = getPostValue('commlogid2');
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
    $requestid = (string) ($currentRequest['requestid'] ?? '');
    $clientlname = (string) ($currentRequest['clientlname'] ?? '');
    $clientfname = (string) ($currentRequest['clientfname'] ?? '');
    $clientemail = (string) ($currentRequest['clientemail'] ?? '');
    $clientphone = (string) ($currentRequest['clientphone'] ?? '');
    $sourceid = (string) ($currentRequest['sourceid'] ?? '');
    $datereceived = (string) ($currentRequest['datereceived'] ?? '');
    $dateupdated = (string) ($currentRequest['dateupdated'] ?? '');
    $daterequired = $currentRequest['daterequired'] ?? NULL;
    $dateresolved = $currentRequest['dateresolved'] ?? NULL;
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

    // Keep communications metadata unchanged outside full-edit scope.
    $departmentagency = '';
    $departmentagencyCommlogId = 0;

    if (!$canEditTitle) {
        $requesttitle = (string) ($currentRequest['title'] ?? '');
    }
    if (!$canEditSlaTimer) {
        $slatimer = (string) ($currentRequest['slatimer'] ?? '');
    }
}

// Never write an empty string to DATE columns.
if (empty($dateupdated)) {
    $dateupdated = !empty($currentRequest['dateupdated'])
        ? (string) $currentRequest['dateupdated']
        : getTodayDate();
}

$isTargetResolved = rmt_is_resolved_status_id($link, $statusid);
if (empty($dateresolved) && $isTargetResolved) {
    $dateresolved = getTodayDate();
} elseif (empty($dateresolved)) {
    $dateresolvedu = true;
    $dateresolved = NULL;
}

function upsertDepartmentAgencyInNotes($notes, $departmentValue, $lang) {
    $cleanedNotes = preg_replace('/^\s*(Department\/agency|Ministère\/organisme):\s*.*(?:\R|$)/miu', '', (string)$notes);
    $cleanedNotes = trim((string)$cleanedNotes);

    if (!hasValue($departmentValue)) {
        return $cleanedNotes;
    }

    $prefix = ($lang === 'fr') ? 'Ministère/organisme: ' : 'Department/agency: ';
    $line = $prefix . $departmentValue;

    if ($cleanedNotes === '') {
        return $line;
    }

    return $line . "\n\n" . $cleanedNotes;
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
$contactid = -1;
$teamname = "";
$teamemail = "";
$contactname = "";
$contactemail = "";

if (hasValue($subserviceid)) {
    $result = mysqli_query($link, "SELECT contactid FROM tblsubservices WHERE id = '$subserviceid'");
    $row = mysqli_fetch_assoc($result);
    if ($row) $contactid = $row['contactid'];
} elseif (hasValue($serviceid)) {
    $result = mysqli_query($link, "SELECT contactid FROM tblservices WHERE id = '$serviceid'");
    $row = mysqli_fetch_assoc($result);
    if ($row) $contactid = $row['contactid'];
}

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

// Send emails for service/subservice changes
if ($csubserviceid != $subserviceid && hasValue($subserviceid)) {
    // Subservice changed
    $result = mysqli_query($link, "SELECT contactid FROM tblsubservices WHERE id = '$subserviceid'");
    $row = mysqli_fetch_assoc($result);
    $contactid = $row['contactid'];
    
    // Get old contactid
    if ($csubserviceid == 0) {
        $resultold = mysqli_query($link, "SELECT contactid FROM tblservices WHERE id = '$cserviceid'");
    } else {
        $resultold = mysqli_query($link, "SELECT contactid FROM tblsubservices WHERE id = '$csubserviceid'");
    }
    $rowold = mysqli_fetch_assoc($resultold);
    $contactidold = $rowold['contactid'];
    
    if ($contactid != $contactidold) {
        $result = mysqli_query($link, "SELECT * FROM tblteams WHERE id = '$contactid'");
        $row = mysqli_fetch_assoc($result);
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
            'subject' => rmt_notification_subject('reassigned', 'internal', 'en', $personalisation),
            'message' => rmt_notification_message('reassigned', 'internal', 'en', $personalisation),
        ];
        sendEmail($newTeamEmail, $reassignedTemplate, json_encode($reassignedTeamPersonalisation), ['recipientType' => 'internal']);
    }
} elseif ($cserviceid != $serviceid) {
    // Service changed
    $result = mysqli_query($link, "SELECT contactid FROM tblservices WHERE id = '$serviceid'");
    $row = mysqli_fetch_assoc($result);
    $contactid = $row['contactid'];
    
    $resultold = mysqli_query($link, "SELECT contactid FROM tblservices WHERE id = '$cserviceid'");
    $rowold = mysqli_fetch_assoc($resultold);
    $contactidold = $rowold['contactid'];
    
    if ($contactid != $contactidold) {
        $result = mysqli_query($link, "SELECT * FROM tblteams WHERE id = '$contactid'");
        $row = mysqli_fetch_assoc($result);
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
            'subject' => rmt_notification_subject('reassigned', 'internal', 'en', $personalisation),
            'message' => rmt_notification_message('reassigned', 'internal', 'en', $personalisation),
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
            'subject' => rmt_notification_subject('reassigned', 'internal', 'en', $personalisation),
            'message' => rmt_notification_message('reassigned', 'internal', 'en', $personalisation),
        ];
        sendEmail($workerEmail, $reassignedTemplate, json_encode($reassignedWorkerPersonalisation), ['recipientType' => 'internal', 'recipientRole' => $workerRoleKey]);
    }
}

// ============================================================================
// VALIDATION
// ============================================================================

if (empty($requestid) || empty($requesttitle) || empty($datereceived) || 
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
        'request_title',
        (string) ($currentRequest['title'] ?? ''),
        (string) $requesttitle
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
        'request_source',
        rmt_normalize_intish($currentRequest['sourceid'] ?? '0'),
        rmt_normalize_intish($sourceid ?? '0')
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

$sql = "UPDATE `tbltriage` SET 
    `requestid` = '$requestid',
    `title` = '$requesttitle',
    `clientlname` = '$clientlname',
    `clientfname` = '$clientfname',
    `clientemail` = '$clientemail',
    `clientphone` = '$clientphone',
    `sourceid` = " . (!empty($sourceid) ? "'$sourceid'" : "NULL") . ",
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

// Update communication logs (admin/superadmin/manager)
if ($canEditCommunicationLogs) {
    if (!empty($commlogid1)) {
        if ($requestFieldHistoryEnabled) {
            $existingCommlog1Result = mysqli_query($link, "SELECT notes FROM tblcommlog WHERE id='$commlogid1' LIMIT 1");
            $existingCommlog1Row = $existingCommlog1Result ? mysqli_fetch_assoc($existingCommlog1Result) : null;
            rmt_append_request_change(
                $generalRequestChanges,
                'client_communication_log',
                (string) ($existingCommlog1Row['notes'] ?? ''),
                stripslashes((string) $commlog1)
            );
        }
        $sql = "UPDATE `tblcommlog` SET `notes` = '$commlog1' WHERE id='$commlogid1'";
        mysqli_query($link, $sql);
    }
    if (!empty($commlogid2)) {
        if ($requestFieldHistoryEnabled) {
            $existingCommlog2Result = mysqli_query($link, "SELECT notes FROM tblcommlog WHERE id='$commlogid2' LIMIT 1");
            $existingCommlog2Row = $existingCommlog2Result ? mysqli_fetch_assoc($existingCommlog2Result) : null;
            rmt_append_request_change(
                $generalRequestChanges,
                'staff_communication_log',
                (string) ($existingCommlog2Row['notes'] ?? ''),
                stripslashes((string) $commlog2)
            );
        }
        $sql = "UPDATE `tblcommlog` SET `notes` = '$commlog2' WHERE id='$commlogid2'";
        mysqli_query($link, $sql);
    }
}

// Keep Department/agency synchronized in client communications notes.
if ($canFullFieldEdit) {
    $targetCommlogId = $departmentagencyCommlogId;
    if ($targetCommlogId <= 0) {
        $targetResult = mysqli_query($link, "SELECT id FROM tblcommlog WHERE triageid = '$requestuid' AND status = '1' ORDER BY id ASC LIMIT 1");
        if ($targetResult && mysqli_num_rows($targetResult) > 0) {
            $targetRow = mysqli_fetch_assoc($targetResult);
            $targetCommlogId = (int)$targetRow['id'];
        }
    }

    if ($targetCommlogId > 0) {
        $notesResult = mysqli_query($link, "SELECT notes FROM tblcommlog WHERE id = '$targetCommlogId' AND triageid = '$requestuid' AND status = '1' LIMIT 1");
        if ($notesResult && mysqli_num_rows($notesResult) > 0) {
            $notesRow = mysqli_fetch_assoc($notesResult);
            $updatedNotes = upsertDepartmentAgencyInNotes($notesRow['notes'], $departmentagency, $lang);
            $updatedNotesEscaped = mysqli_real_escape_string($link, $updatedNotes);
            mysqli_query($link, "UPDATE tblcommlog SET notes = '$updatedNotesEscaped' WHERE id = '$targetCommlogId' AND triageid = '$requestuid' AND status = '1'");
        }
    } elseif (hasValue($departmentagency)) {
        $departmentPrefix = ($lang === 'fr') ? 'Ministère/organisme: ' : 'Department/agency: ';
        $departmentNote = mysqli_real_escape_string($link, $departmentPrefix . $departmentagency);
        mysqli_query($link, "INSERT INTO tblcommlog(`triageid`, `dateadded`, `notes`, `creatorid`, `status`) VALUES ('$requestuid', '$todaydate', '$departmentNote', '$updaterid', '1')");
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
    'request_title' => [
        'en' => 'Request title update',
        'fr' => 'Mise a jour du titre de la demande',
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
    'department_agency' => [
        'en' => 'Department/agency',
        'fr' => 'Ministere/organisme',
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
    'request_source' => [
        'en' => 'Request intake source',
        'fr' => 'Source de la demande',
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

rmt_append_changed_label($changedFieldLabels, $labelsForLang, 'request_title', $currentRequest['title'] ?? '', $requesttitle);
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

$oldSourceId = rmt_normalize_intish($currentRequest['sourceid'] ?? '0');
$newSourceId = rmt_normalize_intish($sourceid);
if ($oldSourceId !== $newSourceId) {
    $changedFieldLabels[] = rmt_lookup_label($labelsForLang, 'request_source');
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
