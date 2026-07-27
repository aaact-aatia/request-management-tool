<?php
require('sql.php');
/** @var mysqli $link */
require('includes/httpscheck.php');
require('includes/loggedincheck.php');
require_once('includes/helpers.php');

if (!($_SESSION['is_superuser'] || $_SESSION['is_admin'])) {
    $lang = $_SESSION['lang'] ?? 'en';
    header("Location: /openrequest.php?lang={$lang}&status=accessdenied");
    exit;
}

if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'fr'], true)) {
    $_SESSION['lang'] = $_GET['lang'];
}
$lang = in_array($_SESSION['lang'] ?? '', ['en', 'fr'], true) ? $_SESSION['lang'] : 'en';
$langFile = require("lang/{$lang}.php");

if (empty($_SESSION['organization_csrf_token'])) {
    $_SESSION['organization_csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['organization_csrf_token'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = (string) ($_POST['csrf_token'] ?? '');
    if (!hash_equals($csrfToken, $submittedToken)) {
        header("Location: /organizations.php?lang={$lang}&status=invalid_request");
        exit;
    }

    $action = (string) ($_POST['action'] ?? '');
    try {
        if ($action === 'save') {
            $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 0;
            $nameEn = trim((string) ($_POST['nameen'] ?? ''));
            $nameFr = trim((string) ($_POST['namefr'] ?? ''));
            $abbreviationEn = trim((string) ($_POST['abbreviationen'] ?? ''));
            $abbreviationFr = trim((string) ($_POST['abbreviationfr'] ?? ''));
            $recordStatus = (int) ($_POST['record_status'] ?? 1);

            if ($nameEn === '' || $nameFr === '' || !in_array($recordStatus, [0, 1], true)) {
                throw new InvalidArgumentException('Invalid organization values.');
            }

            if ($id > 0) {
                $statement = rmt_db_execute(
                    $link,
                    'UPDATE tblorganizations
                     SET nameen = ?, namefr = ?, abbreviationen = NULLIF(?, \'\'), abbreviationfr = NULLIF(?, \'\'), status = ?
                     WHERE id = ?',
                    'ssssii',
                    [$nameEn, $nameFr, $abbreviationEn, $abbreviationFr, $recordStatus, $id]
                );
            } else {
                $statement = rmt_db_execute(
                    $link,
                    'INSERT INTO tblorganizations
                     (nameen, namefr, abbreviationen, abbreviationfr, source_part, status)
                     VALUES (?, ?, NULLIF(?, \'\'), NULLIF(?, \'\'), 0, ?)',
                    'ssssi',
                    [$nameEn, $nameFr, $abbreviationEn, $abbreviationFr, $recordStatus]
                );
            }
            mysqli_stmt_close($statement);
            header("Location: /organizations.php?lang={$lang}&status=success");
            exit;
        }

        if ($action === 'set_status') {
            $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 0;
            $recordStatus = (int) ($_POST['record_status'] ?? -1);
            if ($id <= 0 || !in_array($recordStatus, [0, 1], true)) {
                throw new InvalidArgumentException('Invalid organization status.');
            }

            $statement = rmt_db_execute(
                $link,
                'UPDATE tblorganizations SET status = ? WHERE id = ?',
                'ii',
                [$recordStatus, $id]
            );
            mysqli_stmt_close($statement);
            header("Location: /organizations.php?lang={$lang}&status=success");
            exit;
        }
    } catch (Throwable $exception) {
        header("Location: /organizations.php?lang={$lang}&status=failed");
        exit;
    }

    header("Location: /organizations.php?lang={$lang}&status=invalid_request");
    exit;
}

$organizationsResult = mysqli_query(
    $link,
    'SELECT id, nameen, namefr, abbreviationen, abbreviationfr, status
     FROM tblorganizations
     ORDER BY nameen ASC'
);
$organizations = $organizationsResult ? mysqli_fetch_all($organizationsResult, MYSQLI_ASSOC) : [];
$status = (string) ($_GET['status'] ?? '');
$errorMessages = [
    'failed' => $langFile['organizations_failed'],
    'invalid_request' => $langFile['organizations_invalid_request'],
    'import_failed' => $langFile['admin_csv_import_failed'],
    'header_mismatch' => $langFile['admin_csv_header_mismatch'],
    'no_file' => $langFile['admin_csv_no_file'],
    'invalid_table' => $langFile['admin_csv_invalid_table'],
];
$pageTitle = $langFile['organizations_heading'];
$pageDescription = $langFile['organizations_description'];
$tableName = 'tblorganizations';

include 'includes/template/head.php';
include 'includes/template/header.php';
?>
<main role="main" property="mainContentOfPage" class="container">
    <h1 property="name" id="wb-cont"><?= htmlspecialchars($langFile['organizations_heading']) ?></h1>
    <p><?= htmlspecialchars($langFile['organizations_description']) ?></p>

    <?php if ($status === 'success' || $status === 'import_success'): ?>
        <section class="alert alert-success">
            <h2><?= htmlspecialchars($langFile['success_heading']) ?></h2>
            <p><?= htmlspecialchars($status === 'import_success'
                ? sprintf($langFile['admin_csv_import_success'], (int) ($_GET['count'] ?? 0))
                : $langFile['organizations_success']) ?></p>
        </section>
    <?php elseif ($status === 'import_partial'): ?>
        <section class="alert alert-warning">
            <h2><?= htmlspecialchars($langFile['warning_heading'] ?? 'Warning') ?></h2>
            <p><?= htmlspecialchars(sprintf(
                $langFile['admin_csv_import_partial'],
                (int) ($_GET['ok'] ?? 0),
                (int) ($_GET['fail'] ?? 0)
            )) ?></p>
        </section>
    <?php elseif (in_array($status, ['failed', 'invalid_request', 'import_failed', 'header_mismatch', 'no_file', 'invalid_table'], true)): ?>
        <section class="alert alert-danger">
            <h2><?= htmlspecialchars($langFile['failed_heading']) ?></h2>
            <p><?= htmlspecialchars($errorMessages[$status]) ?></p>
        </section>
    <?php endif; ?>

    <p>
        <a class="wb-lbx btn btn-primary" href="/includes/add-organization.php?lang=<?= htmlspecialchars($lang) ?>"><?= htmlspecialchars($langFile['organizations_add_heading']) ?></a>
    </p>

    <section class="mrgn-tp-lg">
        <h2><?= htmlspecialchars($langFile['organizations_list_heading']) ?></h2>
        <table class="wb-tables wb-tables-filter table table-striped table-hover" data-wb-tables='{ "ordering": true, "pageLength": 25 }'>
            <caption class="wb-inv"><?= htmlspecialchars($langFile['organizations_table_caption']) ?></caption>
            <thead>
                <tr>
                    <th scope="col"><?= htmlspecialchars($langFile['organizations_name_en']) ?></th>
                    <th scope="col"><?= htmlspecialchars($langFile['organizations_name_fr']) ?></th>
                    <th scope="col"><?= htmlspecialchars($langFile['organizations_abbreviations']) ?></th>
                    <th scope="col"><?= htmlspecialchars($langFile['organizations_status']) ?></th>
                    <th scope="col"><?= htmlspecialchars($langFile['actions_column']) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($organizations as $organization): ?>
                    <?php
                    $isActive = (int) $organization['status'] === 1;
                    $abbreviation = (string) $organization[$lang === 'fr' ? 'abbreviationfr' : 'abbreviationen'];
                    ?>
                    <tr>
                        <td lang="en"><?= htmlspecialchars($organization['nameen']) ?></td>
                        <td lang="fr"><?= htmlspecialchars($organization['namefr']) ?></td>
                        <td lang="<?= htmlspecialchars($lang) ?>"><?= htmlspecialchars($abbreviation) ?></td>
                        <td><?= htmlspecialchars($isActive ? $langFile['organizations_active'] : $langFile['organizations_inactive']) ?></td>
                        <td>
                            <a class="wb-lbx btn btn-primary btn-block" href="/includes/edit-organization.php?id=<?= (int) $organization['id'] ?>&amp;lang=<?= htmlspecialchars($lang) ?>"><?= htmlspecialchars($langFile['edit_button']) ?><span class="wb-inv"> <?= htmlspecialchars($organization[$lang === 'fr' ? 'namefr' : 'nameen']) ?></span></a>
                            <form method="post" action="/organizations.php?lang=<?= htmlspecialchars($lang) ?>">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                <input type="hidden" name="action" value="set_status">
                                <input type="hidden" name="id" value="<?= (int) $organization['id'] ?>">
                                <input type="hidden" name="record_status" value="<?= $isActive ? 0 : 1 ?>">
                                <button type="submit" class="btn btn-primary btn-block"><?= htmlspecialchars($isActive ? $langFile['organizations_deactivate'] : $langFile['organizations_activate']) ?><span class="wb-inv"> <?= htmlspecialchars($organization[$lang === 'fr' ? 'namefr' : 'nameen']) ?></span></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <p class="mrgn-tp-lg"><?= htmlspecialchars($langFile['organizations_csv_behavior']) ?></p>
    <?php include 'includes/admin-csv-buttons.php'; ?>
    <?php include 'includes/template/page-details.php'; ?>
</main>
<?php include 'includes/template/footer.php'; ?>
<?php include 'includes/template/scripts.php'; ?>
</body>
</html>
<?php mysqli_close($link); ?>
