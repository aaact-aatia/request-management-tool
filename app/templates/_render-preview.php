<?php
require('../sql.php');
/** @var mysqli $link */
require('../includes/httpscheck.php');
require('../includes/helpers.php');
require('../includes/loggedincheck.php');
require_once(__DIR__ . '/_preview-definitions.php');

$lang = detectLanguage();
$isFrench = ($lang === 'fr');

if (!(($_SESSION['is_superuser'] ?? 0) || ($_SESSION['is_admin'] ?? 0))) {
    header("location:/settings.php?lang={$lang}&status=forbidden");
    exit();
}

if (!isset($previewTemplateKey) || !is_string($previewTemplateKey) || $previewTemplateKey === '') {
    http_response_code(500);
    exit('Template preview key is not configured.');
}

$templateDefinitions = rmt_get_template_preview_definitions();
if (!isset($templateDefinitions[$previewTemplateKey])) {
    http_response_code(404);
    exit('Template preview not found.');
}

$definition = $templateDefinitions[$previewTemplateKey];
$configuredTemplateId = trim((string) app_setting($previewTemplateKey, ''));

$messageScenarios = [
    'request_created_client' => [
        'label_en' => 'New request to client',
        'label_fr' => 'Nouvelle demande au client',
        'event' => 'request_created',
        'recipientType' => 'client',
        'description_en' => 'Client-facing confirmation for a new request.',
        'description_fr' => 'Confirmation envoyée au client pour une nouvelle demande.',
        'status_label_en' => 'In progress',
        'status_label_fr' => 'En cours',
    ],
    'request_created_team' => [
        'label_en' => 'New request to team',
        'label_fr' => 'Nouvelle demande à l équipe',
        'event' => 'request_created',
        'recipientType' => 'internal',
        'description_en' => 'Internal team notification for a newly created request.',
        'description_fr' => 'Notification interne pour une nouvelle demande créée.',
        'status_label_en' => 'In progress',
        'status_label_fr' => 'En cours',
    ],
    'request_afterfact_team' => [
        'label_en' => 'After-fact request to team',
        'label_fr' => 'Demande après-fact à l équipe',
        'event' => 'request_afterfact',
        'recipientType' => 'internal',
        'description_en' => 'Internal team message when the request was submitted after the work happened.',
        'description_fr' => 'Message interne lorsque la demande a été soumise après la réalisation des travaux.',
        'status_label_en' => 'In progress',
        'status_label_fr' => 'En cours',
    ],
    'request_aaact' => [
        'label_en' => 'AAACT routing message',
        'label_fr' => 'Message de routage AATIA',
        'event' => 'request_aaact',
        'recipientType' => 'internal',
        'description_en' => 'Internal triage message for AAACT routing.',
        'description_fr' => 'Message interne de triage pour le routage AATIA.',
        'status_label_en' => 'In progress',
        'status_label_fr' => 'En cours',
    ],
    'resolved_team' => [
        'label_en' => 'Resolved notification to team',
        'label_fr' => 'Notification résolue à l équipe',
        'event' => 'resolved',
        'recipientType' => 'internal',
        'description_en' => 'Internal team message when a request is marked resolved.',
        'description_fr' => 'Message interne lorsque la demande est marquée résolue.',
        'status_label_en' => 'Resolved',
        'status_label_fr' => 'Résolue',
    ],
    'resolved_client' => [
        'label_en' => 'Resolved notification to client',
        'label_fr' => 'Notification résolue au client',
        'event' => 'resolved',
        'recipientType' => 'client',
        'description_en' => 'Client-facing message when a request is marked resolved.',
        'description_fr' => 'Message destiné au client lorsqu une demande est marquée résolue.',
        'status_label_en' => 'Resolved',
        'status_label_fr' => 'Résolue',
    ],
    'status_changed_client' => [
        'label_en' => 'Status changed notification to client',
        'label_fr' => 'Notification de changement de statut au client',
        'event' => 'status_changed',
        'recipientType' => 'client',
        'description_en' => 'Client update when the request status changes.',
        'description_fr' => 'Mise à jour au client lorsque le statut de la demande change.',
        'status_label_en' => 'In progress',
        'status_label_fr' => 'En cours',
    ],
    'reassigned_team' => [
        'label_en' => 'Reassigned notification to team',
        'label_fr' => 'Notification de réattribution à l équipe',
        'event' => 'reassigned',
        'recipientType' => 'internal',
        'description_en' => 'Internal team message when a request is reassigned.',
        'description_fr' => 'Message interne lorsque la demande est réattribuée.',
        'status_label_en' => 'In progress',
        'status_label_fr' => 'En cours',
    ],
    'reassigned_client' => [
        'label_en' => 'Reassigned notification to client',
        'label_fr' => 'Notification de réattribution au client',
        'event' => 'reassigned',
        'recipientType' => 'client',
        'description_en' => 'Client-facing message when a request is reassigned.',
        'description_fr' => 'Message destiné au client lorsqu une demande est réattribuée.',
        'status_label_en' => 'In progress',
        'status_label_fr' => 'En cours',
    ],
];

$selectedScenario = $_GET['message'] ?? 'request_created_client';
if (!isset($messageScenarios[$selectedScenario])) {
    $selectedScenario = 'request_created_client';
}

$scenario = $messageScenarios[$selectedScenario];
$scenarioLabel = $isFrench ? $scenario['label_fr'] : $scenario['label_en'];
$scenarioDescription = $isFrench ? $scenario['description_fr'] : $scenario['description_en'];
$scenarioStatusLabel = $isFrench ? $scenario['status_label_fr'] : $scenario['status_label_en'];

$previewPayloadLanguage = ($scenario['recipientType'] === 'client') ? $lang : 'en';

$scenarioRecipientName = '';
if ($scenario['recipientType'] === 'client') {
    $scenarioRecipientName = 'Ariane Tremblay';
} elseif ($scenario['event'] === 'reassigned') {
    $scenarioRecipientName = 'Document Remediation Group';
} elseif ($scenario['event'] === 'request_aaact') {
    $scenarioRecipientName = 'AAACT Triage';
} else {
    $scenarioRecipientName = 'AAACT Triage';
}

$sampleContext = [
    'requestid' => 'REQ-26-123',
    'requesttitle' => 'Fix inaccessible PDF on service page',
    'catalogue_name' => 'Digital accessibility support',
    'service_name' => 'Document remediation',
    'teamname' => 'AAACT Triage',
    'recipient_name' => $scenarioRecipientName,
    'status_label' => $scenarioStatusLabel,
];

$sampleMessage = rmt_notification_message(
    $scenario['event'],
    $scenario['recipientType'],
    $previewPayloadLanguage,
    $sampleContext
);
$sampleSubject = rmt_notification_subject(
    $scenario['event'],
    $scenario['recipientType'],
    $previewPayloadLanguage,
    $sampleContext
);

$previewSubjectIntro = $isFrench
    ? 'Le sujet suit la langue d\'origine du destinataire client et reste en anglais pour les messages internes.'
    : 'The subject follows the request language for client mail and stays English-first for internal mail.';

$previewBodyIntro = $isFrench
    ? 'Le corps inclut les deux langues avec une ligne de transition vers le message suivant.'
    : 'The body includes both languages with a transition line before the second message.';

$markdown = $isFrench
    ? "Bonjour,\n\n((message))\n\n- Voir la demande: ((url))\n"
    : "Hello,\n\n((message))\n\n- View request: ((url))\n";

$page = [
    'title' => [
        'en' => 'GC Notify template preview - ' . $definition['name_en'],
        'fr' => 'Aperçu de modèle GC Notify - ' . $definition['name_fr'],
    ],
    'description' => [
        'en' => 'Preview and copy the generic GC Notify template.',
        'fr' => 'Aperçu et copie du modèle GC Notify générique.',
    ],
];

$pageTitle = $page['title'][$lang];
$pageDescription = $page['description'][$lang];

include '../includes/template/head.php';
?>
<?php include '../includes/template/header.php'; ?>
<main role="main" property="mainContentOfPage" class="container">
    <h1 property="name" id="wb-cont"><?php echo htmlspecialchars($pageTitle); ?></h1>

    <p><?php echo htmlspecialchars($pageDescription); ?></p>

    <form class="well" method="get" action="/templates/notification-generic.php">
        <input type="hidden" name="lang" value="<?php echo htmlspecialchars($lang); ?>">
        <div class="form-group">
            <label for="message" class="field-name"><?php echo $isFrench ? 'Message à prévisualiser' : 'Message to preview'; ?></label>
            <select id="message" name="message" class="form-control" onchange="this.form.submit()">
                <?php foreach ($messageScenarios as $scenarioKey => $option): ?>
                    <option value="<?php echo htmlspecialchars($scenarioKey); ?>" <?php echo $scenarioKey === $selectedScenario ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($isFrench ? $option['label_fr'] : $option['label_en']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <p class="mrgn-bttm-0"><?php echo htmlspecialchars($scenarioDescription); ?></p>
    </form>

    <section class="well" aria-labelledby="template-info-heading">
        <h2 id="template-info-heading"><?php echo $isFrench ? 'Information sur le modèle' : 'Template information'; ?></h2>
        <dl class="dl-horizontal">
            <dt><?php echo $isFrench ? 'Nom du modèle' : 'Template name'; ?></dt>
            <dd><?php echo htmlspecialchars($isFrench ? $definition['name_fr'] : $definition['name_en']); ?></dd>

            <dt><?php echo $isFrench ? 'Destinataires' : 'Audience'; ?></dt>
            <dd><?php echo htmlspecialchars($isFrench ? $definition['audience_fr'] : $definition['audience_en']); ?></dd>

            <dt><?php echo $isFrench ? 'Usage' : 'Purpose'; ?></dt>
            <dd><?php echo htmlspecialchars($isFrench ? $definition['purpose_fr'] : $definition['purpose_en']); ?></dd>

            <dt><?php echo $isFrench ? 'Sujet suggéré' : 'Suggested subject'; ?></dt>
            <dd>
                <p class="mrgn-bttm-sm"><?php echo htmlspecialchars($previewSubjectIntro); ?></p>
                <code><?php echo htmlspecialchars($sampleSubject); ?></code>
            </dd>

            <dt><?php echo $isFrench ? 'ID du modèle GC Notify' : 'GC Notify template ID'; ?></dt>
            <dd><code><?php echo htmlspecialchars($configuredTemplateId !== '' ? $configuredTemplateId : 'unset'); ?></code></dd>

            <dt><?php echo $isFrench ? 'Variable de configuration' : 'Setting key'; ?></dt>
            <dd><code><?php echo htmlspecialchars($previewTemplateKey); ?></code></dd>

            <dt><?php echo $isFrench ? 'Variables utilisées' : 'Variables used'; ?></dt>
            <dd><code>((subject))</code>, <code>((message))</code>, <code>((requestid))</code>, <code>((url))</code></dd>
        </dl>
    </section>

    <section class="panel panel-default" aria-labelledby="template-preview-heading">
        <header class="panel-heading">
            <h2 id="template-preview-heading" class="panel-title"><?php echo $isFrench ? 'Aperçu du message' : 'Message preview'; ?></h2>
        </header>
        <div class="panel-body">
            <p><?php echo $isFrench ? 'Aperçu du gabarit avec le message sélectionné injecté par l application.' : 'Template preview with the selected message injected by the application.'; ?></p>
            <p><?php echo htmlspecialchars($previewBodyIntro); ?></p>
            <section class="well well-sm">
                <h3 class="h4 mrgn-tp-0"><?php echo htmlspecialchars($isFrench ? 'Aperçu du rendu envoyé' : 'Preview of sent payload'); ?></h3>
                <p><strong><?php echo htmlspecialchars($sampleSubject); ?></strong></p>
                <p><?php echo nl2br(htmlspecialchars($sampleMessage, ENT_QUOTES, 'UTF-8')); ?></p>
                <p><a href="#"><?php echo htmlspecialchars($isFrench ? 'Voir la demande' : 'View request'); ?> REQ-26-123</a></p>
            </section>
        </div>
    </section>

    <section class="panel panel-default" aria-labelledby="template-markdown-heading">
        <header class="panel-heading">
            <h2 id="template-markdown-heading" class="panel-title"><?php echo $isFrench ? 'Source markdown' : 'Markdown source'; ?></h2>
        </header>
        <div class="panel-body">
            <p class="mrgn-bttm-md"><?php echo $isFrench
                ? 'Copiez ce markdown dans GC Notify pour ce modèle.'
                : 'Copy this markdown into GC Notify for this template.'; ?></p>
            <label for="templateMarkdown" class="wb-inv"><?php echo $isFrench ? 'Contenu markdown du modèle' : 'Template markdown content'; ?></label>
            <textarea id="templateMarkdown" class="form-control" style="width:100%; max-width:100%;" rows="10" readonly><?php echo htmlspecialchars($markdown); ?></textarea>
            <div class="mrgn-tp-md">
                <button id="copyMarkdownBtn" type="button" class="btn btn-primary">
                    <?php echo $isFrench ? 'Copier le markdown' : 'Copy markdown'; ?>
                </button>
                <a class="btn btn-default" href="/templates/?lang=<?php echo urlencode($lang); ?>"><?php echo $isFrench ? 'Voir tous les modèles' : 'View all templates'; ?></a>
                <span id="copyStatus" class="mrgn-lft-sm" aria-live="polite"></span>
            </div>
        </div>
    </section>

    <?php include '../includes/template/page-details.php'; ?>
</main>

<?php include '../includes/template/footer.php'; include '../includes/template/scripts.php'; ?>
<script>
(function () {
    var copyButton = document.getElementById('copyMarkdownBtn');
    var textarea = document.getElementById('templateMarkdown');
    var status = document.getElementById('copyStatus');
    if (!copyButton || !textarea || !status) {
        return;
    }

    copyButton.addEventListener('click', function () {
        var onSuccess = function () {
            status.textContent = <?php echo json_encode($isFrench ? 'Markdown copié.' : 'Markdown copied.'); ?>;
        };

        var onFailure = function () {
            status.textContent = <?php echo json_encode($isFrench ? 'Impossible de copier automatiquement. Sélectionnez le texte et copiez-le manuellement.' : 'Unable to copy automatically. Select the text and copy it manually.'); ?>;
        };

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(textarea.value).then(onSuccess).catch(onFailure);
            return;
        }

        try {
            textarea.focus();
            textarea.select();
            var copied = document.execCommand('copy');
            status.textContent = copied
                ? <?php echo json_encode($isFrench ? 'Markdown copié.' : 'Markdown copied.'); ?>
                : <?php echo json_encode($isFrench ? 'Impossible de copier automatiquement. Sélectionnez le texte et copiez-le manuellement.' : 'Unable to copy automatically. Select the text and copy it manually.'); ?>;
        } catch (error) {
            onFailure();
        }
    });
})();
</script>
</body>
</html>
<?php
mysqli_close($link);

