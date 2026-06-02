<?php
// Grab MySQL connection (includes session management)
require('sql.php');
/** @var mysqli $link */

// Handle language from query string or session
if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'fr'])) {
    $_SESSION['lang'] = $_GET['lang'];
}

// Set default language
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'en';
}

// HTTPS check
require('includes/httpscheck.php');

// Check if logged in
require('includes/loggedincheck.php');

// Check if Super Admin
if ($_SESSION['atype'] != 1) {
    header("Location: index.php");
    exit();
}

// Translations
$translations = [
    'en' => [
        'page_title' => 'Holiday Management - Request Management Tool - IT Accessibility Office',
        'heading' => 'Holiday Management',
        'add_holiday' => 'Add New Holiday',
        'holiday_date' => 'Holiday Date',
        'holiday_name' => 'Holiday Name',
        'name_en' => 'Name (English)',
        'name_fr' => 'Name (French)',
        'recurring' => 'Recurring',
        'status' => 'Status',
        'actions' => 'Actions',
        'edit' => 'Edit',
        'delete' => 'Delete',
        'yes' => 'Yes',
        'no' => 'No',
        'active' => 'Active',
        'inactive' => 'Inactive',
        'no_holidays' => 'No holidays found.',
        'total_holidays' => 'Total Holidays',
        'filter_year' => 'Filter by Year:',
        'all_years' => 'All Years',
        'success_added' => 'Holiday added successfully!',
        'success_updated' => 'Holiday updated successfully!',
        'success_deleted' => 'Holiday deleted successfully!',
        'required' => 'required',
        'recurring_help' => 'Check if this holiday repeats annually (e.g., Christmas, Canada Day)',
        'add_button' => 'Add Holiday',
        'cancel' => 'Cancel',
    ],
    'fr' => [
        'page_title' => 'Gestion des jours fériés - Outil de gestion des demandes - Bureau de l\'accessibilité des TI',
        'heading' => 'Gestion des jours fériés',
        'add_holiday' => 'Ajouter un jour férié',
        'holiday_date' => 'Date du jour férié',
        'holiday_name' => 'Nom du jour férié',
        'name_en' => 'Nom (anglais)',
        'name_fr' => 'Nom (français)',
        'recurring' => 'Récurrent',
        'status' => 'Statut',
        'actions' => 'Actions',
        'edit' => 'Modifier',
        'delete' => 'Supprimer',
        'yes' => 'Oui',
        'no' => 'Non',
        'active' => 'Actif',
        'inactive' => 'Inactif',
        'no_holidays' => 'Aucun jour férié trouvé.',
        'total_holidays' => 'Total des jours fériés',
        'filter_year' => 'Filtrer par année :',
        'all_years' => 'Toutes les années',
        'success_added' => 'Jour férié ajouté avec succès!',
        'success_updated' => 'Jour férié mis à jour avec succès!',
        'success_deleted' => 'Jour férié supprimé avec succès!',
        'required' => 'obligatoire',
        'recurring_help' => 'Cochez si ce jour férié se répète chaque année (par ex., Noël, fête du Canada)',
        'add_button' => 'Ajouter le jour férié',
        'cancel' => 'Annuler',
    ]
];

// Store language code
$lang = $_SESSION['lang'];
$t = $translations[$lang];

// Handle status messages
$statusMessage = '';
if (isset($_GET['status'])) {
    if ($_GET['status'] == 'added') {
        $statusMessage = '<div class="alert alert-success" role="alert">' . $t['success_added'] . '</div>';
    } elseif ($_GET['status'] == 'updated') {
        $statusMessage = '<div class="alert alert-success" role="alert">' . $t['success_updated'] . '</div>';
    } elseif ($_GET['status'] == 'deleted') {
        $statusMessage = '<div class="alert alert-success" role="alert">' . $t['success_deleted'] . '</div>';
    }
}

// =============================================================================
// PAGE FRONTMATTER - Define page metadata
// =============================================================================
$page = [
	'title' => [
		'en' => 'Holiday Management',
		'fr' => 'Gestion des jours fériés'
	],
	'description' => [
		'en' => 'Manage government holidays for SLA calculations',
		'fr' => 'Gérer les jours fériés du gouvernement pour les calculs de NES'
	]
];

// Extract values for current language
$pageTitle = $page['title'][$lang];
$pageDescription = $page['description'][$lang];

// Get filter year
$filterYear = isset($_GET['year']) ? mysqli_real_escape_string($link, $_GET['year']) : '';

// Build query - show only future holidays
$today = date('Y-m-d');
$sql = "SELECT * FROM tblholidays WHERE holiday_date >= '$today'";
if ($filterYear != '') {
    $sql .= " AND YEAR(holiday_date) = '$filterYear'";
}
$sql .= " ORDER BY holiday_date ASC";

$result = mysqli_query($link, $sql);

// Get distinct years for filter
$yearsResult = mysqli_query($link, "SELECT DISTINCT YEAR(holiday_date) as year FROM tblholidays WHERE holiday_date >= '$today' ORDER BY year ASC");
$years = [];
while ($row = mysqli_fetch_assoc($yearsResult)) {
    $years[] = $row['year'];
}

// Include template head
include 'includes/template/head.php';
?>
	<?php include 'includes/template/header.php'; ?>
    
    <main role="main" property="mainContentOfPage" class="container">
        <h1><?= $t['heading'] ?></h1>
        
        <?= $statusMessage ?>
        
        <div class="row">
            <div class="col-md-6">
                <p><a href="#addHolidayModal" class="wb-lbx btn btn-primary"><?= $t['add_holiday'] ?></a></p>
            </div>
            <div class="col-md-6">
                <form method="get" action="" class="form-inline pull-right">
                    <input type="hidden" name="lang" value="<?= $lang ?>">
                    <div class="form-group">
                        <label for="year" class="mrgn-rght-sm"><?= $t['filter_year'] ?></label>
                        <select name="year" id="year" class="form-control" onchange="this.form.submit()">
                            <option value=""><?= $t['all_years'] ?></option>
                            <?php foreach ($years as $year): ?>
                                <option value="<?= $year ?>" <?= $filterYear == $year ? 'selected' : '' ?>><?= $year ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>
        </div>
        
        <?php if (mysqli_num_rows($result) > 0): ?>
            <p><?= $t['total_holidays'] ?><?= $lang == 'fr' ? ' :' : ':' ?> <strong><?= mysqli_num_rows($result) ?></strong></p>
            
            <table class="wb-tables table table-striped" data-wb-tables='{"ordering": true, "paging": true, "pageLength": 25}'>
                <thead>
                    <tr>
                        <th><?= $t['holiday_date'] ?></th>
                        <th><?= $t['holiday_name'] ?></th>
                        <th><?= $t['recurring'] ?></th>
                        <th><?= $t['status'] ?></th>
                        <th><?= $t['actions'] ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['holiday_date']) ?></td>
                            <td><?= htmlspecialchars($row[$lang == 'fr' ? 'name_fr' : 'name_en']) ?></td>
                            <td><?= $row['recurring'] ? $t['yes'] : $t['no'] ?></td>
                            <td><?= $row['status'] ? $t['active'] : $t['inactive'] ?></td>
                            <td>
                                <a href="includes/edit-holiday.php?id=<?= $row['id'] ?>&lang=<?= $lang ?>" class="wb-lbx btn btn-sm btn-default"><?= $t['edit'] ?></a>
                                <a href="includes/delete-holiday.php?id=<?= $row['id'] ?>&lang=<?= $lang ?>" class="wb-lbx btn btn-sm btn-danger"><?= $t['delete'] ?></a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p><?= $t['no_holidays'] ?></p>
        <?php endif; ?>
        
        <!-- Add Holiday Modal -->
        <section id="addHolidayModal" class="mfp-hide modal-dialog modal-content overlay-def">
            <header class="modal-header">
                <h2 class="modal-title"><?= $t['add_holiday'] ?></h2>
            </header>
            <div class="modal-body">
                <form method="post" action="includes/add-holiday.php">
                    <input type="hidden" name="lang" value="<?= $lang ?>">
                    <div class="form-group">
                        <label for="holiday_date"><?= $t['holiday_date'] ?> <strong class="required">(<?= $t['required'] ?>)</strong></label>
                        <input type="date" class="form-control" id="holiday_date" name="holiday_date" required>
                    </div>
                    <div class="form-group">
                        <label for="name_en"><?= $t['name_en'] ?> <strong class="required">(<?= $t['required'] ?>)</strong></label>
                        <input type="text" class="form-control" id="name_en" name="name_en" required>
                    </div>
                    <div class="form-group">
                        <label for="name_fr"><?= $t['name_fr'] ?> <strong class="required">(<?= $t['required'] ?>)</strong></label>
                        <input type="text" class="form-control" id="name_fr" name="name_fr" required>
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="recurring" value="1">
                            <?= $t['recurring'] ?>
                        </label>
                        <p class="help-block"><?= $t['recurring_help'] ?></p>
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="status" value="1" checked>
                            <?= $t['active'] ?>
                        </label>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary"><?= $t['add_button'] ?></button>
                        <button type="button" class="btn btn-default popup-modal-dismiss"><?= $t['cancel'] ?></button>
        </section>

		<?php include 'includes/template/page-details.php'; ?>
	</main>
	
	<?php include 'includes/template/footer.php'; include 'includes/template/scripts.php'; ?>
</body>
</html>
<?php mysqli_close($link); ?>
