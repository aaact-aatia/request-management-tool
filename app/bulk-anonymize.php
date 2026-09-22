<?php
require('sql.php');
/** @var mysqli $link */
require_once 'includes/helpers.php';
require_once 'includes/bulk-anonymize-processing.php';
require('includes/httpscheck.php');
require('includes/loggedincheck.php');

$lang = detectLanguage();
$t = require("lang/{$lang}.php");

if (!isSuperAdmin()) {
    header("location:/requests.php?lang={$lang}&status=forbidden");
    exit();
}

if (empty($_SESSION['bulk_anonymize_token'])) {
    $_SESSION['bulk_anonymize_token'] = bin2hex(random_bytes(32));
}

$catalogueStatement = rmt_db_execute($link, 'SELECT id, nameen, namefr FROM tblcatalogue WHERE status = 1 ORDER BY nameen ASC');
$catalogues = [];
$catalogueResult = mysqli_stmt_get_result($catalogueStatement);
while ($catalogue = mysqli_fetch_assoc($catalogueResult)) {
    $catalogues[(int) $catalogue['id']] = $catalogue;
}
mysqli_stmt_close($catalogueStatement);

$selectedCatalogueIds = [];
$excludedServiceIds = [];
$previewCount = null;
$previewToken = '';
$successCount = isset($_GET['count']) ? max(0, (int) $_GET['count']) : null;
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = (string) ($_POST['csrf_token'] ?? '');
    if (!hash_equals((string) $_SESSION['bulk_anonymize_token'], $submittedToken)) {
        $errorMessage = $t['bulk_anon_invalid_request'];
    } else {
        $selectedCatalogueIds = array_values(array_intersect(
            rmt_bulk_anonymize_normalize_ids((array) ($_POST['catalogue_ids'] ?? [])),
            array_keys($catalogues)
        ));
        $excludedServiceIds = rmt_bulk_anonymize_parse_excluded_services((string) ($_POST['excluded_service_ids'] ?? ''));

        if ($selectedCatalogueIds === []) {
            $errorMessage = $t['bulk_anon_select_catalogue_error'];
        } else {
            $criteriaHash = rmt_bulk_anonymize_criteria_hash($selectedCatalogueIds, $excludedServiceIds);
            if ($_POST['action'] === 'preview') {
                $previewCount = rmt_bulk_anonymize_count($link, $selectedCatalogueIds, $excludedServiceIds);
                $previewToken = bin2hex(random_bytes(32));
                $_SESSION['bulk_anonymize_preview'] = $criteriaHash;
                $_SESSION['bulk_anonymize_preview_token'] = $previewToken;
            } elseif ($_POST['action'] === 'run'
                && ($_POST['confirm_run'] ?? '') === '1'
                && hash_equals((string) ($_SESSION['bulk_anonymize_preview'] ?? ''), $criteriaHash)
                && hash_equals((string) ($_SESSION['bulk_anonymize_preview_token'] ?? ''), (string) ($_POST['preview_token'] ?? ''))
            ) {
                $runCount = rmt_bulk_anonymize_count($link, $selectedCatalogueIds, $excludedServiceIds);
                $auditNotes = sprintf(
                    'Bulk anonymization: catalogues=%s; excluded_service_ids=%s; records=%d',
                    implode(',', $selectedCatalogueIds),
                    $excludedServiceIds === [] ? 'none' : implode(',', $excludedServiceIds),
                    $runCount
                );
                $result = rmt_bulk_anonymize_run($link, $selectedCatalogueIds, $excludedServiceIds, $t['bulk_anon_no_details'], $auditNotes, $lang, (int) $_SESSION['pid']);
                unset($_SESSION['bulk_anonymize_preview'], $_SESSION['bulk_anonymize_preview_token']);
                $_SESSION['bulk_anonymize_token'] = bin2hex(random_bytes(32));
                header("location:/bulk-anonymize.php?lang={$lang}&status=success&count={$result['request_count']}");
                exit();
            } else {
                $errorMessage = $t['bulk_anon_preview_required'];
            }
        }
    }
}

$pageTitle = $t['bulk_anon_title'];
$pageDescription = $t['bulk_anon_intro'];
include 'includes/template/head.php';
include 'includes/template/header.php';
?>
<main role="main" property="mainContentOfPage" class="container">
    <h1 property="name" id="wb-cont"><?= htmlspecialchars($t['bulk_anon_title']) ?></h1>
    <?php if ($errorMessage !== ''): ?>
        <section class="alert alert-danger" aria-labelledby="bulk-anon-error" tabindex="-1">
            <h2 id="bulk-anon-error"><?= htmlspecialchars($t['bulk_anon_error_heading']) ?></h2>
            <p><?= htmlspecialchars($errorMessage) ?></p>
        </section>
    <?php elseif ($successCount !== null && ($_GET['status'] ?? '') === 'success'): ?>
        <section class="alert alert-success" aria-labelledby="bulk-anon-success" tabindex="-1">
            <h2 id="bulk-anon-success"><?= htmlspecialchars($t['bulk_anon_success_heading']) ?></h2>
            <p><?= htmlspecialchars(sprintf($t['bulk_anon_success'], $successCount)) ?></p>
        </section>
    <?php endif; ?>

    <p><?= htmlspecialchars($t['bulk_anon_intro']) ?></p>
    <section class="alert alert-warning" aria-labelledby="bulk-anon-warning">
        <h2 id="bulk-anon-warning"><?= htmlspecialchars($t['bulk_anon_warning_heading']) ?></h2>
        <p><?= htmlspecialchars($t['bulk_anon_warning']) ?></p>
    </section>

    <form method="post" action="/bulk-anonymize.php?lang=<?= urlencode($lang) ?>">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['bulk_anonymize_token'], ENT_QUOTES, 'UTF-8') ?>">
        <fieldset>
            <legend><?= htmlspecialchars($t['bulk_anon_select_catalogues']) ?></legend>
            <?php foreach ($catalogues as $catalogueId => $catalogue): ?>
                <label class="checkbox-inline mrgn-rght-md">
                    <input type="checkbox" name="catalogue_ids[]" value="<?= $catalogueId ?>" <?= in_array($catalogueId, $selectedCatalogueIds, true) ? 'checked' : '' ?>>
                    <?= htmlspecialchars($catalogue[$lang === 'fr' ? 'namefr' : 'nameen']) ?>
                </label>
            <?php endforeach; ?>
        </fieldset>
        <div class="form-group">
            <label for="excluded-service-ids"><?= htmlspecialchars($t['bulk_anon_exclude_services']) ?></label>
            <input class="form-control" id="excluded-service-ids" name="excluded_service_ids" value="<?= htmlspecialchars(implode(', ', $excludedServiceIds)) ?>">
        </div>
        <button type="submit" name="action" value="preview" class="btn btn-primary"><?= htmlspecialchars($t['bulk_anon_preview_btn']) ?></button>
        <a class="btn btn-default" href="/requests.php?lang=<?= urlencode($lang) ?>"><?= htmlspecialchars($t['priority_update_cancel']) ?></a>
    </form>

    <?php if ($previewCount !== null): ?>
        <section class="alert alert-info mrgn-tp-md" aria-labelledby="bulk-anon-preview">
            <h2 id="bulk-anon-preview"><?= htmlspecialchars($t['bulk_anon_preview_heading']) ?></h2>
            <p><?= htmlspecialchars(sprintf($t['bulk_anon_preview_result'], $previewCount)) ?></p>
            <?php if ($previewCount > 0): ?>
                <form method="post" action="/bulk-anonymize.php?lang=<?= urlencode($lang) ?>">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['bulk_anonymize_token'], ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="action" value="run">
                    <input type="hidden" name="confirm_run" value="1">
                    <input type="hidden" name="preview_token" value="<?= htmlspecialchars($previewToken, ENT_QUOTES, 'UTF-8') ?>">
                    <?php foreach ($selectedCatalogueIds as $catalogueId): ?><input type="hidden" name="catalogue_ids[]" value="<?= $catalogueId ?>"><?php endforeach; ?>
                    <input type="hidden" name="excluded_service_ids" value="<?= htmlspecialchars(implode(',', $excludedServiceIds), ENT_QUOTES, 'UTF-8') ?>">
                    <button type="submit" class="btn btn-danger" onclick="return confirm('<?= htmlspecialchars($t['bulk_anon_confirm'], ENT_QUOTES, 'UTF-8') ?>');"><?= htmlspecialchars($t['bulk_anon_run_btn']) ?></button>
                </form>
            <?php endif; ?>
        </section>
    <?php endif; ?>
    <?php include 'includes/template/page-details.php'; ?>
</main>
<?php include 'includes/template/footer.php'; include 'includes/template/scripts.php'; ?>