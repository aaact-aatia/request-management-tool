<?php
require_once __DIR__ . '/session_start.php';
require('../sql.php');
/** @var mysqli $link */
require_once('helpers.php');
require_once('csrf.php');

$lang = isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'fr'], true)
    ? $_GET['lang']
    : ($_SESSION['lang'] ?? 'en');
$_SESSION['lang'] = $lang;
$langFile = require("../lang/{$lang}.php");

if (!($_SESSION['is_superuser'] || $_SESSION['is_admin'])) {
    http_response_code(403);
    exit;
}

$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 0;
$organization = $id > 0
    ? rmt_db_fetch_one($link, 'SELECT id, nameen, namefr, status FROM tblorganizations WHERE id = ?', 'i', [$id])
    : null;

if (!$organization) {
    http_response_code(404);
    ?>
    <section class="modal-dialog modal-content overlay-def">
        <header class="modal-header">
            <h2 class="modal-title"><?= htmlspecialchars($langFile['failed_heading']) ?></h2>
        </header>
        <div class="modal-body">
            <p><?= htmlspecialchars($langFile['organizations_not_found']) ?></p>
            <button type="button" class="btn btn-default popup-modal-dismiss"><?= htmlspecialchars($langFile['organizations_cancel_status']) ?></button>
        </div>
    </section>
    <?php
    mysqli_close($link);
    exit;
}

$isActive = (int) $organization['status'] === 1;
$newStatus = $isActive ? 0 : 1;
$actionLabel = $isActive ? $langFile['organizations_deactivate'] : $langFile['organizations_activate'];
$organizationName = (string) $organization[$lang === 'fr' ? 'namefr' : 'nameen'];
$confirmMessage = sprintf(
    $isActive ? $langFile['organizations_deactivate_confirm'] : $langFile['organizations_activate_confirm'],
    $organizationName
);
$csrfToken = rmt_csrf_token('organizations');
?>
<section class="modal-dialog modal-content overlay-def">
    <header class="modal-header">
        <h2 class="modal-title"><?= htmlspecialchars($actionLabel) ?></h2>
    </header>
    <div class="modal-body">
        <p><?= htmlspecialchars($confirmMessage) ?></p>
        <form method="post" action="/organizations.php?lang=<?= htmlspecialchars($lang) ?>">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="organization_action" value="set_status">
            <input type="hidden" name="organization_id" value="<?= (int) $organization['id'] ?>">
            <input type="hidden" name="record_status" value="<?= $newStatus ?>">
            <div class="form-group form-buttons">
                <button type="submit" class="btn btn-primary"><?= htmlspecialchars($actionLabel) ?><span class="wb-inv"> <?= htmlspecialchars($organizationName) ?></span></button>
                <button type="button" class="btn btn-default popup-modal-dismiss"><?= htmlspecialchars($langFile['organizations_cancel_status']) ?></button>
            </div>
        </form>
    </div>
</section>
<?php mysqli_close($link); ?>