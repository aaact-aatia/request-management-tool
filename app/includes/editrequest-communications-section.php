<?php
/**
 * Edit Request - Communications Section
 * Displays communication logs and allows adding new entries
 */
?>

<h2 id="communications"><?php echo $t['communications_heading']; ?></h2>
<p><?php echo htmlspecialchars($t['communications_language_guidance'], ENT_QUOTES, 'UTF-8'); ?></p>

<?php if ($status === 'logsuccess'): ?>
<section id="log-status-message" class="alert alert-success" role="status" aria-live="polite" aria-atomic="true" tabindex="-1">
    <h3><?php echo htmlspecialchars($t['log_success_heading'], ENT_QUOTES, 'UTF-8'); ?></h3>
    <p><?php echo htmlspecialchars($t['log_success_message'], ENT_QUOTES, 'UTF-8'); ?></p>
</section>
<?php elseif ($status === 'logfailed'): ?>
<section id="log-status-message" class="alert alert-danger" role="alert" aria-live="assertive" aria-atomic="true" tabindex="-1">
    <h3><?php echo htmlspecialchars($t['log_failed_heading'], ENT_QUOTES, 'UTF-8'); ?></h3>
    <p><?php echo htmlspecialchars($t['log_failed_message'], ENT_QUOTES, 'UTF-8'); ?></p>
</section>
<?php endif; ?>

<?php
$canEditCommunicationLogs = in_array((int)($_SESSION['atype'] ?? 0), [3, 4, 5], true) || rmt_has_admin_access();
$canViewExistingComms = rmt_has_admin_access() || in_array((int)($_SESSION['atype'] ?? 0), [3, 4, 6], true);
$existingCommsCount = 0;
$existingComms = [];
if ($canViewExistingComms) {
    $existingCommsResult = mysqli_query(
        $link,
        "SELECT adminlog.dateadded, adminlog.notes, adminlog.language_code, adminlog.creatorid,
                users.firstname, users.lastname
         FROM tbladminlog AS adminlog
         LEFT JOIN tblusers AS users ON users.id = adminlog.creatorid
         WHERE adminlog.triageid = '$requestuid' AND adminlog.status = '1'
         ORDER BY adminlog.id DESC"
    );
    if ($existingCommsResult) {
        while ($existingComm = mysqli_fetch_assoc($existingCommsResult)) {
            $existingComms[] = $existingComm;
        }
    }
    $existingCommsCount = count($existingComms);
}
?>
<div class="form-group">
    <label for="adminnotes"><span class="field-name"><?php echo $t['add_new_commlog']; ?>:</span></label>
    <input class="form-control full-width" type="text" id="adminnotes" name="adminnotes" <?php echo $canEditCommunicationLogs ? '' : 'readonly'; ?>>
</div>

<div class="form-group form-buttons">
    <?php if ($canEditCommunicationLogs): ?>
    <button type="submit" name="form_action" value="add_log" class="btn btn-primary" formnovalidate><?php echo htmlspecialchars($t['add_log_button'], ENT_QUOTES, 'UTF-8'); ?></button>
    <?php endif; ?>
</div>

<?php if ($canViewExistingComms): ?>
<section aria-labelledby="existing-communications-heading">
    <h3 id="existing-communications-heading">
        <?php echo htmlspecialchars($t['view_existing_comms'] . ' (' . $existingCommsCount . ')', ENT_QUOTES, 'UTF-8'); ?>
    </h3>
    <?php if ($existingCommsCount > 0): ?>
    <dl>
        <?php foreach ($existingComms as $existingComm): ?>
        <dt>
            <?php echo htmlspecialchars((string)$existingComm['dateadded'], ENT_QUOTES, 'UTF-8'); ?>
            <?php if ((int)$existingComm['creatorid'] !== 0): ?>
                - <?php echo htmlspecialchars(trim((string)$existingComm['firstname'] . ' ' . (string)$existingComm['lastname']), ENT_QUOTES, 'UTF-8'); ?>
            <?php endif; ?>
        </dt>
        <?php
        $entryLanguage = in_array((string)($existingComm['language_code'] ?? ''), ['en', 'fr'], true)
            ? (string)$existingComm['language_code']
            : '';
        $languageAttribute = $entryLanguage !== '' && $entryLanguage !== $lang
            ? ' lang="' . htmlspecialchars($entryLanguage, ENT_QUOTES, 'UTF-8') . '"'
            : '';
        ?>
        <dd<?php echo $languageAttribute; ?>><?php echo nl2br(htmlspecialchars((string)$existingComm['notes'], ENT_QUOTES, 'UTF-8')); ?></dd>
        <?php endforeach; ?>
    </dl>
    <?php else: ?>
    <p><?php echo htmlspecialchars($t['ecomms_no_comms'] ?? ($lang === 'fr' ? 'Aucune communication disponible!' : 'No communications available!'), ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>
</section>
<?php endif; ?>
