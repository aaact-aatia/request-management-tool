<?php
// Grab HTTPS check
require('includes/httpscheck.php');

// Grab MySQL connection (handles session management)
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

// Store language code for templates
$lang = $_SESSION['lang'];

// Load language file
$langFile = require("lang/{$_SESSION['lang']}.php");

// Declare vars
$loginfailed = false;

// Check if this is a logout action
if (!empty($_GET['loggedout']))
{
	$logout = $_GET['loggedout'];
}
else
{
	$logout = false;
}

if (!empty($_GET['passwordupdate'])) {
	$passwordUpdated = true;
} else {
	$passwordUpdated = false;
}

// Process the sign in form
if ($_SERVER['REQUEST_METHOD']=='POST'){	
	
	$username = strtolower(mysqli_real_escape_string($link,$_POST['token1']));
	$password = mysqli_real_escape_string($link,$_POST['token2']);
	
	// Check if username has a domain name
	if (strpos($username, '@')!==false) {
		// String contains domain name so do nothing
	} else {
		// String doesn't contain domain so add it for login
		$username .= "@ssc-spc.gc.ca";
	}
	
	$hasSuperRoleColumn = rmt_db_column_exists($link, 'tblusers', 'is_superuser');
	$hasAdminRoleColumn = rmt_db_column_exists($link, 'tblusers', 'is_admin');
	$superRoleSelect = $hasSuperRoleColumn ? ', is_superuser' : '';
	$adminRoleSelect = $hasAdminRoleColumn ? ', is_admin' : '';

	$sql = "SELECT id,firstname,email,password,atype,team$superRoleSelect$adminRoleSelect FROM tblusers WHERE email='$username' AND status = '1'";
	$result = mysqli_query($link,$sql);	
	while($row = mysqli_fetch_array($result)){
		// Check password
		$isPasswordCorrect = password_verify($password, $row['password']);
		if ($isPasswordCorrect==false) {
			// Password incorrect
			$loginfailed = true;
		} else {
			// Password correct
			$primaryAtype = (int)$row['atype'];
			// Preserve legacy account-type permissions when migrated rows have role flags unset.
			$isSuperuser = ((int)($row['is_superuser'] ?? 0) === 1) || $primaryAtype === 1;
			$isAdmin = ((int)($row['is_admin'] ?? 0) === 1) || in_array($primaryAtype, [1, 2], true);
			if ($isSuperuser) {
				$isAdmin = true;
			}

			$_SESSION['pid'] = $row['id'];
			$_SESSION['primary_atype'] = $primaryAtype;
			$_SESSION['is_superuser'] = $isSuperuser ? 1 : 0;
			$_SESSION['is_admin'] = $isAdmin ? 1 : 0;
			// On normal login (not in test mode), superusers get atype=1 for full permissions
			// Non-superusers get their database atype. Testing mode overrides this in settings.
			$_SESSION['atype'] = $isSuperuser ? 1 : $primaryAtype;
			$_SESSION['firstname']=$row['firstname'];
			$_SESSION['email']=$row['email'];
			$team = $row['team'];
			$_SESSION['team'] = explode(',', $team);
			
			// Dev account switcher is now based on is_superuser flag, not real_atype
			// Check if user has any assigned requests
			$userId = $_SESSION['pid'];
		
			$checkSql = "SELECT COUNT(*) as count FROM tbltriage 
						WHERE status = '1' 
						AND (statusid='1' OR statusid='3' OR statusid='5' OR statusid='6' OR statusid='7' OR statusid='10' OR statusid='11' OR statusid='12')
						AND workerid = '$userId'
						LIMIT 1";
			$checkResult = mysqli_query($link, $checkSql);
			$checkRow = mysqli_fetch_array($checkResult);
			
			// Redirect based on whether they have assigned requests
			if ($checkRow['count'] > 0) {
				// Has assigned requests - go to indexonly
				header("location:indexonly.php?lang={$_SESSION['lang']}");
			} else {
				// No assigned requests - go to all open requests
				header("location:index.php?lang={$_SESSION['lang']}");
			}
			exit();
		}
	}
	
	if (mysqli_num_rows($result)==0){
		$loginfailed = true;
	}	
}

// =============================================================================
// PAGE FRONTMATTER - Define page metadata
// =============================================================================
$page = [
	'title' => [
		'en' => 'Sign in',
		'fr' => 'Se connecter'
	],
	'description' => [
		'en' => 'Sign in to the Request Management Tool',
		'fr' => 'Se connecter à l\'outil de gestion des demandes'
	]
];

// Extract values for current language
$pageTitle = $page['title'][$_SESSION['lang']];
$pageDescription = $page['description'][$_SESSION['lang']];

// Page-specific language strings
$translations = [
	'en' => [
		'main_heading' => 'Sign in',
		'error_heading' => 'Error',
		'error_1' => 'Your username or password is incorrect.',
		'error_2' => 'Please try again.',
		'logout_heading' => 'Logged out',
		'logout_message' => 'You have been successfully logged out.',
		'success_heading' => 'Success',
		'password_updated' => 'Your password has been successfully updated.',
		'username_label' => 'Username',
		'username_placeholder' => 'Enter your username',
		'password_label' => 'Password',
		'password_placeholder' => 'Enter your password',
		'required' => 'required',
		'signin_button' => 'Sign in',
		'clear_button' => 'Clear',
	],
	'fr' => [
		'main_heading' => 'Se connecter',
		'error_heading' => 'Erreur',
		'error_1' => 'Votre nom d\'utilisateur ou mot de passe est incorrect.',
		'error_2' => 'Veuillez réessayer.',
		'logout_heading' => 'Déconnecté',
		'logout_message' => 'Vous avez été déconnecté avec succès.',
		'success_heading' => 'Succès',
		'password_updated' => 'Votre mot de passe a été mis à jour avec succès.',
		'username_label' => 'Nom d\'utilisateur',
		'username_placeholder' => 'Entrez votre nom d\'utilisateur',
		'password_label' => 'Mot de passe',
		'password_placeholder' => 'Entrez votre mot de passe',
		'required' => 'obligatoire',
		'signin_button' => 'Se connecter',
		'clear_button' => 'Effacer',
	]
];

$langStrings = $translations[$_SESSION['lang']];

// Include template head
include 'includes/template/head.php';
?>
	<?php include 'includes/template/header.php'; ?>
	<main role="main" property="mainContentOfPage" class="container">
		<h1 property="name" id="wb-cont"><?= $langStrings['main_heading'] ?></h1>
		
		<?php 
		if ($loginfailed) {
		?>
		<section class="alert alert-danger">
			<h2><?= $langStrings['error_heading'] ?></h2>
			<ul>
				<li><?= $langStrings['error_1'] ?></li>
				<li><?= $langStrings['error_2'] ?></li>
			</ul>
		</section>
		<?php
		} elseif ($logout) {
		?>
		<section class="alert alert-danger">
			<h2><?= $langStrings['logout_heading'] ?></h2>
			<ul>
				<li><?= $langStrings['logout_message'] ?></li>
			</ul>
		</section>		
		<?php
		} elseif ($passwordUpdated) {
		?>
		<section class="alert alert-success">
			<h2><?= $langStrings['success_heading'] ?></h2>
			<ul>
				<li><?= $langStrings['password_updated'] ?></li>
			</ul>
		</section>
		<?php	
		}
		?>
				
			<div class="col-xs-12">
				<form id="login" method="post" action="signin.php?lang=<?= $_SESSION['lang'] ?>">
				<div class="form-group">
					<label for="token1"><span class="field-name"><?= $langStrings['username_label'] ?> <strong>(<?= $langStrings['required'] ?>)</strong></span></label>
					<input type="text" class="form-control" id="token1" name="token1" placeholder="<?= $langStrings['username_placeholder'] ?>">
				</div>
				<div class="form-group">
					<label for="token2"><span class="field-name"><?= $langStrings['password_label'] ?> <strong>(<?= $langStrings['required'] ?>)</strong></span></label>
					<input type="password" class="form-control" id="token2" name="token2" placeholder="<?= $langStrings['password_placeholder'] ?>">
				</div>
				<div class="form-group form-buttons">
					<button type="submit" class="btn btn-primary"><?= $langStrings['signin_button'] ?></button>
					<input type="reset" class="btn btn-default cancel" id="button2" value="<?= $langStrings['clear_button'] ?>" />
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
