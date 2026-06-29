<?php
if (isset($_SERVER['SCRIPT_FILENAME']) && realpath(__FILE__) === realpath((string) $_SERVER['SCRIPT_FILENAME'])) {
    http_response_code(404);
    exit();
}


/**
 * Template: Desktop Navigation Menu
 * 
 * Renders the main navigation menu for desktop/tablet views.
 * Menu items are conditionally displayed based on authentication and permissions:
 * - $_SESSION['pid']: User is authenticated
 * - $_SESSION['atype'] == 1: Super admin (full access)
 * - $_SESSION['atype'] == 2: Admin (limited admin access)
 */

// Set language from session (already initialized in header.php)
$lang_code = $_SESSION['lang'] ?? 'en';

// Resolve effective permissions for menu visibility
// Use permission flags for admin access instead of account type
$isTestingDifferentType = isset($_SESSION['is_superuser']) && $_SESSION['is_superuser'] == 1 && isset($_SESSION['atype']) && (int)$_SESSION['atype'] !== 1;
$isSuperAdmin = !$isTestingDifferentType && isset($_SESSION['is_superuser']) && $_SESSION['is_superuser'] == 1;

// Menu text translations
$menu_text = [
	'en' => [
		'nav_heading' => 'Main navigation menu',
		'overview' => 'Overview',
		'view_all' => 'View all requests',
		'view_my' => 'View my requests only',
		'view_resolved' => 'View closed requests',
		'new_request' => 'New request',
		'search' => 'Search requests',
		'reports' => 'Reports',
		'admin' => 'Administration',
		'contacts' => 'Teams',
		'catalogue' => 'Service catalogue',
		'holidays' => 'Holidays',
		'gcnotify_settings' => 'GC Notify settings',
		'sources' => 'Sources',
		'status' => 'Status',
		'users' => 'Users',
		'csv_tools' => 'CSV import/export',
		'help' => 'Help',
		'version' => 'Version History'
	],
	'fr' => [
		'nav_heading' => 'Menu de navigation principal',
		'overview' => 'Aperçu',
		'view_all' => 'Afficher toutes les demandes',
		'view_my' => 'Afficher mes demandes uniquement',
		'view_resolved' => 'Afficher les demandes fermées',
		'new_request' => 'Nouvelle demande',
		'search' => 'Recherche d\'une demande',
		'reports' => 'Rapports',
		'admin' => 'Administration',
		'contacts' => 'Équipes',
		'catalogue' => 'Catalogue de services',
		'holidays' => 'Jours fériés',
		'gcnotify_settings' => 'Parametres GC Notify',
		'sources' => 'Sources',
		'status' => 'Statuts',
		'users' => 'Utilisateurs',
		'csv_tools' => 'Import/export CSV',
		'help' => 'Aide',
		'version' => 'Historique des versions'
	]
];

$menuLangStrings = $menu_text[$lang_code];
?>
<nav role="navigation" id="wb-sm" data-trgt="mb-pnl" class="wb-menu visible-md visible-lg" typeof="SiteNavigationElement">
	<div class="pnl-strt container nvbar">
		<h2 class="wb-inv"><?= htmlspecialchars($menuLangStrings['nav_heading']) ?></h2>
		<div class="row">
			<ul class="list-inline menu" role="menubar">
				<?php
				if (!empty($_SESSION['pid']) && !isReadOnly()) {
				?>
					<li><a href="#" class="item"><?= htmlspecialchars($menuLangStrings['overview']) ?></a>
						<ul class="sm list-unstyled" id="s2" role="menu">
							<li><a href="/index.php?lang=<?= $lang_code ?>"><?= htmlspecialchars($menuLangStrings['view_all']) ?></a></li>
							<li><a href="/indexonly.php?lang=<?= $lang_code ?>"><?= htmlspecialchars($menuLangStrings['view_my']) ?></a></li>
							<li><a href="/indexresolved.php?lang=<?= $lang_code ?>"><?= htmlspecialchars($menuLangStrings['view_resolved']) ?></a></li>
						</ul>
					</li>
				<?php } ?>
				<li><a href="/openrequest.php?lang=<?= $lang_code ?>" class="item"><?= htmlspecialchars($menuLangStrings['new_request']) ?></a></li>
				<?php if (!empty($_SESSION['pid']) && !isReadOnly()) { ?>
					<li><a href="/asearch.php?lang=<?= $lang_code ?>" class="item"><?= htmlspecialchars($menuLangStrings['search']) ?></a></li>
					<li><a href="/reports.php?lang=<?= $lang_code ?>" class="item"><?= htmlspecialchars($menuLangStrings['reports']) ?></a></li>
				<?php
				// Only Super admins can access this option
				if ($isSuperAdmin) {
				?>
					<!-- <li><a href="/batch-ace-info.php?lang=<?= $lang_code ?>">Update (batch) AAACT tickets</a></li> -->
				<?php
				}
				// Only Super admins can access admin options
				if ($isSuperAdmin) {
				?>
					<li><a href="#s2" class="item"><?= htmlspecialchars($menuLangStrings['admin']) ?></a>
						<ul class="sm list-unstyled" id="s2" role="menu">
							<li><a href="/teams.php?lang=<?= $lang_code ?>"><?= htmlspecialchars($menuLangStrings['contacts']) ?></a></li>
							<?php
							// Only Super admins can access this option
						if ($isSuperAdmin) {
							?>
							<li><a href="/catalogue.php?lang=<?= $lang_code ?>"><?= htmlspecialchars($menuLangStrings['catalogue']) ?></a></li>
							<li><a href="/holidays-mgmt.php?lang=<?= $lang_code ?>"><?= htmlspecialchars($menuLangStrings['holidays']) ?></a></li>
							<li><a href="/gcnotify-settings.php?lang=<?= $lang_code ?>"><?= htmlspecialchars($menuLangStrings['gcnotify_settings']) ?></a></li>
							<?php } ?>
							<li><a href="/sources.php?lang=<?= $lang_code ?>"><?= htmlspecialchars($menuLangStrings['sources']) ?></a></li>
							<li><a href="/status.php?lang=<?= $lang_code ?>"><?= htmlspecialchars($menuLangStrings['status']) ?></a></li>
							<?php
							// Only Super admins can access this option
						if ($isSuperAdmin) {
							?>
								<!-- <li><a href="/batch-ace-info.php?lang=<?= $lang_code ?>">Update (batch) AAACT tickets</a></li> -->
								<li><a href="/users.php?lang=<?= $lang_code ?>"><?= htmlspecialchars($menuLangStrings['users']) ?></a></li>
							<?php } ?>
						</ul>
					</li>
				<?php
				}
				?>
				<?php } // end logged-in block ?>
				<li><a href="/help.php?lang=<?= $lang_code ?>"><?= htmlspecialchars($menuLangStrings['help']) ?></a></li>
				<li><a href="/version-history.php?lang=<?= $lang_code ?>"><?= htmlspecialchars($menuLangStrings['version']) ?></a></li>

			</ul>
		</div>
	</div>
</nav>