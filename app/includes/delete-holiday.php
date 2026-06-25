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
$lang = isset($_GET['lang']) && $_GET['lang'] === 'fr' ? 'fr' : 'en';

// Translations
$translations = [
    'en' => [
        'page_title' => 'Delete Holiday',
        'heading' => 'Delete Holiday',
        'confirm_message' => 'Are you sure you want to delete this holiday?',
        'date_label' => 'Date:',
        'name_en_label' => 'Name (English):',
        'name_fr_label' => 'Name (French):',
        'warning_message' => 'This action cannot be undone.',
        'yes_delete' => 'Yes, Delete',
        'cancel' => 'Cancel',
    ],
    'fr' => [
        'page_title' => 'Supprimer le jour férié',
        'heading' => 'Supprimer le jour férié',
        'confirm_message' => 'Êtes-vous sûr de vouloir supprimer ce jour férié?',
        'date_label' => 'Date :',
        'name_en_label' => 'Nom (anglais) :',
        'name_fr_label' => 'Nom (français) :',
        'warning_message' => 'Cette action ne peut pas être annulée.',
        'yes_delete' => 'Oui, supprimer',
        'cancel' => 'Annuler',
    ]
];

$t = $translations[$lang];

// Get holiday ID
$id = isset($_GET['id']) ? mysqli_real_escape_string($link, $_GET['id']) : '';

if (empty($id)) {
    header("Location: ../holidays-mgmt.php?lang=$lang");
    exit();
}

// Fetch holiday
$sql = "SELECT * FROM tblholidays WHERE id = '$id'";
$result = rmt_admin_query($link, $sql);

if (rmt_result_num_rows($result) == 0) {
    header("Location: ../holidays-mgmt.php?lang=$lang");
    exit();
}

$holiday = mysqli_fetch_assoc($result);

// Handle deletion
if (isset($_GET['confirm']) && $_GET['confirm'] == 'yes') {
    $deleteSql = "DELETE FROM tblholidays WHERE id = '$id'";
    
    if (rmt_admin_query($link, $deleteSql)) {
        // Log admin action
        $adminNote = ($lang == 'fr' ? "Supprimé le jour férié : " : "Deleted holiday: ") . $holiday['name_en'] . " / " . $holiday['name_fr'] . ($lang == 'fr' ? " le " : " on ") . $holiday['holiday_date'];
        $userId = $_SESSION['pid'];
        $logSql = "INSERT INTO tbladminlog (triageid, dateadded, notes, creatorid, status) 
                   VALUES (0, NOW(), '$adminNote', $userId, 1)";
        rmt_admin_query($link, $logSql);
        
        echo '<script>window.parent.location.href = "../holidays-mgmt.php?lang=' . $lang . '&status=deleted";</script>';
        exit();
    }
}
?>
<section id="delete-holiday-modal" class="modal-dialog modal-content overlay-def">
    <header class="modal-header">
        <h2 class="modal-title"><?= $t['heading'] ?></h2>
    </header>
    <div class="modal-body">
        <div class="alert alert-warning" role="alert">
            <p><strong><?= $t['confirm_message'] ?></strong></p>
            <dl>
                <dt><?= $t['date_label'] ?></dt>
                <dd><?= htmlspecialchars($holiday['holiday_date']) ?></dd>
                <dt><?= $t['name_en_label'] ?></dt>
                <dd><?= htmlspecialchars($holiday['name_en']) ?></dd>
                <dt><?= $t['name_fr_label'] ?></dt>
                <dd><?= htmlspecialchars($holiday['name_fr']) ?></dd>
            </dl>
            <p><?= $t['warning_message'] ?></p>
        </div>
        
        <div class="form-group">
            <a href="?id=<?= $id ?>&lang=<?= $lang ?>&confirm=yes" class="btn btn-danger"><?= $t['yes_delete'] ?></a>
            <button type="button" class="btn btn-default popup-modal-dismiss"><?= $t['cancel'] ?></button>
        </div>
    </div>
</section>
<?php mysqli_close($link); ?>
