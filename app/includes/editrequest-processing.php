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
    header("location:/editrequest.php?lang=$langCode&id=$requestuid&status=failed");
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

// ============================================================================
// STATUS CHANGE TRACKING
// ============================================================================

$cstatusid = (string) ($currentRequest['statusid'] ?? '');
$previousWorkerIdForHistory = (int) ($currentRequest['workerid'] ?? 0);
$newWorkerIdForHistory = (int) $workerid;
$statusChanged = ((string) $cstatusid !== (string) $statusid);
$assignmentChanged = ($previousWorkerIdForHistory !== $newWorkerIdForHistory);
$isCurrentResolved = rmt_is_resolved_status_id($link, $cstatusid);

if ($statusChanged || $assignmentChanged) {
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
// FILE UPLOADS (disabled)
// ============================================================================

// File uploads are intentionally disabled in edit flow until storage is implemented.

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
    header("location: /editrequest.php?lang=$lang&id=$requestuid&status=failed");
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
            rmt_append_request_change($generalRequestChanges, 'client_communication_log', (string) ($existingCommlog1Row['notes'] ?? ''), $commlog1);
        }
        $sql = "UPDATE `tblcommlog` SET `notes` = '$commlog1' WHERE id='$commlogid1'";
        mysqli_query($link, $sql);
    }
    if (!empty($commlogid2)) {
        if ($requestFieldHistoryEnabled) {
            $existingCommlog2Result = mysqli_query($link, "SELECT notes FROM tblcommlog WHERE id='$commlogid2' LIMIT 1");
            $existingCommlog2Row = $existingCommlog2Result ? mysqli_fetch_assoc($existingCommlog2Result) : null;
            rmt_append_request_change($generalRequestChanges, 'staff_communication_log', (string) ($existingCommlog2Row['notes'] ?? ''), $commlog2);
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

header("location:/viewrequest.php?lang=$lang&erid=$redirectid&reqid=" . urlencode("a11y-" . $requestid) . "&status=success");
exit();
?>
