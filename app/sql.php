<?php
// Set global variables

require('cors.php');

if (session_status() != PHP_SESSION_ACTIVE)
{
	ini_set('display_errors', 1);
	ini_set('session.gc_maxlifetime', 86400);
	session_set_cookie_params(86400);
	date_default_timezone_set('America/New_York');
	session_start();
} 
else
{
	session_abort();
	ini_set('display_errors', 1);
	ini_set('session.gc_maxlifetime', 86400);
	session_set_cookie_params(86400);
	date_default_timezone_set('America/New_York');
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

// Set UTF-8
mysqli_query($link, "SET NAMES utf8mb4");
?>