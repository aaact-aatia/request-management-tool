<?php
/**
 * Account Type Switcher Handler
 * 
 * Handles switching between account types for development testing.
 * Only available to superadmin users.
 * 
 * @package RMT
 * @since 2.1.0
 */

// Grab MySQL connection
require('../sql.php');

// Verify user is logged in and has superuser permissions
if (!isset($_SESSION['pid']) || !isset($_SESSION['is_superuser']) || $_SESSION['is_superuser'] != 1) {
    // Not authorized - redirect
    header("Location: /openrequest.php?lang=" . ($_SESSION['lang'] ?? 'en'));
    exit();
}

// Get language
$lang_code = $_SESSION['lang'] ?? 'en';

// Handle reset to superadmin's actual role
if (isset($_POST['reset_atype'])) {
    // Set atype to 1 (full superadmin permissions), not to their database atype
    $_SESSION['atype'] = 1;
    unset($_SESSION['test_team_ids']);
    unset($_SESSION['test_employee_id']);
    
    // Redirect back to settings page
    header("Location: /settings.php?lang={$lang_code}&status=success");
    exit();
}

// Handle account type switch
if (isset($_POST['test_atype'])) {
    $newAtype = mysqli_real_escape_string($link, $_POST['test_atype']);
    $testTeamId = mysqli_real_escape_string($link, $_POST['test_team_id'] ?? '');
    $testEmployeeId = mysqli_real_escape_string($link, $_POST['test_employee_id'] ?? '');
    
    // Verify this is a valid account type
    $result = mysqli_query($link, "SELECT id FROM tblaccounttype WHERE id = '{$newAtype}' AND status = 1");
    
    if (mysqli_num_rows($result) > 0) {
        // Valid account type - switch to it
        $_SESSION['atype'] = $newAtype;

        if ((int)$newAtype === 4 && $testTeamId !== '' && ctype_digit($testTeamId)) {
			$teamCheck = mysqli_query($link, "SELECT id FROM tblteams WHERE id = '$testTeamId' AND status = 1 LIMIT 1");
            if ($teamCheck && mysqli_num_rows($teamCheck) > 0) {
                $_SESSION['test_team_ids'] = (string)$testTeamId;
            } else {
                unset($_SESSION['test_team_ids']);
            }
        } else {
            unset($_SESSION['test_team_ids']);
        }

        if ((int)$newAtype === 5 && $testEmployeeId !== '' && ctype_digit($testEmployeeId)) {
            $employeeCheck = mysqli_query($link, "SELECT id FROM tblusers WHERE id = '$testEmployeeId' AND status = 1 AND atype = 5 LIMIT 1");
            if ($employeeCheck && mysqli_num_rows($employeeCheck) > 0) {
                $_SESSION['test_employee_id'] = (string)$testEmployeeId;
            } else {
                unset($_SESSION['test_employee_id']);
            }
        } else {
            unset($_SESSION['test_employee_id']);
        }
    }
    
    // Redirect back to settings page
    header("Location: /settings.php?lang={$lang_code}&status=success");
    exit();
}

// If no action specified, redirect to settings
header("Location: /settings.php?lang={$lang_code}");
exit();
?>
