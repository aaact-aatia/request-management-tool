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

function isAdmin() {
    return isset($_SESSION['atype']) && $_SESSION['atype'] == 1;
}

function canEditRequests() {
    return isset($_SESSION['atype']) && in_array($_SESSION['atype'], [1, 2, 3, 4, 5]);
}

function canManageSLA() {
    return isset($_SESSION['atype']) && in_array($_SESSION['atype'], [1, 2, 3, 4]);
}

function isReadOnly() {
    return isset($_SESSION['atype']) && $_SESSION['atype'] == 6;
}

function canViewAllRequests() {
    return isset($_SESSION['atype']) && in_array($_SESSION['atype'], [1, 6]);
}

function canViewReports() {
    return isset($_SESSION['atype']) && in_array($_SESSION['atype'], [1, 2, 3, 4, 5, 6]);
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

function renderDateInput($id, $label, $value = '', $required = false, $min = null, $max = null) {
    $requiredAttr = $required ? 'required' : '';
    $requiredLabel = $required ? ' <strong>(required)</strong>' : '';
    $minAttr = $min ? "min=\"$min\"" : '';
    $maxAttr = $max ? "max=\"$max\"" : '';
    
    return <<<HTML
    <div class="form-group">
        <label for="$id"><span class="field-name">$label$requiredLabel</span></label>
        <input type="date" class="form-control" id="$id" name="$id" 
               value="$value" $requiredAttr $minAttr $maxAttr>
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

function renderSelect($id, $label, $options, $selectedValue = '', $required = false, $emptyText = 'Make your selection') {
    $requiredAttr = $required ? 'required' : '';
    $requiredLabel = $required ? ' <strong>(required)</strong>' : '';
    
    $html = <<<HTML
    <div class="form-group">
        <label for="$id"><span class="field-name">$label$requiredLabel</span></label>
        <select class="form-control" id="$id" name="$id" $requiredAttr>
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
