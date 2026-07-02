<?php

/**
 * Client Survey Thank You page
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
require_once 'includes/config.php';

$pageTitle = $langFile['client_survey_thank_you_page_title'];
$pageDescription = '';

include 'includes/template/head.php';
include 'includes/template/header.php';
?>
<main role="main" property="mainContentOfPage" class="container">
    <h1 property="name" id="wb-cont"><?= htmlspecialchars($langFile['client_survey_thank_you_heading']) ?></h1>

    <section class="alert alert-success">
        <p><?= htmlspecialchars($langFile['client_survey_thank_you_message']) ?></p>
    </section>

    <p>
        <a class="btn btn-primary" href="/openrequest.php?lang=<?= urlencode($_SESSION['lang']) ?>">
            <?= htmlspecialchars($langFile['client_survey_thank_you_new_request_link']) ?>
        </a>
    </p>

    <?php include 'includes/template/page-details.php'; ?>
</main>
<?php
include 'includes/template/footer.php';
include 'includes/template/scripts.php';

mysqli_close($link);
