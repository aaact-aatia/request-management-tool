<?php
if (session_status() != PHP_SESSION_ACTIVE)
{
	session_start();
}

// Language detection
$lang = isset($_GET['lang']) && $_GET['lang'] === 'fr' ? 'fr' : 'en';
		
unset($_SESSION["pid"]);
unset($_SESSION["firstname"]);
unset($_SESSION["email"]);
unset($_SESSION["atype"]);
unset($_SESSION["primary_atype"]);
unset($_SESSION["is_superuser"]);
unset($_SESSION["is_admin"]);
unset($_SESSION["real_atype"]); // Clear dev mode tracking

header("location:signin.php?lang=$lang&loggedout=true"); 
exit();
