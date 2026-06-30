<?php
require_once __DIR__ . '/env.php';

function rmt_notify_mode(): string
{
	$defaultMode = app_is_production() ? 'live' : 'redirect';
	$mode = strtolower((string) app_env('NOTIFY_MODE', $defaultMode));

	if (!in_array($mode, ['live', 'redirect', 'disabled'], true)) {
		return $defaultMode;
	}

	return $mode;
}

function rmt_notify_redirect_recipient(string $recipientType = 'general'): ?string
{
	if (!empty($_SESSION['email']) && filter_var($_SESSION['email'], FILTER_VALIDATE_EMAIL)) {
		return $_SESSION['email'];
	}

	return rmt_notify_override_recipient($recipientType);
}

function rmt_notify_override_recipient(string $recipientType = 'general'): ?string
{

	$candidates = [];
	if ($recipientType === 'client') {
		$candidates[] = 'NOTIFY_OVERRIDE_CLIENT_EMAIL';
	}

	if ($recipientType === 'internal') {
		$candidates[] = 'NOTIFY_OVERRIDE_INTERNAL_EMAIL';
	}

	$candidates[] = 'NOTIFY_OVERRIDE_EMAIL';
	$candidates[] = 'GCNOTIFY_TEST_EMAIL';

	foreach ($candidates as $key) {
		$value = app_env($key);
		if ($value !== null && $value !== '' && filter_var($value, FILTER_VALIDATE_EMAIL)) {
			return $value;
		}
	}

	return null;
}

function rmt_notify_force_override_recipient(): bool
{
	$rawValue = strtolower(trim((string) app_setting('NOTIFY_REDIRECT_FORCE_OVERRIDE', 'false')));

	return in_array($rawValue, ['1', 'true', 'yes', 'on'], true);
}

function sendEmail($emailAddress, $templateId, $personalisation, array $options = [])
{
	$recipientType = $options['recipientType'] ?? 'general';
	$originalRecipient = trim((string) $emailAddress);
	$mode = rmt_notify_mode();

	if ($originalRecipient === '' || $templateId === '') {
		error_log('GC Notify skipped: missing recipient or template ID.');
		return false;
	}

	if ($mode === 'disabled') {
		error_log(sprintf('GC Notify disabled: skipped %s notification to %s.', $recipientType, $originalRecipient));
		return false;
	}

	$finalRecipient = $originalRecipient;
	$fallbackRedirectRecipient = null;
	if ($mode === 'redirect') {
		$overrideRecipient = rmt_notify_override_recipient($recipientType);
		$redirectRecipient = rmt_notify_force_override_recipient()
			? ($overrideRecipient ?? rmt_notify_redirect_recipient($recipientType))
			: rmt_notify_redirect_recipient($recipientType);

		if ($redirectRecipient === null) {
			error_log(sprintf('GC Notify redirect skipped: no safe redirect recipient configured for %s notification to %s.', $recipientType, $originalRecipient));
			return false;
		}

		$finalRecipient = $redirectRecipient;

		if ($overrideRecipient !== null && strcasecmp($overrideRecipient, $finalRecipient) !== 0) {
			$fallbackRedirectRecipient = $overrideRecipient;
		}
	}

	$personalisationPayload = is_array($personalisation)
		? $personalisation
		: json_decode((string) $personalisation, true);

	if (!is_array($personalisationPayload)) {
		$personalisationPayload = [];
	}

	$apiKey = app_env('GCNOTIFY_API_KEY', '');
	if ($apiKey === '') {
		error_log('GC Notify skipped: GCNOTIFY_API_KEY is missing.');
		return false;
	}

	$send = static function (string $recipient) use ($templateId, $personalisationPayload, $apiKey): array {
		$payload = [
			'email_address' => $recipient,
			'template_id' => $templateId,
			'personalisation' => $personalisationPayload,
		];

		$curlOptions = [
			CURLOPT_URL => 'https://api.notification.canada.ca/v2/notifications/email',
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => 'POST',
			CURLOPT_POSTFIELDS => json_encode($payload),
			CURLOPT_HTTPHEADER => [
				'Content-Type: application/json',
				'Authorization: ApiKey-v1 ' . $apiKey,
			],
		];

		$curlOptions = array_replace($curlOptions, app_gcnotify_curl_tls_options());

		$curl = curl_init();
		curl_setopt_array($curl, $curlOptions);

		$response = curl_exec($curl);
		$error = curl_error($curl);
		$httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close($curl);

		return [
			'error' => $error,
			'httpCode' => $httpCode,
			'response' => (string) $response,
		];
	};

	$attempt = $send($finalRecipient);
	$error = (string) $attempt['error'];
	$httpCode = (int) $attempt['httpCode'];
	$response = (string) $attempt['response'];

	if (
		$mode === 'redirect'
		&& $fallbackRedirectRecipient !== null
		&& $error === ''
		&& in_array($httpCode, [400, 401, 403], true)
	) {
		error_log(sprintf('GC Notify redirect retry: primary redirect recipient %s rejected (HTTP %d), retrying with override recipient %s.', $finalRecipient, $httpCode, $fallbackRedirectRecipient));
		$finalRecipient = $fallbackRedirectRecipient;
		$attempt = $send($finalRecipient);
		$error = (string) $attempt['error'];
		$httpCode = (int) $attempt['httpCode'];
		$response = (string) $attempt['response'];
	}

	if ($error !== '') {
		error_log(sprintf('GC Notify request failed for %s recipient %s (original %s): %s', $recipientType, $finalRecipient, $originalRecipient, $error));
		return false;
	}

	if ($httpCode < 200 || $httpCode >= 300) {
		error_log(sprintf('GC Notify returned HTTP %d for %s recipient %s (original %s). Response: %s', $httpCode, $recipientType, $finalRecipient, $originalRecipient, (string) $response));
		return false;
	}

	return true;
}
?>