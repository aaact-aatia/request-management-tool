<?php
// Read-only preview of a template's rendered subject/message. Opened as a lightbox dialog from
// notification-template-edit.php (single language via ?language=) or from the templates list
// (both languages together, when ?language= is omitted). Shows the exact scope currently in
// effect (team/service/subservice override, or the next level down the resolution chain).

require_once __DIR__ . '/session_start.php';

$lang_code = $_SESSION['lang'] ?? 'en';
$t = require("../lang/{$lang_code}.php");

require('../sql.php');
/** @var mysqli $link */
require_once('helpers.php');
require('loggedincheck.php');

$teamId = isset($_GET['team_id']) ? (int) $_GET['team_id'] : -1;
$serviceId = isset($_GET['service_id']) ? (int) $_GET['service_id'] : 0;
$subserviceId = isset($_GET['subservice_id']) ? (int) $_GET['subservice_id'] : 0;
$audience = trim((string) ($_GET['audience'] ?? ''));
$event = trim((string) ($_GET['event'] ?? ''));
$requestedLanguage = trim((string) ($_GET['language'] ?? ''));

$isValidAudience = in_array($audience, rmt_notification_audiences(), true);
$isValidEvent = $isValidAudience && rmt_notification_is_valid_audience_event($audience, $event);
$isValidLanguage = ($requestedLanguage === '' || in_array($requestedLanguage, ['en', 'fr'], true));

if ($teamId < RMT_NOTIFICATION_GLOBAL_TEAM_ID || !$isValidAudience || !$isValidEvent || !$isValidLanguage) {
    http_response_code(404);
    exit();
}

if (!rmt_notification_user_can_manage_scope($link, $teamId, $serviceId, $subserviceId)) {
    header("location:/notification-templates.php?lang={$lang_code}&status=forbidden");
    exit();
}

$recipientType = ($audience === 'client') ? 'client' : 'internal';
$sampleContext = [
    'requestid' => 'REQ-26-123',
    'requesttitle' => 'Fix inaccessible PDF on service page',
    'catalogue_name' => 'Digital accessibility support',
    'service_name' => 'Document remediation',
    'teamname' => 'AAACT Triage',
    'client_fname' => 'Ariane',
    'client_lname' => 'Tremblay',
    'status_label' => $lang_code === 'fr' ? 'En cours' : 'In progress',
    'url' => '#',
    'survey_link_en' => '#',
    'survey_link_fr' => '#',
];

$previewLanguages = ($requestedLanguage === '') ? ['en', 'fr'] : [$requestedLanguage];
$previews = [];
foreach ($previewLanguages as $previewLanguage) {
    $previews[$previewLanguage] = [
        'subject' => rmt_notification_subject_single_language($event, $recipientType, $previewLanguage, $sampleContext, $link, $teamId, $serviceId, $subserviceId),
        'message' => rmt_notification_message_single_language($event, $recipientType, $previewLanguage, $sampleContext, $link, $teamId, $serviceId, $subserviceId),
    ];
}

$eventLabel = $t['notification_templates_event_' . $event] ?? $event;
$audienceLabel = $audience === 'client'
    ? $t['notification_templates_audience_client']
    : $t['notification_templates_audience_employee'];
$dialogSubtitle = ($requestedLanguage === '')
    ? $audienceLabel . ' / ' . $eventLabel
    : $audienceLabel . ' / ' . $eventLabel . ' / ' . ($requestedLanguage === 'en' ? $t['notification_templates_lang_en'] : $t['notification_templates_lang_fr']);
?>
<section id="filter-id" class="modal-dialog modal-content overlay-def">
    <header class="modal-header">
        <h2 class="modal-title"><?= htmlspecialchars($t['notification_templates_preview']) ?> — <?= htmlspecialchars($dialogSubtitle) ?></h2>
    </header>
    <div class="modal-body">
        <p class="small"><?= $lang_code === 'fr'
            ? 'Apercu avec des donnees d\'exemple. Reflete le contenu actuellement enregistre pour cette portee (ou la portee suivante si aucun modele n\'est encore enregistre ici).'
            : 'Preview using sample data. Reflects the currently saved content for this scope (or the next scope down if nothing is saved here yet).'; ?></p>
        <?php foreach ($previews as $previewLanguage => $preview) { ?>
        <?php if (count($previews) > 1) { ?>
        <h3 class="h4"><?= $previewLanguage === 'en' ? htmlspecialchars($t['notification_templates_lang_en']) : htmlspecialchars($t['notification_templates_lang_fr']) ?></h3>
        <?php } ?>
        <p><strong><?= htmlspecialchars($preview['subject']) ?></strong></p>
        <p><?= nl2br(htmlspecialchars($preview['message'], ENT_QUOTES, 'UTF-8')) ?></p>
        <?php } ?>
        <div class="form-group form-buttons">
            <button type="button" class="btn btn-default popup-modal-dismiss"><?= $lang_code === 'fr' ? 'Fermer' : 'Close' ?></button>
        </div>
    </div>
</section>

<?php
mysqli_close($link);
