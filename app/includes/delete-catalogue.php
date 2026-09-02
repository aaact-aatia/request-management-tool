<?php

require_once __DIR__ . '/session_start.php';

$lang = (isset($_GET['lang']) && $_GET['lang'] === 'fr') ? 'fr' : 'en';
if (!($_SESSION['is_superuser'] || $_SESSION['is_admin'])) {
    header("location:/openrequest.php?lang={$lang}&status=accessdenied");
    exit();
}

require '../sql.php';
/** @var mysqli $link */
require_once __DIR__ . '/catalogue-delete.php';

$catalogueId = (int) ($_GET['id'] ?? 0);
$formAction = "/includes/delete-catalogue.php?lang={$lang}&id={$catalogueId}";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        rmt_delete_catalogue_hierarchy($link, 'catalogue', $catalogueId, (int) ($_POST['replacement_id'] ?? 0), 0);
        header("location:/catalogue.php?lang={$lang}&status=success");
    } catch (Throwable $exception) {
        error_log($exception->getMessage());
        header("location:/catalogue.php?lang={$lang}&status=failed");
    }
    exit();
}

rmt_render_catalogue_delete_dialog($link, 'catalogue', $catalogueId, 0, $lang, $formAction);
mysqli_close($link);