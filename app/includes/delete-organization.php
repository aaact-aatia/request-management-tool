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
    ? rmt_db_fetch_one(
        $link,
        'SELECT id, nameen, namefr FROM tblorganizations WHERE id = ? AND source_part = 0',
        'i',
        [$id]
    )
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
            <button type="button" class="btn btn-default popup-modal-dismiss"><?= htmlspecialchars($langFile['organizations_cancel_delete']) ?></button>
        </div>
    </section>
    <?php
    mysqli_close($link);
    exit;
}

$organizationName = (string) $organization[$lang === 'fr' ? 'namefr' : 'nameen'];
$csrfToken = rmt_csrf_token('organizations');
?>
<section class="modal-dialog modal-content overlay-def">
    <header class="modal-header">
        <h2 class="modal-title"><?= htmlspecialchars($langFile['organizations_delete_heading']) ?></h2>
    </header>
    <div class="modal-body">
        <div class="alert alert-warning">
            <p><strong><?= htmlspecialchars(sprintf($langFile['organizations_delete_confirm'], $organizationName)) ?></strong></p>
            <p><?= htmlspecialchars($langFile['organizations_delete_warning']) ?></p>
        </div>
        <form method="post" action="/organizations.php?lang=<?= htmlspecialchars($lang) ?>">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="organization_action" value="delete">
            <input type="hidden" name="organization_id" value="<?= (int) $organization['id'] ?>">
            <div class="form-group form-buttons">
                <button type="submit" class="btn btn-danger"><?= htmlspecialchars($langFile['delete_button']) ?><span class="wb-inv"> <?= htmlspecialchars($organizationName) ?></span></button>
                <button type="button" class="btn btn-default popup-modal-dismiss"><?= htmlspecialchars($langFile['organizations_cancel_delete']) ?></button>
            </div>
        </form>
    </div>
</section>
<?php mysqli_close($link); ?>