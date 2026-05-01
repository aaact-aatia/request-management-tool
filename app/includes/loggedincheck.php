<?php
$lang_code = $_SESSION['lang'] ?? 'en';
if(empty($_SESSION['pid'])){
	header("location:index.php?lang=$lang_code&status=notlogged"); 
	exit();	
}
?>
