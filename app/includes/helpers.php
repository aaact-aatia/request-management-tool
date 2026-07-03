<?php
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
        return isset($_SESSION['atype']) && in_array($_SESSION['atype'], [3, 4]);
    }

    $isAdminOrSuperuser = (isset($_SESSION['is_superuser']) && $_SESSION['is_superuser']) || 
                         (isset($_SESSION['is_admin']) && $_SESSION['is_admin']);
    return $isAdminOrSuperuser || (isset($_SESSION['atype']) && in_array($_SESSION['atype'], [3, 4]));
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

function getPostValue($key, $default = "") {
    return !empty($_POST[$key]) ? mysqli_real_escape_string($GLOBALS['link'], $_POST[$key]) : $default;
}

function getGetValue($key, $default = "") {
    return !empty($_GET[$key]) ? mysqli_real_escape_string($GLOBALS['link'], $_GET[$key]) : $default;
}

// ============================================================================
// DATABASE HELPERS
// ============================================================================

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
    $note = mysqli_real_escape_string($link, rmt_request_language_meta_note($language));
    $today = mysqli_real_escape_string($link, date('Y-m-d'));
    $triageIdEscaped = (int) $triageId;
    $creatorIdEscaped = (int) $creatorId;

    $checkSql = "SELECT id FROM tbladminlog WHERE triageid = '$triageIdEscaped' AND notes = '$note' LIMIT 1";
    $checkResult = mysqli_query($link, $checkSql);
    if ($checkResult && mysqli_num_rows($checkResult) > 0) {
        return;
    }

    // Store with status=0 to keep this internal metadata hidden from normal admin log views.
    $insertSql = "INSERT INTO tbladminlog(`triageid`, `dateadded`, `notes`, `creatorid`, `status`) VALUES ('$triageIdEscaped', '$today', '$note', '$creatorIdEscaped', '0')";
    mysqli_query($link, $insertSql);
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

function rmt_notification_subject_single_language(string $event, string $recipientType, string $language, array $context = []): string {
    $lang = app_normalize_language($language);
    $isFrench = ($lang === 'fr');
    $isClient = ($recipientType === 'client');

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

function rmt_notification_subject(string $event, string $recipientType, ?string $language, array $context = []): string {
    $subjects = [];

    foreach (rmt_notification_language_order($recipientType, $language) as $renderLanguage) {
        $subject = rmt_notification_subject_single_language($event, $recipientType, $renderLanguage, $context);
        if ($subject !== '' && !in_array($subject, $subjects, true)) {
            $subjects[] = $subject;
        }
    }

    return implode(' / ', $subjects);
}

function rmt_notification_message_single_language(string $event, string $recipientType, string $language, array $context = []): string {
    $lang = app_normalize_language($language);
    $isFrench = ($lang === 'fr');
    $isClient = ($recipientType === 'client');

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

function rmt_notification_message(string $event, string $recipientType, ?string $language, array $context = []): string {
    $order = rmt_notification_language_order($recipientType, $language);
    if (empty($order)) {
        return '';
    }

    $firstLanguage = $order[0];
    $secondLanguage = $order[1] ?? null;

    $firstBody = rmt_notification_message_single_language($event, $recipientType, $firstLanguage, $context);
    if ($secondLanguage === null) {
        return $firstBody;
    }

    $secondBody = rmt_notification_message_single_language($event, $recipientType, $secondLanguage, $context);

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

function renderTextInput($id, $label, $value = '', $required = false, $readonly = false, $type = 'text', $extraAttrs = '') {
    $requiredAttr = $required ? 'required' : '';
    $readonlyAttr = $readonly ? 'readonly="readonly"' : '';
    $requiredLabel = $required ? ' <strong>(required)</strong>' : '';
    $escapedValue = htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    
    return <<<HTML
    <div class="form-group">
        <label for="$id"><span class="field-name">$label$requiredLabel</span></label>
        <input type="$type" class="form-control" id="$id" name="$id" 
               value="$escapedValue" $requiredAttr $readonlyAttr $extraAttrs>
    </div>
HTML;
}

function renderDateInput($id, $label, $value = '', $required = false, $min = null, $max = null, $readonly = false) {
    $requiredAttr = $required ? 'required' : '';
    $requiredLabel = $required ? ' <strong>(required)</strong>' : '';
    $minAttr = $min ? "min=\"$min\"" : '';
    $maxAttr = $max ? "max=\"$max\"" : '';
    $readonlyAttr = $readonly ? 'readonly="readonly"' : '';
    
    return <<<HTML
    <div class="form-group">
        <label for="$id"><span class="field-name">$label$requiredLabel</span></label>
        <input type="date" class="form-control" id="$id" name="$id" 
             value="$value" $requiredAttr $minAttr $maxAttr $readonlyAttr>
    </div>
HTML;
}

function renderTextarea($id, $label, $value = '', $required = false, $readonly = false, $rows = 10) {
    $requiredAttr = $required ? 'required' : '';
    $readonlyAttr = $readonly ? 'readonly' : '';
    $requiredLabel = $required ? ' <strong>(required)</strong>' : '';
    $escapedValue = htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    
    return <<<HTML
    <div class="form-group">
        <label for="$id"><span class="field-name">$label$requiredLabel</span></label>
        <textarea class="form-control" id="$id" name="$id" cols="50" rows="$rows" 
                  $requiredAttr $readonlyAttr>$escapedValue</textarea>
    </div>
HTML;
}

function renderSelect($id, $label, $options, $selectedValue = '', $required = false, $emptyText = 'Make your selection', $disabled = false) {
    $requiredAttr = $required ? 'required' : '';
    $requiredLabel = $required ? ' <strong>(required)</strong>' : '';
    $disabledAttr = $disabled ? 'disabled="disabled"' : '';
    
    $html = <<<HTML
    <div class="form-group">
        <label for="$id"><span class="field-name">$label$requiredLabel</span></label>
        <select class="form-control" id="$id" name="$id" $requiredAttr $disabledAttr>
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
