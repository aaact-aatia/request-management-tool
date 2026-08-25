<?php
// Edit/reset a single notification template (team, service, or subservice scope). Opened as a
// lightbox modal from notification-templates.php.

require_once __DIR__ . '/session_start.php';

$lang_code = $_SESSION['lang'] ?? 'en';
$t = require("../lang/{$lang_code}.php");

require('../sql.php');
/** @var mysqli $link */
require_once('helpers.php');
require('loggedincheck.php');

$teamId = isset($_GET['team_id']) ? (int) $_GET['team_id'] : (isset($_POST['team_id']) ? (int) $_POST['team_id'] : -1);
$serviceId = isset($_GET['service_id']) ? (int) $_GET['service_id'] : (isset($_POST['service_id']) ? (int) $_POST['service_id'] : 0);
$subserviceId = isset($_GET['subservice_id']) ? (int) $_GET['subservice_id'] : (isset($_POST['subservice_id']) ? (int) $_POST['subservice_id'] : 0);
$audience = trim((string) ($_GET['audience'] ?? $_POST['audience'] ?? ''));
$event = trim((string) ($_GET['event'] ?? $_POST['event'] ?? ''));
$language = trim((string) ($_GET['language'] ?? $_POST['language'] ?? ''));

$isValidAudience = in_array($audience, rmt_notification_audiences(), true);
$isValidEvent = $isValidAudience && rmt_notification_is_valid_audience_event($audience, $event);
$isValidLanguage = in_array($language, ['en', 'fr'], true);
$isScoped = ($serviceId > 0 || $subserviceId > 0);

// The app-wide default (team_id = 0) is superadmin-only to edit - see rmt_notification_user_can_manage_scope().
if ($teamId < RMT_NOTIFICATION_GLOBAL_TEAM_ID || !$isValidAudience || !$isValidEvent || !$isValidLanguage) {
    http_response_code(404);
    exit();
}

if (!rmt_notification_user_can_manage_scope($link, $teamId, $serviceId, $subserviceId)) {
    header("location:/notification-templates.php?lang={$lang_code}&status=forbidden");
    exit();
}

// Rows are scoped to exactly one level: subservice, service, or team (never combined).
$rowTeamId = $isScoped ? RMT_NOTIFICATION_GLOBAL_TEAM_ID : $teamId;
$rowServiceId = $subserviceId > 0 ? 0 : $serviceId;
$rowSubserviceId = $subserviceId;
$scopeQueryString = "team_id={$teamId}&service_id={$serviceId}&subservice_id={$subserviceId}";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string) ($_POST['form_action'] ?? 'save'));
    $updatedBy = (int) ($_SESSION['pid'] ?? 0);

    if ($action === 'reset') {
        // The app-wide default row (team_id=0, service_id=0, subservice_id=0) can be edited by
        // superadmins but never deleted - there's nothing below it to fall back to.
        if ($rowTeamId === RMT_NOTIFICATION_GLOBAL_TEAM_ID && $rowServiceId === 0 && $rowSubserviceId === 0) {
            header("location:/notification-templates.php?lang={$lang_code}&{$scopeQueryString}&status=failed");
            exit();
        }

        rmt_db_execute(
            $link,
            'DELETE FROM tblnotificationtemplates WHERE team_id = ? AND service_id = ? AND subservice_id = ? AND audience = ? AND event = ? AND language = ?',
            'iiisss',
            [$rowTeamId, $rowServiceId, $rowSubserviceId, $audience, $event, $language]
        );
        header("location:/notification-templates.php?lang={$lang_code}&{$scopeQueryString}&status=reset");
        exit();
    }

    $subject = trim((string) ($_POST['subject'] ?? ''));
    $body = trim((string) ($_POST['body'] ?? ''));

    if ($subject === '' || $body === '') {
        header("location:/notification-templates.php?lang={$lang_code}&{$scopeQueryString}&status=failed");
        exit();
    }

    rmt_db_execute(
        $link,
        'INSERT INTO tblnotificationtemplates (team_id, service_id, subservice_id, audience, event, language, subject, body, updatedby, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
         ON DUPLICATE KEY UPDATE subject = VALUES(subject), body = VALUES(body), updatedby = VALUES(updatedby), status = 1',
        'iiisssssi',
        [$rowTeamId, $rowServiceId, $rowSubserviceId, $audience, $event, $language, $subject, $body, $updatedBy]
    );

    header("location:/notification-templates.php?lang={$lang_code}&{$scopeQueryString}&status=saved");
    exit();
}

// Prefill: this exact scope's override, else the next level down the resolution chain, else blank.
$existingRow = rmt_notification_template_fetch($link, $rowTeamId, $audience, $event, $language, $rowServiceId, $rowSubserviceId);
$hasOwnOverride = ($existingRow !== null);
if ($existingRow === null) {
    $existingRow = rmt_notification_template_resolve($link, $teamId, $audience, $event, $language, $serviceId, $subserviceId);
}
$prefillSubject = $existingRow['subject'] ?? '';
$prefillBody = $existingRow['body'] ?? '';
$isInheritedPrefill = ($existingRow !== null && !$hasOwnOverride);

$eventLabel = $t['notification_templates_event_' . $event] ?? $event;
$audienceLabel = $audience === 'client'
    ? $t['notification_templates_audience_client']
    : $t['notification_templates_audience_employee'];
$languageLabel = $language === 'en' ? $t['notification_templates_lang_en'] : $t['notification_templates_lang_fr'];
?>
<section id="filter-id" class="modal-dialog modal-content overlay-def">
    <header class="modal-header">
        <h2 class="modal-title"><?= htmlspecialchars($t['notification_templates_edit_heading']) ?> — <?= htmlspecialchars($audienceLabel) ?> / <?= htmlspecialchars($eventLabel) ?> / <?= htmlspecialchars($languageLabel) ?></h2>
    </header>
    <div class="modal-body">
        <?php if ($isInheritedPrefill) { ?>
        <p class="small"><?= htmlspecialchars($t['notification_templates_prefill_global_note']) ?></p>
        <?php } ?>
        <p class="small"><strong><?= htmlspecialchars($t['notification_templates_placeholders_heading']) ?></strong></p>
        <ul class="small">
            <?php foreach (rmt_notification_placeholder_catalog() as $placeholder) { ?>
            <li><code>{{<?= htmlspecialchars($placeholder['token']) ?>}}</code> — <?= htmlspecialchars($placeholder[$lang_code]) ?></li>
            <?php } ?>
        </ul>
        <form method="post" action="/includes/edit-notification-template.php">
            <input type="hidden" name="team_id" value="<?= $teamId ?>">
            <input type="hidden" name="service_id" value="<?= $serviceId ?>">
            <input type="hidden" name="subservice_id" value="<?= $subserviceId ?>">
            <input type="hidden" name="audience" value="<?= htmlspecialchars($audience) ?>">
            <input type="hidden" name="event" value="<?= htmlspecialchars($event) ?>">
            <input type="hidden" name="language" value="<?= htmlspecialchars($language) ?>">
            <input type="hidden" name="form_action" value="save">
            <div class="form-group">
                <label for="subject"><span class="field-name"><?= htmlspecialchars($t['notification_templates_subject_label']) ?></span></label>
                <input type="text" class="form-control" id="subject" name="subject" maxlength="500" value="<?= htmlspecialchars($prefillSubject) ?>" required>
            </div>
            <div class="form-group">
                <label for="body"><span class="field-name"><?= htmlspecialchars($t['notification_templates_body_label']) ?></span></label>
                <textarea class="form-control" id="body" name="body" rows="10" required><?= htmlspecialchars($prefillBody) ?></textarea>
            </div>
            <div class="form-group form-buttons">
                <button type="submit" class="btn btn-default"><?= htmlspecialchars($t['notification_templates_save']) ?></button>
                <button type="button" class="btn btn-default popup-modal-dismiss"><?= $lang_code === 'fr' ? 'Annuler' : 'Cancel' ?></button>
            </div>
        </form>
        <?php if ($hasOwnOverride && !($rowTeamId === RMT_NOTIFICATION_GLOBAL_TEAM_ID && $rowServiceId === 0 && $rowSubserviceId === 0)) { ?>
        <form method="post" action="/includes/edit-notification-template.php" onsubmit="return confirm('<?= htmlspecialchars($t['notification_templates_reset_confirm'], ENT_QUOTES) ?>');">
            <input type="hidden" name="team_id" value="<?= $teamId ?>">
            <input type="hidden" name="service_id" value="<?= $serviceId ?>">
            <input type="hidden" name="subservice_id" value="<?= $subserviceId ?>">
            <input type="hidden" name="audience" value="<?= htmlspecialchars($audience) ?>">
            <input type="hidden" name="event" value="<?= htmlspecialchars($event) ?>">
            <input type="hidden" name="language" value="<?= htmlspecialchars($language) ?>">
            <input type="hidden" name="form_action" value="reset">
            <button type="submit" class="btn btn-danger"><?= htmlspecialchars($t['notification_templates_reset']) ?></button>
        </form>
        <?php } ?>
    </div>
</section>
<?php
mysqli_close($link);
