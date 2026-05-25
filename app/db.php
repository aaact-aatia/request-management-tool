<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/env.php';

$dbhost = app_env_required('DB_HOST');
$dbuser = app_env_required('DB_USER');
$dbpass = app_env_required('DB_PASS');
$dbname = app_env_required('DB_NAME');
$dbport = (int) app_env('DB_PORT', '3306');
$dbsslca = app_env('DB_SSL_CA');
$dbsslmode = strtoupper((string) app_env('DB_SSL_MODE', $dbsslca ? 'REQUIRED' : 'DISABLED'));

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$link = mysqli_init();
if ($link === false) {
    error_log('Failed to initialize MySQL connection.');
    http_response_code(500);
    exit('Database connection failed.');
}

mysqli_options($link, MYSQLI_OPT_CONNECT_TIMEOUT, 10);

$clientFlags = 0;
if ($dbsslmode !== 'DISABLED') {
    if (!empty($dbsslca)) {
        mysqli_ssl_set($link, null, null, $dbsslca, null, null);
    }

    $clientFlags = MYSQLI_CLIENT_SSL;
}

try {
    $connected = mysqli_real_connect($link, $dbhost, $dbuser, $dbpass, $dbname, $dbport, null, $clientFlags);
} catch (mysqli_sql_exception $exception) {
    error_log('Database connection failed: ' . $exception->getMessage());
    http_response_code(500);
    exit('Database connection failed.');
}

if (!$connected) {
    error_log('Database connection failed: ' . mysqli_connect_error());
    http_response_code(500);
    exit('Database connection failed.');
}

$link->set_charset("utf8mb4");
?>
