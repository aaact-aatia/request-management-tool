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
        'GCNOTIFY_TEMPLATE_ID',
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

function app_notify_template_id(string $templateKey): string
{
    $templates = [
        'notification_generic' => ['env' => 'GCNOTIFY_TEMPLATE_ID', 'default' => ''],
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

function app_dev_notification_preview_enabled(): bool
{
    if (app_is_production()) {
        return false;
    }

    return session_status() === PHP_SESSION_ACTIVE;
}

function app_dev_notification_preview_add(array $entry): void
{
    if (!app_dev_notification_preview_enabled()) {
        return;
    }

    if (!isset($_SESSION['dev_notification_preview']) || !is_array($_SESSION['dev_notification_preview'])) {
        $_SESSION['dev_notification_preview'] = [];
    }

    $_SESSION['dev_notification_preview'][] = [
        'recipientType' => (string) ($entry['recipientType'] ?? 'general'),
        'intendedRecipient' => (string) ($entry['intendedRecipient'] ?? ''),
        'finalRecipient' => (string) ($entry['finalRecipient'] ?? ''),
        'mode' => (string) ($entry['mode'] ?? app_notify_mode()),
        'result' => (string) ($entry['result'] ?? 'attempted'),
    ];
}

function app_dev_notification_preview_consume(): array
{
    if (!app_dev_notification_preview_enabled()) {
        return [];
    }

    $entries = $_SESSION['dev_notification_preview'] ?? [];
    if (!is_array($entries)) {
        $entries = [];
    }

    unset($_SESSION['dev_notification_preview']);
    return $entries;
}
?>