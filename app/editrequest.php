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
        'request_id' => 'Request ID #',
        'request_title' => 'Request title',
        'first_sprint_start' => 'First Sprint Start Date',
        'first_sprint_end' => 'First Sprint End Date',
        'sprint_schedule' => 'Sprint Schedule',
        'sprint_defects' => 'Sprint Defect',
        'client_lastname' => 'Client last name',
        'client_firstname' => 'Client first name',
        'client_email' => 'Client email',
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
        'no_files_found' => 'No files found.',
        'select_all' => 'Select All',
        'download_all' => 'Download All',
        'communications_heading' => 'Communications',
        'edit_original_commlog' => 'Edit original communications log entry',
        'view_existing_comms' => 'View existing communications',
        'add_new_commlog' => 'Add new ITAO communications log entry',
        'itao_use_only' => 'ITAO use only',
        'assigned_team_member' => 'Assigned ITAO team member',
        'select_team_member' => 'Select a team member',
        'reset_sla_timer' => 'Need to reset the SLA timer? Choose the new start date',
        'is_new_request' => 'Is this a new request ?',
        'update_request' => 'Update request',
        'required' => 'required'
    ],
    'fr' => [
        'page_title' => 'Modifier la demande',
        'tool_name' => 'Outil de gestion des demandes - Bureau de l\'accessibilité de la TI',
        'success_heading' => 'Succès',
        'success_message' => 'Vous avez mis à jour la base de données, merci!',
        'failed_heading' => 'Échec',
        'failed_message' => 'La mise à jour de la base de données que vous avez demandée n\'a pas fonctionné, veuillez réessayer, merci!',
        'request_id' => '# de la demande',
        'request_title' => 'Titre de la demande',
        'first_sprint_start' => 'Date de début du premier sprint',
        'first_sprint_end' => 'Date de fin du premier sprint',
        'sprint_schedule' => 'Calendrier du sprint',
        'sprint_defects' => 'Défauts du sprint',
        'client_lastname' => 'Nom du client',
        'client_firstname' => 'Prénom du client',
        'client_email' => 'Courriel du client',
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
        'no_files_found' => 'Aucun fichier trouvé.',
        'select_all' => 'Tout sélectionner',
        'download_all' => 'Tout télécharger',
        'communications_heading' => 'Communications',
        'edit_original_commlog' => 'Modifier le journal des communications client',
        'view_existing_comms' => 'Afficher les communications existantes',
        'add_new_commlog' => 'Ajouter une nouvelle entrée au journal des communications du BATI',
        'itao_use_only' => 'À l\'usage du BATI uniquement',
        'assigned_team_member' => 'Membre assigné de l\'équipe BATI',
        'select_team_member' => 'Sélectionnez un membre de l\'équipe',
        'reset_sla_timer' => 'Besoin de réinitialiser la minuterie SLA? Choisissez la nouvelle date de début',
        'is_new_request' => 'C\'est une nouvelle requete ??',
        'update_request' => 'Mettre à jour',
        'required' => 'requis'
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

?>
<!DOCTYPE html>
<html class="no-js" lang="<?php echo $lang; ?>" dir="ltr">
<head>
    <meta charset="utf-8">
    <title><?php echo $t['page_title']; ?> - a11y-<?php echo $requestid; ?> - <?php echo $t['tool_name']; ?></title>
    <meta content="width=device-width,initial-scale=1" name="viewport">
    <meta name="description" content="">
    <?php include 'includes/refTop.php'; ?>
</head>

<body vocab="https://schema.org/" typeof="WebPage">
    <div id="def-top"></div>
    <?php include $lang === 'fr' ? 'includes/appTop-fr.php' : 'includes/appTop.php'; ?>
    
    <main role="main" property="mainContentOfPage" class="container">
        <h1 property="name" id="wb-cont"><?php echo $t['page_title']; ?> - a11y-<?php echo $requestid; ?></h1>
        
        <?php if ($status == 'success'): ?>
        <section class="alert alert-success">
            <h2><?php echo $t['success_heading']; ?></h2>
            <ul><li><?php echo $t['success_message']; ?></li></ul>
        </section>
        <?php elseif ($status == 'failed'): ?>
        <section class="alert alert-danger">
            <h2><?php echo $t['failed_heading']; ?></h2>
            <ul><li><?php echo $t['failed_message']; ?></li></ul>
        </section>
        <?php endif; ?>
        
        <?php
        // Fetch request data
        $sql = "SELECT * FROM tbltriage WHERE id='$requestuid'";
        $result = mysqli_query($link, $sql);
        
        if (mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);

            $departmentAgency = '';
            $departmentAgencyCommlogId = 0;
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
            
            <?php
            // Request ID
            $readonly = !isAdmin();
            echo renderTextInput('requestid', $t['request_id'], $row['requestid'], true, $readonly);
            
            // Request Title
            echo renderTextInput('requesttitle', $t['request_title'], $row['title'], true);
            
            // Sprint fields (only for subserviceid 95)
            if ($row['subserviceid'] == 95) {
                echo renderDateInput('firstsprintstartdate', $t['first_sprint_start'], $row['firstsprintstartdate']);
                echo renderDateInput('firstsprintenddate', $t['first_sprint_end'], $row['firstsprintenddate']);
                echo renderTextInput('sprintschedule', $t['sprint_schedule'], $row['sprintschedule']);
                echo renderTextInput('sprintdefects', $t['sprint_defects'], $row['sprintdefects']);
            }
            
            // Client information
            echo renderTextInput('clientlname', $t['client_lastname'], $row['clientlname']);
            echo renderTextInput('clientfname', $t['client_firstname'], $row['clientfname']);
            echo renderTextInput('clientemail', $t['client_email'], $row['clientemail'], false, false, 'email');
            echo renderTextInput('departmentagency', $t['department_agency'], $departmentAgency);
            echo renderTextInput('clientphone', $t['client_phone'], $row['clientphone'], false, false, 'tel', 'data-rule-phoneUS="true"');
            echo '<input type="hidden" name="departmentagency_commlogid" value="' . $departmentAgencyCommlogId . '">';
            
            // Request Source - only for adaptive technology requests (catalogue 4)
            $serviceid = $row['serviceid'];
            $catalogueid = $row['catalogueid'];
            if ($catalogueid == 4) {
                $sources = getDropdownOptions($link, 'tblsources', $lang);
                $sourceOptions = [];
                while ($source = mysqli_fetch_assoc($sources)) {
                    $sourceOptions[] = $source;
                }
                echo renderSelect('sourceid', $t['request_source'], $sourceOptions, $row['sourceid'], true, $t['select_source']);
            }
            
            // Dates
            $dateRange = getDateRange(1);
            echo renderDateInput('datereceived', $t['date_received'], $row['datereceived'], true, $dateRange['min'], $dateRange['max']);
            echo renderDateInput('dateupdated', $t['date_updated'], $row['dateupdated'], false, $dateRange['min'], $dateRange['max']);
            
            $dateRequiredLabel = ($catalogueid == 5 && $serviceid != 47) ? $t['coaching_session_date'] : $t['date_required'];
            echo renderDateInput('daterequired', $dateRequiredLabel, $row['daterequired'], false, $dateRange['min'], $dateRange['max']);
            
            echo renderDateInput('dateresolved', $t['date_resolved'], $row['dateresolved'], false, $dateRange['min'], $dateRange['max']);
            
            // Status
            $statuses = getDropdownOptions($link, 'tblstatus', $lang);
            $statusOptions = [];
            while ($status = mysqli_fetch_assoc($statuses)) {
                $statusOptions[] = $status;
            }
            echo renderSelect('statusid', $t['status'], $statusOptions, $row['statusid'], true, $t['select_status']);
            
            // Audience (only for catalogues 8 or 9 with non-zero audienceid)
            if (in_array($catalogueid, [8, 9]) && hasValue($row['audienceid'] ?? null)) {
                $audiences = getDropdownOptions($link, 'tblaudience', $lang);
                $audienceOptions = [];
                while ($audience = mysqli_fetch_assoc($audiences)) {
                    $audienceOptions[] = $audience;
                }
                echo renderSelect('audience', $t['intended_audience'], $audienceOptions, $row['audienceid'], true, $t['select_audience']);
            }
            
            // Catalogue
            $catalogues = getDropdownOptions($link, 'tblcatalogue', $lang);
            $catalogueOptions = [];
            while ($catalogue = mysqli_fetch_assoc($catalogues)) {
                $catalogueOptions[] = $catalogue;
            }
            ?>
            <div class="form-group">
                <label for="catalogueid"><span class="field-name"><?php echo $t['catalogue_name']; ?>: <strong>(<?php echo $t['required']; ?>)</strong></span></label>
                <select class="form-control" id="catalogueid" name="catalogueid" onchange="ajax1(this.value)" required>
                    <option value=""><?php echo $t['select_catalogue']; ?></option>
                    <?php foreach ($catalogueOptions as $option): ?>
                    <option value="<?php echo $option['id']; ?>" <?php echo ($row['catalogueid'] == $option['id']) ? 'selected' : ''; ?>>
                        <?php echo $option['name']; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <?php
            // Service dropdown (populated if catalogue is selected)
            if (hasValue($row['catalogueid'])) {
                $services = getServicesByCategory($link, $row['catalogueid'], $lang);
            ?>
            <div class="form-group divservice">
                <label for="serviceid"><span class="field-name"><?php echo $t['service_name']; ?>: <strong>(<?php echo $t['required']; ?>)</strong></span></label>
                <select class="form-control" id="serviceid" name="serviceid" onchange="ajax2(this.value)" required>
                    <option value=""><?php echo $t['select_service']; ?></option>
                    <?php while ($service = mysqli_fetch_assoc($services)): ?>
                    <option value="<?php echo $service['id']; ?>" <?php echo ($row['serviceid'] == $service['id']) ? 'selected' : ''; ?>>
                        <?php echo $service['name']; ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <?php
            } else {
                echo '<div class="form-group divservice"></div>';
            }
            
            // Subservice dropdown (populated if service has subservices)
            if (hasValue($row['serviceid'])) {
                $subservices = getSubservicesByService($link, $row['serviceid'], $lang);
                if (mysqli_num_rows($subservices) > 0 && $row['subserviceid'] != 0) {
            ?>
            <div class="form-group divsubservice">
                <label for="subserviceid"><span class="field-name"><?php echo $t['subservice_name']; ?>: <strong>(<?php echo $t['required']; ?>)</strong></span></label>
                <select class="form-control" id="subserviceid" name="subserviceid" required>
                    <option value=""><?php echo $t['select_subservice']; ?></option>
                    <?php while ($subservice = mysqli_fetch_assoc($subservices)): ?>
                    <option value="<?php echo $subservice['id']; ?>" <?php echo ($row['subserviceid'] == $subservice['id']) ? 'selected' : ''; ?>>
                        <?php echo $subservice['name']; ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <?php
                } else {
                    echo '<div class="form-group divsubservice"></div>';
                }
            }
            
            // Attachments section
            $attach1 = $row['attach1'];
            $attach2 = $row['attach2'];
            $attach3 = $row['attach3'];
            
            if (hasValue($attach1) || hasValue($attach2) || hasValue($attach3)) {
                echo "<h2>{$t['attachments_heading']}</h2>";
                
                for ($i = 1; $i <= 3; $i++) {
                    $attachVar = "attach$i";
                    $attachValue = $$attachVar;
                    $viewLink = hasValue($attachValue) ? 
                        " - <a href=\"$attachValue\" target=\"_blank\"><span class=\"glyphicon glyphicon-file\"></span> {$t['view_attachment']} $i</a>" : '';
                    
                    echo "<div class=\"form-group\">";
                    echo "<label for=\"attach$i\"><span class=\"field-name\">{$t['attachment']} $i ({$t['url_only']})</span>$viewLink</label>";
                    echo "<input type=\"text\" autocomplete=\"url\" class=\"form-control\" id=\"attach$i\" name=\"attach$i\" value=\"$attachValue\">";
                    echo "</div>";
                }
            }
            ?>
            
            <?php include 'includes/editrequest-files-section.php'; ?>
            
            <?php include 'includes/editrequest-communications-section.php'; ?>
            
            <?php if (canEditRequests()): ?>
                <?php include 'includes/editrequest-itao-section.php'; ?>
            <?php endif; ?>
            
            <div class="form-group form-buttons">
                <button type="submit" class="btn btn-default"><?php echo $t['update_request']; ?></button>
            </div>
        </form>
        
        <?php
        }
        ?>
        
        <div id="def-preFooter"></div>
        <?php include 'includes/preFooter.php'; ?>
    </main>
    
    <div class="image-preview" id="imagePreview" role="dialog" aria-hidden="true">
        <button class="close-btn" id="closePreview" aria-label="Close image preview">&times;</button>
        <img id="previewImage" src="" alt="Preview">
        <p id="imageAnnouncement" class="sr-only" aria-live="assertive"></p>
    </div>
    
    <div id="def-footer"></div>
    <?php include 'includes/appFooter.php'; ?>
</body>

<?php include 'includes/editrequest-scripts.php'; ?>

</html>
<?php mysqli_close($link); ?>
