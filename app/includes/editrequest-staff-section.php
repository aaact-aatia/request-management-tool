<?php
if (isset($_SERVER['SCRIPT_FILENAME']) && realpath(__FILE__) === realpath((string) $_SERVER['SCRIPT_FILENAME'])) {
    http_response_code(404);
    exit();
}

/**
 * Edit Request - AAACT Admin Section
 * Team member assignment, SLA timer, and admin controls
 */
?>

<h2><?php echo $t['staff_use_only']; ?></h2>

<div class="form-group">
    <label for="workerid"><span class="field-name"><?php echo $t['assigned_team_member']; ?>:</span></label>
    <select class="form-control" id="workerid" name="workerid">
        <option value="0"><?php echo $t['select_team_member']; ?></option>
        <?php 
        // Resolve the contact ID for this request from first-tier catalogue.
        // Fall back to legacy service/subservice ownership for older records.
        $contactid = 0;
        $hasCatalogueContact = function_exists('rmt_db_column_exists')
            && rmt_db_column_exists($link, 'tblcatalogue', 'contactid');
        if ($hasCatalogueContact && !empty($row['catalogueid'])) {
            $r = mysqli_query($link, "SELECT contactid FROM tblcatalogue WHERE id='" . (int)$row['catalogueid'] . "'");
            $cr = mysqli_fetch_assoc($r);
            $contactid = (int)($cr['contactid'] ?? 0);
        }
        if (!empty($row['subserviceid'])) {
            if (!$contactid) {
                $r = mysqli_query($link, "SELECT contactid FROM tblsubservices WHERE id='" . (int)$row['subserviceid'] . "'");
                $cr = mysqli_fetch_assoc($r);
                $contactid = (int)($cr['contactid'] ?? 0);
            }
        }
        if (!$contactid && !empty($row['serviceid'])) {
            $r = mysqli_query($link, "SELECT contactid FROM tblservices WHERE id='" . (int)$row['serviceid'] . "'");
            $cr = mysqli_fetch_assoc($r);
            $contactid = (int)($cr['contactid'] ?? 0);
        }

        // Default to AAACT (contactid=1) when service has no team assignment
        if (!$contactid) {
            $contactid = 1;
        }

        // Show employees (atype 1-5) filtered by team contact if known, otherwise all
        $result2 = mysqli_query($link, "SELECT * FROM tblusers WHERE status='1' AND atype <= 5 ORDER BY firstname ASC, lastname ASC");
        while ($row2 = mysqli_fetch_array($result2)) {
            $tarray = array_filter(explode(",", $row2['team']));
            if ($contactid && !in_array($contactid, $tarray)) {
                continue;
            }
        ?>
        <option value="<?php echo $row2['id']; ?>" <?php if ($row['workerid'] == $row2['id']) { ?>selected<?php } ?>>
            <?php echo $row2['firstname']; ?> <?php echo $row2['lastname']; ?>
        </option>
        <?php
            }
        ?>
    </select>
</div>

<?php
// SLA timer - locked to certain account types
if (canManageSLA()) {
    $eslatimer = $row['slatimer'];
    if (empty($eslatimer) || is_null($eslatimer)) {
        $slatimer = $row['datereceived'];
    } else {
        $slatimer = $eslatimer;
    }
?>
<div class="form-group">
    <label for="slatimer"><span class="field-name"><?php echo $t['reset_sla_timer']; ?>:</span></label>
    <input type="date" class="form-control" id="slatimer" name="slatimer"
           min="<?php echo date('Y-m-d', strtotime('-1 years')); ?>" 
           max="<?php echo date('Y-m-d'); ?>"
           value="<?php echo $slatimer; ?>" required />
</div>
<?php 
} else {
    // Non-admin users get hidden field
    $eslatimer = $row['slatimer'];
    if (empty($eslatimer) || is_null($eslatimer)) {
        $slatimer = $row['datereceived'];
    } else {
        $slatimer = $eslatimer;
    }
?>
<input type="hidden" id="slatimer" name="slatimer" value="<?php echo $slatimer; ?>" />
<?php	
}
?>
