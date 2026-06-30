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
    'GCNOTIFY_TEMPLATE_ID',
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

        $statusValue = ($value === '') ? 0 : 1;
        $sql = "INSERT INTO tblappconfig (config_key, config_value, updated_by, status)
                VALUES ('{$escapedKey}', '{$escapedValue}', {$updatedBy}, {$statusValue})
                ON DUPLICATE KEY UPDATE
                    config_value = VALUES(config_value),
                    updated_by = VALUES(updated_by),
                    status = VALUES(status)";
        mysqli_query($link, $sql);
    }

    header("location:/gcnotify-settings.php?lang={$lang}&status=saved");
    exit();
}

$storedValues = app_db_settings_all();
$values = [];
foreach ($configKeys as $key) {
    $values[$key] = (string) ($storedValues[$key] ?? '');
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
            <p class="help-block">
                <?php echo $isFrench
                    ? 'Créez un seul modèle GC Notify avec ((subject)) pour le sujet et ((message)) pour le corps principal du courriel.'
                    : 'Create one GC Notify template with ((subject)) for the subject and ((message)) for the main email body.'; ?>
            </p>
            <p class="help-block">
                <a href="/templates/notification-generic.php?lang=<?php echo urlencode($lang); ?>">
                    <?php echo $isFrench ? 'Voir l apercu du modele generique' : 'View generic template preview'; ?>
                </a>
            </p>

            <div class="form-group">
                <label id="GCNOTIFY_TEMPLATE_ID-label" for="GCNOTIFY_TEMPLATE_ID">
                    <span class="field-name"><?php echo $isFrench ? 'Modele GC Notify generique' : 'Generic GC Notify template'; ?></span>
                </label>
                <p id="GCNOTIFY_TEMPLATE_ID-desc" class="help-block">
                    <?php echo $isFrench
                        ? 'Utilise pour toutes les notifications. Le modele doit contenir ((subject)) et ((message)) et peut inclure ((requestid)) et ((url)) dans le pied de page.'
                        : 'Used for all notifications. The template should contain ((subject)) and ((message)) and can include ((requestid)) and ((url)) in the footer.'; ?>
                </p>
                <input
                    type="text"
                    class="form-control"
                    id="GCNOTIFY_TEMPLATE_ID"
                    name="GCNOTIFY_TEMPLATE_ID"
                    value="<?php echo htmlspecialchars($values['GCNOTIFY_TEMPLATE_ID']); ?>"
                    placeholder="UUID"
                    aria-describedby="GCNOTIFY_TEMPLATE_ID-desc"
                >
            </div>
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
