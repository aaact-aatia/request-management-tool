<?php
/**
 * Client Survey Link Helper - Manual survey links for staff use when notifications are unavailable.
 */

require('sql.php');
/** @var mysqli $link */
require('includes/helpers.php');
require('emailController.php');

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
$actionStatus = '';

if ($triageId <= 0) {
    $status = 'failed';
} else {
    $sql = "SELECT id, requestid, title, clientfname, clientlname, clientemail, statusid, catalogueid FROM tbltriage WHERE id = '$triageId' LIMIT 1";
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

$returnTo = isset($_GET['return_to']) ? trim((string)$_GET['return_to']) : '';
$isValidReturnTo = preg_match('#^/editrequest\.php\?#', $returnTo) === 1;

if ($request !== null && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['email_action']) ? trim((string) $_POST['email_action']) : '';
    $clientEmail = trim((string) ($request['clientemail'] ?? ''));
    $requestLanguage = rmt_get_request_language($link, (int) $request['id'], $lang);
    $requestViewUrl = app_url('viewrequest.php?lang=' . $requestLanguage . '&erid=' . base64_encode((string) $request['id']) . '&reqid=' . urlencode('a11y-' . (string) $request['requestid']));

    if ($clientEmail === '') {
        $actionStatus = 'missing_email';
    } elseif ($action === 'send_resolved_email') {
        $templateId = app_notify_template_id('notification_generic');
        $category = rmt_notification_template_category('resolved');
        $resolvedContext = [
            'requestid' => (string) $request['requestid'],
            'client_fname' => (string) ($request['clientfname'] ?? ''),
            'client_lname' => (string) ($request['clientlname'] ?? ''),
            'url' => $requestViewUrl,
        ];
        if ($surveyEnabled) {
            $resolvedContext['survey_link_en'] = $enLink;
            $resolvedContext['survey_link_fr'] = $frLink;
        }
        $resolvedMessage = rmt_notification_message('resolved', 'client', $requestLanguage, $resolvedContext);

        $personalisation = [
            'requestid' => (string) $request['requestid'],
            'requesttitle' => (string) $request['title'],
            'client_fname' => (string) ($request['clientfname'] ?? ''),
            'client_lname' => (string) ($request['clientlname'] ?? ''),
            'url' => $requestViewUrl,
            'notification_event' => 'resolved',
            'template_category_id' => $category['id'],
            'template_category_name_en' => $category['name_en'],
            'template_category_name_fr' => $category['name_fr'],
            'subject' => rmt_notification_subject('resolved', 'client', $requestLanguage, [
                'requestid' => (string) $request['requestid'],
            ]),
            'message' => $resolvedMessage,
        ];

        $sent = sendEmail($clientEmail, $templateId, json_encode($personalisation), ['recipientType' => 'client']);
        if ($sent) {
            if ($surveyEnabled) {
                $result3 = mysqli_query($link, "SELECT cssurvey FROM tbltriage WHERE id = '$triageId'");
                $row3 = $result3 ? mysqli_fetch_array($result3) : null;
                $surveySentCount = $row3[0] ?? 0;
                $updatedSurveySentCount = (empty($surveySentCount) ? 1 : ((int) $surveySentCount + 1));
                mysqli_query($link, "UPDATE tbltriage SET cssurvey = '$updatedSurveySentCount' WHERE id = '$triageId'");
            }

            $senderId = isset($_SESSION['pid']) ? (int) $_SESSION['pid'] : 0;
            rmt_mark_resolved_email_sent($link, (int) $request['id'], $senderId);
            $actionStatus = 'resolved_sent';
        } else {
            $actionStatus = 'send_failed';
        }
    }

    if ($isValidReturnTo && $action === 'send_resolved_email') {
        $returnStatus = 'resolvedemailfailed';
        if ($actionStatus === 'resolved_sent') {
            $returnStatus = 'resolvedemailsent';
        } elseif ($actionStatus === 'missing_email') {
            $returnStatus = 'resolvedemailmissing';
        }

        $separator = (strpos($returnTo, '?') !== false) ? '&' : '?';
        header('Location: ' . $returnTo . $separator . 'status=' . urlencode($returnStatus));
        exit();
    }
}

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

    <p>
        <strong><?= htmlspecialchars($langFile['client_survey_link_survey_status_label']) ?>:</strong>
        <?= htmlspecialchars($surveyEnabled ? $langFile['client_survey_link_survey_status_enabled'] : $langFile['client_survey_link_survey_status_disabled']) ?>
    </p>

    <?php if (!$surveyEnabled): ?>
    <section class="alert alert-warning">
        <p><?= htmlspecialchars($langFile['client_survey_link_survey_disabled']) ?></p>
    </section>
    <?php endif; ?>

    <?php if ($actionStatus === 'resolved_sent'): ?>
    <section class="alert alert-success">
        <p><?= htmlspecialchars($langFile['client_survey_link_resolved_sent']) ?></p>
    </section>
    <?php elseif ($actionStatus === 'missing_email'): ?>
    <section class="alert alert-danger">
        <p><?= htmlspecialchars($langFile['client_survey_link_missing_email']) ?></p>
    </section>
    <?php elseif ($actionStatus === 'send_failed'): ?>
    <section class="alert alert-danger">
        <p><?= htmlspecialchars($langFile['client_survey_link_send_failed']) ?></p>
    </section>
    <?php endif; ?>

    <form method="post" action="/client-survey-link.php?lang=<?= htmlspecialchars($lang) ?>&erid=<?= urlencode($encodedTriageId) ?>">
        <div class="form-group form-buttons">
            <button type="submit" class="btn btn-primary" name="email_action" value="send_resolved_email">
                <?= htmlspecialchars($langFile['client_survey_link_send_resolved']) ?>
            </button>
        </div>
    </form>

    <?php if ($surveyEnabled): ?>
    <p><?= htmlspecialchars($langFile['client_survey_link_intro']) ?></p>

    <div class="form-group">
        <p class="mrgn-tp-sm"><a href="<?= htmlspecialchars($frLink) ?>" target="_blank" rel="noopener noreferrer"><?= htmlspecialchars($langFile['client_survey_link_french']) ?></a></p>
    </div>

    <div class="form-group">
        <p class="mrgn-tp-sm"><a href="<?= htmlspecialchars($enLink) ?>" target="_blank" rel="noopener noreferrer"><?= htmlspecialchars($langFile['client_survey_link_english']) ?></a></p>
    </div>
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