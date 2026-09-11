<?php
/**
 * CLI Tool: Sync Notification Templates from Markdown to Database / Migration SQL
 *
 * Usage:
 *   php scripts/sync-notification-templates.php --validate
 *   php scripts/sync-notification-templates.php --diff
 *   php scripts/sync-notification-templates.php --generate-sql[=path/to/migration.sql]
 *   php scripts/sync-notification-templates.php --apply
 *
 * Options:
 *   --file=<path>          Path to markdown file (default: docs/notification-templates.md)
 *   --validate             Parse and validate markdown templates and placeholders
 *   --diff                 Show differences between Markdown templates and current DB defaults
 *   --generate-sql[=<file>] Output SQL migration query (to file or stdout)
 *   --apply                Apply parsed app-wide default templates directly to database
 *   --help, -h             Show this help message
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit("This script can only be run from the command line.\n");
}

// Environment bootstrap
$envPath = file_exists('/var/www/html/env.php')
    ? '/var/www/html/env.php'
    : dirname(__DIR__) . '/app/env.php';

require_once $envPath;

// Allowed placeholder tokens from app/includes/notification-templates.php
const ALLOWED_PLACEHOLDERS = [
    'requestid',
    'requesttitle',
    'assignee',
    'assigned_by',
    'teamname',
    'teamemail',
    'catalogue_name',
    'service_name',
    'status_label',
    'client_fname',
    'client_lname',
    'url',
    'survey_link_en',
    'survey_link_fr',
];

// Expected 12 template combinations (Audience -> Event -> Languages)
const EXPECTED_TEMPLATES = [
    'client' => [
        'request_created' => ['en', 'fr'],
        'resolved' => ['en', 'fr'],
    ],
    'employee' => [
        'request_created' => ['en', 'fr'],
        'reassigned' => ['en', 'fr'],
        'resolved' => ['en', 'fr'],
        'status_changed' => ['en', 'fr'],
    ],
];

// Heading event name mappings
const EVENT_MAP = [
    'request created' => 'request_created',
    'resolved / closed' => 'resolved',
    'assigned' => 'reassigned',
    'reassigned' => 'reassigned',
    'status / details updated' => 'status_changed',
];

/**
 * Parse CLI arguments
 */
function parse_cli_options(array $argv): array {
    $defaultFile = file_exists('/var/www/docs/notification-templates.md')
        ? '/var/www/docs/notification-templates.md'
        : dirname(__DIR__) . '/docs/notification-templates.md';

    $options = [
        'file' => $defaultFile,
        'validate' => false,
        'diff' => false,
        'generate_sql' => false,
        'sql_file' => null,
        'apply' => false,
        'help' => false,
    ];

    foreach (array_slice($argv, 1) as $arg) {
        if ($arg === '-h' || $arg === '--help') {
            $options['help'] = true;
        } elseif ($arg === '--validate') {
            $options['validate'] = true;
        } elseif ($arg === '--diff') {
            $options['diff'] = true;
        } elseif ($arg === '--apply') {
            $options['apply'] = true;
        } elseif (strpos($arg, '--file=') === 0) {
            $options['file'] = substr($arg, 7);
        } elseif (strpos($arg, '--generate-sql') === 0) {
            $options['generate_sql'] = true;
            if (strpos($arg, '=') !== false) {
                $options['sql_file'] = substr($arg, strpos($arg, '=') + 1);
            }
        }
    }

    // Default action if none specified is validate
    if (!$options['validate'] && !$options['diff'] && !$options['generate_sql'] && !$options['apply'] && !$options['help']) {
        $options['validate'] = true;
    }

    return $options;
}

/**
 * Print usage help
 */
function show_help(): void {
    echo <<<HELP
Sync Notification Templates Utility

Usage:
  php scripts/sync-notification-templates.php [options]

Options:
  --file=<path>          Path to markdown file (default: docs/notification-templates.md)
  --validate             Parse and validate markdown templates and placeholders (default action)
  --diff                 Compare Markdown templates with database defaults
  --generate-sql[=<path>] Output SQL migration query (to specified file or stdout)
  --apply                Apply parsed app-wide default templates directly to database
  --help, -h             Show this help message

HELP;
}

/**
 * Clean backticks wrapping placeholders in markdown text
 * Example: `{{requestid}}` -> {{requestid}}
 */
function clean_placeholders(string $text): string {
    // Strip backticks wrapping single or consecutive {{token}} placeholders
    return preg_replace('/`(\{\{[^`\n]+?\}\})`/', '$1', $text);
}

/**
 * Extract placeholders from text
 */
function extract_placeholders(string $text): array {
    preg_match_all('/\{\{\s*([a-z_]+)\s*\}\}/i', $text, $matches);
    return array_unique(array_map('strtolower', $matches[1] ?? []));
}

/**
 * Parse docs/notification-templates.md into structured template objects
 */
function parse_markdown_templates(string $filePath): array {
    if (!file_exists($filePath)) {
        throw new RuntimeException("Markdown file not found: {$filePath}");
    }

    $content = file_get_contents($filePath);
    $lines = explode("\n", $content);

    $parsed = [];
    $currentAudience = null;
    $currentEvent = null;
    $currentLang = null;
    $currentSection = null; // 'subject' or 'message'
    $buffer = [];

    $flush_buffer = function () use (&$parsed, &$currentAudience, &$currentEvent, &$currentLang, &$currentSection, &$buffer) {
        if ($currentAudience && $currentEvent && $currentLang && $currentSection) {
            $rawText = trim(implode("\n", $buffer));
            $cleanText = clean_placeholders($rawText);

            if (!isset($parsed[$currentAudience])) {
                $parsed[$currentAudience] = [];
            }
            if (!isset($parsed[$currentAudience][$currentEvent])) {
                $parsed[$currentAudience][$currentEvent] = [];
            }
            if (!isset($parsed[$currentAudience][$currentEvent][$currentLang])) {
                $parsed[$currentAudience][$currentEvent][$currentLang] = [
                    'subject' => '',
                    'body' => '',
                ];
            }

            if ($currentSection === 'subject') {
                $parsed[$currentAudience][$currentEvent][$currentLang]['subject'] = $cleanText;
            } elseif ($currentSection === 'message') {
                $parsed[$currentAudience][$currentEvent][$currentLang]['body'] = $cleanText;
            }
        }
        $buffer = [];
    };

    foreach ($lines as $line) {
        $trimmed = trim($line);

        // Top level audience sections (e.g. ## Client Messages, ## Employee Messages)
        if (preg_match('/^##\s+(Client|Employee)\s+Messages/i', $trimmed, $m)) {
            $flush_buffer();
            $currentAudience = strtolower($m[1]);
            $currentEvent = null;
            $currentLang = null;
            $currentSection = null;
            continue;
        }

        // Ignore Special Internal Routing Messages or Available Placeholders
        if (preg_match('/^##\s+(Special Internal Routing|Available Placeholders)/i', $trimmed)) {
            $flush_buffer();
            $currentAudience = null;
            $currentEvent = null;
            $currentLang = null;
            $currentSection = null;
            continue;
        }

        if (!$currentAudience) {
            continue;
        }

        // Event section (e.g. ### Request Created)
        if (preg_match('/^###\s+(.+)$/', $trimmed, $m)) {
            $flush_buffer();
            $eventName = strtolower(trim($m[1]));
            $currentEvent = EVENT_MAP[$eventName] ?? null;
            $currentLang = null;
            $currentSection = null;
            continue;
        }

        if (!$currentEvent) {
            continue;
        }

        // Language subsection (e.g. #### English, #### French)
        if (preg_match('/^####\s+(English|French)/i', $trimmed, $m)) {
            $flush_buffer();
            $currentLang = (strtolower($m[1]) === 'english') ? 'en' : 'fr';
            $currentSection = null;
            continue;
        }

        if (!$currentLang) {
            continue;
        }

        // Field subsection (e.g. ##### Subject, ##### Objet, ##### Message)
        if (preg_match('/^#####\s+(Subject|Objet|Message)/i', $trimmed, $m)) {
            $flush_buffer();
            $fieldName = strtolower($m[1]);
            $currentSection = ($fieldName === 'message') ? 'message' : 'subject';
            continue;
        }

        if ($currentSection) {
            $buffer[] = $line;
        }
    }

    $flush_buffer();

    return $parsed;
}

/**
 * Validate parsed templates against expected schema and placeholders
 */
function validate_templates(array $templates): array {
    $errors = [];
    $warnings = [];
    $count = 0;

    foreach (EXPECTED_TEMPLATES as $audience => $events) {
        foreach ($events as $event => $langs) {
            foreach ($langs as $lang) {
                $count++;
                if (!isset($templates[$audience][$event][$lang])) {
                    $errors[] = "Missing template for audience: '{$audience}', event: '{$event}', language: '{$lang}'";
                    continue;
                }

                $tpl = $templates[$audience][$event][$lang];

                if (empty($tpl['subject'])) {
                    $errors[] = "Empty subject for audience: '{$audience}', event: '{$event}', language: '{$lang}'";
                }

                if (empty($tpl['body'])) {
                    $errors[] = "Empty body for audience: '{$audience}', event: '{$event}', language: '{$lang}'";
                }

                // Check placeholders in subject and body
                $placeholders = array_merge(
                    extract_placeholders($tpl['subject']),
                    extract_placeholders($tpl['body'])
                );

                foreach ($placeholders as $ph) {
                    if (!in_array($ph, ALLOWED_PLACEHOLDERS, true)) {
                        $warnings[] = "Unknown placeholder '{{{$ph}}}' in audience: '{$audience}', event: '{$event}', language: '{$lang}'";
                    }
                }
            }
        }
    }

    return [
        'valid' => empty($errors),
        'total' => $count,
        'errors' => $errors,
        'warnings' => $warnings,
    ];
}

/**
 * Generate SQL migration content
 */
function generate_sql(array $templates): string {
    $sql = "-- Auto-generated app-wide default notification templates\n";
    $sql .= "-- Generated from docs/notification-templates.md on " . date('Y-m-d H:i:s') . "\n\n";
    $sql .= "SET NAMES utf8mb4;\n";
    $sql .= "SET CHARACTER SET utf8mb4;\n\n";

    $values = [];
    foreach (EXPECTED_TEMPLATES as $audience => $events) {
        foreach ($events as $event => $langs) {
            foreach ($langs as $lang) {
                if (!isset($templates[$audience][$event][$lang])) {
                    continue;
                }

                $tpl = $templates[$audience][$event][$lang];
                $escapedSubject = addcslashes($tpl['subject'], "'\\");
                $escapedBody = addcslashes($tpl['body'], "'\\");

                // Convert actual newlines to \n in SQL string literal
                $escapedBody = str_replace(["\r\n", "\r", "\n"], '\n', $escapedBody);

                $values[] = "(0, 0, 0, '{$audience}', '{$event}', '{$lang}',\n '{$escapedSubject}',\n '{$escapedBody}',\n 1)";
            }
        }
    }

    $sql .= "INSERT INTO `tblnotificationtemplates`\n";
    $sql .= "  (`team_id`, `service_id`, `subservice_id`, `audience`, `event`, `language`, `subject`, `body`, `status`)\n";
    $sql .= "VALUES\n";
    $sql .= implode(",\n\n", $values) . "\n";
    $sql .= "ON DUPLICATE KEY UPDATE\n";
    $sql .= "  `subject` = VALUES(`subject`),\n";
    $sql .= "  `body` = VALUES(`body`),\n";
    $sql .= "  `status` = 1;\n";

    return $sql;
}

/**
 * Main execution
 */
function main(array $argv): int {
    $options = parse_cli_options($argv);

    if ($options['help']) {
        show_help();
        return 0;
    }

    echo "Reading templates from: " . $options['file'] . "\n";

    try {
        $templates = parse_markdown_templates($options['file']);
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
        return 1;
    }

    $validation = validate_templates($templates);

    if (!empty($validation['warnings'])) {
        foreach ($validation['warnings'] as $warning) {
            echo " [WARNING] {$warning}\n";
        }
    }

    if (!$validation['valid']) {
        echo "\nValidation FAILED with " . count($validation['errors']) . " error(s):\n";
        foreach ($validation['errors'] as $error) {
            echo " [ERROR] {$error}\n";
        }
        return 1;
    }

    echo "Successfully parsed and validated {$validation['total']} templates.\n";

    if ($options['validate'] && !$options['diff'] && !$options['generate_sql'] && !$options['apply']) {
        echo "Validation PASSED.\n";
        return 0;
    }

    if ($options['generate_sql']) {
        $sql = generate_sql($templates);

        if ($options['sql_file']) {
            file_put_contents($options['sql_file'], $sql);
            echo "SQL script written to: " . $options['sql_file'] . "\n";
        } else {
            echo "\n--- Generated SQL ---\n";
            echo $sql;
            echo "---------------------\n";
        }
    }

    if ($options['diff'] || $options['apply']) {
        $dbPath = file_exists('/var/www/html/db.php')
            ? '/var/www/html/db.php'
            : dirname(__DIR__) . '/app/db.php';
        $helpersPath = file_exists('/var/www/html/includes/helpers.php')
            ? '/var/www/html/includes/helpers.php'
            : dirname(__DIR__) . '/app/includes/helpers.php';

        require_once $dbPath;
        require_once $helpersPath;

        /** @var mysqli $link */
        $link = $GLOBALS['link'] ?? $link ?? null;

        if (!($link instanceof mysqli)) {
            echo "Error: Database connection not available.\n";
            return 1;
        }

        if ($options['diff']) {
            echo "\nComparing Markdown templates with database defaults (team_id=0, service_id=0, subservice_id=0):\n";
            $diffCount = 0;

            foreach (EXPECTED_TEMPLATES as $audience => $events) {
                foreach ($events as $event => $langs) {
                    foreach ($langs as $lang) {
                        $parsedTpl = $templates[$audience][$event][$lang] ?? ['subject' => '', 'body' => ''];
                        $dbRow = rmt_db_fetch_one(
                            $link,
                            'SELECT subject, body FROM tblnotificationtemplates WHERE team_id=0 AND service_id=0 AND subservice_id=0 AND audience=? AND event=? AND language=? LIMIT 1',
                            'sss',
                            [$audience, $event, $lang]
                        );

                        $dbSubject = $dbRow['subject'] ?? '';
                        $dbBody = $dbRow['body'] ?? '';

                        $subjectDiff = ($parsedTpl['subject'] !== $dbSubject);
                        $bodyDiff = ($parsedTpl['body'] !== $dbBody);

                        if ($subjectDiff || $bodyDiff) {
                            $diffCount++;
                            echo "\nDifference found in [{$audience} / {$event} / {$lang}]:\n";
                            if ($subjectDiff) {
                                echo "  Subject (DB):       {$dbSubject}\n";
                                echo "  Subject (Markdown): {$parsedTpl['subject']}\n";
                            }
                            if ($bodyDiff) {
                                echo "  Body (DB):\n" . indent_text($dbBody, "    ") . "\n";
                                echo "  Body (Markdown):\n" . indent_text($parsedTpl['body'], "    ") . "\n";
                            }
                        }
                    }
                }
            }

            if ($diffCount === 0) {
                echo "No differences found. Database defaults match Markdown file.\n";
            } else {
                echo "\nTotal template differences: {$diffCount}\n";
            }
        }

        if ($options['apply']) {
            echo "\nApplying app-wide default templates to database...\n";
            $sql = generate_sql($templates);

            if (mysqli_multi_query($link, $sql)) {
                do {
                    if ($result = mysqli_store_result($link)) {
                        mysqli_free_result($result);
                    }
                } while (mysqli_more_results($link) && mysqli_next_result($link));

                echo "Database updated successfully.\n";
            } else {
                echo "Error executing database query: " . mysqli_error($link) . "\n";
                return 1;
            }
        }
    }

    return 0;
}

function indent_text(string $text, string $indent): string {
    return $indent . str_replace("\n", "\n" . $indent, $text);
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    exit(main($argv));
}
