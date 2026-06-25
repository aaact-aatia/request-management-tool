<?php
/**
 * Team Details Page
 *
 * Displays team metadata, lead, manager, and members.
 */

require_once __DIR__ . '/includes/session_start.php';

require('includes/httpscheck.php');
require('sql.php');

if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'fr'], true)) {
    $_SESSION['lang'] = $_GET['lang'];
}

if (!isset($_SESSION['lang']) || !in_array($_SESSION['lang'], ['en', 'fr'], true)) {
    $_SESSION['lang'] = 'en';
}

$lang = $_SESSION['lang'];
$langFile = require("lang/{$lang}.php");
require('includes/loggedincheck.php');

$canEditTeams = in_array((int)($_SESSION['atype'] ?? 0), [1, 2, 3, 4], true);
if (!$canEditTeams) {
    header("location:/openrequest.php?lang={$lang}&status=accessdenied");
    exit();
}

$teamId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($teamId <= 0) {
    header("location:/teams.php?lang={$lang}&status=failed");
    exit();
}

$teamSql = "SELECT id, nameen, namefr, email, team_lead_user_id FROM tblteams WHERE id='{$teamId}' LIMIT 1";
$teamResult = mysqli_query($link, $teamSql);
$team = $teamResult ? mysqli_fetch_assoc($teamResult) : null;

$page = [
    'title' => [
        'en' => 'Team details',
        'fr' => 'Details de l\'equipe',
    ],
    'description' => [
        'en' => 'Team details overview',
        'fr' => 'Apercu des details de l\'equipe',
    ],
];

$pageTitle = $page['title'][$lang];
$pageDescription = $page['description'][$lang];

include 'includes/template/head.php';
?>
<?php include 'includes/template/header.php'; ?>
<main role="main" property="mainContentOfPage" class="container">
    <h1 property="name" id="wb-cont"><?= htmlspecialchars($langFile['teams_details_heading']) ?></h1>

    <?php if (!$team): ?>
        <section class="alert alert-danger">
            <h2><?= htmlspecialchars($langFile['failed_heading']) ?></h2>
            <p><?= htmlspecialchars($langFile['teams_details_not_found']) ?></p>
        </section>
        <p><a class="btn btn-default" href="teams.php?lang=<?= urlencode($lang) ?>"><?= htmlspecialchars($langFile['teams_details_back']) ?></a></p>
    <?php else: ?>
        <?php
        $leadName = $langFile['teams_details_no_lead'];
        $leadId = (int)($team['team_lead_user_id'] ?? 0);
        $managerName = $langFile['teams_details_no_manager'];

        // Fetch team lead name
        if ($leadId > 0) {
            $leadSql = "SELECT firstname, lastname FROM tblusers WHERE id='{$leadId}' AND status='1' LIMIT 1";
            $leadResult = mysqli_query($link, $leadSql);
            $leadRow = $leadResult ? mysqli_fetch_assoc($leadResult) : null;
            if (!empty($leadRow)) {
                $leadName = trim(($leadRow['firstname'] ?? '') . ' ' . ($leadRow['lastname'] ?? ''));
            }
        }

        // Fetch manager name - look for managers (atype=3) assigned to this team
        $managerSql = "SELECT firstname, lastname FROM tblusers WHERE atype='3' AND status='1' AND FIND_IN_SET('{$teamId}', team) > 0 ORDER BY firstname ASC, lastname ASC LIMIT 1";
        $managerResult = mysqli_query($link, $managerSql);
        $managerRow = $managerResult ? mysqli_fetch_assoc($managerResult) : null;
        if (!empty($managerRow)) {
            $managerName = trim(($managerRow['firstname'] ?? '') . ' ' . ($managerRow['lastname'] ?? ''));
        }

        $members = [];
        $membersSql = "SELECT id, firstname, lastname, atype FROM tblusers WHERE status='1' AND FIND_IN_SET('{$teamId}', team) > 0 ORDER BY firstname ASC, lastname ASC";
        $membersResult = mysqli_query($link, $membersSql);
        if ($membersResult) {
            while ($memberRow = mysqli_fetch_assoc($membersResult)) {
                $memberId = (int)$memberRow['id'];
                $atype = (int)$memberRow['atype'];
                if ($memberId === $leadId || $atype === 3) {
                    continue;
                }
                $members[] = trim(($memberRow['firstname'] ?? '') . ' ' . ($memberRow['lastname'] ?? ''));
            }
        }
        ?>

        <dl class="colcount-sm-2">
            <div style="break-inside: avoid;">
                <dt><?= htmlspecialchars($langFile['teams_details_name_en']) ?></dt>
                <dd><?= htmlspecialchars($team['nameen'] ?? '—') ?></dd>
            </div>
            <div style="break-inside: avoid;">
                <dt><?= htmlspecialchars($langFile['teams_details_name_fr']) ?></dt>
                <dd><?= htmlspecialchars($team['namefr'] ?? '—') ?></dd>
            </div>
            <div style="break-inside: avoid;">
                <dt><?= htmlspecialchars($langFile['teams_details_email']) ?></dt>
                <dd><?= htmlspecialchars($team['email']) ?></dd>
            </div>
            <div style="break-inside: avoid;">
                <dt><?= htmlspecialchars($langFile['teams_details_lead']) ?></dt>
                <dd><?= htmlspecialchars($leadName) ?></dd>
            </div>
            <div style="break-inside: avoid;">
                <dt><?= htmlspecialchars($langFile['teams_details_manager']) ?></dt>
                <dd><?= htmlspecialchars($managerName) ?></dd>
            </div>
        </dl>

        <h2><?= htmlspecialchars($langFile['teams_details_members']) ?></h2>
        <?php if (!empty($members)): ?>
            <ul>
                <?php foreach ($members as $memberName): ?>
                    <li><?= htmlspecialchars($memberName) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p><strong><?= htmlspecialchars($langFile['teams_details_no_members']) ?></strong></p>
        <?php endif; ?>

        <?php if ($canEditTeams): ?>
            <p><a class="wb-lbx btn btn-primary" href="includes/edit-teams.php?id=<?= (int)$team['id'] ?>&lang=<?= urlencode($lang) ?>"><?= htmlspecialchars($langFile['teams_edit']) ?></a></p>
        <?php endif; ?>
        <p><a class="btn btn-default" href="teams.php?lang=<?= urlencode($lang) ?>"><?= htmlspecialchars($langFile['teams_details_back']) ?></a></p>
    <?php endif; ?>

    <?php include 'includes/template/page-details.php'; ?>
</main>

<?php include 'includes/template/footer.php'; include 'includes/template/scripts.php'; ?>
</body>
</html>
<?php
mysqli_close($link);
?>