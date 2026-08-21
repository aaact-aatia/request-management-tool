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
    ? rmt_db_fetch_one($link, 'SELECT * FROM tblorganizations WHERE id = ?', 'i', [$id])
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
            <button type="button" class="btn btn-default popup-modal-dismiss"><?= htmlspecialchars($langFile['organizations_cancel']) ?></button>
        </div>
    </section>
    <?php
    mysqli_close($link);
    exit;
}

$csrfToken = rmt_csrf_token('organizations');
$selectedStatus = (int) $organization['status'];
?>
<section class="modal-dialog modal-content overlay-def">
    <header class="modal-header">
        <h2 class="modal-title"><?= htmlspecialchars($langFile['organizations_edit_heading']) ?></h2>
    </header>
    <div class="modal-body">
        <form method="post" action="/organizations.php?lang=<?= htmlspecialchars($lang) ?>">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="organization_action" value="save">
            <input type="hidden" name="organization_id" value="<?= (int) $organization['id'] ?>">

            <div class="form-group">
                <label for="edit-organization-nameen"><span class="field-name"><?= htmlspecialchars($langFile['organizations_name_en']) ?> <strong>(<?= htmlspecialchars($langFile['required']) ?>)</strong></span></label>
                <input class="form-control full-width" id="edit-organization-nameen" name="nameen" type="text" lang="en" maxlength="255" required value="<?= htmlspecialchars((string) $organization['nameen'], ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="form-group">
                <label for="edit-organization-namefr"><span class="field-name"><?= htmlspecialchars($langFile['organizations_name_fr']) ?> <strong>(<?= htmlspecialchars($langFile['required']) ?>)</strong></span></label>
                <input class="form-control full-width" id="edit-organization-namefr" name="namefr" type="text" lang="fr" maxlength="255" required value="<?= htmlspecialchars((string) $organization['namefr'], ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="form-group">
                <label for="edit-organization-abbreviationen"><?= htmlspecialchars($langFile['organizations_abbreviation_en']) ?></label>
                <input class="form-control full-width" id="edit-organization-abbreviationen" name="abbreviationen" type="text" lang="en" maxlength="50" value="<?= htmlspecialchars((string) $organization['abbreviationen'], ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="form-group">
                <label for="edit-organization-abbreviationfr"><?= htmlspecialchars($langFile['organizations_abbreviation_fr']) ?></label>
                <input class="form-control full-width" id="edit-organization-abbreviationfr" name="abbreviationfr" type="text" lang="fr" maxlength="50" value="<?= htmlspecialchars((string) $organization['abbreviationfr'], ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="form-group">
                <label for="edit-organization-status"><?= htmlspecialchars($langFile['organizations_status']) ?></label>
                <select class="form-control full-width" id="edit-organization-status" name="record_status">
                    <option value="1" <?= $selectedStatus === 1 ? 'selected' : '' ?>><?= htmlspecialchars($langFile['organizations_active']) ?></option>
                    <option value="0" <?= $selectedStatus === 0 ? 'selected' : '' ?>><?= htmlspecialchars($langFile['organizations_inactive']) ?></option>
                </select>
            </div>
            <div class="form-group form-buttons">
                <button type="submit" class="btn btn-primary"><?= htmlspecialchars($langFile['organizations_save']) ?></button>
                <button type="button" class="btn btn-default popup-modal-dismiss"><?= htmlspecialchars($langFile['organizations_cancel']) ?></button>
            </div>
        </form>
    </div>
</section>
<?php mysqli_close($link); ?>
