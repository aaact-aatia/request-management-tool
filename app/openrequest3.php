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
    $catalogueid = (int)getPostValue('catalogueid', 0);
    $serviceid = (int)getPostValue('serviceid', 0);
    $subserviceid = (int)getPostValue('subserviceid', 0);
    $reauditFlag = (int)getPostValue('reauditFlag', 0);
    $statusid = 1; // Initial status
    
    // Grab all form fields using helper
    $requesttitle = getPostValue('requesttitle');
    $audienceid = getPostValue('audience', 0);
    $clientlname = getPostValue('clientlname');
    $clientfname = getPostValue('clientfname');
    $clientemail = getPostValue('clientemail');
    $departmentagency = getPostValue('departmentagency');
    $clientphone = getPostValue('clientphone');
    $requestlang = app_normalize_language($lang);
    $bdm = getPostValue('bdm', 0);
    $attach1 = getPostValue('attach1');
    $attach2 = getPostValue('attach2');
    $attach3 = getPostValue('attach3');
    $clientnotes = getPostValue('clientnotes');
    $additionalinfo = getPostValue('additionalinfo');
    $notification = getPostValue('notification', 1);
    $afterfact = getPostValue('afterfact');
    $sprintdefects = getPostValue('sprintdefects');
    $sprintschedule = getPostValue('sprintschedule');
    
    // Handle date fields
    $daterequired = getPostValue('daterequired');
    $daterequiredu = false;
    if (empty($daterequired)) {
        $daterequiredu = true;
        $daterequired = "1900-01-01";
    }
    
    $firstsprintstartdate = getPostValue('firstsprintstartdate');
    $firstsprintenddate = getPostValue('firstsprintenddate');
    
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
    $result = mysqli_query($link, "SELECT MAX(CAST(SUBSTRING(requestid, 8) AS UNSIGNED)) AS max_seq 
                                    FROM tbltriage 
                                    WHERE requestid LIKE 'REQ-$year-%'");
    $seqRow = mysqli_fetch_array($result);
    $sequence = ($seqRow['max_seq'] ?? 0) + 1;
    $nrequestid = sprintf('REQ-%s-%03d', $year, $sequence);
    $dateopened = date('Y-m-d');
    $slatimer = date('Y-m-d');
    $userid = isset($_SESSION['pid']) && !empty($_SESSION['pid']) ? $_SESSION['pid'] : 'NULL';
    
    // Handle file uploads to Azure Blob Storage
    if (isset($_FILES['fileToUpload']) && !empty($_FILES['fileToUpload']['tmp_name'][0])) {
        $azureBlobManager = new AzureBlobStorageManager();
        
        foreach ($_FILES['fileToUpload']['tmp_name'] as $key => $fileTmpPath) {
            $fileNameWithExtension = $_FILES['fileToUpload']['name'][$key];
            $fileType = pathinfo($fileNameWithExtension, PATHINFO_EXTENSION);
            $fileSize = $_FILES['fileToUpload']['size'][$key] / 1024; // KB
            $randomCode = $nrequestid . "-" . bin2hex(random_bytes(16)) . "." . $fileType;
            
            // Escape for SQL
            $fileName = mysqli_real_escape_string($link, $fileNameWithExtension);
            $fileType = mysqli_real_escape_string($link, $fileType);
            $randomCode = mysqli_real_escape_string($link, $randomCode);
            
            // Upload to Azure
            if ($azureBlobManager->uploadFile($fileTmpPath, $randomCode)) {
                $sql = "INSERT INTO tblfiles (`requestid`, `name`, `code`, `type`, `size`) 
                        VALUES ('$nrequestid', '$fileName', '$randomCode', '$fileType', '$fileSize')";
                mysqli_query($link, $sql);
            }
        }
    }
    
    // Insert the full triage record in one shot
    $daterequiredSql = $daterequiredu ? 'NULL' : "'$daterequired'";
    $columns = "requestid, creatorid, catalogueid, serviceid, subserviceid, statusid, datereceived, slatimer, isreaudit, title, clientlname, clientfname, clientemail, clientphone, daterequired, bdm, attach1, attach2, attach3, status";
    $values  = "'$nrequestid', $userid, $catalogueid, $serviceid, $subserviceid, $statusid, '$dateopened', '$slatimer', $reauditFlag, '$requesttitle', '$clientlname', '$clientfname', '$clientemail', '$clientphone', $daterequiredSql, '$bdm', '$attach1', '$attach2', '$attach3', '$status'";

    $hasRequestLangColumn = function_exists('rmt_db_column_exists')
        && rmt_db_column_exists($link, 'tbltriage', 'requestlang');
    if ($hasRequestLangColumn) {
        $columns .= ", requestlang";
        $values  .= ", '$requestlang'";
    }
    
    if ($firstsprintenddate) {
        $columns .= ", firstsprintenddate";
        $values  .= ", '$firstsprintenddate'";
    }
    if ($firstsprintstartdate) {
        $columns .= ", firstsprintstartdate";
        $values  .= ", '$firstsprintstartdate'";
    }
    
    $sql = "INSERT INTO tbltriage ($columns) VALUES ($values)";
    mysqli_query($link, $sql);
    $latestid = mysqli_insert_id($link);
    $nrequestemailid = base64_encode($latestid);

    // Preserve original request language even on older schemas that may not include tbltriage.requestlang.
    rmt_save_request_language_metadata($link, (int) $latestid, $requestlang, (int) $creatorid);
    
    // Add client notes to communication log if provided
    $datereceived = date("Y-m-d");
    $creatorid = $_SESSION['pid'] ?? 0;
    
    if (hasValue($clientnotes)) {
        $sql = "INSERT INTO tblcommlog(`triageid`, `dateadded`, `notes`, `creatorid`, `status`) 
                VALUES ('$latestid', '$datereceived', '$clientnotes', '$creatorid', '$status')";
        mysqli_query($link, $sql);
    }

    if (hasValue($departmentCommsNote)) {
        $sql = "INSERT INTO tblcommlog(`triageid`, `dateadded`, `notes`, `creatorid`, `status`) 
                VALUES ('$latestid', '$datereceived', '$departmentCommsNote', '$creatorid', '$status')";
        mysqli_query($link, $sql);
    }
    
    if (hasValue($additionalinfo)) {
        $sql = "INSERT INTO tblcommlog(`triageid`, `dateadded`, `notes`, `creatorid`, `status`) 
                VALUES ('$latestid', '$datereceived', '$additionalinfo', '$creatorid', '$status')";
        mysqli_query($link, $sql);
    }
    
    // Determine the team to notify based on service/subservice
    $contactid = -1;
    
    if ($subserviceid && $subserviceid != 0) {
        // Get serviceid from subservice
        $result = mysqli_query($link, "SELECT serviceid FROM tblsubservices WHERE id = '$subserviceid'");
        $row = mysqli_fetch_array($result);
        if (!empty($row)) {
            $serviceid = $row[0];
        }
        
        // Get contact from service
        $result = mysqli_query($link, "SELECT contactid FROM tblservices WHERE id = '$serviceid'");
        $row = mysqli_fetch_array($result);
        if (!empty($row)) {
            $contactid = $row[0];
        }
        
        // Get team details
        $result = mysqli_query($link, "SELECT * FROM tblteams WHERE id = '$contactid'");
        $row = mysqli_fetch_array($result);
        if (!empty($row)) {
            $teamname = $isFrench ? $row['namefr'] : $row['nameen'];
            $teamemail = $row['email'];
            $contactname = $row['contactname'];
            $contactemail = $row['contactemail'];
        }
    } elseif ($serviceid && $serviceid != 0) {
        // Get contact from service directly
        $result = mysqli_query($link, "SELECT contactid FROM tblservices WHERE id = '$serviceid'");
        $row = mysqli_fetch_array($result);
        if (!empty($row)) {
            $contactid = $row[0];
        }
        
        // Get team details
        $result = mysqli_query($link, "SELECT * FROM tblteams WHERE id = '$contactid'");
        $row = mysqli_fetch_array($result);
        if (!empty($row)) {
            $teamname = $isFrench ? $row['namefr'] : $row['nameen'];
            $teamemail = $row['email'];
            $contactname = $row['contactname'];
            $contactemail = $row['contactemail'];
        }
    } else {
        // Fallback to AAACT triage
        $teamname = "AAACT Triage";
        $teamemail = "daiu-anci@ssc-spc.gc.ca";
        $contactname = "Brad Souster";
        $contactemail = "Brad.Souster@ssc-spc.gc.ca";
    }
    
    // Prepare email data
    $attachments = array_filter([$attach1, $attach2, $attach3]);
    $attach = implode("\n", $attachments);
    
    // Get catalogue name
    $cataloguename = "";
    $nameField = $isFrench ? 'namefr' : 'nameen';
    $result2 = mysqli_query($link, "SELECT $nameField FROM tblcatalogue WHERE id='$catalogueid' AND status='1'");
    if ($result2 && mysqli_num_rows($result2) > 0) {
        $row = mysqli_fetch_assoc($result2);
        $cataloguename = $row[$nameField];
    }
    
    // Get service name
    $servicename = "";
    $result2 = mysqli_query($link, "SELECT $nameField FROM tblservices WHERE id='$serviceid' AND status='1'");
    if ($result2 && mysqli_num_rows($result2) > 0) {
        $row = mysqli_fetch_assoc($result2);
        $servicename = $row[$nameField];
    }
    
    $domain = app_base_url();
    
    // Email personalization data
    $personalisation = [
        "requestid" => $nrequestid,
        "nrequestid" => $nrequestid,
        "teamname" => $teamname,
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
        
        // Send to client (not for AAACT)
        if ($teamemail != "daiu-anci@ssc-spc.gc.ca") {
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
        
    } elseif ($notification != "N" || $notification == 1) {
        // Default notification behavior (not for AAACT)
        if ($teamemail != "daiu-anci@ssc-spc.gc.ca") {
            // Team notification
            $template_id = app_notify_template_id('notification_generic');
            
            if ($catalogueid == 9 || $catalogueid == 8) {
                $template_id = app_notify_template_id('notification_generic');
            }
            
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
            
            // Client notification
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
    }
    
    // Redirect to view request page
    header("location:/viewrequest.php?lang=" . $lang . "&erid=" . $nrequestemailid . "&reqid=" . urlencode("a11y-" . $nrequestid) . "&status=newrequestcomplete");
    exit();
}

// Close connection
mysqli_close($link);
?>
