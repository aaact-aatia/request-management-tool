<?php
if (isset($_SERVER['SCRIPT_FILENAME']) && realpath(__FILE__) === realpath((string) $_SERVER['SCRIPT_FILENAME'])) {
    http_response_code(404);
    exit();
}

/**
 * Help docs utility helpers for markdown discovery, safe file reads, and grouping.
 */

if (!function_exists('rmt_docs_should_show_index')) {
    function rmt_docs_should_show_index(bool $isAdminUser, string $language): bool {
        return $isAdminUser && $language === 'en';
    }
}

if (!function_exists('rmt_docs_allowed_extensions')) {
    function rmt_docs_allowed_extensions(): array {
        return ['md', 'markdown'];
    }
}

if (!function_exists('rmt_docs_denied_path_segments')) {
    function rmt_docs_denied_path_segments(): array {
        return ['.git', '.svn', '__macosx'];
    }
}

if (!function_exists('rmt_docs_is_allowed_extension')) {
    function rmt_docs_is_allowed_extension(string $extension): bool {
        return in_array(strtolower($extension), rmt_docs_allowed_extensions(), true);
    }
}

if (!function_exists('rmt_docs_is_denied_path')) {
    function rmt_docs_is_denied_path(string $relativePath): bool {
        $normalized = str_replace('\\', '/', strtolower(trim($relativePath)));
        if ($normalized === '') {
            return true;
        }

        if ($normalized[0] === '/' || strpos($normalized, '..') !== false || strpos($normalized, "\0") !== false) {
            return true;
        }

        $segments = array_filter(explode('/', $normalized), static function (string $segment): bool {
            return $segment !== '';
        });

        foreach ($segments as $segment) {
            if ($segment[0] === '.') {
                return true;
            }

            if (in_array($segment, rmt_docs_denied_path_segments(), true)) {
                return true;
            }

            if ($segment === '.ds_store') {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('rmt_docs_sanitize_request_doc')) {
    function rmt_docs_sanitize_request_doc(string $requestedDoc): string {
        $candidate = str_replace('\\', '/', rawurldecode(trim($requestedDoc)));
        if ($candidate === '' || rmt_docs_is_denied_path($candidate)) {
            return '';
        }

        return $candidate;
    }
}

if (!function_exists('rmt_docs_read_file_with_limits')) {
    function rmt_docs_read_file_with_limits(string $absolutePath, int $maxBytes, float $maxReadSeconds): array {
        if (!is_file($absolutePath) || !is_readable($absolutePath)) {
            return ['ok' => false, 'content' => '', 'error' => 'unreadable'];
        }

        $fileSize = @filesize($absolutePath);
        if (is_int($fileSize) && $fileSize > $maxBytes) {
            return ['ok' => false, 'content' => '', 'error' => 'too_large'];
        }

        $handle = @fopen($absolutePath, 'rb');
        if ($handle === false) {
            return ['ok' => false, 'content' => '', 'error' => 'unreadable'];
        }

        $content = '';
        $bytesRead = 0;
        $start = microtime(true);

        while (!feof($handle)) {
            if ((microtime(true) - $start) > $maxReadSeconds) {
                fclose($handle);
                return ['ok' => false, 'content' => '', 'error' => 'timeout'];
            }

            $chunk = fread($handle, 8192);
            if ($chunk === false) {
                fclose($handle);
                return ['ok' => false, 'content' => '', 'error' => 'unreadable'];
            }

            $bytesRead += strlen($chunk);
            if ($bytesRead > $maxBytes) {
                fclose($handle);
                return ['ok' => false, 'content' => '', 'error' => 'too_large'];
            }

            $content .= $chunk;
        }

        fclose($handle);
        return ['ok' => true, 'content' => $content, 'error' => ''];
    }
}

if (!function_exists('rmt_docs_extract_markdown_heading')) {
    function rmt_docs_extract_markdown_heading(string $markdown): string {
        $lines = preg_split('/\R/', $markdown) ?: [];
        $lineCount = count($lines);

        for ($i = 0; $i < $lineCount; $i++) {
            $line = trim($lines[$i]);
            if ($line === '') {
                continue;
            }

            if (preg_match('/^#{1,6}\s+(.+)$/', $line, $matches) === 1) {
                $title = trim($matches[1]);
                $title = preg_replace('/\s+#+$/', '', $title);
                return trim((string) $title);
            }

            if ($i + 1 < $lineCount) {
                $nextLine = trim($lines[$i + 1]);
                if (preg_match('/^=+$/', $nextLine) === 1 || preg_match('/^-+$/', $nextLine) === 1) {
                    return $line;
                }
            }
        }

        return '';
    }
}

if (!function_exists('rmt_docs_strip_first_heading')) {
    function rmt_docs_strip_first_heading(string $markdown): string {
        $trimmed = ltrim($markdown);

        // ATX heading: # Heading
        if (preg_match('/^#{1,6}\s+.+(?:\R|$)/', $trimmed, $matches) === 1) {
            $offset = strlen($matches[0]);
            return ltrim(substr($trimmed, $offset));
        }

        // Setext heading: Heading + ===/---
        if (preg_match('/^.+\R(?:=+|-+)\s*(?:\R|$)/', $trimmed, $matches) === 1) {
            $offset = strlen($matches[0]);
            return ltrim(substr($trimmed, $offset));
        }

        return $markdown;
    }
}

if (!function_exists('rmt_docs_format_group_title')) {
    function rmt_docs_format_group_title(string $groupKey): string {
        $normalized = trim($groupKey);
        if ($normalized === '') {
            return '';
        }

        if (strtolower($normalized) === 'adr') {
            return 'Architecture Decision Records (ADR)';
        }

        if (strpos($normalized, '-') === false && strpos($normalized, '_') === false && strlen($normalized) <= 4) {
            return strtoupper($normalized);
        }

        $label = str_replace(['-', '_'], ' ', $normalized);
        return ucwords($label);
    }
}

if (!function_exists('rmt_docs_group_by_top_level')) {
    function rmt_docs_group_by_top_level(array $markdownDocuments): array {
        $grouped = [];

        foreach ($markdownDocuments as $doc) {
            $directory = dirname((string) ($doc['relative_path'] ?? ''));
            $groupKey = $directory === '.' ? '__root__' : explode('/', $directory)[0];

            if (!isset($grouped[$groupKey])) {
                $grouped[$groupKey] = [
                    'title' => $groupKey === '__root__' ? '' : rmt_docs_format_group_title($groupKey),
                    'documents' => [],
                ];
            }

            $grouped[$groupKey]['documents'][] = $doc;
        }

        if (isset($grouped['__root__'])) {
            $rootGroup = ['__root__' => $grouped['__root__']];
            unset($grouped['__root__']);
            ksort($grouped, SORT_NATURAL | SORT_FLAG_CASE);
            return $rootGroup + $grouped;
        }

        ksort($grouped, SORT_NATURAL | SORT_FLAG_CASE);
        return $grouped;
    }
}

if (!function_exists('rmt_docs_memory_cache')) {
    function &rmt_docs_memory_cache(): array {
        static $cache = [];
        return $cache;
    }
}

if (!function_exists('rmt_docs_cache_fetch')) {
    function rmt_docs_cache_fetch(string $key): ?string {
        $memoryCache =& rmt_docs_memory_cache();

        if (array_key_exists($key, $memoryCache)) {
            return $memoryCache[$key];
        }

        if (function_exists('apcu_fetch')) {
            $success = false;
            $value = apcu_fetch($key, $success);
            if ($success && is_string($value)) {
                $memoryCache[$key] = $value;
                return $value;
            }
        }

        return null;
    }
}

if (!function_exists('rmt_docs_cache_store')) {
    function rmt_docs_cache_store(string $key, string $value, int $ttlSeconds = 300): void {
        $memoryCache =& rmt_docs_memory_cache();

        $memoryCache[$key] = $value;

        if (function_exists('apcu_store')) {
            apcu_store($key, $value, $ttlSeconds);
        }
    }
}

if (!function_exists('rmt_docs_build_cache_key')) {
    function rmt_docs_build_cache_key(string $prefix, string $relativePath, int $mtime): string {
        return 'rmt_docs_' . $prefix . '_' . sha1($relativePath . '|' . (string) $mtime);
    }
}
