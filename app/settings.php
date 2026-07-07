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

// Security check - Conditional includes
if ($_SESSION['lang'] === 'fr') {
    require('includes/loggedincheck.php');
} else {
    require('includes/loggedincheck.php');
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
		'en' => 'Account settings',
		'fr' => 'Paramètres du compte'
	],
	'description' => [
		'en' => 'Manage your account settings and preferences',
		'fr' => 'Gérer vos paramètres et préférences de compte'
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
			<h1 property="name" id="wb-cont"><?= htmlspecialchars($langFile['settings_heading']) ?></h1>
			
			<?php 
			// Check if the account is Super admin / admin to show this option
			if ($_SESSION['is_superuser'] OR $_SESSION['is_admin']) {
			?>
			<h2><?= htmlspecialchars($langFile['settings_add_request_heading']) ?></h2>
			
			<ul>
				<li><a href="/addrequest.php?lang=<?= $_SESSION['lang'] ?>" class="item"><?= htmlspecialchars($langFile['settings_add_request_link']) ?></a></li>
			</ul>
			<?php
			}
			?>
			
			<?php
			// Show account type switcher only for superadmin
			if (isset($_SESSION['is_superuser']) && $_SESSION['is_superuser'] == 1) {
				// Get account types from database
				$accountTypes = [];
				$result = mysqli_query($link, "SELECT id, nameen, namefr FROM tblaccounttype WHERE status = 1 ORDER BY id ASC");
				while ($row = mysqli_fetch_array($result)) {
					$accountTypes[] = $row;
				}
				
				$nameField = $_SESSION['lang'] == 'fr' ? 'namefr' : 'nameen';
				$currentAtype = $_SESSION['atype'];
			?>
			<h2><?= $_SESSION['lang'] == 'fr' ? 'Mode développement - Tester en tant que' : 'Development Mode - Test as Account Type' ?></h2>
			
			<div class="alert alert-warning">
				<p><strong><?= $_SESSION['lang'] == 'fr' ? '🔧 Fonction de développement pour super administrateur uniquement' : '🔧 Development feature for superadmin only' ?></strong></p>
				<p><?= $_SESSION['lang'] == 'fr' ? 'Ceci vous permet de tester les fonctionnalités et permissions de différents types de comptes sans vous déconnecter.' : 'This allows you to test features and permissions of different account types without logging out.' ?></p>
			</div>
			
			<form method="post" action="includes/switch-account-type.php">
				<div class="form-group">
					<label for="test_atype"><span class="field-name"><?= $_SESSION['lang'] == 'fr' ? 'Type de compte de test' : 'Test Account Type' ?> <strong>(<?= htmlspecialchars($langFile['required']) ?>)</strong></span></label>
					<select class="form-control" id="test_atype" name="test_atype" required>
						<?php foreach ($accountTypes as $type): ?>
							<option value="<?php echo $type['id']; ?>" <?php echo ($currentAtype == $type['id']) ? 'selected' : ''; ?>>
								<?php echo htmlspecialchars($type[$nameField]); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<p class="help-block"><?= $_SESSION['lang'] == 'fr' ? 'Actuellement vous testez en tant que: <strong>' : 'Currently testing as: <strong>' ?><?php 
						foreach ($accountTypes as $type) {
							if ($type['id'] == $currentAtype) {
								echo htmlspecialchars($type[$nameField]);
								break;
							}
						}
					?></strong></p>
				</div>
				<?php
				$selectedTestTeamId = '';
				$showTestTeamScope = ((int)$currentAtype === 4);
				$selectedTestEmployeeId = '';
				$showTestEmployeeScope = ((int)$currentAtype === 5);
				if (!empty($_SESSION['test_team_ids'])) {
					$parts = array_values(array_filter(array_map('trim', explode(',', (string)$_SESSION['test_team_ids']))));
					$selectedTestTeamId = $parts[0] ?? '';
				}
				if (!empty($_SESSION['test_employee_id'])) {
					$selectedTestEmployeeId = (string)$_SESSION['test_employee_id'];
				}
				$teamNameField = $_SESSION['lang'] == 'fr' ? 'namefr' : 'nameen';
				$teamResult = mysqli_query($link, "SELECT id, $teamNameField AS name FROM tblteams WHERE status='1' ORDER BY $teamNameField ASC");
				$employeeResult = mysqli_query($link, "SELECT id, firstname, lastname, email FROM tblusers WHERE status='1' AND atype = 5 ORDER BY firstname ASC, lastname ASC");
				?>
				<div class="form-group" id="test-team-scope-group"<?php if (!$showTestTeamScope) { ?> style="display:none;"<?php } ?>>
					<label for="test_team_id"><span class="field-name"><?= $_SESSION['lang'] == 'fr' ? 'Équipe de test (pour les rôles à portée d\'équipe)' : 'Test team scope (for team-scoped roles)' ?></span></label>
					<select class="form-control" id="test_team_id" name="test_team_id"<?php if (!$showTestTeamScope) { ?> disabled="disabled"<?php } ?>>
						<option value=""><?= $_SESSION['lang'] == 'fr' ? 'Aucune substitution d\'équipe (utiliser équipe réelle)' : 'No team override (use real account team)' ?></option>
						<?php while ($teamRow = mysqli_fetch_assoc($teamResult)): ?>
							<option value="<?= (int)$teamRow['id'] ?>" <?= ((string)$selectedTestTeamId === (string)$teamRow['id']) ? 'selected' : '' ?>><?= htmlspecialchars($teamRow['name']) ?></option>
						<?php endwhile; ?>
					</select>
				</div>
				<div class="form-group" id="test-employee-scope-group"<?php if (!$showTestEmployeeScope) { ?> style="display:none;"<?php } ?>>
					<label for="test_employee_id"><span class="field-name"><?= $_SESSION['lang'] == 'fr' ? 'Employé de test (pour la portée des demandes assignées)' : 'Test employee scope (for assigned-request scope)' ?></span></label>
					<select class="form-control" id="test_employee_id" name="test_employee_id"<?php if (!$showTestEmployeeScope) { ?> disabled="disabled"<?php } ?>>
						<option value=""><?= $_SESSION['lang'] == 'fr' ? 'Aucune substitution d\'employé (utiliser mon compte)' : 'No employee override (use my account)' ?></option>
						<?php while ($employeeRow = mysqli_fetch_assoc($employeeResult)): ?>
							<?php $employeeLabel = trim(($employeeRow['firstname'] ?? '') . ' ' . ($employeeRow['lastname'] ?? '')) . ' (' . ($employeeRow['email'] ?? '') . ')'; ?>
							<option value="<?= (int)$employeeRow['id'] ?>" <?= ((string)$selectedTestEmployeeId === (string)$employeeRow['id']) ? 'selected' : '' ?>><?= htmlspecialchars($employeeLabel) ?></option>
						<?php endwhile; ?>
					</select>
				</div>
				<div class="form-group form-buttons">
					<button type="submit" class="btn btn-primary"><?= $_SESSION['lang'] == 'fr' ? 'Changer le type de compte' : 'Switch Account Type' ?></button>
					<?php if ((int)$currentAtype !== 1): ?>
						<button type="submit" name="reset_atype" value="1" class="btn btn-warning"><?= $_SESSION['lang'] == 'fr' ? 'Réinitialiser au super admin' : 'Reset to Super Admin' ?></button>
					<?php endif; ?>
				</div>
			</form>
			<?php
			}
			?>
			

				<div class="form-group">
					<label for="token2"><span class="field-name"><?= htmlspecialchars($langFile['passwordreset_new']) ?> <strong>(<?= htmlspecialchars($langFile['required']) ?>)</strong></span></label>
					<input type="password" class="form-control" id="token2" name="token2" placeholder="<?= htmlspecialchars($langFile['passwordreset_new_placeholder']) ?>">
				</div>
				<div class="form-group">
					<label for="token3"><span class="field-name"><?= htmlspecialchars($langFile['passwordreset_confirm']) ?> <strong>(<?= htmlspecialchars($langFile['required']) ?>)</strong></span></label>
					<input type="password" class="form-control" id="token3" name="token3" placeholder="<?= htmlspecialchars($langFile['passwordreset_confirm_placeholder']) ?>">
				</div>
				<div class="form-group form-buttons">
					<button type="submit" class="btn btn-primary"><?= htmlspecialchars($langFile['passwordreset_button']) ?></button>
					<input type="reset" class="btn btn-default cancel" id="button2" value="<?= htmlspecialchars($langFile['passwordreset_clear']) ?>" />
				</div>
				</form>
			</div>
			
			<?php include 'includes/template/page-details.php'; ?>
		</main>
		
		<?php include 'includes/template/footer.php'; include 'includes/template/scripts.php'; ?>
		<script>
		(function () {
			var accountTypeSelect = document.getElementById('test_atype');
			var teamGroup = document.getElementById('test-team-scope-group');
			var teamSelect = document.getElementById('test_team_id');
			var employeeGroup = document.getElementById('test-employee-scope-group');
			var employeeSelect = document.getElementById('test_employee_id');
			if (!accountTypeSelect || !teamGroup || !teamSelect || !employeeGroup || !employeeSelect) {
				return;
			}

			function refreshTeamScopeVisibility() {
				var isTeamLead = accountTypeSelect.value === '4';
				var isEmployee = accountTypeSelect.value === '5';
				teamGroup.style.display = isTeamLead ? '' : 'none';
				teamSelect.disabled = !isTeamLead;
				if (!isTeamLead) {
					teamSelect.value = '';
				}

				employeeGroup.style.display = isEmployee ? '' : 'none';
				employeeSelect.disabled = !isEmployee;
				if (!isEmployee) {
					employeeSelect.value = '';
				}
			}

			accountTypeSelect.addEventListener('change', refreshTeamScopeVisibility);
			refreshTeamScopeVisibility();
		})();
		</script>
	</body>
</html>
<?php
// Close connection
mysqli_close($link);
