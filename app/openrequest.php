<?php
require_once __DIR__ . '/includes/session_start.php';
require('includes/httpscheck.php');
require('sql.php');
/** @var mysqli $link */

if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'fr'], true)) {
    $_SESSION['lang'] = $_GET['lang'];
}

$langCode = (($_SESSION['lang'] ?? 'en') === 'fr') ? 'fr' : 'en';
$_SESSION['lang'] = $langCode;
$t = require __DIR__ . "/lang/{$langCode}.php";
$status = $_GET['status'] ?? '';
$nameColumn = $langCode === 'fr' ? 'namefr' : 'nameen';
$hierarchyResult = mysqli_query(
    $link,
    "SELECT c.id AS catalogueid, c.{$nameColumn} AS cataloguename,
            s.id AS serviceid, s.{$nameColumn} AS servicename,
            ss.id AS subserviceid, ss.{$nameColumn} AS subservicename
     FROM tblcatalogue c
    LEFT JOIN tblservices s ON s.catalogueid = c.id AND s.status = 1
     LEFT JOIN tblsubservices ss ON ss.serviceid = s.id AND ss.status = 1
     WHERE c.status = 1
     ORDER BY c.{$nameColumn}, s.{$nameColumn}, ss.{$nameColumn}"
);

$hierarchy = [];
while ($row = mysqli_fetch_assoc($hierarchyResult)) {
    $catalogueId = (int) $row['catalogueid'];
    $hierarchy[$catalogueId]['name'] = $row['cataloguename'];

    if ($row['serviceid'] === null) {
        $hierarchy[$catalogueId]['services'] = [];
        continue;
    }

    $serviceId = (int) $row['serviceid'];
    $hierarchy[$catalogueId]['services'][$serviceId]['name'] = $row['servicename'];

    if ($row['subserviceid'] !== null) {
        $hierarchy[$catalogueId]['services'][$serviceId]['subservices'][(int) $row['subserviceid']] = $row['subservicename'];
    }
}

function fieldFlowActions(array $actions): string
{
    return htmlspecialchars(json_encode($actions, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
}

function renderBasicIntakeForm(array $hierarchy, array $t, string $langCode): void
{
    ?>
    <form method="post" action="/openrequest2.php?lang=<?= htmlspecialchars($langCode) ?>">
        <p class="small text-muted"><?= htmlspecialchars($t['intake_instruction']) ?></p>
        <div class="form-group">
            <label for="basic-intake-selection">
                <span class="field-name"><?= htmlspecialchars($t['intake_request_type']) ?></span>
                <strong>(<?= htmlspecialchars($t['required']) ?>)</strong>
            </label>
            <select id="basic-intake-selection" name="intake_selection" class="form-control" required>
                <option value=""><?= htmlspecialchars($t['intake_select_request_type']) ?></option>
                <?php foreach ($hierarchy as $catalogueId => $catalogue): ?>
                <?php if (empty($catalogue['services'])): ?>
                <option value="<?= $catalogueId ?>:0:0"><?= htmlspecialchars($catalogue['name']) ?></option>
                <?php endif; ?>
                <?php foreach ($catalogue['services'] as $serviceId => $service): ?>
                <?php if (empty($service['subservices'])): ?>
                <option value="<?= $catalogueId ?>:<?= $serviceId ?>:0"><?= htmlspecialchars($catalogue['name'] . ' - ' . $service['name']) ?></option>
                <?php else: ?>
                <?php foreach ($service['subservices'] as $subserviceId => $subserviceName): ?>
                <option value="<?= $catalogueId ?>:<?= $serviceId ?>:<?= $subserviceId ?>"><?= htmlspecialchars($catalogue['name'] . ' - ' . $service['name'] . ' - ' . $subserviceName) ?></option>
                <?php endforeach; ?>
                <?php endif; ?>
                <?php endforeach; ?>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary"><?= htmlspecialchars($t['intake_continue']) ?></button>
    </form>
    <?php
}

$basicMode = ($_GET['wbdisable'] ?? '') === 'true';

$pageTitle = $t['main_heading'];
$pageDescription = $t['page_description'];
include 'includes/template/head.php';
?>
<?php include 'includes/template/header.php'; ?>
<main role="main" property="mainContentOfPage" class="container">
    <h1 property="name" id="wb-cont"><?= htmlspecialchars($t['main_heading']) ?></h1>

    <?php if ($status === 'failed'): ?>
    <section id="intake-error" class="alert alert-danger" role="alert">
        <h2><?= htmlspecialchars($t['alert_failed_heading']) ?></h2>
        <p><?= htmlspecialchars($t['alert_failed_message']) ?></p>
    </section>
    <?php elseif ($status === 'accessdenied'): ?>
    <section class="alert alert-danger">
        <h2><?= htmlspecialchars($t['alert_access_denied_heading']) ?></h2>
        <p><?= htmlspecialchars($t['alert_access_denied_message']) ?></p>
    </section>
    <?php endif; ?>

    <?php if ($basicMode): ?>
    <div class="wb-frmvld">
        <?php renderBasicIntakeForm($hierarchy, $t, $langCode); ?>
    </div>
    <?php else: ?>
    <div class="wb-frmvld fieldflow-enhanced">
        <form method="post" action="/openrequest2.php?lang=<?= htmlspecialchars($langCode) ?>">
            <p class="small text-muted"><?= htmlspecialchars($t['intake_instruction']) ?></p>
            <div
                class="wb-fieldflow"
                data-wb-fieldflow='<?= fieldFlowActions([
                    'noForm' => true,
                    'defaultselectedlabel' => $t['select_catalogue'],
                ]) ?>'
            >
                <p><?= htmlspecialchars($t['catalogue_name']) ?></p>
                <ul>
                    <?php foreach ($hierarchy as $catalogueId => $catalogue): ?>
                    <?php
                    if (!empty($catalogue['services'])) {
                        $catalogueActions = [
                            ['action' => 'query', 'name' => 'catalogueid', 'value' => (string) $catalogueId],
                            [
                                'action' => 'append',
                                'source' => "#fieldflow-services-{$catalogueId}",
                                'defaultselectedlabel' => $t['select_service'],
                            ],
                        ];
                    } else {
                        $catalogueActions = [[
                            'action' => 'query',
                            'name' => 'intake_selection',
                            'value' => "{$catalogueId}:0:0",
                        ]];
                    }
                    ?>
                    <li data-wb-fieldflow='<?= fieldFlowActions($catalogueActions) ?>'><?= htmlspecialchars($catalogue['name']) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <?php foreach ($hierarchy as $catalogueId => $catalogue): ?>
            <?php if (!empty($catalogue['services'])): ?>
            <div id="fieldflow-services-<?= $catalogueId ?>" class="hidden">
                <p><?= htmlspecialchars($t['service_name']) ?></p>
                <ul>
                    <?php foreach ($catalogue['services'] as $serviceId => $service): ?>
                    <?php
                    $serviceActions = [
                        ['action' => 'query', 'name' => 'serviceid', 'value' => (string) $serviceId],
                    ];
                    if (!empty($service['subservices'])) {
                        $serviceActions[] = [
                            'action' => 'append',
                            'source' => "#fieldflow-subservices-{$serviceId}",
                            'defaultselectedlabel' => $t['select_subservice'],
                        ];
                    }
                    ?>
                    <li data-wb-fieldflow='<?= fieldFlowActions($serviceActions) ?>'><?= htmlspecialchars($service['name']) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            <?php foreach ($catalogue['services'] as $serviceId => $service): ?>
            <?php if (!empty($service['subservices'])): ?>
            <div id="fieldflow-subservices-<?= $serviceId ?>" class="hidden">
                <p><?= htmlspecialchars($t['subservice_name']) ?></p>
                <ul>
                    <?php foreach ($service['subservices'] as $subserviceId => $subserviceName): ?>
                    <li data-wb-fieldflow='<?= fieldFlowActions([
                        ['action' => 'query', 'name' => 'subserviceid', 'value' => (string) $subserviceId],
                    ]) ?>'><?= htmlspecialchars($subserviceName) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            <?php endforeach; ?>
            <?php endforeach; ?>

            <button type="submit" class="btn btn-primary"><?= htmlspecialchars($t['intake_continue']) ?></button>
        </form>
    </div>
    <noscript>
        <style>
            .fieldflow-enhanced { display: none; }
            .fieldflow-basic-fallback { display: block !important; }
        </style>
        <div class="wb-frmvld fieldflow-basic-fallback hidden">
            <?php renderBasicIntakeForm($hierarchy, $t, $langCode); ?>
        </div>
    </noscript>
    <?php endif; ?>

    <?php include 'includes/template/page-details.php'; ?>
</main>
<?php include 'includes/template/footer.php'; ?>
<?php include 'includes/template/scripts.php'; ?>
</body>
</html>
<?php mysqli_close($link); ?>