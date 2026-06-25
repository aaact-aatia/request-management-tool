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
    $_SESSION['atype'] = $_SESSION['primary_atype'];
    
    // Redirect back to settings page
    header("Location: /settings.php?lang={$lang_code}&status=success");
    exit();
}

// Handle account type switch
if (isset($_POST['test_atype'])) {
    $newAtype = mysqli_real_escape_string($link, $_POST['test_atype']);
    
    // Verify this is a valid account type
    $result = mysqli_query($link, "SELECT id FROM tblaccounttype WHERE id = '{$newAtype}' AND status = 1");
    
    if (mysqli_num_rows($result) > 0) {
        // Valid account type - switch to it
        $_SESSION['atype'] = $newAtype;
    }
    
    // Redirect back to settings page
    header("Location: /settings.php?lang={$lang_code}&status=success");
    exit();
}

// If no action specified, redirect to settings
header("Location: /settings.php?lang={$lang_code}");
exit();
?>
