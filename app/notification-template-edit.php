<?php
/**
 * Edit/reset a single notification template's English and French content together
 * (team, service, or subservice scope). Full page (not a modal) - each language section
 * has its own Preview/Reset, but one Save button submits both languages at once.
 */

require_once __DIR__ . '/includes/session_start.php';
require('includes/httpscheck.php');
require('sql.php');
/** @var mysqli $link */
require_once('includes/helpers.php');
require('includes/loggedincheck.php');

if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'fr'], true)) {
    $_SESSION['lang'] = $_GET['lang'];
}
if (!isset($_SESSION['lang']) || !in_array($_SESSION['lang'], ['en', 'fr'], true)) {
    $_SESSION['lang'] = 'en';
}
$lang = $_SESSION['lang'];
$t = require("lang/{$lang}.php");

$teamId = isset($_GET['team_id']) ? (int) $_GET['team_id'] : (isset($_POST['team_id']) ? (int) $_POST['team_id'] : -1);
$serviceId = isset($_GET['service_id']) ? (int) $_GET['service_id'] : (isset($_POST['service_id']) ? (int) $_POST['service_id'] : 0);
$subserviceId = isset($_GET['subservice_id']) ? (int) $_GET['subservice_id'] : (isset($_POST['subservice_id']) ? (int) $_POST['subservice_id'] : 0);
$audience = trim((string) ($_GET['audience'] ?? $_POST['audience'] ?? ''));
$event = trim((string) ($_GET['event'] ?? $_POST['event'] ?? ''));

$isValidAudience = in_array($audience, rmt_notification_audiences(), true);
$isValidEvent = $isValidAudience && rmt_notification_is_valid_audience_event($audience, $event);
$isScoped = ($serviceId > 0 || $subserviceId > 0);

// The app-wide default (team_id = 0) is superadmin-only to edit - see rmt_notification_user_can_manage_scope().
if ($teamId < RMT_NOTIFICATION_GLOBAL_TEAM_ID || !$isValidAudience || !$isValidEvent) {
    http_response_code(404);
    exit();
}

if (!rmt_notification_user_can_manage_scope($link, $teamId, $serviceId, $subserviceId)) {
    header("location:/notification-templates.php?lang={$lang}&status=forbidden");
    exit();
}

// Rows are scoped to exactly one level: subservice, service, or team (never combined).
$rowTeamId = $isScoped ? RMT_NOTIFICATION_GLOBAL_TEAM_ID : $teamId;
$rowServiceId = $subserviceId > 0 ? 0 : $serviceId;
$rowSubserviceId = $subserviceId;
$isGlobalRow = ($rowTeamId === RMT_NOTIFICATION_GLOBAL_TEAM_ID && $rowServiceId === 0 && $rowSubserviceId === 0);
$scopeQueryString = "team_id={$teamId}&service_id={$serviceId}&subservice_id={$subserviceId}";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string) ($_POST['form_action'] ?? 'save'));
    $updatedBy = (int) ($_SESSION['pid'] ?? 0);

    if ($action === 'reset') {
        $resetLanguage = trim((string) ($_POST['reset_language'] ?? ''));
        if (!in_array($resetLanguage, ['en', 'fr'], true)) {
            http_response_code(404);
            exit();
        }

        // The app-wide default row can be edited by superadmins but never deleted - there's
        // nothing below it to fall back to.
        if ($isGlobalRow) {
            header("location:/notification-template-edit.php?lang={$lang}&{$scopeQueryString}&audience=" . urlencode($audience) . "&event=" . urlencode($event) . "&status=failed");
            exit();
        }

        rmt_db_execute(
            $link,
            'DELETE FROM tblnotificationtemplates WHERE team_id = ? AND service_id = ? AND subservice_id = ? AND audience = ? AND event = ? AND language = ?',
            'iiisss',
            [$rowTeamId, $rowServiceId, $rowSubserviceId, $audience, $event, $resetLanguage]
        );
        header("location:/notification-template-edit.php?lang={$lang}&{$scopeQueryString}&audience=" . urlencode($audience) . "&event=" . urlencode($event) . "&status=reset");
        exit();
    }

    $subjectEn = trim((string) ($_POST['subject_en'] ?? ''));
    $bodyEn = trim((string) ($_POST['body_en'] ?? ''));
    $subjectFr = trim((string) ($_POST['subject_fr'] ?? ''));
    $bodyFr = trim((string) ($_POST['body_fr'] ?? ''));

    if ($subjectEn === '' || $bodyEn === '' || $subjectFr === '' || $bodyFr === '') {
        header("location:/notification-template-edit.php?lang={$lang}&{$scopeQueryString}&audience=" . urlencode($audience) . "&event=" . urlencode($event) . "&status=failed");
        exit();
    }

    foreach (['en' => [$subjectEn, $bodyEn], 'fr' => [$subjectFr, $bodyFr]] as $saveLanguage => [$saveSubject, $saveBody]) {
        rmt_db_execute(
            $link,
            'INSERT INTO tblnotificationtemplates (team_id, service_id, subservice_id, audience, event, language, subject, body, updatedby, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
             ON DUPLICATE KEY UPDATE subject = VALUES(subject), body = VALUES(body), updatedby = VALUES(updatedby), status = 1',
            'iiisssssi',
            [$rowTeamId, $rowServiceId, $rowSubserviceId, $audience, $event, $saveLanguage, $saveSubject, $saveBody, $updatedBy]
        );
    }

    header("location:/notification-templates.php?lang={$lang}&{$scopeQueryString}&status=saved");
    exit();
}

// Prefill each language: this exact scope's override, else the next level down the resolution
// chain, else blank.
$languageState = [];
foreach (['en', 'fr'] as $prefillLanguage) {
    $existingRow = rmt_notification_template_fetch($link, $rowTeamId, $audience, $event, $prefillLanguage, $rowServiceId, $rowSubserviceId);
    $hasOwnOverride = ($existingRow !== null);
    if ($existingRow === null) {
        $existingRow = rmt_notification_template_resolve($link, $teamId, $audience, $event, $prefillLanguage, $serviceId, $subserviceId);
    }

    $languageState[$prefillLanguage] = [
        'subject' => $existingRow['subject'] ?? '',
        'body' => $existingRow['body'] ?? '',
        'hasOwnOverride' => $hasOwnOverride,
        'isInherited' => ($existingRow !== null && !$hasOwnOverride),
    ];
}

$status = $_GET['status'] ?? '';
$eventLabel = $t['notification_templates_event_' . $event] ?? $event;
$audienceLabel = $audience === 'client'
    ? $t['notification_templates_audience_client']
    : $t['notification_templates_audience_employee'];

$page = [
    'title' => [
        'en' => 'Edit notification template',
        'fr' => 'Modifier le modele de notification',
    ],
    'description' => [
        'en' => 'Edit a client or employee notification message template',
        'fr' => 'Modifier un modele de message de notification client ou employe',
    ],
];
$pageTitle = $page['title'][$lang];
$pageDescription = $page['description'][$lang];

include 'includes/template/head.php';
?>
    <?php include 'includes/template/header.php'; ?>
        <main role="main" property="mainContentOfPage" class="container">
            <h1 property="name" id="wb-cont"><?= htmlspecialchars($t['notification_templates_edit_heading']) ?></h1>
            <p><?= htmlspecialchars($audienceLabel) ?> / <?= htmlspecialchars($eventLabel) ?></p>

            <?php if ($status === 'failed') { ?>
            <section class="alert alert-danger">
                <h2><?= htmlspecialchars($t['failed_heading']) ?></h2>
                <ul><li><?= htmlspecialchars($t['notification_templates_failed']) ?></li></ul>
            </section>
            <?php } elseif ($status === 'reset') { ?>
            <section class="alert alert-success">
                <h2><?= htmlspecialchars($t['success_heading']) ?></h2>
                <ul><li><?= htmlspecialchars($t['notification_templates_reset_done']) ?></li></ul>
            </section>
            <?php } ?>

            <p class="small"><strong><?= htmlspecialchars($t['notification_templates_placeholders_heading']) ?></strong></p>
            <ul class="small">
                <?php foreach (rmt_notification_placeholder_catalog() as $placeholder) { ?>
                <li><code>{{<?= htmlspecialchars($placeholder['token']) ?>}}</code> — <?= htmlspecialchars($placeholder[$lang]) ?></li>
                <?php } ?>
            </ul>

            <form method="post" action="/notification-template-edit.php">
                <input type="hidden" name="team_id" value="<?= $teamId ?>">
                <input type="hidden" name="service_id" value="<?= $serviceId ?>">
                <input type="hidden" name="subservice_id" value="<?= $subserviceId ?>">
                <input type="hidden" name="audience" value="<?= htmlspecialchars($audience) ?>">
                <input type="hidden" name="event" value="<?= htmlspecialchars($event) ?>">
                <input type="hidden" name="form_action" value="save">

                <?php foreach (['en' => $t['notification_templates_lang_en'], 'fr' => $t['notification_templates_lang_fr']] as $formLanguage => $formLanguageLabel) {
                    $state = $languageState[$formLanguage];
                ?>
                <fieldset class="mrgn-bttm-lg">
                    <legend class="h3"><?= htmlspecialchars($formLanguageLabel) ?></legend>
                    <?php if ($state['isInherited']) { ?>
                    <p class="small"><?= htmlspecialchars($t['notification_templates_prefill_global_note']) ?></p>
                    <?php } ?>
                    <div class="form-group">
                        <label for="subject_<?= $formLanguage ?>"><span class="field-name"><?= htmlspecialchars($t['notification_templates_subject_label']) ?></span></label>
                        <input type="text" class="form-control" id="subject_<?= $formLanguage ?>" name="subject_<?= $formLanguage ?>" maxlength="500" style="width:100%; max-width:100%;" value="<?= htmlspecialchars($state['subject']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="body_<?= $formLanguage ?>"><span class="field-name"><?= htmlspecialchars($t['notification_templates_body_label']) ?></span></label>
                        <textarea class="form-control" id="body_<?= $formLanguage ?>" name="body_<?= $formLanguage ?>" rows="10" style="width:100%; max-width:100%;" required><?= htmlspecialchars($state['body']) ?></textarea>
                    </div>
                    <a class="wb-lbx lbx-modal btn btn-default" href="includes/notification-template-preview-dialog.php?team_id=<?= $teamId ?>&service_id=<?= $serviceId ?>&subservice_id=<?= $subserviceId ?>&audience=<?= urlencode($audience) ?>&event=<?= urlencode($event) ?>&language=<?= $formLanguage ?>&lang=<?= htmlspecialchars($lang) ?>">
                        <?= htmlspecialchars($t['notification_templates_preview']) ?>
                    </a>
                </fieldset>
                <?php } ?>

                <div class="form-group form-buttons">
                    <button type="submit" class="btn btn-primary"><?= htmlspecialchars($t['notification_templates_save']) ?></button>
                    <a class="btn btn-default" href="/notification-templates.php?lang=<?= htmlspecialchars($lang) ?>&<?= htmlspecialchars($scopeQueryString) ?>"><?= $lang === 'fr' ? 'Retour a la liste' : 'Back to list' ?></a>
                </div>
            </form>

            <?php if (!$isGlobalRow) { ?>
            <div class="form-group form-buttons">
                <?php foreach (['en' => $t['notification_templates_lang_en'], 'fr' => $t['notification_templates_lang_fr']] as $resetLanguageKey => $resetLanguageLabel) {
                    if (!$languageState[$resetLanguageKey]['hasOwnOverride']) {
                        continue;
                    }
                ?>
                <form method="post" action="/notification-template-edit.php" class="mrgn-bttm-sm" onsubmit="return confirm('<?= htmlspecialchars($t['notification_templates_reset_confirm'], ENT_QUOTES) ?>');">
                    <input type="hidden" name="team_id" value="<?= $teamId ?>">
                    <input type="hidden" name="service_id" value="<?= $serviceId ?>">
                    <input type="hidden" name="subservice_id" value="<?= $subserviceId ?>">
                    <input type="hidden" name="audience" value="<?= htmlspecialchars($audience) ?>">
                    <input type="hidden" name="event" value="<?= htmlspecialchars($event) ?>">
                    <input type="hidden" name="form_action" value="reset">
                    <input type="hidden" name="reset_language" value="<?= $resetLanguageKey ?>">
                    <button type="submit" class="btn btn-danger"><?= htmlspecialchars($t['notification_templates_reset']) ?> (<?= htmlspecialchars($resetLanguageLabel) ?>)</button>
                </form>
                <?php } ?>
            </div>
            <?php } ?>

            <?php include 'includes/template/page-details.php'; ?>
        </main>

        <?php include 'includes/template/footer.php'; include 'includes/template/scripts.php'; ?>
    </body>
</html>
<?php
mysqli_close($link);
