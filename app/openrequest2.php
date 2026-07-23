<?php
require('sql.php');
/** @var mysqli $link */
require('includes/httpscheck.php');
require('includes/helpers.php');

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

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && !$draftData) {
    header("Location: /openrequest.php?lang={$lang}");
    exit;
}

$translations = [
    'en' => [
        'page_title' => 'New request',
        'heading_sprint' => 'Sprint Spot-Check information required for your request',
        'heading_audit_sample' => 'Audit of representative sample information required for your request',
        'heading_additional' => 'Additional information required for your request',
        'request_title' => 'Brief request title',
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
        'submit' => 'Submit'
    ],
    'fr' => [
        'page_title' => 'Nouvelle demande',
        'heading_sprint' => 'Informations de la vérification ponctuelle du sprint requises pour votre demande',
        'heading_audit_sample' => 'Informations sur l’audit d’un échantillon représentatif requises pour votre demande',
        'heading_additional' => 'Informations complémentaires requises pour votre demande',
        'request_title' => 'Bref titre pour la demande',
        'date_required' => 'Date requise',
        'first_sprint_date' => 'Date de début du premier sprint',
        'last_sprint_date' => 'Date de fin du premier sprint',
        'sprint_schedule' => 'Calendrier du sprint (URL uniquement)',
        'sprint_defects' => 'Échecs du sprint (URL uniquement)',
        'first_name' => 'Prénom',
        'last_name' => 'Nom',
        'email' => 'Courriel',
        'department_agency' => 'Ministère/organisme',
        'phone' => 'Numéro de téléphone au bureau',
        'additional_info' => 'Informations supplémentaires',
        'upload_files' => 'Téléverser des fichiers',
        'submit' => 'Soumettre'
    ]
];
$t = $translations[$lang];

function intakeId(array $draftData, string $key, bool $allowZero = false): int
{
    $value = $draftData[$key] ?? ($_POST[$key] ?? 0);
    return filter_var($value, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => $allowZero ? 0 : 1, 'default' => 0]
    ]);
}

if (!$draftData && isset($_POST['intake_selection'])) {
    $selectionParts = explode(':', (string) $_POST['intake_selection'], 3);
    $catalogueid = filter_var($selectionParts[0] ?? null, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1, 'default' => 0]
    ]);
    $serviceid = filter_var($selectionParts[1] ?? null, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 0, 'default' => -1]
    ]);
    $subserviceid = filter_var($selectionParts[2] ?? null, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 0, 'default' => -1]
    ]);
} else {
    $catalogueid = intakeId($draftData, 'catalogueid');
    $serviceid = intakeId($draftData, 'serviceid', true);
    $subserviceid = intakeId($draftData, 'subserviceid');
}
$selection = rmt_validate_intake_selection($link, $catalogueid, $serviceid, $subserviceid);
if ($selection === null) {
    header("Location: /openrequest.php?lang={$lang}&status=failed#intake-error");
    exit;
}

$catalogueid = $selection['catalogueid'];
$serviceid = $selection['serviceid'];
$subserviceid = $selection['subserviceid'];

$reauditFlag = 0;
$pageTitle = $t['page_title'];
$pageDescription = '';
include 'includes/template/head.php';
?>
<?php include 'includes/template/header.php'; ?>
<main role="main" property="mainContentOfPage" class="container">
    <h1 property="name" id="wb-cont"><?= htmlspecialchars($t['page_title']) ?></h1>

    <?php if ($subserviceid === 95): ?>
    <h2><?= htmlspecialchars($t['heading_sprint']) ?></h2>
    <?php elseif ($subserviceid === 96): ?>
    <h2><?= htmlspecialchars($t['heading_audit_sample']) ?></h2>
    <?php else: ?>
    <h2><?= htmlspecialchars($t['heading_additional']) ?></h2>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" action="openrequest3.php?lang=<?= htmlspecialchars($lang) ?>">
        <input type="hidden" name="catalogueid" value="<?= $catalogueid ?>">
        <input type="hidden" name="serviceid" value="<?= $serviceid ?>">
        <input type="hidden" name="subserviceid" value="<?= $subserviceid ?>">
        <input type="hidden" name="reauditFlag" value="<?= $reauditFlag ?>">

        <?php
        echo renderTextInput('requesttitle', $t['request_title'], $draftData['requesttitle'] ?? '', true);
        echo renderDateInput('daterequired', $t['date_required'], $draftData['daterequired'] ?? '', false);

        if ($subserviceid === 95 || $subserviceid === 96) {
            echo renderDateInput('firstsprintstartdate', $t['first_sprint_date'], $draftData['firstsprintstartdate'] ?? '', true);
            echo renderDateInput('firstsprintenddate', $t['last_sprint_date'], $draftData['firstsprintenddate'] ?? '', true);
            echo renderTextInput('sprintschedule', $t['sprint_schedule'], $draftData['sprintschedule'] ?? '', true, false, 'url');
            echo renderTextInput('sprintdefects', $t['sprint_defects'], $draftData['sprintdefects'] ?? '', true, false, 'url');
        }

        echo renderTextInput('clientfname', $t['first_name'], $draftData['clientfname'] ?? '', true);
        echo renderTextInput('clientlname', $t['last_name'], $draftData['clientlname'] ?? '', true);
        echo renderTextInput('clientemail', $t['email'], $draftData['clientemail'] ?? '', true, false, 'email');
        echo renderTextInput('departmentagency', $t['department_agency'], $draftData['departmentagency'] ?? '', false);
        echo renderTextInput('clientphone', $t['phone'], $draftData['clientphone'] ?? '', false, false, 'tel');
        echo renderTextarea('additionalinfo', $t['additional_info'], $draftData['additionalinfo'] ?? '', false);
        ?>

        <?php if (rmt_file_upload_policy()['enabled']): ?>
        <div class="form-group">
            <label for="fileToUpload"><span class="field-name"><?= htmlspecialchars($t['upload_files']) ?></span></label>
            <input
                type="file"
                class="form-control"
                id="fileToUpload"
                name="fileToUpload[]"
                multiple
                accept="<?= htmlspecialchars(rmt_file_upload_accept_attribute(), ENT_QUOTES, 'UTF-8') ?>"
                aria-describedby="fileToUploadHelp fileToUploadError"
                <?= $uploadErrorMessage !== '' ? 'aria-invalid="true" autofocus' : '' ?>
            >
            <p id="fileToUploadHelp" class="small text-muted"><?= htmlspecialchars(rmt_file_upload_hint($lang), ENT_QUOTES, 'UTF-8') ?></p>
            <p id="fileToUploadError" class="text-danger" aria-live="polite"><?= htmlspecialchars($uploadErrorMessage, ENT_QUOTES, 'UTF-8') ?></p>
        </div>
        <?php endif; ?>

        <div class="form-group form-buttons">
            <button type="submit" class="btn btn-primary"><?= htmlspecialchars($t['submit']) ?></button>
        </div>
    </form>
    <?php include 'includes/template/page-details.php'; ?>
</main>
<?php include 'includes/template/footer.php'; ?>
<?php include 'includes/template/scripts.php'; ?>
</body>
</html>
<?php mysqli_close($link); ?>