<?php
require_once dirname(__DIR__) . '/env.php';
require_once __DIR__ . '/notification-templates.php';

if (isset($_SERVER['SCRIPT_FILENAME']) && realpath(__FILE__) === realpath((string) $_SERVER['SCRIPT_FILENAME'])) {
    http_response_code(404);
    exit();
}

/**
 * Helper Functions for RMT Application
 * Common utilities to reduce code duplication
 */

// ============================================================================
// PERMISSION HELPERS
// ============================================================================

function isRoleTestMode() {
    // Superadmin test mode is active when they switch to any non-superadmin atype.
    return (isset($_SESSION['is_superuser']) && (int)$_SESSION['is_superuser'] === 1)
        && isset($_SESSION['atype'])
        && (int)$_SESSION['atype'] !== 1;
}

function isSuperAdmin() {
    // Returns true if user is a superuser (unless in test mode)
    $inTestMode = isRoleTestMode();

    if ($inTestMode) {
        return false;
    }

    $isSuperuserFlag = isset($_SESSION['is_superuser']) && $_SESSION['is_superuser'] == 1;
    $isAtypeSuperadmin = isset($_SESSION['atype']) && (int) $_SESSION['atype'] === 1;
    return $isSuperuserFlag || $isAtypeSuperadmin;
}

// Alias for backward compatibility - DO NOT USE, prefer isSuperAdmin()
function isAdmin() {
    return isSuperAdmin();
}

function canEditRequests() {
    // If in test mode, only use tested atype permissions, not superuser flags
    $inTestMode = isRoleTestMode();

    if ($inTestMode) {
        return isset($_SESSION['atype']) && in_array((int) $_SESSION['atype'], [1, 3, 4, 5], true);
    }

    $isAdminOrSuperuser = (isset($_SESSION['is_superuser']) && $_SESSION['is_superuser']) || 
                         (isset($_SESSION['is_admin']) && $_SESSION['is_admin']) ||
                         (isset($_SESSION['atype']) && (int) $_SESSION['atype'] === 1);

    return $isAdminOrSuperuser || (isset($_SESSION['atype']) && in_array($_SESSION['atype'], [3, 4, 5]));
}

function canDeleteRequests() {
    // If in test mode, only use tested atype permissions, not superuser flags
    $inTestMode = isRoleTestMode();

    if ($inTestMode) {
        return isset($_SESSION['atype']) && (int) $_SESSION['atype'] === 1;
    }

        return (isset($_SESSION['is_superuser']) && $_SESSION['is_superuser']) ||
            (isset($_SESSION['is_admin']) && $_SESSION['is_admin']) ||
            (isset($_SESSION['atype']) && (int) $_SESSION['atype'] === 1);
}

function canManageSLA() {
    // If in test mode, only use tested atype permissions, not superuser flags
    $inTestMode = isRoleTestMode();

    if ($inTestMode) {
        return isset($_SESSION['atype']) && (int)$_SESSION['atype'] === 3;
    }

    $isAdminOrSuperuser = (isset($_SESSION['is_superuser']) && $_SESSION['is_superuser']) || 
                         (isset($_SESSION['is_admin']) && $_SESSION['is_admin']);
    return $isAdminOrSuperuser || (isset($_SESSION['atype']) && (int)$_SESSION['atype'] === 3);
}

function getEffectiveTeamIds($link): array {
    // In role-test mode, allow an explicit test team override for team-scoped roles.
    if (isRoleTestMode() && !empty($_SESSION['test_team_ids'])) {
        return array_values(array_filter(array_map('trim', explode(',', (string)$_SESSION['test_team_ids']))));
    }

    $userId = (int)($_SESSION['pid'] ?? 0);
    if ($userId <= 0) {
        return [];
    }

    $teamResult = mysqli_query($link, "SELECT team FROM tblusers WHERE id = '$userId' LIMIT 1");
    $teamRow = $teamResult ? mysqli_fetch_assoc($teamResult) : null;

    return array_values(array_filter(array_map('trim', explode(',', (string)($teamRow['team'] ?? '')))));
}

function getEffectiveEmployeeUserId($link): int {
    // In employee role-test mode, allow explicit employee override for assignment-scoped views.
    if (isRoleTestMode() && isset($_SESSION['atype']) && (int)$_SESSION['atype'] === 5 && !empty($_SESSION['test_employee_id'])) {
        return (int)$_SESSION['test_employee_id'];
    }

    return (int)($_SESSION['pid'] ?? 0);
}

function isReadOnly() {
    // If superuser is in test mode (atype != primary_atype), apply readonly based on test atype
    // Otherwise, superusers are never read-only
    $inTestMode = isRoleTestMode();
    
    if (!$inTestMode && isset($_SESSION['is_superuser']) && $_SESSION['is_superuser'] == 1) {
        return false; // Superusers are never read-only (unless testing)
    }
    
    return isset($_SESSION['atype']) && $_SESSION['atype'] == 6;
}

function canViewAllRequests() {
    $isAdminOrSuperuser = (isset($_SESSION['is_superuser']) && $_SESSION['is_superuser']) || 
                         (isset($_SESSION['is_admin']) && $_SESSION['is_admin']);
    // If in test mode, use tested atype permissions only
    $inTestMode = isRoleTestMode();

    if ($inTestMode) {
        return isset($_SESSION['atype']) && $_SESSION['atype'] == 6;
    }

    return $isAdminOrSuperuser || (isset($_SESSION['atype']) && $_SESSION['atype'] == 6);
}

function canViewReports() {
    return isset($_SESSION['pid']); // Everyone can view reports
}

// ============================================================================
// VALUE HELPERS
// ============================================================================

function hasValue($value) {
    return !empty($value) && $value != 0 && $value !== "" && !is_null($value);
}

function rmt_file_upload_policy(): array {
    $storageMode = app_file_storage_mode();
    $enabled = ($storageMode !== 'disabled');

    $maxFiles = (int) app_env('FILE_UPLOAD_MAX_FILES', '5');
    if ($maxFiles <= 0) {
        $maxFiles = 5;
    }

    $maxSizeMb = (int) app_env('FILE_UPLOAD_MAX_SIZE_MB', '10');
    if ($maxSizeMb <= 0) {
        $maxSizeMb = 10;
    }

    return [
        'enabled' => $enabled,
        'max_files' => $maxFiles,
        'max_file_size_mb' => $maxSizeMb,
        'max_file_size_bytes' => $maxSizeMb * 1024 * 1024,
        'allowed_extensions' => [
            'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt',
            'png', 'jpg', 'jpeg', 'webp', 'gif'
        ],
    ];
}

function rmt_file_upload_accept_attribute(): string {
    $policy = rmt_file_upload_policy();
    $accepted = [];
    foreach ($policy['allowed_extensions'] as $extension) {
        $accepted[] = '.' . $extension;
    }

    return implode(',', $accepted);
}

function rmt_file_upload_hint(string $lang = 'en'): string {
    $policy = rmt_file_upload_policy();
    $extensions = strtoupper(implode(', ', $policy['allowed_extensions']));

    if (app_normalize_language($lang) === 'fr') {
        return sprintf(
            'Jusqu\'a %d fichiers, %d Mo maximum par fichier. Types autorises : %s.',
            (int) $policy['max_files'],
            (int) $policy['max_file_size_mb'],
            $extensions
        );
    }

    return sprintf(
        'Up to %d files, %d MB max per file. Allowed types: %s.',
        (int) $policy['max_files'],
        (int) $policy['max_file_size_mb'],
        $extensions
    );
}

function rmt_validate_uploaded_files(array $fileUpload, string $lang = 'en'): array {
    $policy = rmt_file_upload_policy();
    $language = app_normalize_language($lang);

    if (!$policy['enabled']) {
        return [
            'files' => [],
            'errors' => [
                $language === 'fr'
                    ? "Le t\u00e9l\u00e9versement de fichiers n'est pas disponible pour le moment."
                    : 'File uploads are not available at this time.',
            ],
        ];
    }

    $names = $fileUpload['name'] ?? [];
    $tmpNames = $fileUpload['tmp_name'] ?? [];
    $sizes = $fileUpload['size'] ?? [];
    $errors = $fileUpload['error'] ?? [];

    if (!is_array($names) || !is_array($tmpNames) || !is_array($sizes) || !is_array($errors)) {
        return [
            'files' => [],
            'errors' => [
                $language === 'fr'
                    ? 'Le format des fichiers televerses est invalide.'
                    : 'Uploaded file payload is invalid.'
            ],
        ];
    }

    $submittedCount = 0;
    foreach ($names as $name) {
        if (trim((string) $name) !== '') {
            $submittedCount++;
        }
    }

    if ($submittedCount > (int) $policy['max_files']) {
        return [
            'files' => [],
            'errors' => [
                $language === 'fr'
                    ? sprintf('Vous pouvez televerser au maximum %d fichiers.', (int) $policy['max_files'])
                    : sprintf('You can upload a maximum of %d files.', (int) $policy['max_files'])
            ],
        ];
    }

    $validFiles = [];
    $errorMessages = [];

    foreach ($names as $index => $originalName) {
        $originalName = trim((string) $originalName);
        if ($originalName === '') {
            continue;
        }

        $tmpName = (string) ($tmpNames[$index] ?? '');
        $size = (int) ($sizes[$index] ?? 0);
        $errorCode = (int) ($errors[$index] ?? UPLOAD_ERR_NO_FILE);

        if ($errorCode !== UPLOAD_ERR_OK) {
            $errorMessages[] = $language === 'fr'
                ? sprintf('Le televersement de "%s" a echoue.', $originalName)
                : sprintf('Upload failed for "%s".', $originalName);
            continue;
        }

        $extension = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));
        if ($extension === '' || !in_array($extension, $policy['allowed_extensions'], true)) {
            $errorMessages[] = $language === 'fr'
                ? sprintf('Type de fichier non autorise pour "%s".', $originalName)
                : sprintf('File type is not allowed for "%s".', $originalName);
            continue;
        }

        if ($size <= 0 || $size > (int) $policy['max_file_size_bytes']) {
            $errorMessages[] = $language === 'fr'
                ? sprintf('Le fichier "%s" depasse la limite de %d Mo.', $originalName, (int) $policy['max_file_size_mb'])
                : sprintf('File "%s" exceeds the %d MB limit.', $originalName, (int) $policy['max_file_size_mb']);
            continue;
        }

        $validFiles[] = [
            'name' => $originalName,
            'tmp_name' => $tmpName,
            'size_bytes' => $size,
            'size_kb' => round($size / 1024, 2),
            'extension' => $extension,
        ];
    }

    return [
        'files' => $validFiles,
        'errors' => $errorMessages,
    ];
}

function rmt_allow_file_download_code(string $fileCode): void {
    $fileCode = trim($fileCode);
    if ($fileCode === '') {
        return;
    }

    if (!isset($_SESSION['allowed_file_codes']) || !is_array($_SESSION['allowed_file_codes'])) {
        $_SESSION['allowed_file_codes'] = [];
    }

    $now = time();
    $ttl = 2 * 60 * 60;

    foreach ($_SESSION['allowed_file_codes'] as $code => $timestamp) {
        if (($now - (int) $timestamp) > $ttl) {
            unset($_SESSION['allowed_file_codes'][$code]);
        }
    }

    $_SESSION['allowed_file_codes'][$fileCode] = $now;
}

function rmt_is_file_download_code_allowed(string $fileCode): bool {
    if (!isset($_SESSION['allowed_file_codes']) || !is_array($_SESSION['allowed_file_codes'])) {
        return false;
    }

    $timestamp = (int) ($_SESSION['allowed_file_codes'][$fileCode] ?? 0);
    if ($timestamp <= 0) {
        return false;
    }

    $ttl = 2 * 60 * 60;
    if ((time() - $timestamp) > $ttl) {
        unset($_SESSION['allowed_file_codes'][$fileCode]);
        return false;
    }

    return true;
}

function getPostValue($key, $default = "") {
    return !empty($_POST[$key]) ? mysqli_real_escape_string($GLOBALS['link'], $_POST[$key]) : $default;
}

function getGetValue($key, $default = "") {
    return !empty($_GET[$key]) ? mysqli_real_escape_string($GLOBALS['link'], $_GET[$key]) : $default;
}

// ============================================================================
// DATABASE HELPERS
// ============================================================================

function rmt_db_execute(mysqli $link, string $sql, string $types = '', array $params = []): mysqli_stmt {
    if (strlen($types) !== count($params)) {
        throw new InvalidArgumentException('Prepared statement parameter types must match parameter count.');
    }

    $statement = mysqli_prepare($link, $sql);
    if ($types !== '') {
        mysqli_stmt_bind_param($statement, $types, ...$params);
    }
    mysqli_stmt_execute($statement);

    return $statement;
}

function rmt_db_fetch_one(mysqli $link, string $sql, string $types = '', array $params = []): ?array {
    $statement = rmt_db_execute($link, $sql, $types, $params);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($statement));
    mysqli_stmt_close($statement);

    return $row ?: null;
}

function rmt_normalize_request_subject_type(?string $type, bool $allowNull = false): ?string {
    $type = trim((string) $type);
    if ($allowNull && $type === '') {
        return null;
    }

    return in_array($type, ['system', 'document', 'subject'], true) ? $type : 'subject';
}

function rmt_resolve_request_subject_type(
    mysqli $link,
    int $catalogueId,
    int $serviceId = 0,
    int $subserviceId = 0
): string {
    $row = rmt_db_fetch_one(
        $link,
        'SELECT COALESCE(ss.request_subject_type, s.request_subject_type, c.request_subject_type, \'subject\') AS resolved_type
         FROM tblcatalogue c
         LEFT JOIN tblservices s ON s.id = ? AND s.catalogueid = c.id
         LEFT JOIN tblsubservices ss ON ss.id = ? AND ss.serviceid = s.id
         WHERE c.id = ?',
        'iii',
        [$serviceId, $subserviceId, $catalogueId]
    );

    return rmt_normalize_request_subject_type($row['resolved_type'] ?? 'subject') ?? 'subject';
}

function rmt_resolve_responsible_team_id(
    mysqli $link,
    int $catalogueId,
    int $serviceId = 0,
    int $subserviceId = 0
): int {
    $row = rmt_db_fetch_one(
        $link,
        'SELECT COALESCE(NULLIF(ss.contactid, 0), NULLIF(s.contactid, 0), NULLIF(c.contactid, 0), 0) AS team_id
         FROM tblcatalogue c
         LEFT JOIN tblservices s ON s.id = ? AND s.catalogueid = c.id
         LEFT JOIN tblsubservices ss ON ss.id = ? AND ss.serviceid = s.id
         WHERE c.id = ?',
        'iii',
        [$serviceId, $subserviceId, $catalogueId]
    );

    return max(0, (int) ($row['team_id'] ?? 0));
}

function rmt_get_active_teams(mysqli $link): array {
    $result = rmt_admin_query($link, 'SELECT id, nameen, namefr FROM tblteams WHERE status = 1 ORDER BY nameen, namefr');
    $teams = [];

    while ($team = rmt_result_fetch_array($result)) {
        $teams[] = $team;
    }

    return $teams;
}

function rmt_request_subject_text(string $type, string $lang): array {
    $type = rmt_normalize_request_subject_type($type) ?? 'subject';
    $translations = [
        'en' => [
            'system' => ['heading' => 'System information', 'label' => 'System name', 'help' => 'What is the full name of the system?'],
            'document' => ['heading' => 'Document information', 'label' => 'Document title', 'help' => 'What is the title of the document?'],
            'subject' => ['heading' => 'Request information', 'label' => 'Subject', 'help' => 'What is the subject of your request?'],
        ],
        'fr' => [
            'system' => ['heading' => 'Renseignements sur le système', 'label' => 'Nom du système', 'help' => 'Quel est le nom complet du système?'],
            'document' => ['heading' => 'Renseignements sur le document', 'label' => 'Titre du document', 'help' => 'Quel est le titre du document?'],
            'subject' => ['heading' => 'Renseignements sur la demande', 'label' => 'Objet', 'help' => 'Quel est l’objet de votre demande?'],
        ],
    ];

    return $translations[$lang === 'fr' ? 'fr' : 'en'][$type];
}

function rmt_generate_request_title(string $requestId, string $organization, string $requestSubject): string {
    $title = trim($requestId) . ' - ' . trim($organization) . ' - ' . trim($requestSubject);
    return function_exists('mb_substr') ? mb_substr($title, 0, 500, 'UTF-8') : substr($title, 0, 500);
}

function getDropdownOptions($link, $table, $lang = 'en', $where = "status='1'", $orderBy = null) {
    $nameField = $lang === 'fr' ? 'namefr' : 'nameen';
    $orderField = $orderBy ?? $nameField;
    
    $query = "SELECT id, $nameField as name FROM $table WHERE $where ORDER BY $orderField ASC";
    return mysqli_query($link, $query);
}

function getServicesByCategory($link, $catalogueid, $lang = 'en') {
    $nameField = $lang === 'fr' ? 'namefr' : 'nameen';
    $catalogueid = mysqli_real_escape_string($link, $catalogueid);
    
    return mysqli_query($link, 
        "SELECT id, $nameField as name FROM tblservices 
         WHERE catalogueid='$catalogueid' AND status='1' 
         ORDER BY $nameField ASC"
    );
}

function getSubservicesByService($link, $serviceid, $lang = 'en') {
    $nameField = $lang === 'fr' ? 'namefr' : 'nameen';
    $serviceid = mysqli_real_escape_string($link, $serviceid);
    
    return mysqli_query($link,
        "SELECT id, $nameField as name FROM tblsubservices 
         WHERE serviceid='$serviceid' AND status='1' 
         ORDER BY $nameField ASC"
    );
}

function rmt_service_has_active_subservices(mysqli $link, int $serviceId): bool {
    if ($serviceId <= 0) {
        return false;
    }

    return (bool) rmt_db_fetch_one(
        $link,
        'SELECT 1 FROM tblsubservices WHERE serviceid = ? AND status = 1 LIMIT 1',
        'i',
        [$serviceId]
    );
}

function rmt_validate_intake_selection(mysqli $link, int $catalogueId, int $serviceId, int $subserviceId = 0): ?array {
    if ($catalogueId <= 0 || $serviceId < 0 || $subserviceId < 0) {
        return null;
    }

    if ($serviceId === 0) {
        $catalogueStmt = mysqli_prepare(
            $link,
            'SELECT c.id FROM tblcatalogue c
             WHERE c.id = ? AND c.status = 1
               AND NOT EXISTS (
                   SELECT 1 FROM tblservices s
                   WHERE s.catalogueid = c.id AND s.status = 1
               )'
        );
        mysqli_stmt_bind_param($catalogueStmt, 'i', $catalogueId);
        mysqli_stmt_execute($catalogueStmt);
        $validTerminalCatalogue = mysqli_stmt_get_result($catalogueStmt)->num_rows === 1;
        mysqli_stmt_close($catalogueStmt);

        if (!$validTerminalCatalogue || $subserviceId !== 0) {
            return null;
        }

        return [
            'catalogueid' => $catalogueId,
            'serviceid' => 0,
            'subserviceid' => 0
        ];
    }

    $serviceStmt = mysqli_prepare(
        $link,
        'SELECT s.id FROM tblservices s
         INNER JOIN tblcatalogue c ON c.id = s.catalogueid
         WHERE s.id = ? AND s.catalogueid = ? AND s.status = 1 AND c.status = 1'
    );
    mysqli_stmt_bind_param($serviceStmt, 'ii', $serviceId, $catalogueId);
    mysqli_stmt_execute($serviceStmt);
    $validService = mysqli_stmt_get_result($serviceStmt)->num_rows === 1;
    mysqli_stmt_close($serviceStmt);

    if (!$validService) {
        return null;
    }

    $countStmt = mysqli_prepare(
        $link,
        'SELECT COUNT(*) AS total FROM tblsubservices WHERE serviceid = ? AND status = 1'
    );
    mysqli_stmt_bind_param($countStmt, 'i', $serviceId);
    mysqli_stmt_execute($countStmt);
    $subserviceCount = (int) mysqli_fetch_assoc(mysqli_stmt_get_result($countStmt))['total'];
    mysqli_stmt_close($countStmt);

    if ($subserviceCount === 0) {
        return [
            'catalogueid' => $catalogueId,
            'serviceid' => $serviceId,
            'subserviceid' => 0
        ];
    }

    if ($subserviceId <= 0) {
        return null;
    }

    $subserviceStmt = mysqli_prepare(
        $link,
        'SELECT id FROM tblsubservices WHERE id = ? AND serviceid = ? AND status = 1'
    );
    mysqli_stmt_bind_param($subserviceStmt, 'ii', $subserviceId, $serviceId);
    mysqli_stmt_execute($subserviceStmt);
    $validSubservice = mysqli_stmt_get_result($subserviceStmt)->num_rows === 1;
    mysqli_stmt_close($subserviceStmt);

    if (!$validSubservice) {
        return null;
    }

    return [
        'catalogueid' => $catalogueId,
        'serviceid' => $serviceId,
        'subserviceid' => $subserviceId
    ];
}

function rmt_get_sla_days_required_for_request($link, int $serviceId = 0, int $subserviceId = 0): int {
    $subserviceId = (int) $subserviceId;
    $serviceId = (int) $serviceId;

    if ($subserviceId > 0) {
        $safeSubserviceId = mysqli_real_escape_string($link, (string) $subserviceId);
        $subserviceResult = mysqli_query($link, "SELECT sds FROM tblsubservices WHERE id = '$safeSubserviceId' LIMIT 1");
        $subserviceRow = $subserviceResult ? mysqli_fetch_assoc($subserviceResult) : null;
        $subserviceSla = (int) ($subserviceRow['sds'] ?? 0);
        if ($subserviceSla > 0) {
            return $subserviceSla;
        }

        return 0;
    }

    if ($serviceId > 0 && !rmt_service_has_active_subservices($link, $serviceId)) {
        // Preserve legacy special-case SLA handling.
        if (in_array($serviceId, [21, 22, 23, 24], true)) {
            return 15;
        }

        $safeServiceId = mysqli_real_escape_string($link, (string) $serviceId);
        $serviceResult = mysqli_query($link, "SELECT sds FROM tblservices WHERE id = '$safeServiceId' LIMIT 1");
        $serviceRow = $serviceResult ? mysqli_fetch_assoc($serviceResult) : null;
        return (int) ($serviceRow['sds'] ?? 0);
    }

    return 0;
}

function rmt_get_sla_clock_start_date($slaTimer, $dateReceived): string {
    $candidate = trim((string) $slaTimer);
    if ($candidate === '' || $candidate === '0000-00-00') {
        $candidate = trim((string) $dateReceived);
    }

    if ($candidate === '') {
        return '';
    }

    $timestamp = strtotime($candidate);
    if ($timestamp === false) {
        return '';
    }

    return date('Y-m-d', $timestamp);
}

function rmt_table_has_column($link, string $tableName, string $columnName): bool {
    $tableName = mysqli_real_escape_string($link, $tableName);
    $columnName = mysqli_real_escape_string($link, $columnName);

    $sql = "SELECT 1
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = '$tableName'
              AND COLUMN_NAME = '$columnName'
            LIMIT 1";
    $result = mysqli_query($link, $sql);

    return ($result && mysqli_num_rows($result) > 0);
}

function rmt_request_language_meta_note(string $language): string {
    return '__rmt_request_lang:' . app_normalize_language($language);
}

function rmt_save_request_language_metadata($link, int $triageId, string $language, int $creatorId = 0): void {
    if (!($link instanceof mysqli) || $triageId <= 0) {
        return;
    }

    $language = app_normalize_language($language);
    $note = rmt_request_language_meta_note($language);
    $existingRow = rmt_db_fetch_one(
        $link,
        'SELECT id FROM tbladminlog WHERE triageid = ? AND notes = ? LIMIT 1',
        'is',
        [$triageId, $note]
    );
    if ($existingRow !== null) {
        return;
    }

    // Store with status=0 to keep this internal metadata hidden from normal admin log views.
    $statement = rmt_db_execute(
        $link,
        'INSERT INTO tbladminlog (`triageid`, `dateadded`, `notes`, `creatorid`, `status`)
         VALUES (?, ?, ?, ?, 0)',
        'issi',
        [$triageId, date('Y-m-d'), $note, $creatorId]
    );
    mysqli_stmt_close($statement);
}

function rmt_get_request_language($link, int $triageId, ?string $fallbackLanguage = 'en'): string {
    $fallback = app_normalize_language($fallbackLanguage, 'en');
    if (!($link instanceof mysqli) || $triageId <= 0) {
        return $fallback;
    }

    if (function_exists('rmt_db_column_exists') && rmt_db_column_exists($link, 'tbltriage', 'requestlang')) {
        $triageIdEscaped = (int) $triageId;
        $result = mysqli_query($link, "SELECT requestlang FROM tbltriage WHERE id = '$triageIdEscaped' LIMIT 1");
        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            $stored = app_normalize_language($row['requestlang'] ?? '', '');
            if ($stored !== '') {
                return $stored;
            }
        }
    }

    $triageIdEscaped = (int) $triageId;
    $metaPrefix = mysqli_real_escape_string($link, '__rmt_request_lang:');
    $metaSql = "SELECT notes
                FROM tbladminlog
                WHERE triageid = '$triageIdEscaped'
                  AND notes LIKE '{$metaPrefix}%'
                ORDER BY id ASC
                LIMIT 1";
    $metaResult = mysqli_query($link, $metaSql);
    if ($metaResult && mysqli_num_rows($metaResult) > 0) {
        $metaRow = mysqli_fetch_assoc($metaResult);
        $notes = trim((string) ($metaRow['notes'] ?? ''));
        if (strpos($notes, '__rmt_request_lang:') === 0) {
            $metaLang = substr($notes, strlen('__rmt_request_lang:'));
            $normalizedMetaLang = app_normalize_language($metaLang, '');
            if ($normalizedMetaLang !== '') {
                return $normalizedMetaLang;
            }
        }
    }

    // Legacy fallback: infer from Department/agency note language when available.
    $legacySql = "SELECT notes
                  FROM tblcommlog
                  WHERE triageid = '$triageIdEscaped'
                    AND status = '1'
                  ORDER BY id ASC
                  LIMIT 5";
    $legacyResult = mysqli_query($link, $legacySql);
    if ($legacyResult) {
        while ($legacyRow = mysqli_fetch_assoc($legacyResult)) {
            $note = trim((string) ($legacyRow['notes'] ?? ''));
            if (stripos($note, 'Ministère/organisme:') === 0) {
                return 'fr';
            }
            if (stripos($note, 'Department/agency:') === 0) {
                return 'en';
            }
        }
    }

    return $fallback;
}

function rmt_mark_resolved_email_sent($link, int $triageId, int $creatorId = 0): void {
    if (!($link instanceof mysqli) || $triageId <= 0) {
        return;
    }

    $triageIdEscaped = (int) $triageId;
    $creatorIdEscaped = (int) $creatorId;
    $today = mysqli_real_escape_string($link, date('Y-m-d'));
    $note = mysqli_real_escape_string($link, '__rmt_resolved_email_sent');

    // Keep metadata hidden from normal staff communications by storing status=0.
    $insertSql = "INSERT INTO tbladminlog(`triageid`, `dateadded`, `notes`, `creatorid`, `status`) VALUES ('$triageIdEscaped', '$today', '$note', '$creatorIdEscaped', '0')";
    mysqli_query($link, $insertSql);
}

function rmt_log_client_survey_sent($link, int $triageId, int $creatorId, int $sendNumber): void {
    if (!($link instanceof mysqli) || $triageId <= 0 || $sendNumber <= 0) {
        return;
    }

    $triageIdEscaped = (int) $triageId;
    $creatorIdEscaped = (int) $creatorId;
    $today = mysqli_real_escape_string($link, date('Y-m-d'));
    $sentAt = date('Y-m-d H:i:s');
    $noteText = $sendNumber === 1
        ? "Client survey link sent at $sentAt (send #$sendNumber)."
        : "Client survey link resent at $sentAt (send #$sendNumber).";
    $note = mysqli_real_escape_string($link, $noteText);

    $insertSql = "INSERT INTO tbladminlog(`triageid`, `dateadded`, `notes`, `creatorid`, `status`) VALUES ('$triageIdEscaped', '$today', '$note', '$creatorIdEscaped', '1')";
    mysqli_query($link, $insertSql);

    if (rmt_table_has_column($link, 'RequestFieldHistory', 'requestID')) {
        $requestResult = mysqli_query($link, "SELECT requestid FROM tbltriage WHERE id = '$triageIdEscaped' LIMIT 1");
        $requestRow = $requestResult ? mysqli_fetch_assoc($requestResult) : null;
        $requestId = mysqli_real_escape_string($link, (string) ($requestRow['requestid'] ?? $triageId));
        $fieldName = mysqli_real_escape_string($link, 'survey_link_sent');
        $newValue = mysqli_real_escape_string($link, (string) $sendNumber);
        $changeTime = mysqli_real_escape_string($link, $sentAt);
        $historySql = "INSERT INTO RequestFieldHistory(`requestID`, `fieldName`, `oldValue`, `newValue`, `actorUserID`, `changeTimeStamp`) VALUES ('$requestId', '$fieldName', NULL, '$newValue', '$creatorIdEscaped', '$changeTime')";
        mysqli_query($link, $historySql);
    }
}

function rmt_get_resolved_email_sent_date($link, int $triageId): ?string {
    if (!($link instanceof mysqli) || $triageId <= 0) {
        return null;
    }

    $triageIdEscaped = (int) $triageId;
    $note = mysqli_real_escape_string($link, '__rmt_resolved_email_sent');
    $sql = "SELECT dateadded
            FROM tbladminlog
            WHERE triageid = '$triageIdEscaped'
              AND notes = '$note'
            ORDER BY id DESC
            LIMIT 1";
    $result = mysqli_query($link, $sql);
    if (!$result || mysqli_num_rows($result) === 0) {
        return null;
    }

    $row = mysqli_fetch_assoc($result);
    $dateAdded = trim((string) ($row['dateadded'] ?? ''));
    return $dateAdded !== '' ? $dateAdded : null;
}

function rmt_notification_escape(string $value): string {
    $value = strip_tags($value);
    $value = preg_replace('/\s+/', ' ', $value);

    return trim((string) $value);
}

function rmt_notification_context_name(array $context, string $recipientType = 'general'): string {
    $clientName = trim((string) (($context['client_fname'] ?? '') . ' ' . ($context['client_lname'] ?? '')));

    if ($recipientType === 'client') {
        $candidateNames = [
            $context['recipient_name'] ?? '',
            $clientName,
            $context['employee_name'] ?? '',
            $context['group_name'] ?? '',
            $context['teamname'] ?? '',
        ];
    } else {
        $candidateNames = [
            $context['recipient_name'] ?? '',
            $context['employee_name'] ?? '',
            $context['group_name'] ?? '',
            $context['teamname'] ?? '',
            $clientName,
        ];
    }

    foreach ($candidateNames as $candidateName) {
        $candidateName = trim((string) $candidateName);
        if ($candidateName !== '') {
            return strip_tags($candidateName);
        }
    }

    return '';
}

function rmt_notification_salutation(string $language, array $context = [], string $recipientType = 'general'): string {
    $isFrench = (app_normalize_language($language) === 'fr');

    $clientName = trim((string) (($context['client_fname'] ?? '') . ' ' . ($context['client_lname'] ?? '')));
    $clientName = rmt_notification_escape($clientName);
    $recipientName = rmt_notification_escape(rmt_notification_context_name($context, $recipientType));

    if ($clientName !== '') {
        return $isFrench ? 'Bonjour, ' . $clientName . ',' : 'Hello, ' . $clientName . ',';
    }

    if ($recipientName !== '') {
        return $isFrench ? 'Bonjour, ' . $recipientName . ',' : 'Hello, ' . $recipientName . ',';
    }

    return $isFrench ? 'Bonjour,' : 'Hello,';
}

function rmt_notification_signature_single_language(string $language, array $context = []): string {
    $isFrench = (app_normalize_language($language) === 'fr');
    $teamName = rmt_notification_escape((string) ($context['teamname'] ?? ''));
    $teamEmail = rmt_notification_escape((string) ($context['team_email'] ?? 'aaact-aatia@ssc-spc.gc.ca'));
    if ($teamEmail === '') {
        $teamEmail = 'aaact-aatia@ssc-spc.gc.ca';
    }

    if ($isFrench) {
        $lines = [];
        if ($teamName !== '') {
            $lines[] = $teamName;
        }
        $lines[] = 'Accessibilite, adaptation et technologie informatique adaptee (AATIA)';
        $lines[] = $teamEmail;

        return implode("\n", $lines);
    }

    $lines = [];
    if ($teamName !== '') {
        $lines[] = $teamName;
    }
    $lines[] = 'Accessibility, Accommodation and Adaptive Computer Technology (AAACT)';
    $lines[] = $teamEmail;

    return implode("\n", $lines);
}

function rmt_notification_language_order(string $recipientType, ?string $language): array {
    $lang = app_normalize_language($language);

    if ($recipientType === 'client' && $lang === 'fr') {
        return ['fr', 'en'];
    }

    return ['en', 'fr'];
}

function rmt_notification_template_category(string $event): array {
    // GC Notify reference categories: https://documentation.notification.canada.ca/en/template-categories.html
    switch ($event) {
        case 'request_created':
            return [
                'id' => '977e2a00-f957-4ff0-92f2-ca3286b24786', // Confirmation
                'name_en' => 'Confirmation',
                'name_fr' => 'Confirmation',
            ];

        case 'request_afterfact':
        case 'request_aaact':
            return [
                'id' => 'e0b8fbe5-f435-4977-8fc8-03f13d9296a5', // Request
                'name_en' => 'Request',
                'name_fr' => 'Demande',
            ];

        case 'status_changed':
        case 'resolved':
        case 'reassigned':
            return [
                'id' => '55eb1137-6dc6-4094-9031-f61124a279dc', // Status update
                'name_en' => 'Status update',
                'name_fr' => 'Mise a jour du statut',
            ];
    }

    return [
        'id' => '207b293c-2ae5-48e8-836d-fcabd60b2153', // General communication
        'name_en' => 'General communication',
        'name_fr' => 'Communication generale',
    ];
}

function rmt_notification_subject_single_language(string $event, string $recipientType, string $language, array $context = [], ?mysqli $link = null, ?int $teamId = null, int $serviceId = 0, int $subserviceId = 0): string {
    $lang = app_normalize_language($language);
    $isFrench = ($lang === 'fr');
    $isClient = ($recipientType === 'client');

    if ($link !== null) {
        $audience = $isClient ? 'client' : 'employee';
        $override = rmt_notification_template_resolve($link, $teamId, $audience, $event, $lang, $serviceId, $subserviceId);
        if ($override !== null && trim((string) $override['subject']) !== '') {
            return rmt_notification_render_template((string) $override['subject'], $context, $lang, $recipientType);
        }
    }

    $requestId = trim((string) ($context['requestid'] ?? ''));
    $statusLabel = trim((string) ($context['status_label'] ?? ''));
    $teamName = trim((string) ($context['teamname'] ?? ''));
    $subjectPrefix = '';

    switch ($event) {
        case 'request_created':
            if ($isClient) {
                return $subjectPrefix . ($isFrench
                    ? 'Votre demande d\'accessibilité ' . $requestId . ' a été reçue'
                    : 'Your accessibility request ' . $requestId . ' has been received');
            }

            return $subjectPrefix . ($isFrench
                ? 'Nouvelle demande d\'accessibilité ' . $requestId . ' assignée à votre équipe'
                : 'New accessibility request ' . $requestId . ' assigned to your team');

        case 'request_afterfact':
            return $subjectPrefix . ($isFrench
                ? 'Demande d\'accessibilité après-fact ' . $requestId . ' assignée à votre équipe'
                : 'After-fact accessibility request ' . $requestId . ' assigned to your team');

        case 'request_aaact':
            return $subjectPrefix . ($isFrench
                ? 'Demande d\'accessibilité ' . $requestId . ' à faire trier par AATIA'
                : 'Accessibility request ' . $requestId . ' needs AAACT triage');

        case 'resolved':
            if ($isClient) {
                return $subjectPrefix . ($isFrench
                    ? 'Votre demande d\'accessibilité ' . $requestId . ' a été résolue'
                    : 'Your accessibility request ' . $requestId . ' has been resolved');
            }

            return $subjectPrefix . ($isFrench
                ? 'Demande d\'accessibilité ' . $requestId . ' marquée comme résolue'
                : 'Accessibility request ' . $requestId . ' marked as resolved');

        case 'status_changed':
            return $subjectPrefix . ($isFrench
                ? 'Mise à jour du statut de la demande ' . $requestId . (!empty($statusLabel) ? ' - ' . $statusLabel : '')
                : 'Status update for accessibility request ' . $requestId . (!empty($statusLabel) ? ' - ' . $statusLabel : ''));

        case 'reassigned':
            if ($isClient) {
                return $subjectPrefix . ($isFrench
                    ? 'Votre demande d\'accessibilité ' . $requestId . ' a été réattribuée'
                    : 'Your accessibility request ' . $requestId . ' has been reassigned');
            }

            return $subjectPrefix . ($isFrench
                ? 'Demande d\'accessibilité ' . $requestId . ' réattribuée à ' . $teamName
                : 'Accessibility request ' . $requestId . ' reassigned to ' . $teamName);
    }

    return $subjectPrefix . ($isFrench
        ? 'Mise à jour de la demande d\'accessibilité ' . $requestId
        : 'Accessibility request update ' . $requestId);
}

function rmt_notification_subject(string $event, string $recipientType, ?string $language, array $context = [], ?mysqli $link = null, ?int $teamId = null, int $serviceId = 0, int $subserviceId = 0): string {
    $subjects = [];

    foreach (rmt_notification_language_order($recipientType, $language) as $renderLanguage) {
        $subject = rmt_notification_subject_single_language($event, $recipientType, $renderLanguage, $context, $link, $teamId, $serviceId, $subserviceId);
        if ($subject !== '' && !in_array($subject, $subjects, true)) {
            $subjects[] = $subject;
        }
    }

    return implode(' / ', $subjects);
}

function rmt_notification_message_single_language(string $event, string $recipientType, string $language, array $context = [], ?mysqli $link = null, ?int $teamId = null, int $serviceId = 0, int $subserviceId = 0): string {
    $lang = app_normalize_language($language);
    $isFrench = ($lang === 'fr');
    $isClient = ($recipientType === 'client');

    if ($link !== null) {
        $audience = $isClient ? 'client' : 'employee';
        $override = rmt_notification_template_resolve($link, $teamId, $audience, $event, $lang, $serviceId, $subserviceId);
        if ($override !== null && trim((string) $override['body']) !== '') {
            return rmt_notification_render_template((string) $override['body'], $context, $lang, $recipientType);
        }
    }

    $requestId = rmt_notification_escape((string) ($context['requestid'] ?? ''));
    $requestTitle = rmt_notification_escape((string) ($context['requesttitle'] ?? ''));
    $catalogueName = rmt_notification_escape((string) ($context['catalogue_name'] ?? ''));
    $serviceName = rmt_notification_escape((string) ($context['service_name'] ?? ''));
    $teamName = rmt_notification_escape((string) ($context['teamname'] ?? ''));
    $statusLabelRaw = $isFrench
        ? ($context['status_fr'] ?? ($context['status_label'] ?? ''))
        : ($context['status_en'] ?? ($context['status_label'] ?? ''));
    $statusLabel = rmt_notification_escape((string) $statusLabelRaw);
    $recipientPrefix = '';
    $requestUrl = trim((string) ($context['url'] ?? ''));
    $surveyLink = trim((string) ($isFrench ? ($context['survey_link_fr'] ?? '') : ($context['survey_link_en'] ?? '')));

    $format = static function (array $paragraphs): string {
        $cleaned = array_values(array_filter(array_map(static function (string $paragraph): string {
            return trim($paragraph);
        }, $paragraphs), static function (string $paragraph): bool {
            return $paragraph !== '';
        }));

        return implode("\n\n", $cleaned);
    };

    $linkLine = '';
    if ($requestUrl !== '') {
        $linkLine = $isFrench
            ? 'Voir la demande : [' . $requestId . '](' . $requestUrl . ')'
            : 'View request: [' . $requestId . '](' . $requestUrl . ')';
    }

    $signatureBlock = rmt_notification_signature_single_language($language, $context);

    $withLink = static function (array $paragraphs) use ($format, $linkLine, $signatureBlock): string {
        if ($linkLine !== '') {
            $paragraphs[] = $linkLine;
        }

        if ($signatureBlock !== '') {
            $paragraphs[] = $signatureBlock;
        }

        return $format($paragraphs);
    };

    switch ($event) {
        case 'request_created':
            if ($isClient) {
                return $withLink($isFrench ? [
                    rmt_notification_salutation($language, $context, $recipientType),
                    '',
                    $recipientPrefix . 'Votre demande d\'accessibilité ' . $requestId . ' a été reçue.',
                    'Nous l\'examinerons et nous communiquerons avec vous si des renseignements supplémentaires sont nécessaires.',
                    'Titre de la demande : ' . $requestTitle,
                ] : [
                    rmt_notification_salutation($language, $context, $recipientType),
                    '',
                    $recipientPrefix . 'Your accessibility request ' . $requestId . ' has been received.',
                    'We will review it and contact you if more information is needed.',
                    'Request title: ' . $requestTitle,
                ]);
            }

            return $withLink($isFrench ? [
                rmt_notification_salutation($language, $context, $recipientType),
                '',
                $recipientPrefix . 'Une nouvelle demande d\'accessibilité ' . $requestId . ' a été assignée à votre équipe.',
                'Titre de la demande : ' . $requestTitle,
                'Catalogue : ' . $catalogueName,
                'Service : ' . $serviceName,
            ] : [
                rmt_notification_salutation($language, $context, $recipientType),
                '',
                $recipientPrefix . 'A new accessibility request ' . $requestId . ' has been assigned to your team.',
                'Request title: ' . $requestTitle,
                'Catalogue: ' . $catalogueName,
                'Service: ' . $serviceName,
            ]);

        case 'request_afterfact':
            return $withLink($isFrench ? [
                rmt_notification_salutation($language, $context, $recipientType),
                '',
                $recipientPrefix . 'Une nouvelle demande d\'accessibilité ' . $requestId . ' a été soumise après la réalisation des travaux et a été assignée à votre équipe.',
                'Titre de la demande : ' . $requestTitle,
                'Catalogue : ' . $catalogueName,
                'Service : ' . $serviceName,
            ] : [
                rmt_notification_salutation($language, $context, $recipientType),
                '',
                $recipientPrefix . 'A new accessibility request ' . $requestId . ' was submitted after the work already happened and has been assigned to your team.',
                'Request title: ' . $requestTitle,
                'Catalogue: ' . $catalogueName,
                'Service: ' . $serviceName,
            ]);

        case 'request_aaact':
            return $withLink($isFrench ? [
                rmt_notification_salutation($language, $context, $recipientType),
                '',
                $recipientPrefix . 'Une nouvelle demande d\'accessibilité ' . $requestId . ' requiert un triage AATIA.',
                'Consultez les détails de la demande et acheminez-la à l\'équipe appropriée.',
                'Titre de la demande : ' . $requestTitle,
            ] : [
                rmt_notification_salutation($language, $context, $recipientType),
                '',
                $recipientPrefix . 'A new accessibility request ' . $requestId . ' needs AAACT triage.',
                'Review the request details and route it to the appropriate team.',
                'Request title: ' . $requestTitle,
            ]);

        case 'resolved':
            if ($isClient) {
                $surveyCta = '';
                if ($surveyLink !== '') {
                    $surveyCta = $isFrench
                        ? 'Nous aimerions savoir comment s\'est deroule votre experience. Veuillez remplir ce court sondage : [a11y-' . $requestId . '](' . $surveyLink . ')'
                        : 'We would love to hear how we did. Please fill out this short survey: [a11y-' . $requestId . '](' . $surveyLink . ')';
                }

                return $withLink($isFrench ? [
                    rmt_notification_salutation($language, $context, $recipientType),
                    '',
                    $recipientPrefix . 'Votre demande ' . $requestId . ' a été résolue.',
                    'Si vous croyez que d\'autres travaux sont nécessaires, répondez à ce message et mentionnez votre numéro de demande.',
                    $surveyCta,
                ] : [
                    rmt_notification_salutation($language, $context, $recipientType),
                    '',
                    $recipientPrefix . 'Your request ' . $requestId . ' has been resolved.',
                    'If you believe more work is required, reply to this message and reference your request number.',
                    $surveyCta,
                ]);
            }

            return $withLink($isFrench ? [
                rmt_notification_salutation($language, $context, $recipientType),
                '',
                $recipientPrefix . 'La demande d\'accessibilité ' . $requestId . ' a été marquée comme résolue.',
                'Assurez-vous que les dossiers finaux et les actions de suivi sont complets.',
            ] : [
                rmt_notification_salutation($language, $context, $recipientType),
                '',
                $recipientPrefix . 'Accessibility request ' . $requestId . ' has been marked as resolved.',
                'Ensure any final records or follow-up actions are complete.',
            ]);

        case 'status_changed':
            return $withLink($isFrench ? [
                rmt_notification_salutation($language, $context, $recipientType),
                '',
                $recipientPrefix . 'Le statut de votre demande ' . $requestId . ' a changé pour ' . $statusLabel . '.',
                'Veuillez consulter les derniers détails de statut en utilisant le lien de demande ci-dessous.',
            ] : [
                rmt_notification_salutation($language, $context, $recipientType),
                '',
                $recipientPrefix . 'The status of your request ' . $requestId . ' has changed to ' . $statusLabel . '.',
                'Please review the latest status details using the request link below.',
            ]);

        case 'reassigned':
            if ($isClient) {
                return $withLink($isFrench ? [
                    rmt_notification_salutation($language, $context, $recipientType),
                    '',
                    $recipientPrefix . 'Votre demande ' . $requestId . ' a été réattribuée à une autre équipe.',
                    'La nouvelle équipe poursuivra les travaux et fera un suivi si des renseignements supplémentaires sont nécessaires.',
                ] : [
                    rmt_notification_salutation($language, $context, $recipientType),
                    '',
                    $recipientPrefix . 'Your request ' . $requestId . ' has been reassigned to a different team.',
                    'The new team will continue the work and follow up if more information is needed.',
                ]);
            }

            return $withLink($isFrench ? [
                rmt_notification_salutation($language, $context, $recipientType),
                '',
                $recipientPrefix . 'La demande d\'accessibilité ' . $requestId . ' a été réattribuée à ' . $teamName . '.',
                'Examinez le contexte de la demande et confirmez la prise en charge avec votre équipe.',
            ] : [
                rmt_notification_salutation($language, $context, $recipientType),
                '',
                $recipientPrefix . 'Accessibility request ' . $requestId . ' has been reassigned to ' . $teamName . '.',
                'Review the request context and confirm ownership with your team.',
            ]);
    }

    return $withLink($isFrench ? [
        rmt_notification_salutation($language, $context, $recipientType),
        '',
        'Voici une mise à jour pour la demande d\'accessibilité ' . $requestId . '.',
    ] : [
        rmt_notification_salutation($language, $context, $recipientType),
        '',
        'This is an update for accessibility request ' . $requestId . '.',
    ]);
}

function rmt_notification_message(string $event, string $recipientType, ?string $language, array $context = [], ?mysqli $link = null, ?int $teamId = null, int $serviceId = 0, int $subserviceId = 0): string {
    $order = rmt_notification_language_order($recipientType, $language);
    if (empty($order)) {
        return '';
    }

    $firstLanguage = $order[0];
    $secondLanguage = $order[1] ?? null;

    $firstBody = rmt_notification_message_single_language($event, $recipientType, $firstLanguage, $context, $link, $teamId, $serviceId, $subserviceId);
    if ($secondLanguage === null) {
        return $firstBody;
    }

    $secondBody = rmt_notification_message_single_language($event, $recipientType, $secondLanguage, $context, $link, $teamId, $serviceId, $subserviceId);

    if ($firstLanguage === 'en') {
        $transitionLink = 'Message en francais a suivre.';
        $secondHeading = '## Français';
    } else {
        $transitionLink = 'English message to follow.';
        $secondHeading = '## English';
    }

    return implode("\n\n", [
        $transitionLink,
        $firstBody,
        $secondHeading,
        $secondBody,
    ]);
}

function rmt_get_resolved_status_ids($link): array {
    static $resolvedStatusIds = null;

    if (is_array($resolvedStatusIds)) {
        return $resolvedStatusIds;
    }

    $resolvedStatusIds = [];
    $hasResolvedFlag = rmt_table_has_column($link, 'tblstatus', 'is_resolved');

    if ($hasResolvedFlag) {
        $sql = "SELECT id FROM tblstatus WHERE status = '1' AND is_resolved = '1' ORDER BY id ASC";
    } else {
        $sql = "SELECT id FROM tblstatus
                WHERE status = '1'
                  AND (LOWER(nameen) = 'resolved' OR LOWER(namefr) IN ('résolu', 'resolu'))
                ORDER BY id ASC";
    }

    $result = mysqli_query($link, $sql);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $resolvedStatusIds[] = (int)$row['id'];
        }
    }

    return $resolvedStatusIds;
}

function rmt_is_resolved_status_id($link, $statusId): bool {
    $statusId = (int)$statusId;
    if ($statusId <= 0) {
        return false;
    }

    return in_array($statusId, rmt_get_resolved_status_ids($link), true);
}

function getTeamMembersByContact($link, $contactid) {
    $contactid = mysqli_real_escape_string($link, $contactid);
    
    $result = mysqli_query($link,
        "SELECT id, firstname, lastname, team FROM tblusers 
         WHERE status='1' 
         ORDER BY firstname ASC, lastname ASC"
    );
    
    $members = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $teams = explode(",", $row['team']);
        if (in_array($contactid, $teams)) {
            $members[] = $row;
        }
    }
    
    return $members;
}

// ============================================================================
// HTML RENDERING HELPERS
// ============================================================================

function renderTextInput($id, $label, $value = '', $required = false, $readonly = false, $type = 'text', $extraAttrs = '', $fullWidth = false) {
    $requiredAttr = $required ? 'required' : '';
    $readonlyAttr = $readonly ? 'readonly="readonly"' : '';
    $requiredText = (($_SESSION['lang'] ?? 'en') === 'fr') ? 'obligatoire' : 'required';
    $requiredLabel = $required ? " <strong>($requiredText)</strong>" : '';
    $escapedValue = htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    $controlClass = $fullWidth ? 'form-control full-width' : 'form-control';
    
    return <<<HTML
    <div class="form-group">
        <label for="$id"><span class="field-name">$label$requiredLabel</span></label>
        <input type="$type" class="$controlClass" id="$id" name="$id"
               value="$escapedValue" $requiredAttr $readonlyAttr $extraAttrs>
    </div>
HTML;
}

function renderDateInput($id, $label, $value = '', $required = false, $min = null, $max = null, $readonly = false) {
    $requiredAttr = $required ? 'required' : '';
    $requiredText = (($_SESSION['lang'] ?? 'en') === 'fr') ? 'obligatoire' : 'required';
    $requiredLabel = $required ? " <strong>($requiredText)</strong>" : '';
    $minAttr = $min ? "min=\"$min\"" : '';
    $maxAttr = $max ? "max=\"$max\"" : '';
    $readonlyAttr = $readonly ? 'readonly="readonly"' : '';
    $escapedValue = htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    
    return <<<HTML
    <div class="form-group">
        <label for="$id"><span class="field-name">$label$requiredLabel</span></label>
        <input type="date" class="form-control" id="$id" name="$id" 
             value="$escapedValue" $requiredAttr $minAttr $maxAttr $readonlyAttr>
    </div>
HTML;
}

function renderTextarea($id, $label, $value = '', $required = false, $readonly = false, $rows = 10, $fullWidth = false) {
    $requiredAttr = $required ? 'required' : '';
    $readonlyAttr = $readonly ? 'readonly' : '';
    $requiredText = (($_SESSION['lang'] ?? 'en') === 'fr') ? 'obligatoire' : 'required';
    $requiredLabel = $required ? " <strong>($requiredText)</strong>" : '';
    $escapedValue = htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    $controlClass = $fullWidth ? 'form-control full-width' : 'form-control';
    
    return <<<HTML
    <div class="form-group">
        <label for="$id"><span class="field-name">$label$requiredLabel</span></label>
        <textarea class="$controlClass" id="$id" name="$id" cols="50" rows="$rows"
                  $requiredAttr $readonlyAttr>$escapedValue</textarea>
    </div>
HTML;
}

function renderSelect($id, $label, $options, $selectedValue = '', $required = false, $emptyText = 'Make your selection', $disabled = false, $fullWidth = false) {
    $requiredAttr = $required ? 'required' : '';
    $requiredText = (($_SESSION['lang'] ?? 'en') === 'fr') ? 'obligatoire' : 'required';
    $requiredLabel = $required ? " <strong>($requiredText)</strong>" : '';
    $disabledAttr = $disabled ? 'disabled="disabled"' : '';
    $controlClass = $fullWidth ? 'form-control full-width' : 'form-control';
    
    $html = <<<HTML
    <div class="form-group">
        <label for="$id"><span class="field-name">$label$requiredLabel</span></label>
        <select class="$controlClass" id="$id" name="$id" $requiredAttr $disabledAttr>
            <option value="">$emptyText</option>
HTML;
    
    foreach ($options as $option) {
        $value = is_array($option) ? $option['id'] : $option;
        $text = is_array($option) ? $option['name'] : $option;
        $selected = ($value == $selectedValue) ? 'selected' : '';
        $html .= "<option value=\"$value\" $selected>$text</option>\n";
    }
    
    $html .= "</select>\n</div>";
    return $html;
}

// ============================================================================
// DATE HELPERS
// ============================================================================

function getDateRange($years = 1) {
    return [
        'min' => date('Y-m-d', strtotime("-$years years")),
        'max' => date('Y-m-d', strtotime("+$years years"))
    ];
}

function getTodayDate() {
    return date('Y-m-d');
}

// ============================================================================
// LANGUAGE HELPERS
// ============================================================================

function detectLanguage() {
    if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'fr'])) {
        $_SESSION['lang'] = $_GET['lang'];
        return $_GET['lang'];
    }
    
    if (isset($_SESSION['lang']) && in_array($_SESSION['lang'], ['en', 'fr'])) {
        return $_SESSION['lang'];
    }
    
    return 'en'; // Default
}

function getIncludePath($file, $lang) {
    $langSuffix = $lang === 'fr' ? '-fr' : '-en';
    return str_replace('.php', "$langSuffix.php", $file);
}

?>
