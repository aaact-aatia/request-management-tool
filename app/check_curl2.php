<?php
/**
 * GC Notify API test script
 * Tests connectivity to the GC Notify email API.
 * Requires GCNOTIFY_API_KEY in runtime environment.
 * Reads GCNOTIFY_TEMPLATE_ID and GCNOTIFY_TEST_EMAIL from app settings (DB with env fallback).
 * See docs/future/006-gcnotify-integration.md
 */

// Load session, env vars, and DB connection
require('sql.php');
/** @var mysqli $link */

$testEmail = (string) app_setting('GCNOTIFY_TEST_EMAIL', '');
$templateId = (string) app_setting('GCNOTIFY_TEMPLATE_ID', '');
$apiKey = (string) app_env('GCNOTIFY_API_KEY', '');

$curl = curl_init();

$curlOptions = array(
    CURLOPT_URL => 'https://api.notification.canada.ca/v2/notifications/email',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => json_encode([
        'email_address' => $testEmail,
        'template_id'   => $templateId,
        'personalisation' => [
            'requestid'      => 1,
            'nrequestid'     => 1,
            'teamname'       => 'Test Team',
            'requesttitle'   => 'Test Request',
            'nrequestemailid' => 1,
            'nrequestemail'  => $testEmail,
        ],
    ]),
    CURLOPT_HTTPHEADER => array(
        'Content-Type: application/json',
        'Authorization: ApiKey-v1 ' . $apiKey,
    ),
);

$curlOptions = array_replace($curlOptions, app_gcnotify_curl_tls_options());
curl_setopt_array($curl, $curlOptions);

$response = curl_exec($curl);
$error    = curl_error($curl);
curl_close($curl);

echo '<p>PHP VERSION: ' . phpversion() . '</p>';
echo '<p>CURL ERROR: ' . ($error ?: 'none') . '</p>';
echo '<p>Response: ' . htmlspecialchars($response) . '</p>';
