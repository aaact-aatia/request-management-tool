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