<?php

require_once __DIR__ . '/env.php';

$allowedOrigins = app_env('CORS_ALLOWED_ORIGINS', '');

$allowed_origins = $allowedOrigins !== ''
    ? array_map('trim', explode(',', $allowedOrigins))
    : [];

if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowed_origins)) {
    header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
}

header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Max-Age: 86400'); // Cache for 1 day


?>