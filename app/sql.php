<?php
// Set global variables

require_once __DIR__ . '/env.php';

$displayErrors = app_is_production() ? '0' : '1';
ini_set('display_errors', $displayErrors);
ini_set('display_startup_errors', $displayErrors);
ini_set('log_errors', '1');
if (file_exists('/proc/self/fd/2')) {
	ini_set('error_log', '/proc/self/fd/2');
}
date_default_timezone_set(app_env('TZ', 'America/New_York'));

// Establish database connection BEFORE session setup
// so we can configure MySQL-backed session handler
require_once 'db.php';

// Configure MySQL-backed sessions (must be done BEFORE any headers sent)
// Must be before cors.php since cors.php sends headers
require_once 'includes/session.php';

// Now we can send CORS headers after session configuration
require('cors.php');

// Configure and start session only when a session is not already active.
if (session_status() !== PHP_SESSION_ACTIVE) {
	session_start();
}



// for now
if(!isset($_SESSION["team"])){
	unset($_SESSION["pid"]);
}

if (!isset($_SESSION["pid"]))
{
	$_SESSION["pid"] = null;
	$_SESSION["atype"] = null;
	$_SESSION["primary_atype"] = null;
	$_SESSION["is_superuser"] = 0;
	$_SESSION["is_admin"] = 0;
	$_SESSION["email"] = null;
	$_SESSION["firstname"] = null;
	$_SESSION["team"] = null;
}

/** @var mysqli $link */

if (!function_exists('rmt_db_column_exists')) {
	/**
	 * Check if a table column exists in the current database.
	 */
	function rmt_db_column_exists($dbLink, string $tableName, string $columnName): bool {
		if (!($dbLink instanceof mysqli)) {
			return false;
		}

		$tableName = mysqli_real_escape_string($dbLink, $tableName);
		$columnName = mysqli_real_escape_string($dbLink, $columnName);

		$sql = "SELECT 1
				FROM INFORMATION_SCHEMA.COLUMNS
				WHERE TABLE_SCHEMA = DATABASE()
				  AND TABLE_NAME = '$tableName'
				  AND COLUMN_NAME = '$columnName'
				LIMIT 1";
		$result = mysqli_query($dbLink, $sql);

		return ($result instanceof mysqli_result) && mysqli_num_rows($result) > 0;
	}
}

if (!function_exists('rmt_db_table_exists')) {
	/**
	 * Check if a table exists in the current database.
	 */
	function rmt_db_table_exists($dbLink, string $tableName): bool {
		if (!($dbLink instanceof mysqli)) {
			return false;
		}

		$tableName = mysqli_real_escape_string($dbLink, $tableName);

		$sql = "SELECT 1
				FROM INFORMATION_SCHEMA.TABLES
				WHERE TABLE_SCHEMA = DATABASE()
				  AND TABLE_NAME = '$tableName'
				LIMIT 1";
		$result = mysqli_query($dbLink, $sql);

		return ($result instanceof mysqli_result) && mysqli_num_rows($result) > 0;
	}
}

if (!function_exists('rmt_is_schema_mismatch_error')) {
	/**
	 * Detect common MySQL schema drift errors (missing table/column/index).
	 */
	function rmt_is_schema_mismatch_error($dbLink): bool {
		if (!($dbLink instanceof mysqli)) {
			return false;
		}

		$schemaErrorCodes = [
			1050, // Table already exists
			1051, // Unknown table
			1054, // Unknown column
			1091, // Can't drop/check that column/key
			1146, // Table doesn't exist
		];

		return in_array(mysqli_errno($dbLink), $schemaErrorCodes, true);
	}
}

if (!function_exists('rmt_render_schema_error')) {
	/**
	 * Render a friendly schema error for admin forms instead of hard failing.
	 */
	function rmt_render_schema_error(?string $langCode = null): void {
		if ($langCode === null || $langCode === '') {
			$langCode = (isset($_SESSION['lang']) && in_array($_SESSION['lang'], ['en', 'fr'], true)) ? $_SESSION['lang'] : 'en';
		}

		$isFrench = ($langCode === 'fr');
		$title = $isFrench ? 'Mise a jour requise' : 'Update required';
		$message = $isFrench
			? 'Cette fonction d\'administration est temporairement indisponible parce que la base de donnees ne correspond pas a la version attendue de l\'application. Veuillez contacter l\'equipe de support pour appliquer les mises a jour de schema requises.'
			: 'This admin form is temporarily unavailable because the database schema does not match the expected application version. Please contact support to apply the required database updates.';

		http_response_code(500);
		echo '<section id="filter-id" class="modal-dialog modal-content overlay-def">';
		echo '<header class="modal-header"><h2 class="modal-title">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h2></header>';
		echo '<div class="modal-body"><p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p></div>';
		echo '</section>';
	}
}

if (!function_exists('rmt_admin_query')) {
	/**
	 * Execute an admin query and handle schema drift with a friendly message.
	 */
	function rmt_admin_query($dbLink, string $query, ?string $langCode = null) {
		try {
			$result = mysqli_query($dbLink, $query);
		} catch (mysqli_sql_exception $e) {
			$schemaErrorCodes = [1050, 1051, 1054, 1091, 1146];
			if (in_array((int)$e->getCode(), $schemaErrorCodes, true)) {
				rmt_render_schema_error($langCode);
				exit();
			}

			throw $e;
		}

		if ($result === false && rmt_is_schema_mismatch_error($dbLink)) {
			rmt_render_schema_error($langCode);
			exit();
		}

		return $result;
	}
}

if (!function_exists('rmt_result_num_rows')) {
	function rmt_result_num_rows($result): int {
		return ($result instanceof mysqli_result) ? mysqli_num_rows($result) : 0;
	}
}

if (!function_exists('rmt_result_fetch_array')) {
	function rmt_result_fetch_array($result) {
		return ($result instanceof mysqli_result) ? mysqli_fetch_array($result) : false;
	}
}


?>