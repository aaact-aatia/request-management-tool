<?php
/**
 * Password Reset Page
 */

// Start session
if (session_status() != PHP_SESSION_ACTIVE)
{
	session_start();
}

// Grab HTTPS check
require('includes/httpscheck.php');

// Grab MySQL connection
require('sql.php');
/** @var mysqli $link */

// Handle language from query string or session
if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'fr'])) {
	$_SESSION['lang'] = $_GET['lang'];
}

// Set default language if not set
if (!isset($_SESSION['lang']) || !in_array($_SESSION['lang'], ['en', 'fr'])) {
	$_SESSION['lang'] = 'en';
}

// Load language file
$lang = $_SESSION['lang'];
$langFile = require("lang/{$_SESSION['lang']}.php");

// Set password check to false
$passwordCheck = true;
$passworderrors = true;

// Process the sign in form
if ($_SERVER['REQUEST_METHOD']=='POST'){	
	// Grab current passwords
	$cpassword = mysqli_real_escape_string($link,$_POST['token1']);
	$npassword = mysqli_real_escape_string($link,$_POST['token2']);
	$n2password = mysqli_real_escape_string($link,$_POST['token3']);
	
	// Check if new passwords match
	if ($npassword!=$n2password) {
		$passwordCheck = false;
	}
	
	// Check if password match strength
	$uppercase = preg_match('@[A-Z]@', $npassword);
	$lowercase = preg_match('@[a-z]@', $npassword);
	$number    = preg_match('@[0-9]@', $npassword);
	$specialChars = preg_match('@[^\w]@', $npassword);

	if(!$uppercase || !$lowercase || !$number || !$specialChars || strlen($npassword) < 8) {
		$passwordCheck = false;
	}
	
	// Grab logged in user
	$currentuser = $_SESSION['pid'];
	
	// Check if current password is correct
	$sql = "SELECT id,password FROM tblusers WHERE id = '$currentuser'";
	$result = mysqli_query($link,$sql);	
	while($row = mysqli_fetch_array($result)){
		// Check password
		$isPasswordCorrect = password_verify($cpassword, $row['password']);
		if ($isPasswordCorrect==false) {
			// Password incorrect
			$passwordCheck = false;
		} else {
			// Check first if new passwords match
			if ($passwordCheck) {
				// Password correct so update database password
				$nupassword = password_hash($npassword, PASSWORD_DEFAULT);
				
				// Create SQL statement
				$sql = "UPDATE `tblusers` SET `password` = '$nupassword' WHERE id='$currentuser'";
				mysqli_query($link,$sql);
				
				// Reset login and force user to login again
				unset($_SESSION["pid"]);
				unset($_SESSION["firstname"]);
				unset($_SESSION["email"]);
				unset($_SESSION["atype"]);
				
				// Redirect to login page
				$signinPage = "signin.php?lang=" . $_SESSION['lang'];
				header("location:$signinPage&passwordupdate=yes"); 
				exit();
			}
		}
	}
}

// Load config
require_once 'includes/config.php';

// Page-specific metadata
$pageTitle = $langFile['passwordreset_page_title'];
$pageDescription = '';

include 'includes/template/head.php';
include 'includes/template/header.php';
?>
		<main role="main" property="mainContentOfPage" class="container">
			<h1 property="name" id="wb-cont"><?= htmlspecialchars($langFile['passwordreset_heading']) ?></h1>
			
			<?php 
			if ($passwordCheck==false) {
			?>
			<section class="alert alert-danger">
				<h2><?= htmlspecialchars($langFile['passwordreset_error_heading']) ?></h2>
				<ul>
					<li><?= $langFile['passwordreset_error_1'] ?></li>
					<li><?= $langFile['passwordreset_error_2'] ?></li>
					<li><?= $langFile['passwordreset_error_3'] ?></li>
				</ul>
			</section>
			<?php
			} 
			?>
				
			<div class="col-xs-12">
				<form id="login" method="post" action="passwordreset.php?lang=<?= $_SESSION['lang'] ?>">
				<div class="form-group">
					<label for="token1"><span class="field-name"><?= $langFile['passwordreset_current'] ?> <strong>(<?= htmlspecialchars($langFile['required']) ?>)</strong></span></label>
					<input type="password" class="form-control" id="token1" name="token1" placeholder="<?= htmlspecialchars($langFile['passwordreset_current_placeholder']) ?>">
				</div>
				<div class="form-group">
					<label for="token2"><span class="field-name"><?= $langFile['passwordreset_new'] ?> <strong>(<?= htmlspecialchars($langFile['required']) ?>)</strong></span></label>
					<input type="password" class="form-control" id="token2" name="token2" placeholder="<?= htmlspecialchars($langFile['passwordreset_new_placeholder']) ?>">
				</div>
				<div class="form-group">
					<label for="token3"><span class="field-name"><?= $langFile['passwordreset_confirm'] ?> <strong>(<?= htmlspecialchars($langFile['required']) ?>)</strong></span></label>
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
<?php 
include 'includes/template/footer.php';
include 'includes/template/scripts.php';

// Close connection
mysqli_close($link);
