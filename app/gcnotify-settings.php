<?php
// Bootstrap
require('sql.php');
/** @var mysqli $link */
require('includes/httpscheck.php');
require('includes/helpers.php');
require('includes/loggedincheck.php');

$lang = detectLanguage();
$t = require("lang/{$lang}.php");
$isFrench = ($lang === 'fr');

if (!(($_SESSION['is_superuser'] ?? 0) || ($_SESSION['is_admin'] ?? 0))) {
    header("location:/settings.php?lang={$lang}&status=forbidden");
    exit();
}

app_settings_table_ensure($link);

$configKeys = [
    'APP_BASE_URL',
    'NOTIFY_MODE',
    'NOTIFY_REDIRECT_FORCE_OVERRIDE',
    'NOTIFY_OVERRIDE_EMAIL',
    'NOTIFY_OVERRIDE_CLIENT_EMAIL',
    'NOTIFY_OVERRIDE_INTERNAL_EMAIL',
    'GCNOTIFY_TEST_EMAIL',
    'GCNOTIFY_CURL_CA_BUNDLE',
    'GCNOTIFY_CURL_INSECURE',
    'GCNOTIFY_TEMPLATE_REQUEST_TEAM_EN',
    'GCNOTIFY_TEMPLATE_REQUEST_TEAM_FR',
    'GCNOTIFY_TEMPLATE_REQUEST_AFTERFACT_TEAM_EN',
    'GCNOTIFY_TEMPLATE_REQUEST_AFTERFACT_TEAM_FR',
    'GCNOTIFY_TEMPLATE_REQUEST_AAACT',
    'GCNOTIFY_TEMPLATE_REQUEST_CLIENT_EN',
    'GCNOTIFY_TEMPLATE_REQUEST_CLIENT_FR',
    'GCNOTIFY_TEMPLATE_REQUEST_DEFAULT_TEAM_EN',
    'GCNOTIFY_TEMPLATE_REQUEST_DEFAULT_TEAM_FR',
    'GCNOTIFY_TEMPLATE_REQUEST_DEFAULT_CLIENT_EN',
    'GCNOTIFY_TEMPLATE_REQUEST_DEFAULT_CLIENT_FR',
    'GCNOTIFY_TEMPLATE_RESOLVED_TEAM_EN',
    'GCNOTIFY_TEMPLATE_RESOLVED_TEAM_FR',
    'GCNOTIFY_TEMPLATE_RESOLVED_CLIENT_EN',
    'GCNOTIFY_TEMPLATE_RESOLVED_CLIENT_FR',
    'GCNOTIFY_TEMPLATE_STATUS_CHANGED_CLIENT_EN',
    'GCNOTIFY_TEMPLATE_STATUS_CHANGED_CLIENT_FR',
    'GCNOTIFY_TEMPLATE_REASSIGNED_TEAM_EN',
    'GCNOTIFY_TEMPLATE_REASSIGNED_TEAM_FR',
    'GCNOTIFY_TEMPLATE_REASSIGNED_CLIENT_EN',
    'GCNOTIFY_TEMPLATE_REASSIGNED_CLIENT_FR',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $updatedBy = (int) ($_SESSION['pid'] ?? 0);

    foreach ($configKeys as $key) {
        $value = isset($_POST[$key]) ? trim((string) $_POST[$key]) : '';

        if ($key === 'GCNOTIFY_CURL_INSECURE' || $key === 'NOTIFY_REDIRECT_FORCE_OVERRIDE') {
            $value = in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true) ? 'true' : 'false';
        }

        $escapedKey = mysqli_real_escape_string($link, $key);
        $escapedValue = mysqli_real_escape_string($link, $value);

        $sql = "INSERT INTO tblappconfig (config_key, config_value, updated_by, status)
                VALUES ('{$escapedKey}', '{$escapedValue}', {$updatedBy}, 1)
                ON DUPLICATE KEY UPDATE
                    config_value = VALUES(config_value),
                    updated_by = VALUES(updated_by),
                    status = 1";
        mysqli_query($link, $sql);
    }

    header("location:/gcnotify-settings.php?lang={$lang}&status=saved");
    exit();
}

$values = [];
foreach ($configKeys as $key) {
    $values[$key] = (string) app_setting($key, '');
}

$status = $_GET['status'] ?? '';

$page = [
    'title' => [
        'en' => 'GC Notify settings',
        'fr' => 'Parametres GC Notify',
    ],
    'description' => [
        'en' => 'Manage non-secret GC Notify settings',
        'fr' => 'Gerer les parametres non sensibles de GC Notify',
    ],
];

$pageTitle = $page['title'][$lang];
$pageDescription = $page['description'][$lang];

include 'includes/template/head.php';
?>
<?php include 'includes/template/header.php'; ?>
<main role="main" property="mainContentOfPage" class="container">
    <h1 property="name" id="wb-cont"><?php echo htmlspecialchars($t['gcnotify_settings_heading']); ?></h1>

    <p><?php echo htmlspecialchars($t['gcnotify_settings_intro']); ?></p>

    <?php if ($status === 'saved'): ?>
        <div class="alert alert-success">
            <p><?php echo htmlspecialchars($t['gcnotify_settings_saved']); ?></p>
        </div>
    <?php endif; ?>

    <div class="alert alert-info">
        <p><strong><?php echo htmlspecialchars($t['gcnotify_settings_secret_note_title']); ?></strong></p>
        <p><?php echo htmlspecialchars($t['gcnotify_settings_secret_note_body']); ?></p>
    </div>

    <form method="post" action="/gcnotify-settings.php?lang=<?php echo urlencode($lang); ?>">
        <fieldset class="mrgn-bttm-lg">
            <legend class="h2"><?php echo htmlspecialchars($t['gcnotify_settings_delivery_heading']); ?></legend>

        <?php
        $deliveryFieldMeta = [
            'NOTIFY_MODE' => [
                'label' => [
                    'en' => 'Delivery mode',
                    'fr' => 'Mode de distribution',
                ],
                'description' => [
                    'en' => 'Controls how emails are sent: live sends real emails, redirect reroutes emails, disabled sends nothing.',
                    'fr' => 'Controle la facon d envoyer les courriels : live envoie reellement, redirect reroute les courriels, disabled n envoie rien.',
                ],
            ],
            'NOTIFY_REDIRECT_FORCE_OVERRIDE' => [
                'label' => [
                    'en' => 'Force redirect recipient',
                    'fr' => 'Forcer le destinataire de redirection',
                ],
                'description' => [
                    'en' => 'When true in redirect mode, all notifications are forced to the main redirect email address.',
                    'fr' => 'Quand vrai en mode redirect, toutes les notifications sont forcees vers l adresse principale de redirection.',
                ],
            ],
            'APP_BASE_URL' => [
                'label' => [
                    'en' => 'Application base URL',
                    'fr' => 'URL de base de l application',
                ],
                'description' => [
                    'en' => 'Public base URL used to build links included in notification emails.',
                    'fr' => 'URL publique de base utilisee pour construire les liens dans les courriels de notification.',
                ],
            ],
            'NOTIFY_OVERRIDE_EMAIL' => [
                'label' => [
                    'en' => 'Redirect email (all notifications)',
                    'fr' => 'Courriel de redirection (toutes les notifications)',
                ],
                'description' => [
                    'en' => 'Primary mailbox used when redirect mode routes all notifications to one address.',
                    'fr' => 'Boite principale utilisee lorsque le mode redirect route toutes les notifications vers une seule adresse.',
                ],
            ],
            'NOTIFY_OVERRIDE_CLIENT_EMAIL' => [
                'label' => [
                    'en' => 'Redirect email (client notifications)',
                    'fr' => 'Courriel de redirection (notifications client)',
                ],
                'description' => [
                    'en' => 'Optional mailbox for client-facing notifications when redirect mode is enabled.',
                    'fr' => 'Boite optionnelle pour les notifications destinees aux clients lorsque le mode redirect est active.',
                ],
            ],
            'NOTIFY_OVERRIDE_INTERNAL_EMAIL' => [
                'label' => [
                    'en' => 'Redirect email (internal notifications)',
                    'fr' => 'Courriel de redirection (notifications internes)',
                ],
                'description' => [
                    'en' => 'Optional mailbox for internal team notifications when redirect mode is enabled.',
                    'fr' => 'Boite optionnelle pour les notifications internes aux equipes lorsque le mode redirect est active.',
                ],
            ],
            'GCNOTIFY_TEST_EMAIL' => [
                'label' => [
                    'en' => 'GC Notify test recipient email',
                    'fr' => 'Courriel de destinataire de test GC Notify',
                ],
                'description' => [
                    'en' => 'Safe-list test recipient used for diagnostics and trial sends in development environments.',
                    'fr' => 'Destinataire de test en liste autorisee utilise pour les diagnostics et envois d essai en developpement.',
                ],
            ],
            'GCNOTIFY_CURL_CA_BUNDLE' => [
                'label' => [
                    'en' => 'Custom CA bundle path',
                    'fr' => 'Chemin du bundle CA personnalise',
                ],
                'description' => [
                    'en' => 'Optional certificate bundle path for TLS trust when connecting to GC Notify.',
                    'fr' => 'Chemin optionnel du bundle de certificats pour la confiance TLS lors de la connexion a GC Notify.',
                ],
            ],
            'GCNOTIFY_CURL_INSECURE' => [
                'label' => [
                    'en' => 'Disable TLS verification (diagnostic only)',
                    'fr' => 'Desactiver la verification TLS (diagnostic seulement)',
                ],
                'description' => [
                    'en' => 'When true, disables certificate verification. Use only for short-term troubleshooting.',
                    'fr' => 'Quand vrai, desactive la verification de certificat. Utiliser seulement pour le depannage a court terme.',
                ],
            ],
        ];

        $selectOptions = [
            'NOTIFY_MODE' => ['live', 'redirect', 'disabled'],
            'NOTIFY_REDIRECT_FORCE_OVERRIDE' => ['false', 'true'],
            'GCNOTIFY_CURL_INSECURE' => ['false', 'true'],
        ];

        $defaultValues = [
            'NOTIFY_MODE' => 'redirect',
            'NOTIFY_REDIRECT_FORCE_OVERRIDE' => 'false',
            'GCNOTIFY_CURL_INSECURE' => 'false',
        ];

        foreach ($deliveryFieldMeta as $key => $meta):
            $label = $meta['label'][$lang] ?? $key;
            $description = $meta['description'][$lang] ?? '';
            $labelId = $key . '-label';
            $descId = $key . '-desc';
            $currentValue = strtolower(trim((string) ($values[$key] ?? '')));
            if ($currentValue === '' && isset($defaultValues[$key])) {
                $currentValue = $defaultValues[$key];
            }
        ?>
            <div class="form-group">
                <label id="<?php echo htmlspecialchars($labelId); ?>" for="<?php echo htmlspecialchars($key); ?>">
                    <span class="field-name"><?php echo htmlspecialchars($label); ?></span>
                </label>
                <p id="<?php echo htmlspecialchars($descId); ?>" class="help-block"><?php echo htmlspecialchars($description); ?></p>
                <?php if (isset($selectOptions[$key])): ?>
                    <select
                        class="form-control"
                        id="<?php echo htmlspecialchars($key); ?>"
                        name="<?php echo htmlspecialchars($key); ?>"
                        aria-describedby="<?php echo htmlspecialchars($descId); ?>"
                    >
                        <?php foreach ($selectOptions[$key] as $option): ?>
                            <option value="<?php echo htmlspecialchars($option); ?>" <?php echo $currentValue === $option ? 'selected' : ''; ?>><?php echo htmlspecialchars($option); ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php else: ?>
                    <input
                        type="text"
                        class="form-control"
                        id="<?php echo htmlspecialchars($key); ?>"
                        name="<?php echo htmlspecialchars($key); ?>"
                        value="<?php echo htmlspecialchars((string) ($values[$key] ?? '')); ?>"
                        aria-describedby="<?php echo htmlspecialchars($descId); ?>"
                    >
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        </fieldset>

        <fieldset class="mrgn-bttm-lg" aria-describedby="gcnotify-templates-help">
            <legend class="h2"><?php echo htmlspecialchars($t['gcnotify_settings_templates_heading']); ?></legend>
            <p id="gcnotify-templates-help" class="help-block"><?php echo htmlspecialchars($t['gcnotify_settings_templates_intro']); ?></p>

        <?php
        $templateKeys = [
            'GCNOTIFY_TEMPLATE_REQUEST_TEAM_EN',
            'GCNOTIFY_TEMPLATE_REQUEST_TEAM_FR',
            'GCNOTIFY_TEMPLATE_REQUEST_AFTERFACT_TEAM_EN',
            'GCNOTIFY_TEMPLATE_REQUEST_AFTERFACT_TEAM_FR',
            'GCNOTIFY_TEMPLATE_REQUEST_AAACT',
            'GCNOTIFY_TEMPLATE_REQUEST_CLIENT_EN',
            'GCNOTIFY_TEMPLATE_REQUEST_CLIENT_FR',
            'GCNOTIFY_TEMPLATE_REQUEST_DEFAULT_TEAM_EN',
            'GCNOTIFY_TEMPLATE_REQUEST_DEFAULT_TEAM_FR',
            'GCNOTIFY_TEMPLATE_REQUEST_DEFAULT_CLIENT_EN',
            'GCNOTIFY_TEMPLATE_REQUEST_DEFAULT_CLIENT_FR',
            'GCNOTIFY_TEMPLATE_RESOLVED_TEAM_EN',
            'GCNOTIFY_TEMPLATE_RESOLVED_TEAM_FR',
            'GCNOTIFY_TEMPLATE_RESOLVED_CLIENT_EN',
            'GCNOTIFY_TEMPLATE_RESOLVED_CLIENT_FR',
            'GCNOTIFY_TEMPLATE_STATUS_CHANGED_CLIENT_EN',
            'GCNOTIFY_TEMPLATE_STATUS_CHANGED_CLIENT_FR',
            'GCNOTIFY_TEMPLATE_REASSIGNED_TEAM_EN',
            'GCNOTIFY_TEMPLATE_REASSIGNED_TEAM_FR',
            'GCNOTIFY_TEMPLATE_REASSIGNED_CLIENT_EN',
            'GCNOTIFY_TEMPLATE_REASSIGNED_CLIENT_FR',
        ];

        $templateMeta = [
            'GCNOTIFY_TEMPLATE_REQUEST_TEAM_EN' => [
                'label' => [
                    'en' => 'New request to assigned team (English)',
                    'fr' => 'Nouvelle demande a l equipe assignee (anglais)',
                ],
                'description' => [
                    'en' => 'Template used for a new request email sent to the assigned team in English.',
                    'fr' => 'Gabarit utilise pour un courriel de nouvelle demande envoye a l equipe assignee en anglais.',
                ],
            ],
            'GCNOTIFY_TEMPLATE_REQUEST_TEAM_FR' => [
                'label' => [
                    'en' => 'New request to assigned team (French)',
                    'fr' => 'Nouvelle demande a l equipe assignee (francais)',
                ],
                'description' => [
                    'en' => 'Template used for a new request email sent to the assigned team in French.',
                    'fr' => 'Gabarit utilise pour un courriel de nouvelle demande envoye a l equipe assignee en francais.',
                ],
            ],
            'GCNOTIFY_TEMPLATE_REQUEST_AFTERFACT_TEAM_EN' => [
                'label' => [
                    'en' => 'After-fact request to assigned team (English)',
                    'fr' => 'Demande apres coup a l equipe assignee (anglais)',
                ],
                'description' => [
                    'en' => 'Template used for after-fact request notifications to the assigned team in English.',
                    'fr' => 'Gabarit utilise pour les notifications de demande apres coup a l equipe assignee en anglais.',
                ],
            ],
            'GCNOTIFY_TEMPLATE_REQUEST_AFTERFACT_TEAM_FR' => [
                'label' => [
                    'en' => 'After-fact request to assigned team (French)',
                    'fr' => 'Demande apres coup a l equipe assignee (francais)',
                ],
                'description' => [
                    'en' => 'Template used for after-fact request notifications to the assigned team in French.',
                    'fr' => 'Gabarit utilise pour les notifications de demande apres coup a l equipe assignee en francais.',
                ],
            ],
            'GCNOTIFY_TEMPLATE_REQUEST_AAACT' => [
                'label' => [
                    'en' => 'New request to AAACT internal mailbox',
                    'fr' => 'Nouvelle demande a la boite interne AATIA',
                ],
                'description' => [
                    'en' => 'Template used for internal AAACT notifications when a new request is created.',
                    'fr' => 'Gabarit utilise pour les notifications internes AATIA lorsqu une nouvelle demande est creee.',
                ],
            ],
            'GCNOTIFY_TEMPLATE_REQUEST_CLIENT_EN' => [
                'label' => [
                    'en' => 'New request to client (English)',
                    'fr' => 'Nouvelle demande au client (anglais)',
                ],
                'description' => [
                    'en' => 'Template used for client confirmation when a new request is submitted in English.',
                    'fr' => 'Gabarit utilise pour la confirmation au client lorsqu une nouvelle demande est soumise en anglais.',
                ],
            ],
            'GCNOTIFY_TEMPLATE_REQUEST_CLIENT_FR' => [
                'label' => [
                    'en' => 'New request to client (French)',
                    'fr' => 'Nouvelle demande au client (francais)',
                ],
                'description' => [
                    'en' => 'Template used for client confirmation when a new request is submitted in French.',
                    'fr' => 'Gabarit utilise pour la confirmation au client lorsqu une nouvelle demande est soumise en francais.',
                ],
            ],
            'GCNOTIFY_TEMPLATE_REQUEST_DEFAULT_TEAM_EN' => [
                'label' => [
                    'en' => 'Default service request to team (English)',
                    'fr' => 'Demande service par defaut a l equipe (anglais)',
                ],
                'description' => [
                    'en' => 'Template used for team notifications when the request follows the default service flow in English.',
                    'fr' => 'Gabarit utilise pour les notifications a l equipe lorsque la demande suit le flux de service par defaut en anglais.',
                ],
            ],
            'GCNOTIFY_TEMPLATE_REQUEST_DEFAULT_TEAM_FR' => [
                'label' => [
                    'en' => 'Default service request to team (French)',
                    'fr' => 'Demande service par defaut a l equipe (francais)',
                ],
                'description' => [
                    'en' => 'Template used for team notifications when the request follows the default service flow in French.',
                    'fr' => 'Gabarit utilise pour les notifications a l equipe lorsque la demande suit le flux de service par defaut en francais.',
                ],
            ],
            'GCNOTIFY_TEMPLATE_REQUEST_DEFAULT_CLIENT_EN' => [
                'label' => [
                    'en' => 'Default service request to client (English)',
                    'fr' => 'Demande service par defaut au client (anglais)',
                ],
                'description' => [
                    'en' => 'Template used for client emails in the default service flow in English.',
                    'fr' => 'Gabarit utilise pour les courriels au client dans le flux de service par defaut en anglais.',
                ],
            ],
            'GCNOTIFY_TEMPLATE_REQUEST_DEFAULT_CLIENT_FR' => [
                'label' => [
                    'en' => 'Default service request to client (French)',
                    'fr' => 'Demande service par defaut au client (francais)',
                ],
                'description' => [
                    'en' => 'Template used for client emails in the default service flow in French.',
                    'fr' => 'Gabarit utilise pour les courriels au client dans le flux de service par defaut en francais.',
                ],
            ],
            'GCNOTIFY_TEMPLATE_RESOLVED_TEAM_EN' => [
                'label' => [
                    'en' => 'Resolved notification to team (English)',
                    'fr' => 'Notification resolue a l equipe (anglais)',
                ],
                'description' => [
                    'en' => 'Template used when a request is marked resolved and a team notification is sent in English.',
                    'fr' => 'Gabarit utilise lorsqu une demande est marquee resolue et qu une notification d equipe est envoyee en anglais.',
                ],
            ],
            'GCNOTIFY_TEMPLATE_RESOLVED_TEAM_FR' => [
                'label' => [
                    'en' => 'Resolved notification to team (French)',
                    'fr' => 'Notification resolue a l equipe (francais)',
                ],
                'description' => [
                    'en' => 'Template used when a request is marked resolved and a team notification is sent in French.',
                    'fr' => 'Gabarit utilise lorsqu une demande est marquee resolue et qu une notification d equipe est envoyee en francais.',
                ],
            ],
            'GCNOTIFY_TEMPLATE_RESOLVED_CLIENT_EN' => [
                'label' => [
                    'en' => 'Resolved notification to client (English)',
                    'fr' => 'Notification resolue au client (anglais)',
                ],
                'description' => [
                    'en' => 'Template used when a request is resolved and the client is notified in English.',
                    'fr' => 'Gabarit utilise lorsqu une demande est resolue et que le client est avise en anglais.',
                ],
            ],
            'GCNOTIFY_TEMPLATE_RESOLVED_CLIENT_FR' => [
                'label' => [
                    'en' => 'Resolved notification to client (French)',
                    'fr' => 'Notification resolue au client (francais)',
                ],
                'description' => [
                    'en' => 'Template used when a request is resolved and the client is notified in French.',
                    'fr' => 'Gabarit utilise lorsqu une demande est resolue et que le client est avise en francais.',
                ],
            ],
            'GCNOTIFY_TEMPLATE_STATUS_CHANGED_CLIENT_EN' => [
                'label' => [
                    'en' => 'Status changed notification to client (English)',
                    'fr' => 'Notification de changement de statut au client (anglais)',
                ],
                'description' => [
                    'en' => 'Template used when a request status changes and a client update is sent in English.',
                    'fr' => 'Gabarit utilise lorsqu un statut de demande change et qu une mise a jour client est envoyee en anglais.',
                ],
            ],
            'GCNOTIFY_TEMPLATE_STATUS_CHANGED_CLIENT_FR' => [
                'label' => [
                    'en' => 'Status changed notification to client (French)',
                    'fr' => 'Notification de changement de statut au client (francais)',
                ],
                'description' => [
                    'en' => 'Template used when a request status changes and a client update is sent in French.',
                    'fr' => 'Gabarit utilise lorsqu un statut de demande change et qu une mise a jour client est envoyee en francais.',
                ],
            ],
            'GCNOTIFY_TEMPLATE_REASSIGNED_TEAM_EN' => [
                'label' => [
                    'en' => 'Reassigned notification to team (English)',
                    'fr' => 'Notification de reattribution a l equipe (anglais)',
                ],
                'description' => [
                    'en' => 'Template used when a request is reassigned and the destination team is notified in English.',
                    'fr' => 'Gabarit utilise lorsqu une demande est reattribuee et que l equipe destinataire est avisee en anglais.',
                ],
            ],
            'GCNOTIFY_TEMPLATE_REASSIGNED_TEAM_FR' => [
                'label' => [
                    'en' => 'Reassigned notification to team (French)',
                    'fr' => 'Notification de reattribution a l equipe (francais)',
                ],
                'description' => [
                    'en' => 'Template used when a request is reassigned and the destination team is notified in French.',
                    'fr' => 'Gabarit utilise lorsqu une demande est reattribuee et que l equipe destinataire est avisee en francais.',
                ],
            ],
            'GCNOTIFY_TEMPLATE_REASSIGNED_CLIENT_EN' => [
                'label' => [
                    'en' => 'Reassigned notification to client (English)',
                    'fr' => 'Notification de reattribution au client (anglais)',
                ],
                'description' => [
                    'en' => 'Template used when a request is reassigned and the client update is sent in English.',
                    'fr' => 'Gabarit utilise lorsqu une demande est reattribuee et qu une mise a jour client est envoyee en anglais.',
                ],
            ],
            'GCNOTIFY_TEMPLATE_REASSIGNED_CLIENT_FR' => [
                'label' => [
                    'en' => 'Reassigned notification to client (French)',
                    'fr' => 'Notification de reattribution au client (francais)',
                ],
                'description' => [
                    'en' => 'Template used when a request is reassigned and the client update is sent in French.',
                    'fr' => 'Gabarit utilise lorsqu une demande est reattribuee et qu une mise a jour client est envoyee en francais.',
                ],
            ],
        ];

        foreach ($templateKeys as $key):
            $label = $templateMeta[$key]['label'][$lang] ?? $key;
            $description = $templateMeta[$key]['description'][$lang] ?? '';
            $labelId = $key . '-label';
            $descId = $key . '-desc';
        ?>
            <div class="form-group">
                <label id="<?php echo htmlspecialchars($labelId); ?>" for="<?php echo htmlspecialchars($key); ?>">
                    <span class="field-name"><?php echo htmlspecialchars($label); ?></span>
                </label>
                <p id="<?php echo htmlspecialchars($descId); ?>" class="help-block"><?php echo htmlspecialchars($description); ?></p>
                <input
                    type="text"
                    class="form-control"
                    id="<?php echo htmlspecialchars($key); ?>"
                    name="<?php echo htmlspecialchars($key); ?>"
                    value="<?php echo htmlspecialchars($values[$key]); ?>"
                    placeholder="UUID"
                    aria-describedby="<?php echo htmlspecialchars($descId); ?>"
                >
            </div>
        <?php endforeach; ?>
        </fieldset>

        <div class="form-group form-buttons">
            <button type="submit" class="btn btn-primary"><?php echo htmlspecialchars($t['gcnotify_settings_save']); ?></button>
            <a class="btn btn-default" href="/settings.php?lang=<?php echo urlencode($lang); ?>"><?php echo htmlspecialchars($t['gcnotify_settings_back']); ?></a>
        </div>
    </form>

    <?php include 'includes/template/page-details.php'; ?>
</main>
<?php include 'includes/template/footer.php'; include 'includes/template/scripts.php'; ?>
</body>
</html>
