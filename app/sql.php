<?php
// Set global variables

require_once __DIR__ . '/env.php';

require('cors.php');

$displayErrors = app_is_production() ? '0' : '1';
ini_set('display_errors', $displayErrors);
ini_set('display_startup_errors', $displayErrors);
ini_set('log_errors', '1');
if (file_exists('/proc/self/fd/2')) {
	ini_set('error_log', '/proc/self/fd/2');
}
date_default_timezone_set(app_env('TZ', 'America/New_York'));

// Configure and start session only when a session is not already active.
if (session_status() !== PHP_SESSION_ACTIVE) {
	ini_set('session.gc_maxlifetime', '86400');
	session_set_cookie_params(86400);
	session_start();
}



// for now
if(!isset($_SESSION["team"])){
	unset($_SESSION["pid"]);
}

if (!isset($_SESSION["pid"]))
{
	$_SESSION["pid"] = null;
	$_SESSION["atype"] = null;
	$_SESSION["email"] = null;
	$_SESSION["firstname"] = null;
	$_SESSION["team"] = null;
}

require_once 'db.php';

/** @var mysqli $link */

// Check which environment is being used
// Grab logged in user
$cenvironment = 0; // Default to production
if (isset($_SESSION['pid'])){
	$currentuser = $_SESSION['pid'];
	$result = mysqli_query($link, "SELECT environment FROM tblusers WHERE id = '$currentuser'");
	$row = mysqli_fetch_array(result: $result);
	$cenvironment = $row['environment'] ?? 0;

	// If it's prod keep going otherwise close connection and open DEV
	// if ($cenvironment==1) {
	// 	//We need to use DEV
	// 	// Close connection
	// 	mysqli_close($link);
	
	// 	//connection to the database
	// 	$link = mysqli_connect("localhost", "upek4k7dkhz94", "uz3s6hae96jp", "dblee9r8rvaboq");
 
	// 	// Check connection
	// 	if($link == false){
	// 		die("ERROR: Could not connect. " . mysqli_connect_error());
	// 	}
	// }
}
?>