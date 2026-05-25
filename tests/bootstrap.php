<?php
/**
 * PHPUnit Bootstrap File
 * Sets up test environment
 */

// Start session for tests
if (session_status() != PHP_SESSION_ACTIVE) {
    session_start();
}

// Mock $_SESSION for tests
$_SESSION['lang'] = 'en';
$_SESSION['atype'] = 1; // Admin
$_SESSION['pid'] = 1;

// Mock $_SERVER for tests
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/';

// Load shared environment helpers
require_once __DIR__ . '/../app/env.php';

// Load helpers (but don't connect to DB yet)
require_once __DIR__ . '/../app/includes/helpers.php';

// Create mock database link for tests
$GLOBALS['link'] = null;
