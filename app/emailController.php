<?php 
	require_once __DIR__ . '/env.php';

    function sendEmail($emailAddress, $templateId, $personalisation){
		
		
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
				  "email_address": "'.$emailAddress.'",
				  "template_id":"'.$templateId.'",
				  "personalisation":'.$personalisation.'
				}',
	    CURLOPT_HTTPHEADER => array(
				  'Content-Type: application/json',
					  'Authorization: ApiKey-v1 ' . app_env('GCNOTIFY_API_KEY', '')
				),
			));
	
			// $response = curl_exec($curl);
			// echo $response;
			// curl_close($curl);
			
           }
		   


?>