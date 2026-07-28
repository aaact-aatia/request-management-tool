<?php
require_once __DIR__ . '/session_start.php';
require('../sql.php');
/** @var mysqli $link */
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

$csrfToken = rmt_csrf_token('organizations');
?>
<section class="modal-dialog modal-content overlay-def">
    <header class="modal-header">
        <h2 class="modal-title"><?= htmlspecialchars($langFile['organizations_add_heading']) ?></h2>
    </header>
    <div class="modal-body">
        <form method="post" action="/organizations.php?lang=<?= htmlspecialchars($lang) ?>">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="organization_action" value="save">
            <input type="hidden" name="organization_id" value="0">

            <div class="form-group">
                <label for="add-organization-nameen"><span class="field-name"><?= htmlspecialchars($langFile['organizations_name_en']) ?> <strong>(<?= htmlspecialchars($langFile['required']) ?>)</strong></span></label>
                <input class="form-control" id="add-organization-nameen" name="nameen" type="text" lang="en" maxlength="255" required>
            </div>
            <div class="form-group">
                <label for="add-organization-namefr"><span class="field-name"><?= htmlspecialchars($langFile['organizations_name_fr']) ?> <strong>(<?= htmlspecialchars($langFile['required']) ?>)</strong></span></label>
                <input class="form-control" id="add-organization-namefr" name="namefr" type="text" lang="fr" maxlength="255" required>
            </div>
            <div class="form-group">
                <label for="add-organization-abbreviationen"><?= htmlspecialchars($langFile['organizations_abbreviation_en']) ?></label>
                <input class="form-control" id="add-organization-abbreviationen" name="abbreviationen" type="text" lang="en" maxlength="50">
            </div>
            <div class="form-group">
                <label for="add-organization-abbreviationfr"><?= htmlspecialchars($langFile['organizations_abbreviation_fr']) ?></label>
                <input class="form-control" id="add-organization-abbreviationfr" name="abbreviationfr" type="text" lang="fr" maxlength="50">
            </div>
            <div class="form-group">
                <label for="add-organization-status"><?= htmlspecialchars($langFile['organizations_status']) ?></label>
                <select class="form-control" id="add-organization-status" name="record_status">
                    <option value="1" selected><?= htmlspecialchars($langFile['organizations_active']) ?></option>
                    <option value="0"><?= htmlspecialchars($langFile['organizations_inactive']) ?></option>
                </select>
            </div>
            <div class="form-group form-buttons">
                <button type="submit" class="btn btn-primary"><?= htmlspecialchars($langFile['organizations_save']) ?></button>
                <button type="button" class="btn btn-default popup-modal-dismiss"><?= htmlspecialchars($langFile['organizations_cancel_add']) ?></button>
            </div>
        </form>
    </div>
</section>
<?php mysqli_close($link); ?>