<?php
require('sql.php');
/** @var mysqli $link */
require_once('includes/helpers.php');
require_once('includes/priority-calculator.php');
require_once('includes/calculate-bdays.php');
require('includes/httpscheck.php');
require('includes/loggedincheck.php');

$lang = detectLanguage();
$t = require("lang/{$lang}.php");

if (!isSuperAdmin()) {
    header("location:/index.php?lang={$lang}&status=forbidden");
    exit();
}

if (empty($_SESSION['priority_update_token'])) {
    $_SESSION['priority_update_token'] = bin2hex(random_bytes(32));
}

$errorMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = (string) ($_POST['csrf_token'] ?? '');
    if (!hash_equals((string) $_SESSION['priority_update_token'], $submittedToken)) {
        http_response_code(400);
        $errorMessage = $t['priority_update_invalid_request'];
    } else {
        try {
            mysqli_begin_transaction($link);

            $selectStatement = rmt_db_execute(
                $link,
                'SELECT triage.*
                 FROM tbltriage triage
                 LEFT JOIN tblstatus request_status ON request_status.id = triage.statusid
                 WHERE triage.status = 1
                   AND COALESCE(request_status.is_resolved, 0) = 0'
            );
            $requests = mysqli_stmt_get_result($selectStatement);
            $updateStatement = mysqli_prepare(
                $link,
                'UPDATE tbltriage SET priority_score = ? WHERE id = ?'
            );

            $updatedCount = 0;
            while ($request = mysqli_fetch_assoc($requests)) {
                $slaDays = rmt_get_sla_days_required_for_request(
                    $link,
                    (int) ($request['serviceid'] ?? 0),
                    (int) ($request['subserviceid'] ?? 0)
                );
                $clockStart = rmt_get_sla_clock_start_date(
                    $request['slatimer'] ?? '',
                    $request['datereceived'] ?? ''
                );
                $slaDueDate = ($slaDays > 0 && $clockStart !== '')
                    ? addBusinessDays($clockStart, $slaDays, $link)
                    : null;

                $priorityScore = rmt_calculate_priority_score($request, $slaDueDate);
                $requestId = (int) $request['id'];
                mysqli_stmt_bind_param($updateStatement, 'ii', $priorityScore, $requestId);
                mysqli_stmt_execute($updateStatement);
                $updatedCount++;
            }

            mysqli_stmt_close($updateStatement);
            mysqli_stmt_close($selectStatement);
            mysqli_commit($link);

            $_SESSION['priority_update_token'] = bin2hex(random_bytes(32));
            header("location:/PriorityUpdates.php?lang={$lang}&status=updated&count={$updatedCount}");
            exit();
        } catch (Throwable $exception) {
            mysqli_rollback($link);
            error_log('Priority score recalculation failed: ' . $exception->getMessage());
            http_response_code(500);
            $errorMessage = $t['priority_update_failed'];
        }
    }
}

$updatedCount = isset($_GET['count']) ? max(0, (int) $_GET['count']) : 0;
$wasUpdated = ($_GET['status'] ?? '') === 'updated';
$successMessageKey = $updatedCount === 1
    ? 'priority_update_success_singular'
    : 'priority_update_success_plural';
$pageTitle = $t['priority_update_heading'];
$pageDescription = $t['priority_update_intro'];

include 'includes/template/head.php';
?>
<?php include 'includes/template/header.php'; ?>
<main role="main" property="mainContentOfPage" class="container">
    <h1 property="name" id="wb-cont"><?= htmlspecialchars($t['priority_update_heading']) ?></h1>

    <?php if ($errorMessage !== ''): ?>
        <section class="alert alert-danger" aria-labelledby="priority-update-error" tabindex="-1">
            <h2 id="priority-update-error"><?= htmlspecialchars($t['priority_update_error_heading']) ?></h2>
            <p><?= htmlspecialchars($errorMessage) ?></p>
        </section>
    <?php elseif ($wasUpdated): ?>
        <section class="alert alert-success" aria-labelledby="priority-update-success" tabindex="-1">
            <h2 id="priority-update-success"><?= htmlspecialchars($t['priority_update_success_heading']) ?></h2>
            <p><?= htmlspecialchars(sprintf($t[$successMessageKey], $updatedCount)) ?></p>
        </section>
    <?php endif; ?>

    <p><?= htmlspecialchars($t['priority_update_intro']) ?></p>

    <section class="alert alert-warning" aria-labelledby="priority-update-warning">
        <h2 id="priority-update-warning"><?= htmlspecialchars($t['priority_update_warning_heading']) ?></h2>
        <p><?= htmlspecialchars($t['priority_update_warning']) ?></p>
    </section>

    <form method="post" action="/PriorityUpdates.php?lang=<?= urlencode($lang) ?>">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['priority_update_token'], ENT_QUOTES, 'UTF-8') ?>">
        <button type="submit" class="btn btn-primary"><?= htmlspecialchars($t['priority_update_submit']) ?></button>
        <a class="btn btn-default" href="/index.php?lang=<?= urlencode($lang) ?>"><?= htmlspecialchars($t['priority_update_cancel']) ?></a>
    </form>

    <?php if ($errorMessage !== '' || $wasUpdated): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var result = document.querySelector('.alert-danger, .alert-success');
                if (result) {
                    result.focus();
                }
            });
        </script>
    <?php endif; ?>

    <?php include 'includes/template/page-details.php'; ?>
</main>
<?php include 'includes/template/footer.php'; include 'includes/template/scripts.php'; ?>