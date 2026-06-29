<?php
// Load session, env vars, and DB connection
require('sql.php');
/** @var mysqli $link */

$requestid = 200303;
$nrequestid = 11;
$teamname = "Super Team";
$requesttitle = "This is my last resort";
$nrequestemail = app_env('GCNOTIFY_TEST_EMAIL', '');
$nrequestemailid = "MTE0MTg=";
$baseurl = "https://api.notification.canada.ca/v2/notifications/email";

//API CALL

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
  CURLOPT_POSTFIELDS =>'{
    "email_address":"'.$nrequestemail.'",
    "template_id":"'.app_env('GCNOTIFY_TEMPLATE_ID', '').'",
    "personalisation":{
        "requestid":"'.$requestid.'",
        "nrequestid":"'.$nrequestid.'",
        "teamname":"'.$teamname.'",
        "requesttitle":"'.$requesttitle.'",
        "nrequestemailid":"'.$nrequestemailid.'",
        "nrequestemail":"'.$nrequestemail.'"
    }
}',
  CURLOPT_HTTPHEADER => array(
    'Content-Type: application/json',
    'Authorization: ApiKey-v1 ' . app_env('GCNOTIFY_API_KEY', '')
  ),
);

$curlOptions = array_replace($curlOptions, app_gcnotify_curl_tls_options());
curl_setopt_array($curl, $curlOptions);

$response = curl_exec($curl);
$error = curl_error($curl);

curl_close($curl);

if ($error !== '') {
    error_log('GC Notify test request failed: ' . $error);
}

?>
<p><?php echo 'PHP VERSION: ' . phpversion(); ?></p>
<p><?php echo 'CURL STATUS: ' . htmlspecialchars($error === '' ? 'none' : (app_is_production() ? 'request failed' : $error)) . '.'; ?></p>
<p><?php echo 'This is the response from curl: ' . htmlspecialchars((string) $response) . '.'; ?></p> 
<?php 
?>