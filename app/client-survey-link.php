<?php
/**
 * Client Survey Link Helper - Manual survey links for staff use when notifications are unavailable.
 */

require('sql.php');
/** @var mysqli $link */

if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'fr'], true)) {
    $_SESSION['lang'] = $_GET['lang'];
}

if (!isset($_SESSION['lang']) || !in_array($_SESSION['lang'], ['en', 'fr'], true)) {
    $_SESSION['lang'] = 'en';
}

$lang = $_SESSION['lang'];
$langFile = require("lang/{$lang}.php");

require('includes/httpscheck.php');
require('includes/loggedincheck.php');

$triageId = 0;
if (!empty($_GET['erid'])) {
    $triageId = (int)base64_decode((string)$_GET['erid']);
} elseif (!empty($_GET['id'])) {
    $triageId = (int)$_GET['id'];
}

$status = '';
$request = null;

if ($triageId <= 0) {
    $status = 'failed';
} else {
    $sql = "SELECT id, requestid, title, clientemail, statusid, catalogueid FROM tbltriage WHERE id = '$triageId' LIMIT 1";
    $result = mysqli_query($link, $sql);
    if ($result && mysqli_num_rows($result) > 0) {
        $request = mysqli_fetch_assoc($result);
    } else {
        $status = 'failed';
    }
}

$surveyEnabled = false;
if ($request !== null) {
    $catalogueId = (int)$request['catalogueid'];
    $surveyResult = mysqli_query($link, "SELECT survey FROM tblcatalogue WHERE id = '$catalogueId' LIMIT 1");
    if ($surveyResult && mysqli_num_rows($surveyResult) > 0) {
        $surveyRow = mysqli_fetch_assoc($surveyResult);
        $surveyEnabled = ((int)$surveyRow['survey'] === 1);
    }
}

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = isset($_SERVER['HTTP_HOST']) ? trim((string)$_SERVER['HTTP_HOST']) : '';
$baseUrl = $host !== '' ? ($scheme . '://' . $host) : '';

$encodedTriageId = $request !== null ? base64_encode((string)$request['id']) : '';
$encodedRequestPublicId = $request !== null ? urlencode('a11y-' . (string)$request['requestid']) : '';
$frLink = ($baseUrl !== '' ? $baseUrl : '') . '/client-survey.php?lang=fr&erid=' . $encodedTriageId . '&reqid=' . $encodedRequestPublicId;
$enLink = ($baseUrl !== '' ? $baseUrl : '') . '/client-survey.php?lang=en&erid=' . $encodedTriageId . '&reqid=' . $encodedRequestPublicId;

$pageTitle = $langFile['client_survey_link_page_title'];
$pageDescription = '';
require_once 'includes/config.php';

include 'includes/template/head.php';
include 'includes/template/header.php';
?>
<main role="main" property="mainContentOfPage" class="container">
    <h1 property="name" id="wb-cont"><?= htmlspecialchars($langFile['client_survey_link_heading']) ?></h1>

    <?php if ($status === 'failed' || $request === null): ?>
    <section class="alert alert-danger">
        <h2><?= htmlspecialchars($langFile['client_survey_failed_heading']) ?></h2>
        <ul>
            <li><?= htmlspecialchars($langFile['client_survey_failed_message']) ?></li>
        </ul>
    </section>
    <?php else: ?>

    <h2>a11y-<?= htmlspecialchars((string)$request['requestid']) ?> - <?= htmlspecialchars((string)$request['title']) ?></h2>

    <?php if (!$surveyEnabled): ?>
    <section class="alert alert-warning">
        <p><?= htmlspecialchars($langFile['client_survey_link_survey_disabled']) ?></p>
    </section>
    <?php endif; ?>

    <p><?= htmlspecialchars($langFile['client_survey_link_intro']) ?></p>

    <div class="form-group">
        <label for="client-survey-link-fr"><?= htmlspecialchars($langFile['client_survey_link_french']) ?></label>
        <input id="client-survey-link-fr" class="form-control full-width" type="text" readonly value="<?= htmlspecialchars($frLink) ?>">
    </div>

    <div class="form-group">
        <label for="client-survey-link-en"><?= htmlspecialchars($langFile['client_survey_link_english']) ?></label>
        <input id="client-survey-link-en" class="form-control full-width" type="text" readonly value="<?= htmlspecialchars($enLink) ?>">
    </div>

    <?php if (!empty($request['clientemail'])): ?>
    <p>
        <a class="btn btn-primary" href="mailto:<?= htmlspecialchars((string)$request['clientemail']) ?>?subject=<?= urlencode('Sondage sur la satisfaction de la clientèle pour / Client satisfaction survey for a11y-' . $request['requestid']) ?>">
            <?= htmlspecialchars($langFile['client_survey_link_email_client']) ?>
        </a>
    </p>
    <?php endif; ?>

    <p>
        <a class="btn btn-default" href="/viewrequest.php?lang=<?= htmlspecialchars($lang) ?>&erid=<?= urlencode($encodedTriageId) ?>">
            <?= htmlspecialchars($langFile['client_survey_link_back_request']) ?>
        </a>
    </p>
    <?php endif; ?>

    <?php include 'includes/template/page-details.php'; ?>
</main>
<?php
include 'includes/template/footer.php';
include 'includes/template/scripts.php';

mysqli_close($link);
?>