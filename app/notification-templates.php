<?php
/**
 * Per-team notification message templates (client and employee GC Notify content).
 *
 * The app-wide default wording is seeded via migration and can only be edited by
 * superadmins (no reset/delete - there's nothing below it to fall back to). Admins and
 * managers/team leads manage real teams, scoped to the team(s) they lead/manage - each can
 * override the default for their team, a specific service, or a specific sub-service, and
 * reset back to the default at any time.
 */

require_once __DIR__ . '/includes/session_start.php';
require('includes/httpscheck.php');
require('sql.php');
/** @var mysqli $link */
require_once('includes/helpers.php');
require('includes/loggedincheck.php');

if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'fr'], true)) {
    $_SESSION['lang'] = $_GET['lang'];
}
if (!isset($_SESSION['lang']) || !in_array($_SESSION['lang'], ['en', 'fr'], true)) {
    $_SESSION['lang'] = 'en';
}
$lang = $_SESSION['lang'];
$t = require("lang/{$lang}.php");
$isFrench = ($lang === 'fr');
$nameField = $isFrench ? 'namefr' : 'nameen';

$manageableTeams = rmt_notification_manageable_teams($link);
if (empty($manageableTeams)) {
    header("location:/settings.php?lang={$lang}&status=forbidden");
    exit();
}

$selectedTeamId = isset($_GET['team_id']) ? (int) $_GET['team_id'] : (int) $manageableTeams[0]['id'];
$manageableTeamIds = array_map(static fn(array $team): int => (int) $team['id'], $manageableTeams);
if (!in_array($selectedTeamId, $manageableTeamIds, true)) {
    $selectedTeamId = (int) $manageableTeams[0]['id'];
}

$teamScopes = rmt_notification_scopes_for_team($link, $selectedTeamId, $lang);

$selectedScope = trim((string) ($_GET['scope'] ?? 'team'));
$scopeServiceId = 0;
$scopeSubserviceId = 0;
if ($selectedScope !== 'team') {
    $isKnownScope = false;
    foreach ($teamScopes as $scopeOption) {
        if ($selectedScope === $scopeOption['type'] . ':' . $scopeOption['id']) {
            $isKnownScope = true;
            if ($scopeOption['type'] === 'service') {
                $scopeServiceId = $scopeOption['id'];
            } else {
                $scopeSubserviceId = $scopeOption['id'];
            }
            break;
        }
    }
    if (!$isKnownScope) {
        $selectedScope = 'team';
    }
}

$status = $_GET['status'] ?? '';

$page = [
    'title' => [
        'en' => 'Notification templates',
        'fr' => 'Modeles de notification',
    ],
    'description' => [
        'en' => 'Manage client and employee notification message templates by team',
        'fr' => 'Gerer les modeles de messages de notification par equipe',
    ],
];
$pageTitle = $page['title'][$lang];
$pageDescription = $page['description'][$lang];

include 'includes/template/head.php';
?>
    <?php include 'includes/template/header.php'; ?>
        <main role="main" property="mainContentOfPage" class="container">
            <h1 property="name" id="wb-cont"><?= htmlspecialchars($t['notification_templates_heading']) ?></h1>
            <p><?= htmlspecialchars($t['notification_templates_intro']) ?></p>

            <?php if ($status === 'saved') { ?>
            <section class="alert alert-success">
                <h2><?= htmlspecialchars($t['success_heading']) ?></h2>
                <ul><li><?= htmlspecialchars($t['notification_templates_saved']) ?></li></ul>
            </section>
            <?php } elseif ($status === 'reset') { ?>
            <section class="alert alert-success">
                <h2><?= htmlspecialchars($t['success_heading']) ?></h2>
                <ul><li><?= htmlspecialchars($t['notification_templates_reset_done']) ?></li></ul>
            </section>
            <?php } elseif ($status === 'failed') { ?>
            <section class="alert alert-danger">
                <h2><?= htmlspecialchars($t['failed_heading']) ?></h2>
                <ul><li><?= htmlspecialchars($t['notification_templates_failed']) ?></li></ul>
            </section>
            <?php } ?>

            <form method="get" action="/notification-templates.php" class="form-inline mrgn-bttm-md">
                <input type="hidden" name="lang" value="<?= htmlspecialchars($lang) ?>">
                <div class="form-group">
                    <label for="team_id"><span class="field-name"><?= htmlspecialchars($t['notification_templates_team_label']) ?></span></label>
                    <select class="form-control" id="team_id" name="team_id" onchange="this.form.submit()">
                        <?php foreach ($manageableTeams as $team) { ?>
                        <option value="<?= (int) $team['id'] ?>" <?= ((int) $team['id'] === $selectedTeamId) ? 'selected' : '' ?>><?= htmlspecialchars($team[$nameField]) ?></option>
                        <?php } ?>
                    </select>
                </div>
                <?php if (!empty($teamScopes)) { ?>
                <div class="form-group">
                    <label for="scope"><span class="field-name"><?= htmlspecialchars($t['notification_templates_scope_label']) ?></span></label>
                    <select class="form-control" id="scope" name="scope" onchange="this.form.submit()">
                        <option value="team" <?= ($selectedScope === 'team') ? 'selected' : '' ?>><?= htmlspecialchars($t['notification_templates_scope_team']) ?></option>
                        <?php foreach ($teamScopes as $scopeOption) {
                            $optionValue = $scopeOption['type'] . ':' . $scopeOption['id'];
                        ?>
                        <option value="<?= htmlspecialchars($optionValue) ?>" <?= ($selectedScope === $optionValue) ? 'selected' : '' ?>><?= htmlspecialchars($scopeOption['label']) ?></option>
                        <?php } ?>
                    </select>
                </div>
                <?php } ?>
            </form>
            <?php if ($selectedScope !== 'team') { ?>
            <p class="alert alert-info"><?= htmlspecialchars($t['notification_templates_scope_note']) ?></p>
            <?php } ?>

            <?php
            foreach (rmt_notification_audiences() as $audience) {
                $audienceLabel = $audience === 'client'
                    ? $t['notification_templates_audience_client']
                    : $t['notification_templates_audience_employee'];
            ?>
            <h2><?= htmlspecialchars($audienceLabel) ?></h2>
            <table class="wb-tables table table-striped table-hover">
                <thead>
                    <tr>
                        <th><?= htmlspecialchars($t['notification_templates_col_event']) ?></th>
                        <th><?= htmlspecialchars($t['notification_templates_lang_en']) ?></th>
                        <th><?= htmlspecialchars($t['notification_templates_lang_fr']) ?></th>
                        <th><?= htmlspecialchars($t['notification_templates_col_actions']) ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php
                foreach (rmt_notification_events_for_audience($audience) as $event) {
                    $eventLabel = $t['notification_templates_event_' . $event] ?? $event;
                    $languageSummaries = [];
                    foreach (['en', 'fr'] as $templateLanguage) {
                        $ownRow = rmt_notification_template_fetch($link, $selectedTeamId, $audience, $event, $templateLanguage, $scopeServiceId, $scopeSubserviceId);

                        if ($ownRow !== null) {
                            $sourceLabel = $t['notification_templates_source_custom'];
                            $updated = trim((string) ($ownRow['dateupdated'] ?? ''));
                        } else {
                            $fallback = rmt_notification_template_resolve($link, $selectedTeamId, $audience, $event, $templateLanguage, $scopeServiceId, $scopeSubserviceId);
                            if ($fallback !== null && (int) $fallback['team_id'] === $selectedTeamId && $selectedTeamId !== RMT_NOTIFICATION_GLOBAL_TEAM_ID) {
                                $sourceLabel = $t['notification_templates_source_team'];
                            } elseif ($fallback !== null && (int) $fallback['team_id'] === RMT_NOTIFICATION_GLOBAL_TEAM_ID) {
                                $sourceLabel = $t['notification_templates_source_global'];
                            } else {
                                $sourceLabel = $t['notification_templates_source_builtin'];
                            }
                            $updated = '';
                        }

                        $languageSummaries[$templateLanguage] = $sourceLabel . ($updated !== '' ? ' (' . $updated . ')' : '');
                    }
                ?>
                    <tr>
                        <td><?= htmlspecialchars($eventLabel) ?></td>
                        <td><?= htmlspecialchars($languageSummaries['en']) ?></td>
                        <td><?= htmlspecialchars($languageSummaries['fr']) ?></td>
                        <td>
                            <a class="btn btn-primary btn-block" href="/notification-template-edit.php?team_id=<?= $selectedTeamId ?>&service_id=<?= $scopeServiceId ?>&subservice_id=<?= $scopeSubserviceId ?>&audience=<?= urlencode($audience) ?>&event=<?= urlencode($event) ?>&lang=<?= htmlspecialchars($lang) ?>">
                                <?= htmlspecialchars($t['notification_templates_edit']) ?><span class="wb-inv"> <?= htmlspecialchars($eventLabel) ?></span>
                            </a>
                            <a class="wb-lbx lbx-modal btn btn-default btn-block" href="includes/notification-template-preview-dialog.php?team_id=<?= $selectedTeamId ?>&service_id=<?= $scopeServiceId ?>&subservice_id=<?= $scopeSubserviceId ?>&audience=<?= urlencode($audience) ?>&event=<?= urlencode($event) ?>&lang=<?= htmlspecialchars($lang) ?>">
                                <?= htmlspecialchars($t['notification_templates_preview']) ?><span class="wb-inv"> <?= htmlspecialchars($eventLabel) ?></span>
                            </a>
                        </td>
                    </tr>
                <?php
                }
                ?>
                </tbody>
            </table>
            <?php } ?>

            <?php include 'includes/template/page-details.php'; ?>
        </main>

        <?php include 'includes/template/footer.php'; include 'includes/template/scripts.php'; ?>
    </body>
</html>
<?php
mysqli_close($link);
