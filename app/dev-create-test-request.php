<?php
require('sql.php');
/** @var mysqli $link */
require('includes/httpscheck.php');
require('includes/helpers.php');
require('includes/loggedincheck.php');
require('emailController.php');

$lang = detectLanguage();
$t = require("lang/{$lang}.php");

if (app_is_production()) {
    header("location:/settings.php?lang={$lang}&status=forbidden");
    exit();
}

if (!(($_SESSION['is_superuser'] ?? 0) || ($_SESSION['is_admin'] ?? 0))) {
    header("location:/settings.php?lang={$lang}&status=forbidden");
    exit();
}

$page = [
    'title' => [
        'en' => 'Quick test request',
        'fr' => 'Demande de test rapide',
    ],
    'description' => [
        'en' => 'Create a prefilled request quickly for notification testing.',
        'fr' => 'Creer rapidement une demande pre-remplie pour tester les notifications.',
    ],
];

$pageTitle = $page['title'][$lang];
$pageDescription = $page['description'][$lang];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requestLanguage = app_normalize_language($lang, $lang);

    $clientEmail = trim((string) ($_SESSION['email'] ?? ''));
    if ($clientEmail === '' || !filter_var($clientEmail, FILTER_VALIDATE_EMAIL)) {
        $clientEmail = (string) app_setting('GCNOTIFY_TEST_EMAIL', '');
    }

    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_POST = [
        'catalogueid' => '3',
        'serviceid' => '34',
        'subserviceid' => '104',
        'reauditFlag' => '0',
        'requesttitle' => $requestLanguage === 'fr' ? 'Demande de test rapide' : 'Quick test request',
        'clientlname' => $requestLanguage === 'fr' ? 'Test' : 'Test',
        'clientfname' => $requestLanguage === 'fr' ? 'Rapide' : 'Quick',
        'clientemail' => $clientEmail,
        'departmentagency' => $requestLanguage === 'fr' ? 'Essai developpement' : 'Development test',
        'clientphone' => '613-555-0100',
        'bdm' => '',
        'attach1' => '',
        'attach2' => '',
        'attach3' => '',
        'clientnotes' => $requestLanguage === 'fr'
            ? 'Demande de test rapide creee depuis l utilitaire de developpement.'
            : 'Quick test request created from the development utility.',
        'additionalinfo' => $requestLanguage === 'fr'
            ? 'Utiliser cette demande pour valider le routage et les notifications.'
            : 'Use this request to validate routing and notifications.',
        'notification' => 'Y',
        'afterfact' => 'N',
        'sprintdefects' => '',
        'sprintschedule' => '',
        'daterequired' => '',
        'firstsprintstartdate' => '',
        'firstsprintenddate' => '',
    ];
    $_FILES = [];

    include __DIR__ . '/openrequest3.php';
    exit();
}

include 'includes/template/head.php';
?>
<?php include 'includes/template/header.php'; ?>
<main role="main" property="mainContentOfPage" class="container">
    <h1 property="name" id="wb-cont"><?php echo htmlspecialchars($pageTitle); ?></h1>

    <p><?php echo htmlspecialchars($page['description'][$lang]); ?></p>

    <div class="alert alert-info">
        <p><?php echo $lang === 'fr'
            ? 'Cette demande de test sera creee et enregistree en francais. Pour tester une demande en anglais, changez la langue de l application vers anglais avant de la creer.'
            : 'This test request will be created and stored as English. To test a French request, switch the app language to French before creating it.'; ?></p>
    </div>

    <form method="post" action="/dev-create-test-request.php?lang=<?php echo urlencode($lang); ?>">
        <div class="form-group form-buttons">
            <button type="submit" class="btn btn-primary"><?php echo $lang === 'fr' ? 'Créer la demande de test' : 'Create test request'; ?></button>
        </div>
    </form>
</main>
<?php include 'includes/template/footer.php'; include 'includes/template/scripts.php'; ?>
</body>
</html>
