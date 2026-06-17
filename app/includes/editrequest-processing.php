<?php
/**
 * Edit Request Form Processing
 * Extracted from main editrequest.php for better organization
 */

// Encode for redirect
$redirectid = base64_encode($requestuid);

// ============================================================================
// COLLECT FORM DATA
// ============================================================================

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

function isResolvedStatusId($link, $statusId) {
    $statusId = (int)$statusId;
    if ($statusId <= 0) {
        return false;
    }

    $result = mysqli_query($link, "SELECT * FROM tblstatus WHERE id = '$statusId' LIMIT 1");
    if (!$result || mysqli_num_rows($result) === 0) {
        return false;
    }

    $row = mysqli_fetch_assoc($result);

    // Prefer explicit admin configuration when available.
    if (array_key_exists('is_resolved', $row)) {
        return (int)$row['is_resolved'] === 1;
    }

    // Backward compatibility: infer from status names if flag is not present.
    $nameEn = strtolower(trim((string)$row['nameen']));
    $nameFr = strtolower(trim((string)$row['namefr']));

    return $nameEn === 'resolved' || $nameFr === 'résolu' || $nameFr === 'resolu';
}

$isTargetResolved = isResolvedStatusId($link, $statusid);
$datereceived = getPostValue('datereceived');
$dateupdated = !empty($_POST['dateupdated']) ? getPostValue('dateupdated') : getTodayDate();

// Handle nullable dates
$daterequired = getPostValue('daterequired');
$daterequiredu = empty($daterequired);
if ($daterequiredu) $daterequired = NULL;

$dateresolved = getPostValue('dateresolved');
if (empty($dateresolved) && $isTargetResolved) {
    $dateresolved = getTodayDate();
} elseif (empty($dateresolved)) {
    $dateresolvedu = true;
    $dateresolved = NULL;
}

$newrequest = getPostValue('newrequest', 'No');
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

// ============================================================================
// STATUS CHANGE TRACKING
// ============================================================================

$result2 = mysqli_query($link, "SELECT statusid FROM tbltriage WHERE id = '$requestuid'");
$row2 = mysqli_fetch_assoc($result2);
$cstatusid = $row2['statusid'];
$isCurrentResolved = isResolvedStatusId($link, $cstatusid);

if ($cstatusid != $statusid) {
    $exactTime = date('Y-m-d H:i:s');
    $sql = "INSERT INTO StatusHistory(`requestID`,`statusID`,`changeTimeStamp`) 
            VALUES ('$requestid', '$statusid', '$exactTime')";
    mysqli_query($link, $sql);
    
    // Update previous status duration
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

// ============================================================================
// FILE UPLOADS
// ============================================================================

if (isset($_FILES['fileToUpload']) && !empty($_FILES['fileToUpload']['tmp_name'][0])) {
    $azureBlobManager = new AzureBlobStorageManager();
    
    foreach ($_FILES['fileToUpload']['tmp_name'] as $key => $fileTmpPath) {
        $fileNameWithExtension = $_FILES['fileToUpload']['name'][$key];
        $fileType = pathinfo($fileNameWithExtension, PATHINFO_EXTENSION);
        $fileSize = $_FILES['fileToUpload']['size'][$key] / 1024; // Convert to KB
        $randomCode = $requestid . "-" . bin2hex(random_bytes(16)) . "." . $fileType;
        
        $fileName = mysqli_real_escape_string($link, $fileNameWithExtension);
        $fileType = mysqli_real_escape_string($link, $fileType);
        $randomCode = mysqli_real_escape_string($link, $randomCode);
        
        if ($azureBlobManager->uploadFile($fileTmpPath, $randomCode)) {
            $sql = "INSERT INTO tblfiles (`requestid`, `name`, `code`, `type`, `size`) 
                    VALUES ('$requestid', '$fileName', '$randomCode', '$fileType', '$fileSize')";
            mysqli_query($link, $sql);
        }
    }
}

// ============================================================================
// TEAM ASSIGNMENT & EMAIL NOTIFICATIONS
// ============================================================================

// Get previous values
$result2 = mysqli_query($link, "SELECT catalogueid, serviceid, subserviceid, workerid 
                                FROM tbltriage WHERE id = '$requestuid'");
$row2 = mysqli_fetch_assoc($result2);
$ccatalogueid = $row2['catalogueid'];
$cserviceid = $row2['serviceid'];
$csubserviceid = $row2['subserviceid'];
$prevWorkerid = $row2['workerid'];

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

$domain = "https://gcdc-ssc-ictaccess-linux-aaact-rmt-dev-asv.azurewebsites.net/";
$nrequestemailid = base64_encode($requestuid);

$personalisation = [
    "requestid" => $requestid,
    "nrequestid" => $requestid,
    "teamname" => $teamname,
    "requesttitle" => $requesttitle,
    "nrequestemailid" => $nrequestemailid,
    "client_fname" => $clientfname,
    "client_lname" => $clientlname,
    "client_email" => $clientemail,
    "attach" => $attach,
    "client_communications" => "",
    "catalogue_name" => $cataloguename,
    "service_name" => $servicename,
    "status_en" => $statusEn,
    "status_fr" => $statusFr,
    "url" => $domain . "/viewrequest.php?lang=en&erid=" . $nrequestemailid . "&reqid=" . urlencode("a11y-" . $requestid)
];

// Send emails based on status changes
if (!$isCurrentResolved && $isTargetResolved) {
    // Queue one survey send for newly resolved requests only.
    mysqli_query($link, "UPDATE tbltriage SET cssurvey = 0 WHERE id = '$requestuid' AND (cssurvey IS NULL)");

    // Request resolved
    sendEmail($teamemail, "5dc8291c-a0b4-4fa0-8733-40c28d3ddf6d", json_encode($personalisation));
    if ($teamemail != "daiu-anci@ssc-spc.gc.ca") {
        sendEmail($clientemail, "49ffefeb-21d0-4508-ac5f-46b41c0f3348", json_encode($personalisation));
    }
} elseif ($cstatusid != $statusid) {
    // Status changed (not to resolved)
    if ($teamemail != "daiu-anci@ssc-spc.gc.ca") {
        sendEmail($clientemail, "393948e5-39fe-418e-b16f-73a1f084a0f2", json_encode($personalisation));
    }
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
        $newTeamEmail = $row['email'];
        
        sendEmail($newTeamEmail, "8270de12-b994-4d29-aa22-428434fd9896", json_encode($personalisation));
        if ($newTeamEmail != "daiu-anci@ssc-spc.gc.ca") {
            sendEmail($clientemail, "8bb9cc70-dd1a-46d6-9843-c73cbe4e70f0", json_encode($personalisation));
        }
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
        $newTeamEmail = $row['email'];
        
        sendEmail($newTeamEmail, "8270de12-b994-4d29-aa22-428434fd9896", json_encode($personalisation));
        if ($newTeamEmail != "daiu-anci@ssc-spc.gc.ca") {
            sendEmail($clientemail, "8bb9cc70-dd1a-46d6-9843-c73cbe4e70f0", json_encode($personalisation));
        }
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

// Update communication logs (admin only)
if (isAdmin() || $_SESSION['atype'] == 2) {
    if (!empty($commlogid1)) {
        $sql = "UPDATE `tblcommlog` SET `notes` = '$commlog1' WHERE id='$commlogid1'";
        mysqli_query($link, $sql);
    }
    if (!empty($commlogid2)) {
        $sql = "UPDATE `tblcommlog` SET `notes` = '$commlog2' WHERE id='$commlogid2'";
        mysqli_query($link, $sql);
    }
}

// Keep Department/agency synchronized in client communications notes.
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

// Add admin notes
if (!empty($adminnotes)) {
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

// Redirect on success.
// When a request is newly resolved, send staff directly to manual survey links.
if (!$isCurrentResolved && $isTargetResolved) {
    header("location:/client-survey-link.php?lang=$lang&erid=$redirectid");
    exit();
}

header("location:/viewrequest.php?lang=$lang&erid=$redirectid&reqid=" . urlencode("a11y-" . $requestid) . "&status=success");
exit();
?>
