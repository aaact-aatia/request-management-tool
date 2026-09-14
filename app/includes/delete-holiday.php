<?php
// Start session
require_once __DIR__ . '/session_start.php';

// Check if Super Admin
if (!($_SESSION['is_superuser'] OR $_SESSION['is_admin'])) {
    header("Location: ../requests.php");
    exit();
}

// HTTPS check
require('../includes/httpscheck.php');

// Database connection
require('../sql.php');
require_once('../includes/helpers.php');
require_once('../includes/csrf.php');

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
$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 0;
$csrfToken = rmt_csrf_token('holidays');

if (empty($id)) {
    header("Location: ../holidays-mgmt.php?lang=$lang");
    exit();
}

// Fetch holiday
$holiday = $id > 0 ? rmt_db_fetch_one($link, 'SELECT * FROM tblholidays WHERE id = ?', 'i', [$id]) : null;
if ($holiday === null) {
    header("Location: ../holidays-mgmt.php?lang=$lang");
    exit();
}

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!rmt_csrf_token_is_valid('holidays', (string) ($_POST['csrf_token'] ?? ''))) {
        header("Location: ../holidays-mgmt.php?lang=$lang&status=error");
        exit();
    }
    $statement = rmt_db_execute($link, 'DELETE FROM tblholidays WHERE id = ?', 'i', [$id]);
    mysqli_stmt_close($statement);
	// Log admin action
	$adminNote = ($lang == 'fr' ? "Supprimé le jour férié : " : "Deleted holiday: ") . $holiday['name_en'] . " / " . $holiday['name_fr'] . ($lang == 'fr' ? " le " : " on ") . $holiday['holiday_date'];
	$userId = (int) ($_SESSION['pid'] ?? 0);
	$logStatement = rmt_db_execute($link, 'INSERT INTO tbladminlog (triageid, dateadded, notes, creatorid, status) VALUES (0, NOW(), ?, ?, 1)', 'si', [$adminNote, $userId]);
	mysqli_stmt_close($logStatement);

	echo '<script>window.parent.location.href = "../holidays-mgmt.php?lang=' . $lang . '&status=deleted";</script>';
	exit();
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
            <form method="post" action="/includes/delete-holiday.php?id=<?= $id ?>&lang=<?= $lang ?>" class="form-inline">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit" class="btn btn-danger"><?= $t['yes_delete'] ?></button>
            </form>
            <button type="button" class="btn btn-default popup-modal-dismiss"><?= $t['cancel'] ?></button>
        </div>
    </div>
</section>
<?php mysqli_close($link); ?>
