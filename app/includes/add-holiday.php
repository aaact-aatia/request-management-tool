<?php
// Start session
require_once __DIR__ . '/session_start.php';

// Check if Super Admin
if ($_SESSION['atype'] != 1) {
    header("Location: ../index.php");
    exit();
}

// HTTPS check
require('../includes/httpscheck.php');

// Database connection
require('../sql.php');

// Get language
$lang = isset($_POST['lang']) ? $_POST['lang'] : 'en';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $holiday_date = mysqli_real_escape_string($link, $_POST['holiday_date']);
    $name_en = mysqli_real_escape_string($link, $_POST['name_en']);
    $name_fr = mysqli_real_escape_string($link, $_POST['name_fr']);
    $recurring = isset($_POST['recurring']) ? 1 : 0;
    $status = isset($_POST['status']) ? 1 : 0;
    
    // Check if holiday already exists
    $checkSql = "SELECT id FROM tblholidays WHERE holiday_date = '$holiday_date'";
    $checkResult = rmt_admin_query($link, $checkSql);
    
    if (rmt_result_num_rows($checkResult) > 0) {
        // Holiday already exists, redirect with error
        header("Location: ../holidays-mgmt.php?lang=$lang&status=exists");
        exit();
    }
    
    // Insert holiday
    $sql = "INSERT INTO tblholidays (holiday_date, name_en, name_fr, recurring, status) 
            VALUES ('$holiday_date', '$name_en', '$name_fr', $recurring, $status)";
    
    if (rmt_admin_query($link, $sql)) {
        // Log admin action
        $adminNote = "Added holiday: $name_en / $name_fr on $holiday_date";
        $userId = $_SESSION['pid'];
        $logSql = "INSERT INTO tbladminlog (triageid, dateadded, notes, creatorid, status) 
                   VALUES (0, NOW(), '$adminNote', $userId, 1)";
        rmt_admin_query($link, $logSql);
        
        header("Location: ../holidays-mgmt.php?lang=$lang&status=added");
    } else {
        header("Location: ../holidays-mgmt.php?lang=$lang&status=error");
    }
} else {
    header("Location: ../holidays-mgmt.php?lang=$lang");
}

mysqli_close($link);
?>
