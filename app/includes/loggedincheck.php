<?php
if (isset($_SERVER['SCRIPT_FILENAME']) && realpath(__FILE__) === realpath((string) $_SERVER['SCRIPT_FILENAME'])) {
    http_response_code(404);
    exit();
}

$lang_code = $_SESSION['lang'] ?? 'en';
if(empty($_SESSION['pid'])){
	header("location:index.php?lang=$lang_code&status=notlogged"); 
	exit();	
}
?>
