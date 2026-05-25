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