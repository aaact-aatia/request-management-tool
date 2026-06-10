<?php
if (!headers_sent()) {
    $target = '/teams.php';
    if (!empty($_SERVER['QUERY_STRING'])) {
        $target .= '?' . $_SERVER['QUERY_STRING'];
    }
    header('Location: ' . $target, true, 301);
    exit();
}
