<?php
/**
 * Language Switcher
 * 
 * This script handles switching between English and French languages.
 * It accepts two GET parameters:
 *   - lang: The target language ('en' or 'fr')
 *   - return: The URL to redirect back to after switching
 * 
 * Usage: switch-lang.php?lang=fr&return=/openrequest.php
 * 
 * @package RMT
 * @since 2.0.0
 */

// Start session
require_once __DIR__ . '/includes/session_start.php';

// Get the language parameter
$targetLang = isset($_GET['lang']) ? $_GET['lang'] : 'en';

// Validate language (only 'en' or 'fr' allowed, default to 'en')
if (!in_array($targetLang, ['en', 'fr'])) {
	$targetLang = 'en';
}

// Set the session language
$_SESSION['lang'] = $targetLang;

// Get the return URL parameter
$returnUrl = isset($_GET['return']) ? $_GET['return'] : '/';

// Validate return URL to prevent open redirect vulnerabilities
// Only allow relative URLs (starting with /)
if (empty($returnUrl) || substr($returnUrl, 0, 1) !== '/') {
	$returnUrl = '/';
}

// Remove any potential domain/protocol from the return URL for security
$returnUrl = preg_replace('/^https?:\/\/[^\/]+/', '', $returnUrl);

// Redirect back to the return URL
header("Location: $returnUrl");
exit;
