<?php
/**
 * Session Initialization Bootstrap
 *
 * This file centralizes all session setup for the application.
 * It configures PHP to use MySQL-backed sessions instead of file-based sessions,
 * which improves reliability in multi-instance, load-balanced, and cloud environments.
 *
 * IMPORTANT: This file must be included AFTER db.php but BEFORE any headers are sent
 * (i.e., before cors.php). Typically called from sql.php after database connection.
 *
 * Why MySQL-backed sessions?
 * - File-based sessions don't persist across container restarts or pod migrations
 * - Azure App Service session affinity (ARRAffinity) is not reliable with proxies/WAF
 * - MySQL sessions work reliably with load balancers, proxies, and multi-instance deployments
 * - Garbage collection is automatic and efficient with database indexes
 *
 * Environment behavior:
 * - Production: fail fast if MySQL session storage is unavailable
 * - Local/dev/test: log warning and allow fallback to file-based sessions
 *
 * @see app/includes/MySQLSessionHandler.php
 */

if (defined('RMT_SESSION_INITIALIZED')) {
    return;
}

define('RMT_SESSION_INITIALIZED', true);

if (!function_exists('rmt_session_bootstrap_fail')) {
    /**
     * Handle fatal/non-fatal bootstrap failures by environment.
     *
     * Returns false in non-production to allow file-session fallback.
     */
    function rmt_session_bootstrap_fail(string $message): bool
    {
        error_log($message);

        if (app_is_production()) {
            http_response_code(500);
            exit('Session configuration error.');
        }

        return false;
    }
}

if (!isset($GLOBALS['link']) || !($GLOBALS['link'] instanceof mysqli)) {
    rmt_session_bootstrap_fail('Session bootstrap error: Database connection is not available for MySQL-backed sessions.');
    return;
}

$link = $GLOBALS['link'];

$tableCheckQuery = "SELECT 1 FROM INFORMATION_SCHEMA.TABLES
                   WHERE TABLE_SCHEMA = DATABASE()
                     AND TABLE_NAME = 'tblphp_sessions'
                   LIMIT 1";

try {
    $result = mysqli_query($link, $tableCheckQuery);
    $tableExists = ($result instanceof mysqli_result) && (mysqli_num_rows($result) > 0);
} catch (Throwable $e) {
    rmt_session_bootstrap_fail('Session bootstrap error: Failed to verify tblphp_sessions table. ' . $e->getMessage());
    return;
}

if (!$tableExists) {
    rmt_session_bootstrap_fail('Session bootstrap error: tblphp_sessions table does not exist. Apply database/session_handler.sql before running in production.');
    return;
}

if (session_status() === PHP_SESSION_NONE) {
    $sessionLifetime = (int) app_env('SESSION_LIFETIME', '86400');

    ini_set('session.gc_maxlifetime', (string) $sessionLifetime);
    session_set_cookie_params([
        'lifetime' => $sessionLifetime,
        'path' => '/',
        'domain' => '',
        'secure' => app_is_production(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    require_once __DIR__ . '/MySQLSessionHandler.php';

    try {
        $sessionHandler = new MySQLSessionHandler($link, $sessionLifetime);
        $handlerRegistered = session_set_save_handler($sessionHandler, true);
        if ($handlerRegistered !== true) {
            rmt_session_bootstrap_fail('Session bootstrap error: session_set_save_handler returned false for MySQLSessionHandler.');
        }
    } catch (Throwable $e) {
        rmt_session_bootstrap_fail('Session bootstrap error: Failed to register MySQLSessionHandler. ' . $e->getMessage());
    }
}
