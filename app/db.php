<?php
require_once __DIR__ . '/vendor/autoload.php';

// Load environment variables
Dotenv\Dotenv::createImmutable(__DIR__)->safeLoad();

// Extract DB credentials
$dbhost = $_ENV['DB_HOST'] ?? 'localhost';
$dbuser = $_ENV['DB_USER'] ?? 'root';
$dbpass = $_ENV['DB_PASS'] ?? '';
$dbname = $_ENV['DB_NAME'] ?? 'test';

// Report errors (remove or tweak for production)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Connect
$link = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname);
if (!$link) {
    die("ERROR: Could not connect. " . mysqli_connect_error());
}

// Set encoding
$link->set_charset("utf8mb4");
?>
