<?php
ob_start();

// Start session
require_once __DIR__ . '/includes/session_start.php';

// Grab HTTPS check
require('includes/httpscheck.php');

// Grab MySQL connection and helpers
require('sql.php');
/** @var mysqli $link */
if (!isset($link) || !($link instanceof mysqli)) {
    throw new RuntimeException('Database connection was not initialized in sql.php');
}
require_once('includes/helpers.php');
require_once('BlobStorage.php');
require_once('emailController.php');

// Detect language
$lang = detectLanguage();
$isFrench = ($lang === 'fr');

// Process the request submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Get service/catalogue IDs passed as hidden fields from step 2
    $catalogueid = (int) ($_POST['catalogueid'] ?? 0);
    $serviceid = (int) ($_POST['serviceid'] ?? 0);
    $subserviceid = (int) ($_POST['subserviceid'] ?? 0);

    $selection = rmt_validate_intake_selection($link, $catalogueid, $serviceid, $subserviceid);
    if ($selection === null) {
        header("location:/openrequest.php?lang={$lang}&status=failed#intake-error");
        exit();
    }

    $catalogueid = $selection['catalogueid'];
    $serviceid = $selection['serviceid'];
    $subserviceid = $selection['subserviceid'];
    $statusid = 1; // Initial status
    
    // Grab all form fields using helper
    $requesttitle = trim((string) ($_POST['requesttitle'] ?? ''));
    $audienceid = (int) ($_POST['audience'] ?? 0);
    $clientlname = trim((string) ($_POST['clientlname'] ?? ''));
    $clientfname = trim((string) ($_POST['clientfname'] ?? ''));
    $clientemail = trim((string) ($_POST['clientemail'] ?? ''));
    $departmentagency = trim((string) ($_POST['departmentagency'] ?? ''));
    $clientphone = trim((string) ($_POST['clientphone'] ?? ''));
    $requestlang = app_normalize_language($lang);
    $bdm = trim((string) ($_POST['bdm'] ?? '0'));
    $attach1 = trim((string) ($_POST['attach1'] ?? ''));
    $attach2 = trim((string) ($_POST['attach2'] ?? ''));
    $attach3 = trim((string) ($_POST['attach3'] ?? ''));
    $clientnotes = trim((string) ($_POST['clientnotes'] ?? ''));
    $additionalinfo = trim((string) ($_POST['additionalinfo'] ?? ''));
    $notification = trim((string) ($_POST['notification'] ?? '1'));
    $afterfact = trim((string) ($_POST['afterfact'] ?? ''));
    $sprintdefects = trim((string) ($_POST['sprintdefects'] ?? ''));
    $sprintschedule = trim((string) ($_POST['sprintschedule'] ?? ''));
    
    // Handle date fields
    $daterequired = trim((string) ($_POST['daterequired'] ?? ''));
    $daterequiredu = false;
    if (empty($daterequired)) {
        $daterequiredu = true;
        $daterequired = "1900-01-01";
    }
    
    $firstsprintstartdate = trim((string) ($_POST['firstsprintstartdate'] ?? ''));
    $firstsprintenddate = trim((string) ($_POST['firstsprintenddate'] ?? ''));
    
    // Auto-generated values
    if ($afterfact == "Y") {
        $statusid = 2;
    }

    $departmentCommsNote = '';
    if (hasValue($departmentagency)) {
        $departmentPrefix = $isFrench ? "Ministère/organisme: " : "Department/agency: ";
        $departmentCommsNote = $departmentPrefix . $departmentagency;
    }
    $status = 1;
    
    // Initialize team variables
    $teamname = "";
    $teamemail = "";
    $contactname = "";
    $contactemail = "";
    
    // Validate required fields
    if (!hasValue($requesttitle) || !hasValue($clientlname) || !hasValue($clientfname) || !hasValue($clientemail)) {
        header("location:/openrequest.php?lang=" . $lang . "&status=failed");
        exit();
    }
    
    // Generate request ID now that validation has passed
    $year = date('y');
    $requestPattern = "REQ-{$year}-%";
    $seqRow = rmt_db_fetch_one(
        $link,
        'SELECT MAX(CAST(SUBSTRING(requestid, 8) AS UNSIGNED)) AS max_seq
         FROM tbltriage
         WHERE requestid LIKE ?',
        's',
        [$requestPattern]
    );
    $sequence = ($seqRow['max_seq'] ?? 0) + 1;
    $nrequestid = sprintf('REQ-%s-%03d', $year, $sequence);
    $dateopened = date('Y-m-d');
    $slatimer = date('Y-m-d');
    $userid = isset($_SESSION['pid']) && !empty($_SESSION['pid']) ? (int) $_SESSION['pid'] : null;

    // Validate uploads before writing request data.
    $validatedUploads = ['files' => [], 'errors' => []];
    if (isset($_FILES['fileToUpload'])) {
        $validatedUploads = rmt_validate_uploaded_files($_FILES['fileToUpload'], $lang);
        if (!empty($validatedUploads['errors'])) {
            $_SESSION['openrequest_draft'] = [
                'catalogueid' => $_POST['catalogueid'] ?? '',
                'serviceid' => $_POST['serviceid'] ?? '',
                'subserviceid' => $_POST['subserviceid'] ?? '',
                'subserviceid2' => $_POST['subserviceid2'] ?? '',
                'requesttitle' => $_POST['requesttitle'] ?? '',
                'audience' => $_POST['audience'] ?? '',
                'clientlname' => $_POST['clientlname'] ?? '',
                'clientfname' => $_POST['clientfname'] ?? '',
                'clientemail' => $_POST['clientemail'] ?? '',
                'departmentagency' => $_POST['departmentagency'] ?? '',
                'clientphone' => $_POST['clientphone'] ?? '',
                'daterequired' => $_POST['daterequired'] ?? '',
                'bdm' => $_POST['bdm'] ?? '',
                'attach1' => $_POST['attach1'] ?? '',
                'attach2' => $_POST['attach2'] ?? '',
                'attach3' => $_POST['attach3'] ?? '',
                'clientnotes' => $_POST['clientnotes'] ?? '',
                'additionalinfo' => $_POST['additionalinfo'] ?? '',
                'notification' => $_POST['notification'] ?? '',
                'afterfact' => $_POST['afterfact'] ?? '',
                'sprintdefects' => $_POST['sprintdefects'] ?? '',
                'sprintschedule' => $_POST['sprintschedule'] ?? '',
                'firstsprintstartdate' => $_POST['firstsprintstartdate'] ?? '',
                'firstsprintenddate' => $_POST['firstsprintenddate'] ?? '',
                'language' => $_POST['language'] ?? '',
            ];
            $_SESSION['openrequest_upload_error_message'] = implode(' ', $validatedUploads['errors']);
            header("location:/openrequest2.php?lang=" . $lang);
            exit();
        }
    }
    
    // Handle file uploads to configured storage backend.
    if (!empty($validatedUploads['files'])) {
        $azureBlobManager = new AzureBlobStorageManager();
        
        foreach ($validatedUploads['files'] as $uploadFile) {
            $fileNameWithExtension = $uploadFile['name'];
            $fileType = $uploadFile['extension'];
            $fileSize = $uploadFile['size_kb'];
            $fileTmpPath = $uploadFile['tmp_name'];
            $randomCode = $nrequestid . "-" . bin2hex(random_bytes(16)) . "." . $fileType;
            
            // Upload to the configured storage backend.
            if ($azureBlobManager->uploadFile($fileTmpPath, $randomCode)) {
                $fileStatement = rmt_db_execute(
                    $link,
                    'INSERT INTO tblfiles (`requestid`, `name`, `code`, `type`, `size`)
                     VALUES (?, ?, ?, ?, ?)',
                    'ssssd',
                    [$nrequestid, $fileNameWithExtension, $randomCode, $fileType, $fileSize]
                );
                mysqli_stmt_close($fileStatement);
            }
        }
    }
    
    // Insert the full triage record in one shot
    $triageColumns = [
        'requestid', 'creatorid', 'catalogueid', 'serviceid', 'subserviceid', 'statusid',
        'datereceived', 'slatimer', 'title', 'clientlname', 'clientfname',
        'clientemail', 'clientphone', 'daterequired', 'bdm', 'attach1', 'attach2', 'attach3', 'status'
    ];
    $triageTypes = 'siiiiissssssssssssi';
    $triageParams = [
        $nrequestid, $userid, $catalogueid, $serviceid, $subserviceid, $statusid,
        $dateopened, $slatimer, $requesttitle, $clientlname, $clientfname,
        $clientemail, $clientphone, $daterequiredu ? null : $daterequired,
        $bdm, $attach1, $attach2, $attach3, $status
    ];

    $hasRequestLangColumn = function_exists('rmt_db_column_exists')
        && rmt_db_column_exists($link, 'tbltriage', 'requestlang');
    if ($hasRequestLangColumn) {
        $triageColumns[] = 'requestlang';
        $triageTypes .= 's';
        $triageParams[] = $requestlang;
    }
    
    if ($firstsprintenddate) {
        $triageColumns[] = 'firstsprintenddate';
        $triageTypes .= 's';
        $triageParams[] = $firstsprintenddate;
    }
    if ($firstsprintstartdate) {
        $triageColumns[] = 'firstsprintstartdate';
        $triageTypes .= 's';
        $triageParams[] = $firstsprintstartdate;
    }
    
    $quotedColumns = array_map(static fn(string $column): string => "`{$column}`", $triageColumns);
    $placeholders = implode(', ', array_fill(0, count($triageColumns), '?'));
    $triageStatement = rmt_db_execute(
        $link,
        'INSERT INTO tbltriage (' . implode(', ', $quotedColumns) . ") VALUES ({$placeholders})",
        $triageTypes,
        $triageParams
    );
    $latestid = mysqli_insert_id($link);
    mysqli_stmt_close($triageStatement);
    $nrequestemailid = base64_encode($latestid);

    // Preserve original request language even on older schemas that may not include tbltriage.requestlang.
    rmt_save_request_language_metadata($link, (int) $latestid, $requestlang, (int) ($_SESSION['pid'] ?? 0));
    
    // Add client notes to communication log if provided
    $datereceived = date("Y-m-d");
    $creatorid = $_SESSION['pid'] ?? 0;
    
    if (hasValue($clientnotes)) {
        $statement = rmt_db_execute(
            $link,
            'INSERT INTO tblcommlog (`triageid`, `dateadded`, `notes`, `creatorid`, `status`)
             VALUES (?, ?, ?, ?, ?)',
            'issii',
            [$latestid, $datereceived, $clientnotes, $creatorid, $status]
        );
        mysqli_stmt_close($statement);
    }

    if (hasValue($departmentCommsNote)) {
        $statement = rmt_db_execute(
            $link,
            'INSERT INTO tblcommlog (`triageid`, `dateadded`, `notes`, `creatorid`, `status`)
             VALUES (?, ?, ?, ?, ?)',
            'issii',
            [$latestid, $datereceived, $departmentCommsNote, $creatorid, $status]
        );
        mysqli_stmt_close($statement);
    }
    
    if (hasValue($additionalinfo)) {
        $statement = rmt_db_execute(
            $link,
            'INSERT INTO tblcommlog (`triageid`, `dateadded`, `notes`, `creatorid`, `status`)
             VALUES (?, ?, ?, ?, ?)',
            'issii',
            [$latestid, $datereceived, $additionalinfo, $creatorid, $status]
        );
        mysqli_stmt_close($statement);
    }
    
    // Determine the team to notify based on first-tier catalogue ownership.
    // Fall back to legacy service/subservice contact ownership for older data.
    $contactid = -1;
    $hasCatalogueContact = function_exists('rmt_db_column_exists')
        && rmt_db_column_exists($link, 'tblcatalogue', 'contactid');

    if ($hasCatalogueContact && $catalogueid && $catalogueid != 0) {
        $row = rmt_db_fetch_one($link, 'SELECT contactid FROM tblcatalogue WHERE id = ?', 'i', [$catalogueid]);
        if (!empty($row['contactid'])) {
            $contactid = (int) $row['contactid'];
        }
    }
    
    if (($contactid <= 0) && $subserviceid && $subserviceid != 0) {
        // Get serviceid from subservice
        $row = rmt_db_fetch_one($link, 'SELECT serviceid FROM tblsubservices WHERE id = ?', 'i', [$subserviceid]);
        if (!empty($row['serviceid'])) {
            $serviceid = (int) $row['serviceid'];
        }
        
        // Get contact from service
        $row = rmt_db_fetch_one($link, 'SELECT contactid FROM tblservices WHERE id = ?', 'i', [$serviceid]);
        if (!empty($row['contactid'])) {
            $contactid = (int) $row['contactid'];
        }
    }

    if (($contactid <= 0) && $serviceid && $serviceid != 0) {
        // Get contact from service directly
        $row = rmt_db_fetch_one($link, 'SELECT contactid FROM tblservices WHERE id = ?', 'i', [$serviceid]);
        if (!empty($row['contactid'])) {
            $contactid = (int) $row['contactid'];
        }
    }

    if ($contactid > 0) {
        // Get team details
        $row = rmt_db_fetch_one($link, 'SELECT * FROM tblteams WHERE id = ?', 'i', [$contactid]);
        if (!empty($row)) {
            $teamname = $isFrench ? $row['namefr'] : $row['nameen'];
            $teamemail = $row['email'];
            $contactname = $row['contactname'];
            $contactemail = $row['contactemail'];
        }
    }

    if (empty($teamemail)) {
        // Fallback to AAACT triage when team ownership is not configured.
        $teamname = "AAACT Triage";
        $teamemail = "daiu-anci@ssc-spc.gc.ca";
        $contactname = "Brad Souster";
        $contactemail = "Brad.Souster@ssc-spc.gc.ca";
    } else {
        $teamname = $teamname ?? "";
        $teamemail = $teamemail ?? "";
        $contactname = $contactname ?? "";
        $contactemail = $contactemail ?? "";
    }
    
    // Prepare email data
    $attachments = array_filter([$attach1, $attach2, $attach3]);
    $attach = implode("\n", $attachments);
    
    // Get catalogue name
    $cataloguename = "";
    $nameField = $isFrench ? 'namefr' : 'nameen';
    $row = rmt_db_fetch_one(
        $link,
        "SELECT {$nameField} FROM tblcatalogue WHERE id = ? AND status = 1",
        'i',
        [$catalogueid]
    );
    if ($row !== null) {
        $cataloguename = $row[$nameField];
    }
    
    // Get service name
    $servicename = "";
    $row = rmt_db_fetch_one(
        $link,
        "SELECT {$nameField} FROM tblservices WHERE id = ? AND status = 1",
        'i',
        [$serviceid]
    );
    if ($row !== null) {
        $servicename = $row[$nameField];
    } elseif ($serviceid === 0) {
        $servicename = $cataloguename;
    }
    
    $domain = app_base_url();
    
    // Email personalization data
    $personalisation = [
        "requestid" => $nrequestid,
        "nrequestid" => $nrequestid,
        "teamname" => $teamname,
        "team_email" => $teamemail,
        "requesttitle" => $requesttitle,
        "nrequestemailid" => $nrequestemailid,
        "nrequestemail" => $clientemail,
        "client_fname" => $clientfname,
        "client_lname" => $clientlname,
        "client_email" => $clientemail,
        "attach" => $attach,
        "client_communications" => $clientnotes,
        "catalogue_name" => $cataloguename,
        "service_name" => $servicename,
        "url" => app_url("viewrequest.php?lang=" . $lang . "&erid=" . $nrequestemailid . "&reqid=" . urlencode("a11y-" . $nrequestid))
    ];
    
    $encoded_personalisation = json_encode($personalisation);
    
    // Send email notifications based on settings
    if ($notification == "Y") {
        // Request with notification enabled
        $template_id = app_notify_template_id('notification_generic');
        
        if ($afterfact == "Y") {
            $template_id = app_notify_template_id('notification_generic');
        }
        
        if (empty($contactemail)) {
            $contactemail = $clientemail;
            $contactname = $clientfname . " " . $clientlname;
        }
        
        // Send to team
        if (!empty($teamemail)) {
            $teamMessageEvent = ($afterfact == "Y") ? 'request_afterfact' : 'request_created';
            $teamCategory = rmt_notification_template_category($teamMessageEvent);
            $teamPersonalisation = $personalisation + [
                'notification_event' => $teamMessageEvent,
                'template_category_id' => $teamCategory['id'],
                'template_category_name_en' => $teamCategory['name_en'],
                'template_category_name_fr' => $teamCategory['name_fr'],
                'subject' => rmt_notification_subject($teamMessageEvent, 'internal', 'en', $personalisation),
                'message' => rmt_notification_message($teamMessageEvent, 'internal', 'en', $personalisation),
            ];
            if ($teamemail == "daiu-anci@ssc-spc.gc.ca") {
                $aaactCategory = rmt_notification_template_category('request_aaact');
                $teamPersonalisation['message'] = rmt_notification_message('request_aaact', 'internal', 'en', $personalisation);
                $teamPersonalisation['subject'] = rmt_notification_subject('request_aaact', 'internal', 'en', $personalisation);
                $teamPersonalisation['notification_event'] = 'request_aaact';
                $teamPersonalisation['template_category_id'] = $aaactCategory['id'];
                $teamPersonalisation['template_category_name_en'] = $aaactCategory['name_en'];
                $teamPersonalisation['template_category_name_fr'] = $aaactCategory['name_fr'];
                sendEmail($teamemail, $template_id, json_encode($teamPersonalisation), ['recipientType' => 'internal']);
            } else {
                sendEmail($teamemail, $template_id, json_encode($teamPersonalisation), ['recipientType' => 'internal']);
            }
        }
        
        // Always send to client for new submissions.
        $clientCategory = rmt_notification_template_category('request_created');
        $clientPersonalisation = $personalisation + [
            'notification_event' => 'request_created',
            'template_category_id' => $clientCategory['id'],
            'template_category_name_en' => $clientCategory['name_en'],
            'template_category_name_fr' => $clientCategory['name_fr'],
            'subject' => rmt_notification_subject('request_created', 'client', $requestlang, $personalisation),
            'message' => rmt_notification_message('request_created', 'client', $requestlang, $personalisation),
        ];
        sendEmail($clientemail, $template_id, json_encode($clientPersonalisation), ['recipientType' => 'client']);
        
    } elseif ($notification != "N" || $notification == 1) {
        // Default notification behavior.
        $template_id = app_notify_template_id('notification_generic');
		
        if ($catalogueid == 9 || $catalogueid == 8) {
            $template_id = app_notify_template_id('notification_generic');
        }
		
        // Team notification
        if (!empty($teamemail)) {
            $teamMessageEvent = ($catalogueid == 9 || $catalogueid == 8) ? 'request_aaact' : 'request_created';
            $teamCategory = rmt_notification_template_category($teamMessageEvent);
            $teamPersonalisation = $personalisation + [
                'notification_event' => $teamMessageEvent,
                'template_category_id' => $teamCategory['id'],
                'template_category_name_en' => $teamCategory['name_en'],
                'template_category_name_fr' => $teamCategory['name_fr'],
                'subject' => rmt_notification_subject($teamMessageEvent, 'internal', 'en', $personalisation),
                'message' => rmt_notification_message($teamMessageEvent, 'internal', 'en', $personalisation),
            ];
            sendEmail($teamemail, $template_id, json_encode($teamPersonalisation), ['recipientType' => 'internal']);
        }
		
        // Always send to client for new submissions.
        $clientCategory = rmt_notification_template_category('request_created');
        $clientPersonalisation = $personalisation + [
            'notification_event' => 'request_created',
            'template_category_id' => $clientCategory['id'],
            'template_category_name_en' => $clientCategory['name_en'],
            'template_category_name_fr' => $clientCategory['name_fr'],
            'subject' => rmt_notification_subject('request_created', 'client', $requestlang, $personalisation),
            'message' => rmt_notification_message('request_created', 'client', $requestlang, $personalisation),
        ];
        sendEmail($clientemail, $template_id, json_encode($clientPersonalisation), ['recipientType' => 'client']);
    }
    

    unset($_SESSION['openrequest_draft'], $_SESSION['openrequest_upload_error_message']);
    // Redirect to view request page
    header("location:/viewrequest.php?lang=" . $lang . "&erid=" . $nrequestemailid . "&reqid=" . urlencode("a11y-" . $nrequestid) . "&status=newrequestcomplete");
    exit();
}

// Close connection
mysqli_close($link);
?>
