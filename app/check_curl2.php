<?php
/**
 * GC Notify API test script
 * Tests connectivity to the GC Notify email API.
 * Requires GCNOTIFY_API_KEY, GCNOTIFY_TEMPLATE_ID, and GCNOTIFY_TEST_EMAIL in the runtime environment.
 * See docs/future/006-gcnotify-integration.md
 */

// Load session, env vars, and DB connection
require('sql.php');
/** @var mysqli $link */

$curl = curl_init();

curl_setopt_array($curl, array(
    CURLOPT_URL => 'https://api.notification.canada.ca/v2/notifications/email',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => json_encode([
        'email_address' => app_env('GCNOTIFY_TEST_EMAIL', ''),
        'template_id'   => app_env('GCNOTIFY_TEMPLATE_ID', ''),
        'personalisation' => [
            'requestid'      => 1,
            'nrequestid'     => 1,
            'teamname'       => 'Test Team',
            'requesttitle'   => 'Test Request',
            'nrequestemailid' => 1,
            'nrequestemail'  => app_env('GCNOTIFY_TEST_EMAIL', ''),
        ],
    ]),
    CURLOPT_HTTPHEADER => array(
        'Content-Type: application/json',
        'Authorization: ApiKey-v1 ' . app_env('GCNOTIFY_API_KEY', ''),
    ),
));

$response = curl_exec($curl);
$error    = curl_error($curl);
curl_close($curl);

echo '<p>PHP VERSION: ' . phpversion() . '</p>';
echo '<p>CURL ERROR: ' . ($error ?: 'none') . '</p>';
echo '<p>Response: ' . htmlspecialchars($response) . '</p>';
