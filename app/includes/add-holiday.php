<?php
// Start session
require_once __DIR__ . '/session_start.php';

// Check if Super Admin
if (!rmt_has_admin_access()) {
    header("Location: ../requests.php");
    exit();
}

// HTTPS check
require('../includes/httpscheck.php');

// Database connection
require('../sql.php');
require_once('helpers.php');
require_once('csrf.php');

// Get language
$lang = isset($_POST['lang']) && in_array($_POST['lang'], ['en', 'fr'], true) ? $_POST['lang'] : 'en';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!rmt_csrf_token_is_valid('holidays', (string) ($_POST['csrf_token'] ?? ''))) {
        header("Location: ../holidays-mgmt.php?lang=$lang&status=error");
        exit();
    }
    $holiday_date = trim((string) ($_POST['holiday_date'] ?? ''));
    $name_en = trim((string) ($_POST['name_en'] ?? ''));
    $name_fr = trim((string) ($_POST['name_fr'] ?? ''));
    $recurring = isset($_POST['recurring']) ? 1 : 0;
    $status = isset($_POST['status']) ? 1 : 0;
    $dateObject = DateTime::createFromFormat('!Y-m-d', $holiday_date);
    if ($dateObject === false || $dateObject->format('Y-m-d') !== $holiday_date || $name_en === '' || $name_fr === '') {
        header("Location: ../holidays-mgmt.php?lang=$lang&status=error");
        exit();
    }
    
    // Check if holiday already exists
    $existingHoliday = rmt_db_fetch_one($link, 'SELECT id FROM tblholidays WHERE holiday_date = ?', 's', [$holiday_date]);
    if ($existingHoliday !== null) {
        // Holiday already exists, redirect with error
        header("Location: ../holidays-mgmt.php?lang=$lang&status=exists");
        exit();
    }
    
    // Insert holiday
    $statement = rmt_db_execute($link, 'INSERT INTO tblholidays (holiday_date, name_en, name_fr, recurring, status) VALUES (?, ?, ?, ?, ?)', 'sssii', [$holiday_date, $name_en, $name_fr, $recurring, $status]);
    mysqli_stmt_close($statement);

    // Log admin action
    $adminNote = "Added holiday: $name_en / $name_fr on $holiday_date";
    $userId = (int) ($_SESSION['pid'] ?? 0);
    $logStatement = rmt_db_execute($link, 'INSERT INTO tbladminlog (triageid, dateadded, notes, language_code, creatorid, status) VALUES (0, NOW(), ?, ?, ?, 1)', 'ssi', [$adminNote, $lang, $userId]);
    mysqli_stmt_close($logStatement);

    header("Location: ../holidays-mgmt.php?lang=$lang&status=added");
} else {
    header("Location: ../holidays-mgmt.php?lang=$lang");
}

mysqli_close($link);
?>
