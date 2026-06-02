<?php
ob_start();

// Start session
if (session_status() != PHP_SESSION_ACTIVE) {
    session_start();
}

// Grab HTTPS check
require('includes/httpscheck.php');

// Grab MySQL connection and helpers
require('sql.php');
/** @var mysqli $link */
if (!isset($link) || !($link instanceof mysqli)) {
    throw new RuntimeException('Database connection was not initialized in sql.php');
}
require('includes/helpers.php');
require('BlobStorage.php');
require('emailController.php');

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
    $nsd = getPostValue('nsd', 0);
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
    $columns = "requestid, creatorid, catalogueid, serviceid, subserviceid, statusid, datereceived, slatimer, isreaudit, title, clientlname, clientfname, clientemail, clientphone, daterequired, nsd, bdm, attach1, attach2, attach3, status";
    $values  = "'$nrequestid', $userid, $catalogueid, $serviceid, $subserviceid, $statusid, '$dateopened', '$slatimer', $reauditFlag, '$requesttitle', '$clientlname', '$clientfname', '$clientemail', '$clientphone', $daterequiredSql, '$nsd', '$bdm', '$attach1', '$attach2', '$attach3', '$status'";
    
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
        $result = mysqli_query($link, "SELECT * FROM tblcontacts WHERE id = '$contactid'");
        $row = mysqli_fetch_array($result);
        if (!empty($row)) {
            $teamname = $isFrench ? $row['teamnamefr'] : $row['teamnameen'];
            $teamemail = $row['teamemail'];
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
        $result = mysqli_query($link, "SELECT * FROM tblcontacts WHERE id = '$contactid'");
        $row = mysqli_fetch_array($result);
        if (!empty($row)) {
            $teamname = $isFrench ? $row['teamnamefr'] : $row['teamnameen'];
            $teamemail = $row['teamemail'];
            $contactname = $row['contactname'];
            $contactemail = $row['contactemail'];
        }
    } else {
        // Fallback to ITAO triage
        $teamname = "ITAO Triage";
        $teamemail = "edsc.ti-it.a11y.esdc@hrsdc-rhdcc.gc.ca";
        $contactname = "Valerie Auger";
        $contactemail = "valerie.auger@hrsdc-rhdcc.gc.ca";
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
    
    $domain = "https://a11y.itaormt-batiogd-int.service.cloud-nuage.canada.ca";
    
    // Email personalization data
    $personalisation = [
        "requestid" => $nrequestid,
        "nrequestid" => $nrequestid,
        "teamname" => $teamname,
        "requesttitle" => $requesttitle,
        "nrequestemailid" => $nrequestemailid,
        "client_fname" => $clientfname,
        "client_lname" => $clientlname,
        "client_email" => $clientemail,
        "attach" => $attach,
        "client_communications" => $clientnotes,
        "catalogue_name" => $cataloguename,
        "service_name" => $servicename,
        "url" => $domain . "/viewrequest.php?lang=" . $lang . "&erid=" . $nrequestemailid
    ];
    
    $encoded_personalisation = json_encode($personalisation);
    
    // Send email notifications based on settings
    if ($notification == "Y") {
        // Request with notification enabled
        $template_id = $isFrench ? "86fb7784-b1cc-40b5-88e5-f7ea43ee75c0" : "d9c219be-799f-4713-950f-21884d5d3c3c";
        
        if ($afterfact == "Y") {
            $template_id = $isFrench ? "c5ea62d8-9e11-482a-acfc-ae8a450de06c" : "949c6248-ef73-4cf2-b1ea-5136c8c856c2";
        }
        
        if (empty($contactemail)) {
            $contactemail = $clientemail;
            $contactname = $clientfname . " " . $clientlname;
        }
        
        // Send to team
        if (!empty($teamemail)) {
            if ($teamemail == "EDSC.SVTIC-ICTAS.A11Y.ESDC@hrsdc-rhdcc.gc.ca") {
                sendEmail($teamemail, "35388592-27f3-47f5-ae09-ac3f9ddf7904", $encoded_personalisation);
            } else {
                sendEmail($teamemail, $template_id, $encoded_personalisation);
            }
        }
        
        // Send to client (not for ACE)
        if ($teamemail != "ACE-CEA@hrsdc-rhdcc.gc.ca") {
            $clientTemplate = $isFrench ? "d4fb66f3-e9f3-442f-9b7b-8b8e24f8799d" : "9e4e2ca4-ad1a-4204-ba1e-4be61a12f51c";
            sendEmail($clientemail, $clientTemplate, $encoded_personalisation);
        }
        
    } elseif ($notification != "N" || $notification == 1) {
        // Default notification behavior (not for ACE)
        if ($teamemail != "ACE-CEA@hrsdc-rhdcc.gc.ca") {
            // Team notification
            $template_id = $isFrench ? "c72c5e69-8a8c-42a2-9bb9-dfcf2c5f7d84" : "265e8009-741e-4a79-8e89-bfedaf071494";
            
            if ($catalogueid == 9 || $catalogueid == 8) {
                $template_id = "35388592-27f3-47f5-ae09-ac3f9ddf7904";
            }
            
            if (!empty($teamemail)) {
                sendEmail($teamemail, $template_id, $encoded_personalisation);
            }
            
            // Client notification
            $clientTemplate = $isFrench ? "36125c35-b1af-4989-9a94-f65b8e5cf49f" : "dcc97e6e-1fdf-4309-9351-a957ff5f6dcb";
            sendEmail($clientemail, $clientTemplate, $encoded_personalisation);
        }
    }
    
    // Redirect to view request page
    header("location:/viewrequest.php?lang=" . $lang . "&erid=" . $nrequestemailid . "&status=newrequestcomplete");
    exit();
}

// Close connection
mysqli_close($link);
?>
