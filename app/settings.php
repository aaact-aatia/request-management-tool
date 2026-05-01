<?php
// Grab MySQL connection (includes session management)
require('sql.php');

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

// Check which environment is being used
// Grab logged in user
$currentuser = $_SESSION['pid'];

// Check if current password is correct
$result = mysqli_query($link, "SELECT environment FROM tblusers WHERE id = '$currentuser'");
$row = mysqli_fetch_array($result);
$cenvironment = $row['environment'];

// Process the sign in form
if ($_SERVER['REQUEST_METHOD']=='POST') {
	// First update the environment
	$nenvironment = mysqli_real_escape_string($link,$_POST['environment']);
	$sql = "UPDATE `tblusers` SET `environment` = '$nenvironment' WHERE id='$currentuser'";
	//echo $sql;
	mysqli_query($link,$sql);
	
	// Redirect to login page
	header("location:settings.php?lang={$_SESSION['lang']}&status=success"); 
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
			if ($status == 'success') {
			?>
			<section class="alert alert-success">
				<h2><?= htmlspecialchars($langFile['success_heading']) ?></h2>
				<ul>
					<li><?= htmlspecialchars($langFile['settings_success_message']) ?></li>
				</ul>
			</section>
			<?php
			}
			// Check if the account is Super admin / admin to show this option
			if ($_SESSION['atype']=='1' OR $_SESSION['atype']=='2') {
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
			if (isset($_SESSION['real_atype']) && $_SESSION['real_atype'] == 1) {
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
			
			<div class="col-xs-12">
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
					<div class="form-group form-buttons">
						<button type="submit" class="btn btn-primary"><?= $_SESSION['lang'] == 'fr' ? 'Changer le type de compte' : 'Switch Account Type' ?></button>
						<?php if ($currentAtype != $_SESSION['real_atype']): ?>
							<button type="submit" name="reset_atype" value="1" class="btn btn-warning"><?= $_SESSION['lang'] == 'fr' ? 'Réinitialiser au super admin' : 'Reset to Super Admin' ?></button>
						<?php endif; ?>
					</div>
				</form>
			</div>
			<?php
			}
			?>
			
			<h2><?= htmlspecialchars($langFile['settings_environment_heading']) ?></h2>
			
			<div class="col-xs-12">
				<form id="login" method="post" action="settings.php?lang=<?= $_SESSION['lang'] ?>">
				<div class="form-group">
					<label for="environment"><span class="field-name"><?= htmlspecialchars($langFile['settings_environment_label']) ?> <strong class="required">(<?= htmlspecialchars($langFile['required']) ?>)</strong></span></label>	
					<select class="form-control" id="environment" name="environment" required>
						<option value="0"<?php if ($cenvironment==0) { ?> selected<?php } ?>><?= htmlspecialchars($langFile['settings_environment_production']) ?></option>
						<option value="1"<?php if ($cenvironment==1) { ?> selected<?php } ?>><?= htmlspecialchars($langFile['settings_environment_development']) ?></option>
					</select>
				</div>
				<div class="form-group form-buttons">
					<button type="submit" class="btn btn-primary"><?= htmlspecialchars($langFile['settings_environment_button']) ?></button>
				</div>
				</form>
			</div>
			
			<h2><?= htmlspecialchars($langFile['settings_password_heading']) ?></h2>
				
			<div class="col-xs-12">
				<form id="login" method="post" action="passwordreset.php?lang=<?= $_SESSION['lang'] ?>">
				<div class="form-group">
					<label for="token1"><span class="field-name"><?= htmlspecialchars($langFile['passwordreset_current']) ?> <strong>(<?= htmlspecialchars($langFile['required']) ?>)</strong></span></label>
					<input type="password" class="form-control" id="token1" name="token1" placeholder="<?= htmlspecialchars($langFile['passwordreset_current_placeholder']) ?>">
				</div>
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
	</body>
</html>
<?php
// Close connection
mysqli_close($link);
