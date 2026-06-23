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

// Load language file
$langFile = require("lang/{$_SESSION['lang']}.php");

// Grab HTTPS check
require('includes/httpscheck.php');

// Security check
if ($_SESSION['lang'] === 'fr') {
	require('includes/loggedincheck.php');
} else {
	require('includes/loggedincheck.php');
}

// Check if the user has the right priv's
if ($_SESSION['atype'] != 1) {
	header("location:/openrequest.php?lang={$_SESSION['lang']}&status=accessdenied"); 
	exit();
}

// Check if there is a status
if (!empty($_GET['status'])){
	$status = $_GET['status'];
}
else{
	$status = "";
}

// =============================================================================
// PAGE FRONTMATTER - Define page metadata
// =============================================================================
$page = [
	'title' => [
		'en' => 'Users',
		'fr' => 'Utilisateurs'
	],
	'description' => [
		'en' => 'Manage user accounts and permissions',
		'fr' => 'Gérer les comptes utilisateurs et les permissions'
	]
];

// Store language code for templates (header.php needs $lang)
$lang = $_SESSION['lang'];

// Extract values for current language
$pageTitle = $page['title'][$lang];
$pageDescription = $page['description'][$lang];

// Include template head
include 'includes/template/head.php';
?>
	<?php include 'includes/template/header.php'; ?>
		<main role="main" property="mainContentOfPage" class="container">
			<h1 property="name" id="wb-cont"><?= htmlspecialchars($langFile['users_heading']) ?></h1>
			
			<?php 
			if ($status == 'success') {
			?>
			<section class="alert alert-success">
				<h2><?= htmlspecialchars($langFile['success_heading']) ?></h2>
				<ul>
					<li><?= htmlspecialchars($langFile['success_message']) ?></li>
				</ul>
			</section>
			<?php
			} elseif ($status == 'failed') {
			?>
			<section class="alert alert-danger">
				<h2><?= htmlspecialchars($langFile['failed_heading']) ?></h2>
				<ul>
					<li><?= htmlspecialchars($langFile['failed_message']) ?></li>
				</ul>
			</section>
			<?php
			}
			?>
			
			<div class="pull-right"><a class="wb-lbx btn btn-primary mrgn-bttm-md" href="includes/add-users.php"><?= htmlspecialchars($langFile['users_add_button']) ?></a></div>
			<div class="clearfix"></div>
			
			<?php
			$hasSuperRoleColumn = rmt_db_column_exists($link, 'tblusers', 'is_superuser');
			$hasAdminRoleColumn = rmt_db_column_exists($link, 'tblusers', 'is_admin');
			// Construct SQL statement
			$sql = "SELECT * FROM tblusers ORDER BY firstname ASC, lastname ASC";
			//echo $sql;
			
			$result = mysqli_query($link,$sql);
			//List it
			if(mysqli_num_rows($result)>0){
			?>
			<table class="wb-tables wb-tables-filter table table-striped table-hover" data-wb-tables='{ "ordering" : true }'>
				<thead>
					<tr>
						<th><?= htmlspecialchars($langFile['users_col_name']) ?></th>
						<th><?= htmlspecialchars($langFile['users_col_email']) ?></th>
						<th><?= htmlspecialchars($langFile['users_col_account_type']) ?></th>					<th><?= htmlspecialchars($langFile['users_col_team']) ?></th>						<th><?= htmlspecialchars($langFile['actions_column']) ?></th>
					</tr>
				</thead>
				<tbody>
				<?php
					while($row = mysqli_fetch_array($result)){
					
					// Get account type id
					$accounttypeid = $row['atype'];
					$accounttypename = '';
					
					// Grab account type name
					$accounttypeField = ($_SESSION['lang'] === 'fr') ? 'namefr' : 'nameen';
					$sql2 = "SELECT $accounttypeField FROM tblaccounttype WHERE id='$accounttypeid'";		
					$result2 = mysqli_query($link,$sql2);				
					if(mysqli_num_rows($result2)>0){
						while($row2 = mysqli_fetch_array($result2)){
							$accounttypename = $row2[$accounttypeField];
						}
					}
					$extraRoleLabels = [];
					$isSuperRole = $hasSuperRoleColumn ? ((int)($row['is_superuser'] ?? 0) === 1) : ((int)$accounttypeid === 1);
					$isAdminRole = $hasAdminRoleColumn ? ((int)($row['is_admin'] ?? 0) === 1) : in_array((int)$accounttypeid, [1, 2], true);

					if ($isSuperRole) {
						$extraRoleLabels[] = ($_SESSION['lang'] === 'fr') ? 'Super administrateur' : 'Super Admin';
					} elseif ($isAdminRole) {
						$extraRoleLabels[] = ($_SESSION['lang'] === 'fr') ? 'Administrateur' : 'Admin';
					}

					if (!empty($extraRoleLabels)) {
						$accounttypename = trim($accounttypename . ' + ' . implode(' + ', $extraRoleLabels));
					}
				// Resolve team names from contact IDs stored in tblusers.team
				$teamNames = [];
				$teamNameField = ($_SESSION['lang'] === 'fr') ? 'namefr' : 'nameen';
				if (!empty($row['team'])) {
					foreach (array_filter(explode(',', $row['team'])) as $tid) {
						$tid = (int)$tid;
						$r = mysqli_query($link, "SELECT $teamNameField FROM tblteams WHERE id='$tid' AND status='1'");
						$tr = mysqli_fetch_assoc($r);
						if ($tr) $teamNames[] = $tr[$teamNameField];
					}
				}
				?>
				<?php
				$relationshipLabel = ($_SESSION['lang'] === 'fr') ? 'Gestionnaire' : 'Manager';
				$relationshipName = '—';
				$unassignedText = ($_SESSION['lang'] === 'fr') ? 'Non assigne' : 'Unassigned';
				$userTypeId = (int)$row['atype'];
				$showRelationship = true;

				if ($userTypeId === 5) {
					$relationshipLabel = ($_SESSION['lang'] === 'fr') ? 'Chef d\'équipe' : 'Team Lead';
					$employeeTeamId = (int)($row['team'] ?? 0);
					$relationshipName = $unassignedText;
					if ($employeeTeamId > 0) {
						$leadLookup = mysqli_query($link, "SELECT u.firstname, u.lastname
							FROM tblteams t
							LEFT JOIN tblusers u ON u.id = t.team_lead_user_id
							WHERE t.id='" . $employeeTeamId . "' AND t.status='1' AND u.status='1'
							LIMIT 1");
						$leadRow = mysqli_fetch_assoc($leadLookup);
						if (!empty($leadRow)) {
							$relationshipName = $leadRow['firstname'] . ' ' . $leadRow['lastname'];
						}
					}
				} else {
					if ($userTypeId === 3) {
						$showRelationship = false;
					}
					$managerId = (int)($row['manager_id'] ?? 0);
					if ($managerId > 0) {
						$managerResult = mysqli_query($link, "SELECT firstname, lastname FROM tblusers WHERE id='" . $managerId . "' AND status='1' LIMIT 1");
						$managerRow = mysqli_fetch_assoc($managerResult);
						if (!empty($managerRow)) {
								$relationshipName = $managerRow['firstname'] . ' ' . $managerRow['lastname'];
						}
					}

					if ($userTypeId === 4 && $relationshipName === '—' && !empty($row['team'])) {
						$leadTeamIds = [];
						foreach (array_filter(explode(',', (string)$row['team'])) as $leadTid) {
							$leadTid = (int)$leadTid;
							if ($leadTid > 0) {
								$leadTeamIds[$leadTid] = true;
							}
						}

						if (!empty($leadTeamIds)) {
							$managerLookup = mysqli_query($link, "SELECT firstname, lastname, team FROM tblusers WHERE atype='3' AND status='1' ORDER BY firstname ASC, lastname ASC");
							while ($managerCandidate = mysqli_fetch_assoc($managerLookup)) {
								$candidateTeams = array_filter(explode(',', (string)($managerCandidate['team'] ?? '')));
								$matchesTeam = false;
								foreach ($candidateTeams as $candidateTidRaw) {
									$candidateTid = (int)$candidateTidRaw;
									if ($candidateTid > 0 && isset($leadTeamIds[$candidateTid])) {
										$matchesTeam = true;
										break;
									}
								}

								if ($matchesTeam) {
									$relationshipName = $managerCandidate['firstname'] . ' ' . $managerCandidate['lastname'];
									break;
								}
							}
						}
					}
				}
				?>
				<tr>
					<td><?php echo $row['firstname'];?> <?php echo $row['lastname'];?></td>
					<td><?php echo $row['email'];?></td>
					<td><?php echo $accounttypename ?></td>
					<td><?php echo !empty($teamNames) ? htmlspecialchars(implode(', ', $teamNames)) : '—'; ?><?php if ($showRelationship) { ?><br><small><?php echo htmlspecialchars($relationshipLabel); ?>: <?php echo htmlspecialchars($relationshipName); ?></small><?php } ?></td>
					<td>
						<a class="wb-lbx btn btn-primary btn-block" href="includes/edit-users.php?id=<?php echo $row['id'];?>&lang=<?php echo $lang;?>"><?= htmlspecialchars($langFile['users_edit_button']) ?><span class="wb-inv"> <?php echo htmlspecialchars($row['firstname'] . ' ' . $row['lastname']); ?></span></a> <a class="wb-lbx btn btn-primary btn-block" href="includes/delete-users.php?id=<?php echo $row['id'];?>&lang=<?php echo $lang;?>"><?= htmlspecialchars($langFile['users_delete_button']) ?><span class="wb-inv"> <?php echo htmlspecialchars($row['firstname'] . ' ' . $row['lastname']); ?></span></a>
					</td>
				</tr>
			<?php } ?>
			</tbody>
			</table>
			
			<?php } else { ?>
			<p><strong><?= htmlspecialchars($langFile['users_no_users']) ?></strong></p>
			<?php } ?>
			
			<?php include 'includes/template/page-details.php'; ?>
		</main>
		
		<?php include 'includes/template/footer.php'; include 'includes/template/scripts.php'; ?>
	</body>
</html>
<?php
// Close connection
mysqli_close($link);
