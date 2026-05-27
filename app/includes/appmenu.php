<?php
// Start session and get database connection
require_once(__DIR__ . '/../sql.php');

// Set language
$lang_code = $_SESSION['lang'] ?? 'en';

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
		'contacts' => 'Contacts',
		'catalogue' => 'Service catalogue',
		'holidays' => 'Holidays',
		'sources' => 'Sources',
		'status' => 'Status',
		'users' => 'Users',
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
		'contacts' => 'Contacts',
		'catalogue' => 'Catalogue de services',
		'holidays' => 'Jours fériés',
		'sources' => 'Sources',
		'status' => 'Statuts',
		'users' => 'Utilisateurs',
		'help' => 'Aide',
		'version' => 'Historique des versions'
	]
];

$t = $menu_text[$lang_code];
?>
<!DOCTYPE html>
<html lang="<?= $lang_code ?>">
	<!-- Application templates - Sample menu -->
	<!-- DataAjaxFragmentStart -->
	<div class="pnl-strt container nvbar">
		<h2 class="wb-inv"><?= htmlspecialchars($t['nav_heading']) ?></h2>
		<div class="row">
			<ul class="list-inline menu" role="menubar">
			<?php if(!empty($_SESSION['pid']) && $_SESSION['atype'] != 6){ ?>
				<li><a href="#" class="item"><?= htmlspecialchars($t['overview']) ?></a>
					<ul class="sm list-unstyled" id="s2" role="menu">
						<li><a href="/index.php?lang=<?= $lang_code ?>"><?= htmlspecialchars($t['view_all']) ?></a></li>
						<li><a href="/indexonly.php?lang=<?= $lang_code ?>"><?= htmlspecialchars($t['view_my']) ?></a></li>
						<li><a href="/indexresolved.php?lang=<?= $lang_code ?>"><?= htmlspecialchars($t['view_resolved']) ?></a></li>
					</ul>
				</li>
				<?php } ?>
				<li><a href="/openrequest.php?lang=<?= $lang_code ?>" class="item"><?= htmlspecialchars($t['new_request']) ?></a></li>
				<?php if(!empty($_SESSION['pid'])){ ?>
				<li><a href="/asearch.php?lang=<?= $lang_code ?>" class="item"><?= htmlspecialchars($t['search']) ?></a></li>
				<li><a href="/reports.php?lang=<?= $lang_code ?>" class="item"><?= htmlspecialchars($t['reports']) ?></a></li>
				<?php
				// Only Super admins can access this option
				if(isset($_SESSION['atype']) && $_SESSION['atype']==1) {
				?>
				<!-- <li><a href="/batch-ace-info.php?lang=<?= $lang_code ?>">Update (batch) ACE tickets</a></li> -->
				<?php 
				}
				// Only Super admins can access admin options
				if (isset($_SESSION['atype']) && ($_SESSION['atype']==1 OR $_SESSION['atype']==2)) {
				?>
				<li><a href="#s2" class="item"><?= htmlspecialchars($t['admin']) ?></a>
					<ul class="sm list-unstyled" id="s2" role="menu">
						<li><a href="/contacts.php?lang=<?= $lang_code ?>"><?= htmlspecialchars($t['contacts']) ?></a></li>
						<?php
						// Only Super admins can access this option
					if (isset($_SESSION['atype']) && $_SESSION['atype']==1) {
						?>
						<li><a href="/catalogue.php?lang=<?= $lang_code ?>"><?= htmlspecialchars($t['catalogue']) ?></a></li>					<li><a href="/holidays-mgmt.php?lang=<?= $lang_code ?>"><?= htmlspecialchars($t['holidays']) ?></a></li>						<?php } ?>
					<li><a href="/sources.php?lang=<?= $lang_code ?>"><?= htmlspecialchars($t['sources']) ?></a></li>
					<li><a href="/status.php?lang=<?= $lang_code ?>"><?= htmlspecialchars($t['status']) ?></a></li>
						<?php
						// Only Super admins can access this option
						if (isset($_SESSION['atype']) && $_SESSION['atype']==1) {
						?>
						<!-- <li><a href="/batch-ace-info.php?lang=<?= $lang_code ?>">Update (batch) ACE tickets</a></li> -->
						<li><a href="/users.php?lang=<?= $lang_code ?>"><?= htmlspecialchars($t['users']) ?></a></li>
						<?php } ?>
					</ul>
				</li>
				<?php
				}
				?>
				<?php } // end logged-in block ?>
				<li><a href="/help.php?lang=<?= $lang_code ?>"><?= htmlspecialchars($t['help']) ?></a></li>
				<li><a href="/version-history.php?lang=<?= $lang_code ?>"><?= htmlspecialchars($t['version']) ?></a></li>

			</ul>
		</div>
	</div>
	<!-- DataAjaxFragmentEnd -->
</html>
