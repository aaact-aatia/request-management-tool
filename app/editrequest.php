<?php
/**
 * Edit Request - Bilingual
 * Rebuilt from scratch with cleaner logic and better structure
 */

ob_start();

// Initialize
require('BlobStorage.php');
require('sql.php');
/** @var mysqli $link */
require('includes/httpscheck.php');
require('includes/calculate-bdays.php');
require('emailController.php');
require('includes/helpers.php');

// Language detection
$lang = detectLanguage();
$langSuffix = $lang === 'fr' ? '-fr' : '';

// Security checks
if ($lang === 'fr') {
    require('includes/loggedincheck.php');
} else {
    require('includes/loggedincheck.php');
}

if (!canEditRequests()) {
    header("location:/newrequest.php?lang=$lang&status=accessdenied");
    exit();
}

// Get request ID
$requestuid = !empty($_GET['erid']) ? base64_decode($_GET['erid']) : getGetValue('id');

if (empty($requestuid)) {
    header("location:/openrequest.php?lang=$lang&status=wrongid");
    exit();
}

// Translations
$translations = [
    'en' => [
        'page_title' => 'Edit request',
        'tool_name' => 'Request Management Tool - IT Accessibility Office',
        'success_heading' => 'Success',
        'success_message' => 'You have successfully updated the database, thank you!',
        'failed_heading' => 'Failed',
        'failed_message' => 'The database update you requested did not work, please try again, thank you!',
        'upload_failed_heading' => 'File upload failed',
        'upload_failed_message' => 'One or more files could not be uploaded. Please review file type and size requirements, then try again.',
        'upload_success_heading' => 'Upload complete',
        'upload_success_message' => 'Your file upload was successful.',
        'upload_button' => 'Upload',
        'request_id' => 'Request ID #',
        'request_title' => 'Request title',
        'first_sprint_start' => 'First Sprint Start Date',
        'first_sprint_end' => 'First Sprint End Date',
        'sprint_schedule' => 'Sprint Schedule',
        'sprint_defects' => 'Sprint Defect',
        'client_lastname' => 'Client last name',
        'client_firstname' => 'Client first name',
        'client_email' => 'Client email',
        'request_language' => 'Original requested language',
        'language_english' => 'English',
        'language_french' => 'French',
        'department_agency' => 'Department/agency',
        'client_phone' => 'Client phone #',
        'request_source' => 'Request source',
        'select_source' => 'Select a request source',
        'date_received' => 'Date received',
        'date_updated' => 'Date updated',
        'date_required' => 'Date required',
        'coaching_session_date' => 'Requested coaching session date',
        'date_resolved' => 'Date resolved',
        'status' => 'Status',
        'select_status' => 'Select a status',
        'intended_audience' => 'What is the intended audience',
        'select_audience' => 'Select an audience type',
        'no' => 'No',
        'yes' => 'Yes',
        'catalogue_name' => 'Catalogue name',
        'select_catalogue' => 'Select a catalogue name',
        'service_name' => 'Service name',
        'select_service' => 'Select a service name',
        'subservice_name' => 'Sub-service name',
        'select_subservice' => 'Select a sub-service name',
        'attachments_heading' => 'Attachments',
        'attachment' => 'Attachment',
        'url_only' => 'URL only',
        'view_attachment' => 'View attachment',
        'files_heading' => 'Files',
        'upload_file' => 'Upload File',
        'checkbox' => 'CheckBox',
        'file_name' => 'File Name',
        'file_type' => 'File Type',
        'file_size' => 'File Size',
        'date_submitted' => 'Date Submitted',
        'action' => 'Action',
        'download' => 'Download',
        'delete' => 'Delete',
        'na' => 'N/A',
        'no_files_found' => 'No files found.',
        'select_all' => 'Select All',
        'download_all' => 'Download All',
        'communications_heading' => 'Communications',
        'edit_original_commlog' => 'Edit original communications log entry',
        'view_existing_comms' => 'View existing communications',
        'add_new_commlog' => 'Add new AAACT communications log entry',
        'staff_use_only' => 'AAACT use only',
        'assigned_team_member' => 'Assigned AAACT team member',
        'select_team_member' => 'Select a team member',
        'reset_sla_timer' => 'Need to reset the SLA timer? Choose the new start date',
        'update_request' => 'Update request',
        'resolved_email_status' => 'Resolved email to client',
        'resolved_email_sent' => 'Sent',
        'resolved_email_not_sent' => 'Not sent',
        'resolved_email_sent_on' => 'Sent on',
        'resolved_email_send_button' => 'Send resolved + survey email now',
        'resolved_email_missing_client' => 'Client email is required before sending this message.',
        'resolved_email_send_success' => 'Resolved + survey email was sent successfully.',
        'resolved_email_send_failed' => 'Failed to send resolved + survey email. Please try again.',
        'required' => 'required',
        'fieldset_request_details' => 'Request details',
        'fieldset_client_info' => 'Client information',
        'fieldset_dates' => 'Dates'
    ],
    'fr' => [
        'page_title' => 'Modifier la demande',
        'tool_name' => 'Outil de gestion des demandes - Bureau de l\'accessibilité de la TI',
        'success_heading' => 'Succès',
        'success_message' => 'Vous avez mis à jour la base de données, merci!',
        'failed_heading' => 'Échec',
        'failed_message' => 'La mise à jour de la base de données que vous avez demandée n\'a pas fonctionné, veuillez réessayer, merci!',
        'upload_failed_heading' => 'Échec du téléversement',
        'upload_failed_message' => 'Un ou plusieurs fichiers n\'ont pas pu être téléversés. Veuillez vérifier les types et tailles permis, puis réessayer.',
        'upload_success_heading' => 'Televersement termine',
        'upload_success_message' => 'Le televersement du fichier a reussi.',
        'upload_button' => 'Televerser',
        'request_id' => '# de la demande',
        'request_title' => 'Titre de la demande',
        'first_sprint_start' => 'Date de début du premier sprint',
        'first_sprint_end' => 'Date de fin du premier sprint',
        'sprint_schedule' => 'Calendrier du sprint',
        'sprint_defects' => 'Défauts du sprint',
        'client_lastname' => 'Nom du client',
        'client_firstname' => 'Prénom du client',
        'client_email' => 'Courriel du client',
        'request_language' => 'Langue demandee initialement',
        'language_english' => 'Anglais',
        'language_french' => 'Francais',
        'department_agency' => 'Ministère/organisme',
        'client_phone' => 'Numéro de téléphone client',
        'request_source' => 'Source de la demande',
        'select_source' => 'Sélectionnez une source pour la demande',
        'date_received' => 'Date de réception',
        'date_updated' => 'Date de mise à jour',
        'date_required' => 'Date requise',
        'coaching_session_date' => 'Date de la séance de coaching demandée',
        'date_resolved' => 'Date de résolution',
        'status' => 'Statut',
        'select_status' => 'Sélectionnez un statut',
        'intended_audience' => 'Public cible',
        'select_audience' => 'Sélectionnez un type de public',
        'no' => 'Non',
        'yes' => 'Oui',
        'catalogue_name' => 'Nom du catalogue',
        'select_catalogue' => 'Sélectionnez un nom de catalogue',
        'service_name' => 'Nom du service',
        'select_service' => 'Sélectionnez un nom de service',
        'subservice_name' => 'Nom du sous-service',
        'select_subservice' => 'Sélectionnez un nom de sous-service',
        'attachments_heading' => 'Pièces jointes',
        'attachment' => 'Pièce jointe',
        'url_only' => 'URL uniquement',
        'view_attachment' => 'Consulter la pièce jointe',
        'files_heading' => 'Fichiers',
        'upload_file' => 'Télécharger un fichier',
        'checkbox' => 'Case à cocher',
        'file_name' => 'Nom du fichier',
        'file_type' => 'Type de fichier',
        'file_size' => 'Taille du fichier',
        'date_submitted' => 'Date de soumission',
        'action' => 'Action',
        'download' => 'Télécharger',
        'delete' => 'Supprimer',
        'na' => 'N/D',
        'no_files_found' => 'Aucun fichier trouvé.',
        'select_all' => 'Tout sélectionner',
        'download_all' => 'Tout télécharger',
        'communications_heading' => 'Communications',
        'edit_original_commlog' => 'Modifier le journal des communications client',
        'view_existing_comms' => 'Afficher les communications existantes',
        'add_new_commlog' => 'Ajouter une nouvelle entrée au journal des communications du AATIA',
        'staff_use_only' => 'À l\'usage du AATIA uniquement',
        'assigned_team_member' => 'Membre assigné de l\'équipe AATIA',
        'select_team_member' => 'Sélectionnez un membre de l\'équipe',
        'reset_sla_timer' => 'Besoin de réinitialiser la minuterie SLA? Choisissez la nouvelle date de début',
        'update_request' => 'Mettre à jour',
        'resolved_email_status' => 'Courriel de resolution au client',
        'resolved_email_sent' => 'Envoye',
        'resolved_email_not_sent' => 'Non envoye',
        'resolved_email_sent_on' => 'Envoye le',
        'resolved_email_send_button' => 'Envoyer le courriel de resolution + sondage',
        'resolved_email_missing_client' => 'Une adresse courriel client est requise avant l\'envoi.',
        'resolved_email_send_success' => 'Le courriel de resolution + sondage a ete envoye avec succes.',
        'resolved_email_send_failed' => 'Echec de l\'envoi du courriel de resolution + sondage. Veuillez reessayer.',
        'required' => 'requis',
        'fieldset_request_details' => 'Détails de la demande',
        'fieldset_client_info' => 'Renseignements sur le client',
        'fieldset_dates' => 'Dates'
    ]
];

$t = $translations[$lang];

// ============================================================================
// FORM PROCESSING
// ============================================================================

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require('includes/editrequest-processing.php');
}

// ============================================================================
// FETCH REQUEST DATA
// ============================================================================

$status = getGetValue('status');
$result = mysqli_query($link, "SELECT requestid FROM tbltriage WHERE id = '$requestuid'");
$row = mysqli_fetch_assoc($result);
$requestid = $row['requestid'];

$pageTitle = $t['page_title'] . ' - a11y-' . $requestid;
$pageDescription = '';

include 'includes/template/head.php';

?>
<?php include 'includes/template/header.php'; ?>
    
    <main role="main" property="mainContentOfPage" class="container">
        <h1 property="name" id="wb-cont"><?php echo $t['page_title']; ?> - a11y-<?php echo $requestid; ?></h1>
        <?php
        $uploadErrorMessage = isset($_SESSION['upload_error_message']) ? (string) $_SESSION['upload_error_message'] : '';
        unset($_SESSION['upload_error_message']);
        ?>
        
        <?php if ($status == 'success'): ?>
        <section class="alert alert-success">
            <h2><?php echo $t['success_heading']; ?></h2>
            <ul><li><?php echo $t['success_message']; ?></li></ul>
        </section>
        <?php elseif ($status == 'resolvedemailsent'): ?>
        <section class="alert alert-success">
            <h2><?php echo $t['success_heading']; ?></h2>
            <ul><li><?php echo $t['resolved_email_send_success']; ?></li></ul>
        </section>
        <?php elseif ($status == 'failed'): ?>
        <section class="alert alert-danger">
            <h2><?php echo $t['failed_heading']; ?></h2>
            <ul><li><?php echo $t['failed_message']; ?></li></ul>
        </section>
        <?php elseif ($status == 'uploadfailed'): ?>
        <section class="alert alert-danger">
            <h2><?php echo $t['upload_failed_heading']; ?></h2>
            <ul>
                <li><?php echo $t['upload_failed_message']; ?></li>
            </ul>
        </section>
        <?php elseif ($status == 'uploadsuccess'): ?>
        <section class="alert alert-success">
            <h2><?php echo $t['upload_success_heading']; ?></h2>
            <ul>
                <li><?php echo $t['upload_success_message']; ?></li>
            </ul>
        </section>
        <?php elseif ($status == 'resolvedemailmissing'): ?>
        <section class="alert alert-danger">
            <h2><?php echo $t['failed_heading']; ?></h2>
            <ul><li><?php echo $t['resolved_email_missing_client']; ?></li></ul>
        </section>
        <?php elseif ($status == 'resolvedemailfailed'): ?>
        <section class="alert alert-danger">
            <h2><?php echo $t['failed_heading']; ?></h2>
            <ul><li><?php echo $t['resolved_email_send_failed']; ?></li></ul>
        </section>
        <?php endif; ?>
        
        <?php
        // Fetch request data
        $sql = "SELECT * FROM tbltriage WHERE id='$requestuid'";
        $result = mysqli_query($link, $sql);
        
        if (mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);

            $effectiveAtype = (int)($_SESSION['atype'] ?? 0);
            if ($effectiveAtype === 4) {
                $teamIds = getEffectiveTeamIds($link);

                $requestContactId = 0;
                $subserviceIdInt = (int)($row['subserviceid'] ?? 0);
                $serviceIdInt = (int)($row['serviceid'] ?? 0);
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
            } elseif ($effectiveAtype === 5) {
                $effectiveEmployeeId = getEffectiveEmployeeUserId($link);
                if ((int)($row['workerid'] ?? 0) !== $effectiveEmployeeId) {
                    header("location:/indexonly.php?lang=$lang&status=accessdenied");
                    exit();
                }
            }

            $departmentAgency = '';
            $departmentAgencyCommlogId = 0;
            $originalRequestLang = rmt_get_request_language($link, (int) $requestuid, $_SESSION['lang'] ?? 'en');
            $originalRequestLangLabel = ($originalRequestLang === 'fr') ? $t['language_french'] : $t['language_english'];
            $deptPrefixRegex = '/^(Department\/agency|Ministère\/organisme):\s*(.+)$/miu';

            $deptResult = mysqli_query($link, "SELECT id, notes FROM tblcommlog WHERE triageid = '$requestuid' AND status = '1' ORDER BY id ASC");
            while ($deptRow = mysqli_fetch_assoc($deptResult)) {
                if (preg_match($deptPrefixRegex, (string)$deptRow['notes'], $matches)) {
                    $departmentAgency = trim($matches[2]);
                    $departmentAgencyCommlogId = (int)$deptRow['id'];
                    break;
                }
            }
        ?>
        
        <form method="POST" enctype="multipart/form-data" action="editrequest.php?lang=<?php echo $lang; ?>&id=<?php echo $row['id']; ?>">

            <input type="hidden" name="requestlang" value="<?php echo htmlspecialchars($originalRequestLang, ENT_QUOTES, 'UTF-8'); ?>">

            <?php
            $inTestMode = isRoleTestMode();
            $canFullFieldEdit = !$inTestMode && (!empty($_SESSION['is_superuser']) || !empty($_SESSION['is_admin']));
            $isManagerAccount = ((int)($_SESSION['atype'] ?? 0) === 3);
            $isTeamLeadAccount = ((int)($_SESSION['atype'] ?? 0) === 4);
            $canEditStatusAndWorker = canEditRequests();
            $canEditTitle = $canFullFieldEdit || $isManagerAccount || $isTeamLeadAccount;
            $readonly = !$canFullFieldEdit;
            $serviceid = $row['serviceid'];
            $catalogueid = $row['catalogueid'];
            $dateRange = getDateRange(1);
            $dateRequiredLabel = ($catalogueid == 5 && $serviceid != 47) ? $t['coaching_session_date'] : $t['date_required'];
            ?>

            <!-- Status (standalone row at top) -->
            <div class="row">
                <div class="col-md-6">
                    <?php
                    $statuses = getDropdownOptions($link, 'tblstatus', $lang);
                    $statusOptions = [];
                    while ($status = mysqli_fetch_assoc($statuses)) {
                        $statusOptions[] = $status;
                    }
                    echo renderSelect('statusid', $t['status'], $statusOptions, $row['statusid'], true, $t['select_status'], !$canEditStatusAndWorker);
                    ?>
                </div>
            </div>

            <!-- Fieldset: Request Details -->
            <fieldset>
                <legend><?php echo $t['fieldset_request_details']; ?></legend>

                <!-- Row: Request ID | Request Title -->
                <div class="row">
                    <div class="col-md-6">
                        <?php echo renderTextInput('requestid', $t['request_id'], $row['requestid'], true, $readonly); ?>
                    </div>
                    <div class="col-md-6">
                        <?php echo renderTextInput('requesttitle', $t['request_title'], $row['title'], true, !$canEditTitle); ?>
                    </div>
                </div>

                <!-- Row: Catalogue | Service -->
                <?php
                $catalogues = getDropdownOptions($link, 'tblcatalogue', $lang);
                $catalogueOptions = [];
                while ($catalogue = mysqli_fetch_assoc($catalogues)) {
                    $catalogueOptions[] = $catalogue;
                }
                ?>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="catalogueid"><span class="field-name"><?php echo $t['catalogue_name']; ?>: <strong>(<?php echo $t['required']; ?>)</strong></span></label>
                            <select class="form-control" id="catalogueid" name="catalogueid" onchange="ajax1(this.value)" required <?php echo $readonly ? 'disabled="disabled"' : ''; ?>>
                                <option value=""><?php echo $t['select_catalogue']; ?></option>
                                <?php foreach ($catalogueOptions as $option): ?>
                                <option value="<?php echo $option['id']; ?>" <?php echo ($row['catalogueid'] == $option['id']) ? 'selected' : ''; ?>>
                                    <?php echo $option['name']; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <?php if (hasValue($row['catalogueid'])): ?>
                        <?php $services = getServicesByCategory($link, $row['catalogueid'], $lang); ?>
                        <div class="form-group divservice">
                            <label for="serviceid"><span class="field-name"><?php echo $t['service_name']; ?>:</span></label>
                            <select class="form-control" id="serviceid" name="serviceid" onchange="ajax2(this.value)" <?php echo $readonly ? 'disabled="disabled"' : ''; ?>>
                                <option value=""><?php echo $t['select_service']; ?></option>
                                <?php while ($service = mysqli_fetch_assoc($services)): ?>
                                <option value="<?php echo $service['id']; ?>" <?php echo ($row['serviceid'] == $service['id']) ? 'selected' : ''; ?>>
                                    <?php echo $service['name']; ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <?php else: ?>
                        <div class="form-group divservice"></div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Row: Subservice -->
                <?php
                if (hasValue($row['serviceid'])) {
                    $subservices = getSubservicesByService($link, $row['serviceid'], $lang);
                    if (mysqli_num_rows($subservices) > 0 && $row['subserviceid'] != 0) {
                ?>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group divsubservice">
                            <label for="subserviceid"><span class="field-name"><?php echo $t['subservice_name']; ?>: <strong>(<?php echo $t['required']; ?>)</strong></span></label>
                            <select class="form-control" id="subserviceid" name="subserviceid" required <?php echo $readonly ? 'disabled="disabled"' : ''; ?>>
                                <option value=""><?php echo $t['select_subservice']; ?></option>
                                <?php while ($subservice = mysqli_fetch_assoc($subservices)): ?>
                                <option value="<?php echo $subservice['id']; ?>" <?php echo ($row['subserviceid'] == $subservice['id']) ? 'selected' : ''; ?>>
                                    <?php echo $subservice['name']; ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <?php
                    } else {
                        echo '<div class="form-group divsubservice"></div>';
                    }
                }
                ?>

                <?php if ($row['subserviceid'] == 95): ?>
                <!-- Sprint fields (subserviceid 95 only) -->
                <div class="row">
                    <div class="col-md-6">
                        <?php echo renderDateInput('firstsprintstartdate', $t['first_sprint_start'], $row['firstsprintstartdate'], false, null, null, $readonly); ?>
                    </div>
                    <div class="col-md-6">
                        <?php echo renderDateInput('firstsprintenddate', $t['first_sprint_end'], $row['firstsprintenddate'], false, null, null, $readonly); ?>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <?php echo renderTextInput('sprintschedule', $t['sprint_schedule'], $row['sprintschedule'], false, $readonly); ?>
                    </div>
                    <div class="col-md-6">
                        <?php echo renderTextInput('sprintdefects', $t['sprint_defects'], $row['sprintdefects'], false, $readonly); ?>
                    </div>
                </div>
                <?php endif; ?>

            </fieldset>

            <!-- Fieldset: Client Information -->
            <fieldset>
                <legend><?php echo $t['fieldset_client_info']; ?></legend>

                <!-- Row: Client Last Name | Client First Name -->
                <div class="row">
                    <div class="col-md-6">
                        <?php echo renderTextInput('clientlname', $t['client_lastname'], $row['clientlname'], false, $readonly); ?>
                    </div>
                    <div class="col-md-6">
                        <?php echo renderTextInput('clientfname', $t['client_firstname'], $row['clientfname'], false, $readonly); ?>
                    </div>
                </div>

                <!-- Row: Client Email | Department/Agency -->
                <div class="row">
                    <div class="col-md-6">
                        <?php echo renderTextInput('clientemail', $t['client_email'], $row['clientemail'], false, $readonly, 'email'); ?>
                    </div>
                    <div class="col-md-6">
                        <?php echo renderTextInput('departmentagency', $t['department_agency'], $departmentAgency, false, $readonly); ?>
                        <input type="hidden" name="departmentagency_commlogid" value="<?php echo $departmentAgencyCommlogId; ?>">
                    </div>
                </div>

                <!-- Row: Client Phone | Request Source (catalogue 4) | Audience (catalogues 8/9) -->
                <div class="row">
                    <div class="col-md-6">
                        <?php echo renderTextInput('requestlang_display', $t['request_language'], $originalRequestLangLabel . ' (' . $originalRequestLang . ')', false, true); ?>
                    </div>
                    <div class="col-md-6">
                        <?php echo renderTextInput('clientphone', $t['client_phone'], $row['clientphone'], false, $readonly, 'tel', 'data-rule-phoneUS="true"'); ?>
                    </div>
                </div>

                <?php if ($catalogueid == 4 || (in_array($catalogueid, [8, 9]) && hasValue($row['audienceid'] ?? null))): ?>
                <div class="row">
                    <div class="col-md-6">
                        <?php if ($catalogueid == 4): ?>
                        <?php
                        $sources = getDropdownOptions($link, 'tblsources', $lang);
                        $sourceOptions = [];
                        while ($source = mysqli_fetch_assoc($sources)) {
                            $sourceOptions[] = $source;
                        }
                        echo renderSelect('sourceid', $t['request_source'], $sourceOptions, $row['sourceid'], true, $t['select_source'], $readonly);
                        ?>
                        <?php elseif (in_array($catalogueid, [8, 9]) && hasValue($row['audienceid'] ?? null)): ?>
                        <?php
                            $audienceOptions = [];
                            if (function_exists('rmt_db_table_exists') && rmt_db_table_exists($link, 'tblaudience')) {
                                $audiences = getDropdownOptions($link, 'tblaudience', $lang);
                                while ($audience = mysqli_fetch_assoc($audiences)) {
                                    $audienceOptions[] = $audience;
                                }
                            } else {
                                $audienceOptions[] = [
                                    'id' => (string) ($row['audienceid'] ?? '0'),
                                    'name' => $t['na'] . ' (' . (string) ($row['audienceid'] ?? '') . ')',
                                ];
                        }
                        echo renderSelect('audience', $t['intended_audience'], $audienceOptions, $row['audienceid'], true, $t['select_audience'], $readonly);
                        ?>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

            </fieldset>

            <!-- Fieldset: Dates -->
            <fieldset>
                <legend><?php echo $t['fieldset_dates']; ?></legend>

                <!-- Row: Date Received | Date Updated -->
                <div class="row">
                    <div class="col-md-6">
                        <?php echo renderDateInput('datereceived', $t['date_received'], $row['datereceived'], true, $dateRange['min'], $dateRange['max'], $readonly); ?>
                    </div>
                    <div class="col-md-6">
                        <?php echo renderDateInput('dateupdated', $t['date_updated'], $row['dateupdated'], false, $dateRange['min'], $dateRange['max'], $readonly); ?>
                    </div>
                </div>

                <!-- Row: Date Required | Date Resolved -->
                <div class="row">
                    <div class="col-md-6">
                        <?php echo renderDateInput('daterequired', $dateRequiredLabel, $row['daterequired'], false, $dateRange['min'], $dateRange['max'], $readonly); ?>
                    </div>
                    <div class="col-md-6">
                        <?php echo renderDateInput('dateresolved', $t['date_resolved'], $row['dateresolved'], false, $dateRange['min'], $dateRange['max'], $readonly); ?>
                    </div>
                </div>

            </fieldset>

            <?php
            $resolvedEmailSentDate = rmt_get_resolved_email_sent_date($link, (int)$requestuid);
            $resolvedEmailSent = ($resolvedEmailSentDate !== null && $resolvedEmailSentDate !== '');
            $isResolvedStatus = rmt_is_resolved_status_id($link, (int)($row['statusid'] ?? 0));
            $encodedRequestId = base64_encode((string)$requestuid);
            $returnToEdit = rawurlencode('/editrequest.php?lang=' . $lang . '&id=' . (int)$row['id']);
            ?>
            <?php if ($isResolvedStatus): ?>
            <h2><?php echo $t['resolved_email_status']; ?></h2>
            <p>
                <strong><?php echo $resolvedEmailSent ? $t['resolved_email_sent'] : $t['resolved_email_not_sent']; ?></strong>
                <?php if ($resolvedEmailSent): ?>
                - <?php echo $t['resolved_email_sent_on']; ?> <?php echo htmlspecialchars($resolvedEmailSentDate, ENT_QUOTES, 'UTF-8'); ?>
                <?php endif; ?>
            </p>
            <?php if (!$resolvedEmailSent): ?>
                <?php if (!empty($row['clientemail'])): ?>
                <div class="form-group form-buttons">
                    <button type="submit"
                            class="btn btn-primary"
                            formaction="/client-survey-link.php?lang=<?php echo $lang; ?>&erid=<?php echo urlencode($encodedRequestId); ?>&return_to=<?php echo $returnToEdit; ?>"
                            formmethod="post"
                            name="email_action"
                            value="send_resolved_email"><?php echo $t['resolved_email_send_button']; ?></button>
                </div>
                <?php else: ?>
                <p><?php echo $t['resolved_email_missing_client']; ?></p>
                <?php endif; ?>
            <?php endif; ?>
            <?php endif; ?>

            <?php
            // Attachments section
            $attach1 = $row['attach1'];
            $attach2 = $row['attach2'];
            $attach3 = $row['attach3'];

            if (hasValue($attach1) || hasValue($attach2) || hasValue($attach3)):
            ?>
            <h2><?php echo $t['attachments_heading']; ?></h2>
            <div class="row">
                <?php for ($i = 1; $i <= 3; $i++):
                    $attachVar = "attach$i";
                    $attachValue = $$attachVar;
                    $viewLink = hasValue($attachValue)
                        ? ' - <a href="' . htmlspecialchars($attachValue, ENT_QUOTES, 'UTF-8') . '" target="_blank"><span class="glyphicon glyphicon-file"></span> ' . $t['view_attachment'] . ' ' . $i . '</a>'
                        : '';
                ?>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="attach<?php echo $i; ?>"><span class="field-name"><?php echo $t['attachment']; ?> <?php echo $i; ?> (<?php echo $t['url_only']; ?>)</span><?php echo $viewLink; ?></label>
                        <input type="text" autocomplete="url" class="form-control" id="attach<?php echo $i; ?>" name="attach<?php echo $i; ?>" value="<?php echo htmlspecialchars($attachValue ?? '', ENT_QUOTES, 'UTF-8'); ?>" <?php echo $readonly ? 'readonly="readonly"' : ''; ?>>
                    </div>
                </div>
                <?php endfor; ?>
            </div>
            <?php endif; ?>

            <?php include 'includes/editrequest-files-section.php'; ?>

            <?php include 'includes/editrequest-communications-section.php'; ?>
            
            <?php if (canEditRequests()): ?>
                <?php include 'includes/editrequest-staff-section.php'; ?>
            <?php endif; ?>
            
            <div class="form-group form-buttons">
                <button type="submit" class="btn btn-default"><?php echo $t['update_request']; ?></button>
            </div>
        </form>
        
        <?php
        }
        ?>
        <?php include 'includes/template/page-details.php'; ?>
    </main>

    <?php include 'includes/template/footer.php'; ?>
    <?php include 'includes/template/scripts.php'; ?>
    <?php include 'includes/editrequest-scripts.php'; ?>

    </body>

</html>
<?php mysqli_close($link); ?>
