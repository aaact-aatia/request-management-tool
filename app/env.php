<?php

function app_env(string $key, ?string $default = null): ?string
{
    $value = getenv($key);
    if ($value !== false) {
        return $value;
    }

    if (array_key_exists($key, $_SERVER)) {
        return $_SERVER[$key];
    }

    if (array_key_exists($key, $_ENV)) {
        return $_ENV[$key];
    }

    return $default;
}

function app_is_production(): bool
{
    $environment = strtolower((string) app_env('APP_ENV', 'production'));

    return !in_array($environment, ['dev', 'development', 'local', 'test', 'testing'], true);
}

function app_notify_mode(): string
{
    $defaultMode = app_is_production() ? 'live' : 'redirect';
    $mode = strtolower((string) app_env('NOTIFY_MODE', $defaultMode));

    if (!in_array($mode, ['live', 'redirect', 'disabled'], true)) {
        return $defaultMode;
    }

    return $mode;
}

function app_notify_redirect_recipient(string $recipientType = 'general'): ?string
{
    if (!empty($_SESSION['email']) && filter_var($_SESSION['email'], FILTER_VALIDATE_EMAIL)) {
        return $_SESSION['email'];
    }

    $candidates = [];
    if ($recipientType === 'client') {
        $candidates[] = 'NOTIFY_OVERRIDE_CLIENT_EMAIL';
    }

    if ($recipientType === 'internal') {
        $candidates[] = 'NOTIFY_OVERRIDE_INTERNAL_EMAIL';
    }

    $candidates[] = 'NOTIFY_OVERRIDE_EMAIL';

    foreach ($candidates as $key) {
        $value = app_env($key);
        if ($value !== null && $value !== '' && filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return $value;
        }
    }

    return null;
}

function app_base_url(): string
{
    $configuredBaseUrl = trim((string) app_env('APP_BASE_URL', ''));
    if ($configuredBaseUrl !== '') {
        return rtrim($configuredBaseUrl, '/');
    }

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = isset($_SERVER['HTTP_HOST']) ? trim((string) $_SERVER['HTTP_HOST']) : '';

    if ($host !== '') {
        return $scheme . '://' . $host;
    }

    return 'https://gcdc-ssc-ictaccess-linux-aaact-rmt-dev-asv.azurewebsites.net';
}

function app_url(string $path = ''): string
{
    $baseUrl = app_base_url();
    if ($path === '') {
        return $baseUrl;
    }

    return $baseUrl . '/' . ltrim($path, '/');
}

function app_notify_template_id(string $templateKey): string
{
    $templates = [
        'request_team_en' => ['env' => 'GCNOTIFY_TEMPLATE_REQUEST_TEAM_EN', 'default' => 'd9c219be-799f-4713-950f-21884d5d3c3c'],
        'request_team_fr' => ['env' => 'GCNOTIFY_TEMPLATE_REQUEST_TEAM_FR', 'default' => '86fb7784-b1cc-40b5-88e5-f7ea43ee75c0'],
        'request_afterfact_team_en' => ['env' => 'GCNOTIFY_TEMPLATE_REQUEST_AFTERFACT_TEAM_EN', 'default' => '949c6248-ef73-4cf2-b1ea-5136c8c856c2'],
        'request_afterfact_team_fr' => ['env' => 'GCNOTIFY_TEMPLATE_REQUEST_AFTERFACT_TEAM_FR', 'default' => 'c5ea62d8-9e11-482a-acfc-ae8a450de06c'],
        'request_aaact' => ['env' => 'GCNOTIFY_TEMPLATE_REQUEST_AAACT', 'default' => '35388592-27f3-47f5-ae09-ac3f9ddf7904'],
        'request_client_en' => ['env' => 'GCNOTIFY_TEMPLATE_REQUEST_CLIENT_EN', 'default' => '9e4e2ca4-ad1a-4204-ba1e-4be61a12f51c'],
        'request_client_fr' => ['env' => 'GCNOTIFY_TEMPLATE_REQUEST_CLIENT_FR', 'default' => 'd4fb66f3-e9f3-442f-9b7b-8b8e24f8799d'],
        'request_default_team_en' => ['env' => 'GCNOTIFY_TEMPLATE_REQUEST_DEFAULT_TEAM_EN', 'default' => '265e8009-741e-4a79-8e89-bfedaf071494'],
        'request_default_team_fr' => ['env' => 'GCNOTIFY_TEMPLATE_REQUEST_DEFAULT_TEAM_FR', 'default' => 'c72c5e69-8a8c-42a2-9bb9-dfcf2c5f7d84'],
        'request_default_client_en' => ['env' => 'GCNOTIFY_TEMPLATE_REQUEST_DEFAULT_CLIENT_EN', 'default' => 'dcc97e6e-1fdf-4309-9351-a957ff5f6dcb'],
        'request_default_client_fr' => ['env' => 'GCNOTIFY_TEMPLATE_REQUEST_DEFAULT_CLIENT_FR', 'default' => '36125c35-b1af-4989-9a94-f65b8e5cf49f'],
        'resolved_team_en' => ['env' => 'GCNOTIFY_TEMPLATE_RESOLVED_TEAM_EN', 'default' => '5dc8291c-a0b4-4fa0-8733-40c28d3ddf6d'],
        'resolved_team_fr' => ['env' => 'GCNOTIFY_TEMPLATE_RESOLVED_TEAM_FR', 'default' => '5dc8291c-a0b4-4fa0-8733-40c28d3ddf6d'],
        'resolved_client_en' => ['env' => 'GCNOTIFY_TEMPLATE_RESOLVED_CLIENT_EN', 'default' => '49ffefeb-21d0-4508-ac5f-46b41c0f3348'],
        'resolved_client_fr' => ['env' => 'GCNOTIFY_TEMPLATE_RESOLVED_CLIENT_FR', 'default' => '49ffefeb-21d0-4508-ac5f-46b41c0f3348'],
        'status_changed_client_en' => ['env' => 'GCNOTIFY_TEMPLATE_STATUS_CHANGED_CLIENT_EN', 'default' => '393948e5-39fe-418e-b16f-73a1f084a0f2'],
        'status_changed_client_fr' => ['env' => 'GCNOTIFY_TEMPLATE_STATUS_CHANGED_CLIENT_FR', 'default' => '393948e5-39fe-418e-b16f-73a1f084a0f2'],
        'reassigned_team_en' => ['env' => 'GCNOTIFY_TEMPLATE_REASSIGNED_TEAM_EN', 'default' => '8270de12-b994-4d29-aa22-428434fd9896'],
        'reassigned_team_fr' => ['env' => 'GCNOTIFY_TEMPLATE_REASSIGNED_TEAM_FR', 'default' => '8270de12-b994-4d29-aa22-428434fd9896'],
        'reassigned_client_en' => ['env' => 'GCNOTIFY_TEMPLATE_REASSIGNED_CLIENT_EN', 'default' => '8bb9cc70-dd1a-46d6-9843-c73cbe4e70f0'],
        'reassigned_client_fr' => ['env' => 'GCNOTIFY_TEMPLATE_REASSIGNED_CLIENT_FR', 'default' => '8bb9cc70-dd1a-46d6-9843-c73cbe4e70f0'],
    ];

    if (!isset($templates[$templateKey])) {
        error_log("Unknown GC Notify template key '{$templateKey}'.");
        return '';
    }

    $template = $templates[$templateKey];
    return (string) app_env($template['env'], $template['default']);
}

function app_env_required(string $key): string
{
    $value = app_env($key);

    if ($value === null || $value === '') {
        error_log("Required environment variable '{$key}' is missing or empty.");

        if (app_is_production()) {
            http_response_code(500);
            exit('Application configuration error.');
        }

        throw new RuntimeException("Required environment variable '{$key}' is missing or empty.");
    }

    return $value;
}
?>