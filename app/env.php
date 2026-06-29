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

function app_configurable_nonsecret_keys(): array
{
    return [
        'APP_BASE_URL',
        'NOTIFY_MODE',
        'NOTIFY_REDIRECT_FORCE_OVERRIDE',
        'NOTIFY_OVERRIDE_EMAIL',
        'NOTIFY_OVERRIDE_CLIENT_EMAIL',
        'NOTIFY_OVERRIDE_INTERNAL_EMAIL',
        'GCNOTIFY_TEST_EMAIL',
        'GCNOTIFY_CURL_CA_BUNDLE',
        'GCNOTIFY_CURL_INSECURE',
        'GCNOTIFY_TEMPLATE_REQUEST_TEAM_EN',
        'GCNOTIFY_TEMPLATE_REQUEST_TEAM_FR',
        'GCNOTIFY_TEMPLATE_REQUEST_AFTERFACT_TEAM_EN',
        'GCNOTIFY_TEMPLATE_REQUEST_AFTERFACT_TEAM_FR',
        'GCNOTIFY_TEMPLATE_REQUEST_AAACT',
        'GCNOTIFY_TEMPLATE_REQUEST_CLIENT_EN',
        'GCNOTIFY_TEMPLATE_REQUEST_CLIENT_FR',
        'GCNOTIFY_TEMPLATE_REQUEST_DEFAULT_TEAM_EN',
        'GCNOTIFY_TEMPLATE_REQUEST_DEFAULT_TEAM_FR',
        'GCNOTIFY_TEMPLATE_REQUEST_DEFAULT_CLIENT_EN',
        'GCNOTIFY_TEMPLATE_REQUEST_DEFAULT_CLIENT_FR',
        'GCNOTIFY_TEMPLATE_RESOLVED_TEAM_EN',
        'GCNOTIFY_TEMPLATE_RESOLVED_TEAM_FR',
        'GCNOTIFY_TEMPLATE_RESOLVED_CLIENT_EN',
        'GCNOTIFY_TEMPLATE_RESOLVED_CLIENT_FR',
        'GCNOTIFY_TEMPLATE_STATUS_CHANGED_CLIENT_EN',
        'GCNOTIFY_TEMPLATE_STATUS_CHANGED_CLIENT_FR',
        'GCNOTIFY_TEMPLATE_REASSIGNED_TEAM_EN',
        'GCNOTIFY_TEMPLATE_REASSIGNED_TEAM_FR',
        'GCNOTIFY_TEMPLATE_REASSIGNED_CLIENT_EN',
        'GCNOTIFY_TEMPLATE_REASSIGNED_CLIENT_FR',
    ];
}

function app_settings_table_ensure($dbLink): bool
{
    if (!($dbLink instanceof mysqli)) {
        return false;
    }

    $sql = "CREATE TABLE IF NOT EXISTS tblappconfig (
        id INT NOT NULL AUTO_INCREMENT,
        config_key VARCHAR(128) NOT NULL,
        config_value TEXT NULL,
        updated_by INT NULL,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        status TINYINT(1) NOT NULL DEFAULT 1,
        PRIMARY KEY (id),
        UNIQUE KEY uniq_tblappconfig_key (config_key)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    return mysqli_query($dbLink, $sql) !== false;
}

function app_settings_table_exists($dbLink): bool
{
    static $checked = false;
    static $exists = false;

    if ($checked) {
        return $exists;
    }

    $checked = true;

    if (!($dbLink instanceof mysqli)) {
        return false;
    }

    $sql = "SELECT 1 FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'tblappconfig'
            LIMIT 1";
    $result = mysqli_query($dbLink, $sql);
    $exists = ($result instanceof mysqli_result) && mysqli_num_rows($result) > 0;

    return $exists;
}

function app_db_settings_all(): array
{
    static $cache = null;

    if (is_array($cache)) {
        return $cache;
    }

    $cache = [];
    global $link;
    if (!($link instanceof mysqli)) {
        return $cache;
    }

    if (!app_settings_table_exists($link)) {
        return $cache;
    }

    $allowed = app_configurable_nonsecret_keys();
    if (empty($allowed)) {
        return $cache;
    }

    $escapedKeys = [];
    foreach ($allowed as $key) {
        $escapedKeys[] = "'" . mysqli_real_escape_string($link, $key) . "'";
    }

    $sql = "SELECT config_key, config_value
            FROM tblappconfig
            WHERE status = 1
              AND config_key IN (" . implode(',', $escapedKeys) . ")";
    $result = mysqli_query($link, $sql);
    if ($result instanceof mysqli_result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $cache[$row['config_key']] = (string) ($row['config_value'] ?? '');
        }
    }

    return $cache;
}

function app_setting(string $key, ?string $default = null): ?string
{
    if (in_array($key, app_configurable_nonsecret_keys(), true)) {
        $settings = app_db_settings_all();
        if (array_key_exists($key, $settings) && $settings[$key] !== '') {
            return $settings[$key];
        }
    }

    return app_env($key, $default);
}

function app_setting_bool(string $key, bool $default = false): bool
{
    $rawValue = app_setting($key);
    if ($rawValue === null || $rawValue === '') {
        return $default;
    }

    $normalized = strtolower(trim((string) $rawValue));

    if (in_array($normalized, ['1', 'true', 'yes', 'on'], true)) {
        return true;
    }

    if (in_array($normalized, ['0', 'false', 'no', 'off'], true)) {
        return false;
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
    $mode = strtolower((string) app_setting('NOTIFY_MODE', $defaultMode));

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
        $value = app_setting($key);
        if ($value !== null && $value !== '' && filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return $value;
        }
    }

    return null;
}

function app_base_url(): string
{
    $configuredBaseUrl = trim((string) app_setting('APP_BASE_URL', ''));
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

function app_normalize_language(?string $language, string $default = 'en'): string
{
    $normalized = strtolower(trim((string) $language));

    if (in_array($normalized, ['en', 'fr'], true)) {
        return $normalized;
    }

    return $default;
}

function app_notify_template_key_for_language(string $baseKey, ?string $language, string $default = 'en'): string
{
    return $baseKey . '_' . app_normalize_language($language, $default);
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
    return (string) app_setting($template['env'], $template['default']);
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

function app_env_bool(string $key, bool $default = false): bool
{
    $rawValue = app_env($key);
    if ($rawValue === null || $rawValue === '') {
        return $default;
    }

    $normalized = strtolower(trim((string) $rawValue));

    if (in_array($normalized, ['1', 'true', 'yes', 'on'], true)) {
        return true;
    }

    if (in_array($normalized, ['0', 'false', 'no', 'off'], true)) {
        return false;
    }

    return $default;
}

function app_gcnotify_curl_tls_options(): array
{
    $options = [];
    $caBundlePath = trim((string) app_setting('GCNOTIFY_CURL_CA_BUNDLE', ''));
    if ($caBundlePath !== '') {
        $options[CURLOPT_CAINFO] = $caBundlePath;
    }

    if (app_setting_bool('GCNOTIFY_CURL_INSECURE', false)) {
        $options[CURLOPT_SSL_VERIFYPEER] = false;
        $options[CURLOPT_SSL_VERIFYHOST] = 0;
    }

    return $options;
}
?>