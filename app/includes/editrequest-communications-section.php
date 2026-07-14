<?php
/**
 * Edit Request - Communications Section
 * Displays communication logs and allows adding new entries
 */
?>

<h2><?php echo $t['communications_heading']; ?></h2>

<?php if ($status === 'logsuccess'): ?>
<section id="log-status-message" class="alert alert-success" role="status" aria-live="polite" tabindex="-1">
    <h3><?php echo htmlspecialchars($t['log_success_heading'], ENT_QUOTES, 'UTF-8'); ?></h3>
    <p><?php echo htmlspecialchars($t['log_success_message'], ENT_QUOTES, 'UTF-8'); ?></p>
</section>
<?php elseif ($status === 'logfailed'): ?>
<section id="log-status-message" class="alert alert-danger" role="alert" aria-live="assertive" tabindex="-1">
    <h3><?php echo htmlspecialchars($t['log_failed_heading'], ENT_QUOTES, 'UTF-8'); ?></h3>
    <p><?php echo htmlspecialchars($t['log_failed_message'], ENT_QUOTES, 'UTF-8'); ?></p>
</section>
<?php endif; ?>

<?php
$x = 1;
$canEditCommunicationLogs = in_array((int)($_SESSION['atype'] ?? 0), [3, 4, 5], true) || !empty($_SESSION['is_superuser']) || !empty($_SESSION['is_admin']);
$canViewExistingComms = !empty($_SESSION['is_superuser']) || !empty($_SESSION['is_admin']) || in_array((int)($_SESSION['atype'] ?? 0), [3, 4, 6], true);
$existingCommsCount = 0;
if ($canViewExistingComms) {
    $existingCommsCountResult = mysqli_query($link, "SELECT COUNT(*) AS total FROM tbladminlog WHERE triageid = '$requestuid' AND status = '1'");
    $existingCommsCountRow = $existingCommsCountResult ? mysqli_fetch_assoc($existingCommsCountResult) : null;
    $existingCommsCount = (int)($existingCommsCountRow['total'] ?? 0);
}
// Grab existing communication logs
$result2 = mysqli_query($link, "SELECT ID, notes FROM tblcommlog WHERE triageid = '$requestuid'");
while ($row2 = mysqli_fetch_assoc($result2)) {
    $ocommlogid = $row2['ID'];
    $ocommlog = preg_replace('/^\s*(Department\/agency|Ministère\/organisme):\s*.*(?:\R|$)/miu', '', (string)$row2['notes']);
    $ocommlog = trim((string)$ocommlog);
    if ($ocommlog === '') {
        continue;
    }
?>
<div class="form-group">
    <label for="commlog<?php echo $x; ?>"><span class="field-name"><?php echo $t['edit_original_commlog']; ?>:</span></label>
    <textarea class="form-control" id="commlog<?php echo $x; ?>" name="commlog<?php echo $x; ?>" 
              cols="50" rows="10" <?php echo $canEditCommunicationLogs ? '' : 'readonly'; ?>><?php echo htmlspecialchars($ocommlog, ENT_QUOTES, 'UTF-8'); ?></textarea>
    <input type="hidden" id="commlogid<?php echo $x; ?>" name="commlogid<?php echo $x; ?>" value="<?php echo $ocommlogid; ?>" />
</div>
<?php
    $x++;
}
?>

<div class="form-group">
    <label for="adminnotes"><span class="field-name"><?php echo $t['add_new_commlog']; ?>:</span></label>
    <textarea class="form-control" id="adminnotes" name="adminnotes" cols="50" rows="10" <?php echo $canEditCommunicationLogs ? '' : 'readonly'; ?>></textarea>
</div>

<div class="form-group form-buttons">
    <?php if ($canEditCommunicationLogs): ?>
    <button type="submit" name="form_action" value="add_log" class="btn btn-primary" formnovalidate><?php echo htmlspecialchars($t['add_log_button'], ENT_QUOTES, 'UTF-8'); ?></button>
    <?php endif; ?>
    <a class="wb-lbx btn btn-primary" href="includes/ecomms<?php echo $langSuffix; ?>.php?id=<?php echo $row['id']; ?>">
        <?php echo htmlspecialchars($t['view_existing_comms'] . ' (' . (int)$existingCommsCount . ')', ENT_QUOTES, 'UTF-8'); ?>
    </a>
</div>
