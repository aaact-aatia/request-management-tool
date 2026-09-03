<?php
require('sql.php');

$lang = isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'fr'], true)
	? $_GET['lang']
	: ($_SESSION['lang'] ?? 'en');
$_SESSION['lang'] = in_array($lang, ['en', 'fr'], true) ? $lang : 'en';

$query = $_SERVER['QUERY_STRING'] ?? '';
$target = !empty($_SESSION['pid']) ? '/requests.php' : '/openrequest.php';
$target .= ($query !== '' ? '?' . $query : '?lang=' . $_SESSION['lang']);
header("Location: $target", true, 301);
exit();
