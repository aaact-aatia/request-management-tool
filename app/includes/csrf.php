<?php

if (!function_exists('rmt_csrf_token')) {
    function rmt_csrf_token(string $scope): string
    {
        $secret = (string) app_env('CSRF_SECRET', '');
        if ($secret === '') {
            $secret = (string) app_env('DB_PASS', '');
        }

        if ($secret === '') {
            throw new RuntimeException('CSRF token secret is not configured.');
        }

        $sessionId = session_id();
        $userId = (string) ($_SESSION['pid'] ?? '');
        if ($sessionId === '' || $userId === '') {
            throw new RuntimeException('CSRF token requires an authenticated session.');
        }

        return hash_hmac('sha256', $scope . "\0" . $sessionId . "\0" . $userId, $secret);
    }
}

if (!function_exists('rmt_csrf_token_is_valid')) {
    function rmt_csrf_token_is_valid(string $scope, string $submittedToken): bool
    {
        return $submittedToken !== '' && hash_equals(rmt_csrf_token($scope), $submittedToken);
    }
}