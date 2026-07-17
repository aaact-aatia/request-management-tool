<?php
/**
 * Open Request Step 2 - Rebuilt with Clean Logic
 * Collects detailed information based on selected catalogue/service
 */

require('sql.php');
/** @var mysqli $link */
require('includes/httpscheck.php');
require('includes/helpers.php');

// Language detection
$lang = detectLanguage();

$draftData = [];
if (isset($_SESSION['openrequest_draft']) && is_array($_SESSION['openrequest_draft'])) {
    $draftData = $_SESSION['openrequest_draft'];
    unset($_SESSION['openrequest_draft']);
}

$uploadErrorMessage = '';
if (isset($_SESSION['openrequest_upload_error_message'])) {
    $uploadErrorMessage = (string) $_SESSION['openrequest_upload_error_message'];
    unset($_SESSION['openrequest_upload_error_message']);
}

// Redirect if accessed without POST
if ($_SERVER['REQUEST_METHOD'] != 'POST' && empty($draftData)) {
    header("Location: /openrequest.php?lang=$lang");
    exit;
}

// Translations
$translations = [
    'en' => [
        'page_title' => 'New request',
        'tool_name' => 'Request Management Tool - IT Accessibility Office',
        'heading_sprint' => 'Sprint Spot-Check information required for your request',
        'heading_audit_sample' => 'Audit of representative sample informations required for your request',
        'heading_additional' => 'Additional information required for your request',
        'failed_title' => 'Failed',
        'failed_message' => 'The new request did not work, please try again, thank you!',
        'request_title' => 'Brief request title',
        'date_coaching' => 'Requested coaching session date',
        'date_required' => 'Date required',
        'first_sprint_date' => 'First Sprint Date',
        'last_sprint_date' => 'Last Sprint Date',
        'sprint_schedule' => 'Sprint Schedule (URL only)',
        'sprint_defects' => 'Sprint defects (URL only)',
        'first_name' => 'First name',
        'last_name' => 'Last name',
        'email' => 'Email',
        'department_agency' => 'Department/agency',
        'phone' => 'Business phone number',
        'additional_info' => 'Additional information',
        'upload_files' => 'Upload files',
        'upload_files_hint' => '',
        'attachment' => 'Attachment',
        'url_only' => 'URL only',
        'yes' => 'Yes',
        'no' => 'No',
        'submit' => 'Submit',
        'required' => 'required'
    ],
    'fr' => [
        'page_title' => 'Nouvelle demande',
        'tool_name' => 'Outil de gestion des demandes - Bureau de l\'accessibilité de la TI',
        'heading_sprint' => 'Informations de la vérification ponctuelle du sprint requises pour votre demande',
        'heading_audit_sample' => 'Audit des informations de l\'échantillon représentatif requises pour votre demande',
        'heading_additional' => 'Informations complémentaires requises pour votre demande',
        'failed_title' => 'Échec',
        'failed_message' => 'La nouvelle demande n\'a pas fonctionné, veuillez réessayer, merci!',
        'request_title' => 'Bref titre pour la demande',
        'date_coaching' => 'Date de la séance de coaching demandée',
        'date_required' => 'Date requise',
        'first_sprint_date' => 'Date de début du premier sprint',
        'last_sprint_date' => 'Date de fin du premier sprint',
        'sprint_schedule' => 'Calendrier du sprint (URL uniquement)',
        'sprint_defects' => 'Échec du sprint (URL uniquement)',
        'first_name' => 'Prénom',
        'last_name' => 'Nom',
        'email' => 'Courriel',
        'department_agency' => 'Ministère/organisme',
        'phone' => 'Numéro de téléphone au bureau',
        'additional_info' => 'Informations supplémentaires',
        'upload_files' => 'Téléverser des fichiers',
        'upload_files_hint' => '',
        'attachment' => 'Pièce jointe',
        'url_only' => 'URL uniquement',
        'yes' => 'Oui',
        'no' => 'Non',
        'submit' => 'Soumettre',
        'required' => 'requis'
    ]
];

$t = $translations[$lang];

// ============================================================================
// COLLECT FORM DATA
// IDs are numeric tblcatalogue/tblservices/tblsubservices primary keys.
// subserviceid2 holds the checklist answer: 'checklist_yes', 'checklist_no', or empty.
// ============================================================================

$catalogueid  = (int) ($draftData['catalogueid']  ?? getPostValue('catalogueid',  0));
$serviceid    = rmt_optional_positive_int($draftData['serviceid']    ?? ($_POST['serviceid']    ?? null));
$subserviceid = rmt_optional_positive_int($draftData['subserviceid'] ?? ($_POST['subserviceid'] ?? null));
$subserviceid2 = $draftData['subserviceid2'] ?? getPostValue('subserviceid2', '');
$clientnotes  = $draftData['clientnotes']  ?? getPostValue('clientnotes');
$language     = $draftData['language']     ?? getPostValue('language');

// Flags
$reauditFlag = 0;
$attach1 = $attach2 = $attach3 = "";
$needsSprintFields = false;
$subserviceName = '';

// ============================================================================
// DB LOOKUP: fetch subservice flags (sprint fields, checklist, name)
// IDs are numeric — no string mapping needed.
// ============================================================================
if ($subserviceid !== null) {
    $subStmt = $link->prepare(
        'SELECT nameen, namefr, needs_sprint_fields, needs_checklist
         FROM tblsubservices WHERE id = ? AND status = 1 LIMIT 1'
    );
    $subStmt->bind_param('i', $subserviceid);
    $subStmt->execute();
    $subRow = $subStmt->get_result()->fetch_assoc();
    $subStmt->close();

    if ($subRow) {
        $needsSprintFields = (bool) $subRow['needs_sprint_fields'];
        $subserviceName    = $lang === 'fr' ? $subRow['namefr'] : $subRow['nameen'];
        // Mark as re-audit if subservice name contains 're-audit' (case-insensitive)
        if (stripos($subserviceName, 're-audit') !== false
            || stripos($subserviceName, 'vérification de suivi') !== false) {
            $reauditFlag = 1;
        }
    }
}

// Validate checklist gate: if subservice requires checklist, subserviceid2 must be 'checklist_yes'
if ($subserviceid !== null && isset($subRow['needs_checklist']) && $subRow['needs_checklist']) {
    if ($subserviceid2 !== 'checklist_yes') {
        // Redirect back; user bypassed the checklist gate
        header('Location: /openrequest.php?lang=' . $lang . '&status=accessdenied');
        exit;
    }
}

$pageTitle    = $t['page_title'];
$pageDescription = '';

include 'includes/template/head.php';

?>
<?php include 'includes/template/header.php'; ?>
    
    <main role="main" property="mainContentOfPage" class="container">
        <h1 property="name" id="wb-cont"><?php echo $t['page_title']; ?></h1>
        
        <?php
        // Section heading: use subservice name when sprint fields are shown
        if ($needsSprintFields) {
            $headingText = htmlspecialchars($subserviceName)
                . ' ' . ($lang === 'fr'
                    ? 'informations requises pour votre demande'
                    : 'information required for your request');
            echo "<h2>$headingText</h2>";
        } else {
            echo '<h2>' . $t['heading_additional'] . '</h2>';
        }
        ?>
        
        <form method="POST" enctype="multipart/form-data" action="openrequest3.php?lang=<?php echo $lang; ?>">
            <!-- Hidden fields to pass forward.
                 serviceid and subserviceid are omitted when null
                 so that openrequest3.php stores SQL NULL rather than a fake zero. -->
            <input type="hidden" name="catalogueid" value="<?php echo $catalogueid; ?>">
            <?php if ($serviceid !== null): ?>
            <input type="hidden" name="serviceid" value="<?php echo $serviceid; ?>">
            <?php endif; ?>
            <?php if ($subserviceid !== null): ?>
            <input type="hidden" name="subserviceid" value="<?php echo $subserviceid; ?>">
            <?php endif; ?>
            <input type="hidden" name="clientnotes" value="<?php echo htmlspecialchars($clientnotes, ENT_QUOTES); ?>">
            <input type="hidden" name="language" value="<?php echo htmlspecialchars($language, ENT_QUOTES); ?>">
            <input type="hidden" name="reauditFlag" value="<?php echo $reauditFlag; ?>">
            
            <?php
            // Request title
            echo renderTextInput('requesttitle', $t['request_title'], $draftData['requesttitle'] ?? '', true);
            
            // Date required (changes label for coaching)
            $dateLabel = ($catalogueid == 2) ? $t['date_coaching'] : $t['date_required'];
            echo renderDateInput('daterequired', $dateLabel, $draftData['daterequired'] ?? '', false);
            
            // Sprint-specific fields (driven by subservice needs_sprint_fields flag)
            if ($needsSprintFields) {
                echo renderDateInput('firstsprintstartdate', $t['first_sprint_date'], $draftData['firstsprintstartdate'] ?? '', true);
                echo renderDateInput('firstsprintenddate', $t['last_sprint_date'], $draftData['firstsprintenddate'] ?? '', true);
                echo renderTextInput('sprintschedule', $t['sprint_schedule'], $draftData['sprintschedule'] ?? '', true, false, 'url');
                echo renderTextInput('sprintdefects', $t['sprint_defects'], $draftData['sprintdefects'] ?? '', true, false, 'url');
            }
            
            // Client information
            echo renderTextInput('clientfname', $t['first_name'], $draftData['clientfname'] ?? '', true);
            echo renderTextInput('clientlname', $t['last_name'], $draftData['clientlname'] ?? '', true);
            echo renderTextInput('clientemail', $t['email'], $draftData['clientemail'] ?? '', true, false, 'email');
            echo renderTextInput('departmentagency', $t['department_agency'], $draftData['departmentagency'] ?? '', false);
            echo renderTextInput('clientphone', $t['phone'], $draftData['clientphone'] ?? '', false, false, 'tel');
            
            // Additional information
            echo renderTextarea('additionalinfo', $t['additional_info'], $draftData['additionalinfo'] ?? '', false);

            // File uploads
            ?>

            <?php if (rmt_file_upload_policy()['enabled']): ?>
            <div class="form-group">
                <label for="fileToUpload"><span class="field-name"><?php echo $t['upload_files']; ?></span></label>
                <input
                    type="file"
                    class="form-control"
                    id="fileToUpload"
                    name="fileToUpload[]"
                    multiple
                    accept="<?php echo htmlspecialchars(rmt_file_upload_accept_attribute(), ENT_QUOTES, 'UTF-8'); ?>"
                    aria-describedby="fileToUploadHelp fileToUploadError"
                    <?php echo !empty($uploadErrorMessage) ? 'aria-invalid="true"' : ''; ?>
                    <?php echo !empty($uploadErrorMessage) ? 'autofocus' : ''; ?>
                >
                <p id="fileToUploadHelp" class="small text-muted"><?php echo htmlspecialchars(rmt_file_upload_hint($lang), ENT_QUOTES, 'UTF-8'); ?></p>
                <p id="fileToUploadError" class="text-danger" aria-live="polite"><?php echo !empty($uploadErrorMessage) ? htmlspecialchars($uploadErrorMessage, ENT_QUOTES, 'UTF-8') : ''; ?></p>
            </div>
            <?php endif; ?>

            <?php
            
            // BDM field removed from the intake flow.
            ?>
            
            <div class="form-group form-buttons">
                <button type="submit" class="btn btn-primary"><?php echo $t['submit']; ?></button>
            </div>
        </form>
        <?php include 'includes/template/page-details.php'; ?>
    </main>

    <?php include 'includes/template/footer.php'; ?>
    <?php include 'includes/template/scripts.php'; ?>
</body>
</html>
<?php mysqli_close($link); ?>
