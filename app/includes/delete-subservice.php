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

$subserviceId = (int) ($_GET['id'] ?? 0);
$serviceId = (int) ($_GET['sid'] ?? 0);
$catalogueId = (int) ($_GET['cid'] ?? 0);
$formAction = "/includes/delete-subservice.php?lang={$lang}&id={$subserviceId}&sid={$serviceId}&cid={$catalogueId}";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        rmt_delete_catalogue_hierarchy($link, 'subservice', $subserviceId, (int) ($_POST['replacement_id'] ?? 0), $serviceId);
        header("location:/catalogue-sub-mgmt.php?lang={$lang}&id={$serviceId}&cid={$catalogueId}&status=success");
    } catch (Throwable $exception) {
        error_log($exception->getMessage());
        header("location:/catalogue-sub-mgmt.php?lang={$lang}&id={$serviceId}&cid={$catalogueId}&status=failed");
    }
    exit();
}

rmt_render_catalogue_delete_dialog($link, 'subservice', $subserviceId, $serviceId, $lang, $formAction);
mysqli_close($link);