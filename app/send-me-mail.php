<?php
// Load session, env vars, and DB connection
require('sql.php');

$requestid = 200303;
$nrequestid = 11;
$teamname = "Super Team";
$requesttitle = "This is my last resort";
$nrequestemail = $_ENV['GCNOTIFY_TEST_EMAIL'];
$nrequestemailid = "MTE0MTg=";
$baseurl = "https://api.notification.canada.ca/v2/notifications/email";

//API CALL

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
  CURLOPT_POSTFIELDS =>'{
    "email_address":"'.$nrequestemail.'",
    "template_id":"{$_ENV['GCNOTIFY_TEMPLATE_ID']}",
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
    'Authorization: ApiKey-v1 ' . $_ENV['GCNOTIFY_API_KEY']
  ),
));

$response = curl_exec($curl);

curl_close($curl);
echo $response;
$curl = curl_init();

?>
<p><?php echo 'PHP VERSION: ' . phpversion(); ?></p>
<p><?php echo 'CURL ERROR: ' . curl_error($curl) . "."; ?></p>
<p><?php echo 'This is the response from curl: '. $response . "."; ?></p> 
<?php 
curl_close($curl);
?>