<?php
/**
 * Open Request Step 2 - Rebuilt with Clean Logic
 * Collects detailed information based on selected catalogue/service
 */

require('sql.php');
require('includes/httpscheck.php');
require('includes/helpers.php');

// Language detection
$lang = detectLanguage();

// Redirect if accessed without POST
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
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
        'nsd_question' => 'Do you have NSD/smart IT setup?',
        'nsd_help' => 'NSD is for technical needs (e.g. software installation), smartIT is for things like desk/chair adjustments',
        'nsd_yes' => 'Yes I have',
        'nsd_no' => 'No I do not have',
        'additional_info' => 'Additional information',
        'attachment' => 'Attachment',
        'url_only' => 'URL only',
        'bdm_question' => 'Is this a BDM related project?',
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
        'nsd_question' => 'Avez-vous une configuration NSD / smart IT?',
        'nsd_help' => 'NSD est pour les besoins techniques (par exemple, installation de logiciels), smartIT est pour des choses comme les ajustements de bureau / chaise',
        'nsd_yes' => 'Oui j\'ai',
        'nsd_no' => 'Non je n\'ai pas',
        'additional_info' => 'Informations supplémentaires',
        'attachment' => 'Pièce jointe',
        'url_only' => 'URL uniquement',
        'bdm_question' => 'S\'agit-il d\'un projet lié au GRD?',
        'yes' => 'Oui',
        'no' => 'Non',
        'submit' => 'Soumettre',
        'required' => 'requis'
    ]
];

$t = $translations[$lang];

// ============================================================================
// COLLECT FORM DATA
// ============================================================================

$catalogueid = getPostValue('catalogueid', 0);
$serviceid = getPostValue('serviceid', 0);
$subserviceid = getPostValue('subserviceid', 0);
$subserviceid2 = getPostValue('subserviceid2', 0);
$clientnotes = getPostValue('clientnotes');
$language = getPostValue('language');

// Flags
$reauditFlag = 0;
$bdmValue = "";
$attach1 = $attach2 = $attach3 = "";

// ============================================================================
// PROCESS SUBSERVICE MAPPINGS
// ============================================================================

// Check if re-audit
if (in_array($subserviceid, ["6:2:1", "6:5:2", "8:1:2:2", "8:2:2"])) {
    $reauditFlag = 1;
}

// Map advice subservices (3:1:x) to actual IDs
$adviceMap = [
    '3:1:1' => 104, // Forms
    '3:1:2' => 105, // Courses
    '3:1:3' => 106, // Documents
    '3:1:4' => 110, // Emails
    '3:1:5' => 107, // Web content
    '3:1:6' => 108, // Services
    '3:1:7' => 109  // Testing
];

if (isset($adviceMap[$subserviceid])) {
    $subserviceid = $adviceMap[$subserviceid];
}

// ============================================================================
// PROCESS DOCUMENT AUDIT PATHS (6:x:x)
// ============================================================================

if (in_array($subserviceid2, ['6:1:1:1', '6:2:1:1'])) {
    // Document audit/re-audit - YES to correcting failures
    $catalogueid = 6;
    $subserviceid = 0;
    $subserviceid2 = 0;
    
    $serviceMap = ['6:1' => 25, '6:2' => 61, '6:3' => 62, '6:4' => 63];
    $serviceid = $serviceMap[$serviceid] ?? $serviceid;
    
} elseif (in_array($subserviceid2, ['6:1:1:2', '6:2:1:2', '6:5:1:1'])) {
    // Document audit - NO to correcting failures OR PDF re-audit
    $catalogueid = 6;
    $subserviceid = 0;
    $subserviceid2 = 0;
    
    $serviceMap = ['6:1' => 25, '6:2' => 61, '6:3' => 62, '6:4' => 63, '6:5' => 64];
    $serviceid = $serviceMap[$serviceid] ?? $serviceid;
    
} elseif ($subserviceid == '6:5:1') {
    // PDF audit
    $catalogueid = 6;
    $serviceid = 64;
    $subserviceid = 0;
}

// ============================================================================
// PROCESS ACCESSIBILITY AUDIT PATHS (8:x:x)
// ============================================================================

if ($subserviceid2 == '8:1:1:1' || $subserviceid2 == '8:1:2:1') {
    // Software audit/re-audit
    $catalogueid = 8;
    $serviceid = 27;
    $subserviceid = 0;
    $subserviceid2 = 0;
    
} elseif ($subserviceid2 == '8:2:1:1' || $subserviceid2 == '8:2:2:1') {
    // Web application - Sprint spot-check or Audit
    $catalogueid = 8;
    $serviceid = 28;
    
    if ($subserviceid2 == '8:2:1:1') {
        $subserviceid = 95; // Sprint spot-check
    } else {
        $subserviceid = 96; // Audit of representative sample
    }
    $subserviceid2 = 0;
    
} elseif ($subserviceid2 == '8:2:1:2' || $subserviceid2 == '8:2:2:2') {
    // Web application - Second tier (MVP vs non-MVP)
    $catalogueid = 8;
    $serviceid = 28;
    
    if ($subserviceid2 == '8:2:1:2') {
        $subserviceid = 95; // Sprint (MVP)
        $bdmValue = 1;
    } else {
        $subserviceid = 96; // Audit (non-MVP)
    }
    $subserviceid2 = 0;
    
} elseif ($subserviceid == '8:4:1' || $subserviceid == '8:4:2') {
    // Audit report questions
    $catalogueid = 8;
    $serviceid = 66;
    $subserviceid = 0;
}

// ============================================================================
// PROCESS ADAPTIVE TECHNOLOGY PATHS (4:x:x)
// ============================================================================

if (in_array($subserviceid, ['4:1:1', '4:2:1', '4:3:1', '99:4:1'])) {
    $catalogueid = 4;
    
    // Determine which software based on serviceid
    $softwareMap = [
        '4:1' => 15,  // Dragon Medical
        '4:2' => 55,  // Dragon NaturallySpeaking
        '4:3' => 56,  // J-Say
        '4:4' => 57,  // JAWS
        '4:5' => 58,  // Kurzweil
        '4:6' => 111, // OpenBook
        '4:8' => 59,  // TextAloud
        '4:10' => 60, // wordQ
        '4:12' => 112, // ZoomText
        '4:13' => 113, // Interact AS
        '4:14' => 114, // Interact streamer
        '4:15' => 115, // NVDA
        '4:16' => 116, // SuperNova
        '4:17' => 117, // Tint & Track
        '4:18' => 118  // Pixie
    ];
    
    $serviceid = $softwareMap[$serviceid] ?? $serviceid;
    $subserviceid = 0;
}

// ============================================================================
// OTHER CATALOGUE PROCESSING
// ============================================================================

// EPMO (7:x)
if ($subserviceid == '7:1:1' || $subserviceid == '7:1:2') {
    $catalogueid = 7;
    $serviceid = 26;
    $subserviceid = 0;
}

// Loan Bank (9:x)
if ($serviceid == '9:1' || $subserviceid == '9:1') {
    $catalogueid = 9;
    $serviceid = 29;
    $subserviceid = 0;
}

// Procurement (10:x)
if ($serviceid == '10:1' || $serviceid == '10:2' || $subserviceid == '10:1' || $subserviceid == '10:2') {
    $catalogueid = 10;
    $serviceidMap = ['10:1' => 30, '10:2' => 31];
    $actualServiceId = $serviceid != 0 ? $serviceid : $subserviceid;
    $serviceid = $serviceidMap[$actualServiceId] ?? $serviceid;
    $subserviceid = 0;
}

// Testing Tools (11:x)
if ($serviceid == '11:1' || $subserviceid == '11:1') {
    $catalogueid = 11;
    $serviceid = 53;
    $subserviceid = 0;
}

// ACP (1:x)
if ($serviceid == '1:1' || $subserviceid == '1:1') {
    $catalogueid = 1;
    $serviceid = 32;
    $subserviceid = 0;
}

// Coaching (2:x)
if (in_array($subserviceid, ['2:1', '2:2', '2:3', '2:4', '2:5', '2:6', '99:2'])) {
    $catalogueid = 2;
    
    $coachingMap = [
        '2:1' => 45, // Curriculum
        '2:2' => 33, // ICT developer
        '2:4' => 47, // PDF
        '2:5' => 48, // Microsoft
        '2:6' => 46  // ACP development
    ];
    
    $serviceid = $coachingMap[$subserviceid] ?? 33;
    $subserviceid = 0;
}

// Needs Assessment (5:x)
if (in_array($subserviceid, ['5:1', '5:2', '5:3', '5:4', '5:5', '99:5'])) {
    $catalogueid = 5;
    
    $needsMap = [
        '5:1' => 16, // Blindness
        '5:2' => 17, // Cognitive
        '5:3' => 18, // Deafness
        '5:4' => 19, // Mobility
        '5:5' => 50  // Multiple
    ];
    
    $serviceid = $needsMap[$subserviceid] ?? 16;
    $subserviceid = 0;
}

// Advice (3:x)
if ($subserviceid == '3:2' || $subserviceid == '3:3' || $subserviceid == '99:3') {
    $catalogueid = 3;
    $serviceidMap = ['3:2' => 45, '3:3' => 34];
    $serviceid = $serviceidMap[$subserviceid] ?? 45;
    $subserviceid = 0;
}

// ============================================================================
// FINAL SERVICEID CONVERSION (catch any unmapped string values)
// ============================================================================

// If serviceid is still a string format, map it to the correct numeric ID
if (!is_numeric($serviceid)) {
    // Document audit services (catalogue 6)
    $documentServiceMap = [
        '6:1' => 25,  // Word
        '6:2' => 61,  // Excel
        '6:3' => 62,  // PowerPoint
        '6:4' => 63,  // Email
        '6:5' => 64,  // PDF
        '6:6' => 65   // Other document
    ];
    
    if (isset($documentServiceMap[$serviceid])) {
        $catalogueid = 6;
        $serviceid = $documentServiceMap[$serviceid];
    } else {
        // For any other unmapped string format, set to 0
        $serviceid = 0;
    }
}

// Ensure catalogueid matches serviceid (final validation)
// Map serviceid back to correct catalogueid if needed
$serviceToCatalogueMap = [
    // Document audits (catalogue 6)
    25 => 6, 61 => 6, 62 => 6, 63 => 6, 64 => 6, 65 => 6,
    // Accessibility audits (catalogue 8)
    27 => 8, 28 => 8, 54 => 8, 66 => 8,
    // Loan bank (catalogue 9)
    29 => 9,
    // Procurement (catalogue 10)
    30 => 10, 31 => 10,
    // Testing tools (catalogue 11)
    53 => 11,
    // ACP (catalogue 1)
    32 => 1,
    // Coaching (catalogue 2)
    33 => 2, 45 => 2, 46 => 2, 47 => 2, 48 => 2,
    // Advice (catalogue 3)
    34 => 3, 104 => 3, 105 => 3, 106 => 3, 107 => 3, 108 => 3, 109 => 3, 110 => 3,
    // Adaptive technology (catalogue 4)
    15 => 4, 55 => 4, 56 => 4, 57 => 4, 58 => 4, 59 => 4, 60 => 4, 111 => 4, 112 => 4, 113 => 4, 114 => 4, 115 => 4, 116 => 4, 117 => 4, 118 => 4,
    // Needs assessment (catalogue 5)
    16 => 5, 17 => 5, 18 => 5, 19 => 5, 50 => 5,
    // EPMO (catalogue 7)
    26 => 7
];

if (isset($serviceToCatalogueMap[$serviceid])) {
    $catalogueid = $serviceToCatalogueMap[$serviceid];
}

// Ensure all IDs are numeric for the form
$catalogueid = (int)$catalogueid;
$serviceid = (int)$serviceid;
$subserviceid = (int)$subserviceid;
$reauditFlag = (int)$reauditFlag;

?>
<!DOCTYPE html>
<html class="no-js" lang="<?php echo $lang; ?>" dir="ltr">
<head>
    <meta charset="utf-8">
    <title><?php echo $t['page_title']; ?> - <?php echo $t['tool_name']; ?></title>
    <meta content="width=device-width,initial-scale=1" name="viewport">
    <?php 
    include 'includes/refTop.php';
    ?>
</head>

<body vocab="https://schema.org/" typeof="WebPage">
    <div id="def-top"></div>
    <?php 
    if ($_SESSION['lang'] === 'fr') {
        include 'includes/appTop-fr.php';
    } else {
        include 'includes/appTop.php';
    }
    ?>
    
    <main role="main" property="mainContentOfPage" class="container">
        <h1 property="name" id="wb-cont"><?php echo $t['page_title']; ?></h1>
        
        <?php
        // Determine which heading to show
        if ($subserviceid == 95) {
            echo "<h2>{$t['heading_sprint']}</h2>";
        } elseif ($subserviceid == 96) {
            echo "<h2>{$t['heading_audit_sample']}</h2>";
        } else {
            echo "<h2>{$t['heading_additional']}</h2>";
        }
        ?>
        
        <form method="POST" action="openrequest3.php?lang=<?php echo $lang; ?>">
            <!-- Hidden fields to pass forward -->
            <input type="hidden" name="catalogueid" value="<?php echo $catalogueid; ?>">
            <input type="hidden" name="serviceid" value="<?php echo $serviceid; ?>">
            <input type="hidden" name="subserviceid" value="<?php echo $subserviceid; ?>">
            <input type="hidden" name="clientnotes" value="<?php echo htmlspecialchars($clientnotes, ENT_QUOTES); ?>">
            <input type="hidden" name="language" value="<?php echo htmlspecialchars($language, ENT_QUOTES); ?>">
            <input type="hidden" name="reauditFlag" value="<?php echo $reauditFlag; ?>">
            <input type="hidden" name="bdmValue" value="<?php echo $bdmValue; ?>">
            
            <?php
            // Request title
            echo renderTextInput('requesttitle', $t['request_title'], '', true);
            
            // Date required (changes label for coaching)
            $dateLabel = ($catalogueid == 2) ? $t['date_coaching'] : $t['date_required'];
            echo renderDateInput('daterequired', $dateLabel, '', false);
            
            // Sprint-specific fields
            if ($subserviceid == 95 || $subserviceid == 96) {
                echo renderDateInput('firstsprintstartdate', $t['first_sprint_date'], '', true);
                echo renderDateInput('firstsprintenddate', $t['last_sprint_date'], '', true);
                echo renderTextInput('sprintschedule', $t['sprint_schedule'], '', true, false, 'url');
                echo renderTextInput('sprintdefects', $t['sprint_defects'], '', true, false, 'url');
            }
            
            // Client information
            echo renderTextInput('clientfname', $t['first_name'], '', true);
            echo renderTextInput('clientlname', $t['last_name'], '', true);
            echo renderTextInput('clientemail', $t['email'], '', true, false, 'email');
            echo renderTextInput('departmentagency', $t['department_agency'], '', true);
            echo renderTextInput('clientphone', $t['phone'], '', false, false, 'tel');
            
            // NSD/Smart IT (only for needs assessment - catalogue 5)
            if ($catalogueid == 5) {
                $nsdOptions = [
                    ['id' => 'Yes I have', 'name' => $t['nsd_yes']],
                    ['id' => 'No I do not have', 'name' => $t['nsd_no']]
                ];
                echo '<div class="form-group">';
                echo '<label for="nsd"><span class="field-name">' . $t['nsd_question'] . '</span></label>';
                echo '<p>' . $t['nsd_help'] . '</p>';
                echo renderSelect('nsd', '', $nsdOptions, '', false, '');
                echo '</div>';
            }
            
            // Additional information
            echo renderTextarea('additionalinfo', $t['additional_info'], '', false);
            
            // Attachments
            for ($i = 1; $i <= 3; $i++) {
                echo renderTextInput("attach$i", "{$t['attachment']} $i ({$t['url_only']})", '', false, false, 'url');
            }
            
            // BDM question (for audits and re-audits)
            if ($reauditFlag == 1 || in_array($catalogueid, [6, 8])) {
                $bdmOptions = [
                    ['id' => '0', 'name' => $t['no']],
                    ['id' => '1', 'name' => $t['yes']]
                ];
                echo renderSelect('bdm', $t['bdm_question'], $bdmOptions, $bdmValue, false, '');
            }
            ?>
            
            <div class="form-group form-buttons">
                <button type="submit" class="btn btn-primary"><?php echo $t['submit']; ?></button>
            </div>
        </form>
        
        <div id="def-preFooter"></div>
        <?php include 'includes/preFooter.php'; ?>
    </main>
    
    <div id="def-footer"></div>
    <?php include 'includes/appFooter.php'; ?>
</body>
</html>
<?php mysqli_close($link); ?>
