<?php
/**
 * Shared session starter.
 *
 * Ensures all direct entry points use the centralized MySQL-backed
 * session bootstrap before calling session_start().
 */

require_once dirname(__DIR__) . '/env.php';
require_once dirname(__DIR__) . '/db.php';
require_once __DIR__ . '/session.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    if (session_start() !== true) {
        error_log('Session startup failed.');
        http_response_code(503);
        exit('Session temporarily unavailable.');
    }
}
