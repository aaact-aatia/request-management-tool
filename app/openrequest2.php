<?php
require('sql.php');
/** @var mysqli $link */
require('includes/httpscheck.php');
require('includes/helpers.php');
require_once('includes/department-directory.php');

$lang = detectLanguage();
$draftData = [];
if (isset($_SESSION['openrequest_draft']) && is_array($_SESSION['openrequest_draft'])) {
    $draftData = $_SESSION['openrequest_draft'];
    unset($_SESSION['openrequest_draft']);
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
        'date_required' => 'Date required',
        'first_sprint_date' => 'First Sprint Date',
        'last_sprint_date' => 'Last Sprint Date',
        'sprint_schedule' => 'Sprint Schedule (URL only)',
        'sprint_defects' => 'Sprint defects (URL only)',
        'first_name' => 'First name',
        'last_name' => 'Last name',
        'email' => 'Email',
        'department_agency' => 'Department/agency',
        'select_department_agency' => 'Select a department or agency',
        'department_agency_hint' => 'Optional. Start typing a department, agency, or acronym, or enter another organization.',
        'additional_info' => 'Additional information',
        'submit' => 'Submit'
    ],
    'fr' => [
        'page_title' => 'Nouvelle demande',
        'heading_sprint' => 'Informations de la vérification ponctuelle du sprint requises pour votre demande',
        'heading_audit_sample' => 'Informations sur l’audit d’un échantillon représentatif requises pour votre demande',
        'heading_additional' => 'Informations complémentaires requises pour votre demande',
        'date_required' => 'Date requise',
        'first_sprint_date' => 'Date de début du premier sprint',
        'last_sprint_date' => 'Date de fin du premier sprint',
        'sprint_schedule' => 'Calendrier du sprint (URL uniquement)',
        'sprint_defects' => 'Échecs du sprint (URL uniquement)',
        'first_name' => 'Prénom',
        'last_name' => 'Nom',
        'email' => 'Courriel',
        'department_agency' => 'Ministère/organisme',
        'select_department_agency' => 'Sélectionnez un ministère ou organisme',
        'department_agency_hint' => 'Facultatif. Commencez à saisir un ministère, un organisme ou un acronyme, ou entrez une autre organisation.',
        'additional_info' => 'Renseignements supplémentaires',
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
$requestSubjectType = rmt_resolve_request_subject_type($link, $catalogueid, $serviceid, $subserviceid);
$requestSubjectText = rmt_request_subject_text($requestSubjectType, $lang);
$departments = rmt_get_department_directory($link, $lang);
$selectedDepartment = (string) ($draftData['departmentagency'] ?? '');
$departmentOptions = rmt_department_directory_options($departments, $selectedDepartment);
$departmentInputValue = rmt_department_directory_input_value($departmentOptions, $selectedDepartment);

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

    <form method="post" action="openrequest3.php?lang=<?= htmlspecialchars($lang) ?>">
        <input type="hidden" name="catalogueid" value="<?= $catalogueid ?>">
        <input type="hidden" name="serviceid" value="<?= $serviceid ?>">
        <input type="hidden" name="subserviceid" value="<?= $subserviceid ?>">

        <?php
        echo renderDateInput('daterequired', $t['date_required'], $draftData['daterequired'] ?? '', false);

        if ($subserviceid === 95 || $subserviceid === 96) {
            echo renderDateInput('firstsprintstartdate', $t['first_sprint_date'], $draftData['firstsprintstartdate'] ?? '', true);
            echo renderDateInput('firstsprintenddate', $t['last_sprint_date'], $draftData['firstsprintenddate'] ?? '', true);
            echo renderTextInput('sprintschedule', $t['sprint_schedule'], $draftData['sprintschedule'] ?? '', true, false, 'url', '', true);
            echo renderTextInput('sprintdefects', $t['sprint_defects'], $draftData['sprintdefects'] ?? '', true, false, 'url', '', true);
        }

        echo renderTextInput('clientfname', $t['first_name'], $draftData['clientfname'] ?? '', true, false, 'text', 'autocomplete="given-name"', true);
        echo renderTextInput('clientlname', $t['last_name'], $draftData['clientlname'] ?? '', true, false, 'text', 'autocomplete="family-name"', true);
        echo renderTextInput('clientemail', $t['email'], $draftData['clientemail'] ?? '', true, false, 'email', 'autocomplete="email"', true);
        if ($departments === []) {
            echo renderTextInput('departmentagency', $t['department_agency'], $selectedDepartment, false, false, 'text', 'autocomplete="organization" aria-describedby="departmentagency-hint"', true);
            echo '<p id="departmentagency-hint">' . htmlspecialchars($t['department_agency_hint']) . '</p>';
        } else {
            ?>
            <div class="form-group">
                <label for="departmentagency"><span class="field-name"><?= htmlspecialchars($t['department_agency']) ?></span></label>
                <input
                    type="text"
                    class="form-control full-width"
                    id="departmentagency"
                    name="departmentagency"
                    list="departmentagency-options"
                    value="<?= htmlspecialchars($departmentInputValue, ENT_QUOTES, 'UTF-8') ?>"
                    autocomplete="organization"
                    aria-describedby="departmentagency-hint"
                    placeholder="<?= htmlspecialchars($t['select_department_agency'], ENT_QUOTES, 'UTF-8') ?>"
                >
                <datalist id="departmentagency-options">
                    <?php foreach ($departmentOptions as $department): ?>
                        <option value="<?= htmlspecialchars($department['label'], ENT_QUOTES, 'UTF-8') ?>"></option>
                    <?php endforeach; ?>
                </datalist>
                <p id="departmentagency-hint"><?= htmlspecialchars($t['department_agency_hint']) ?></p>
            </div>
            <?php
        }
        ?>
        <section>
            <h2><?= htmlspecialchars($requestSubjectText['heading']) ?></h2>
            <div class="form-group">
                <label for="request_subject">
                    <span class="field-name"><?= htmlspecialchars($requestSubjectText['label']) ?> <strong>(<?= $lang === 'fr' ? 'obligatoire' : 'required' ?>)</strong></span>
                </label>
                <p id="request-subject-help"><?= htmlspecialchars($requestSubjectText['help']) ?></p>
                <input
                    type="text"
                    class="form-control full-width"
                    id="request_subject"
                    name="request_subject"
                    value="<?= htmlspecialchars((string) ($draftData['request_subject'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                    maxlength="500"
                    aria-describedby="request-subject-help"
                    required
                >
            </div>
        </section>
        <?php
        echo renderTextarea('additionalinfo', $t['additional_info'], $draftData['additionalinfo'] ?? '', false, false, 10, true);
        ?>

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