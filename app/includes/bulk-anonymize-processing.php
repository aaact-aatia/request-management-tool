<?php

if (isset($_SERVER['SCRIPT_FILENAME']) && realpath(__FILE__) === realpath((string) $_SERVER['SCRIPT_FILENAME'])) {
    http_response_code(404);
    exit();
}

function rmt_bulk_anonymize_normalize_ids(array $values): array
{
    $ids = [];
    foreach ($values as $value) {
        $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id !== false) {
            $ids[(int) $id] = true;
        }
    }

    return array_keys($ids);
}

function rmt_bulk_anonymize_parse_excluded_services(string $value): array
{
    $parts = preg_split('/\s*,\s*/', trim($value), -1, PREG_SPLIT_NO_EMPTY);
    return rmt_bulk_anonymize_normalize_ids($parts ?: []);
}

function rmt_bulk_anonymize_criteria_hash(array $catalogueIds, array $excludedServiceIds): string
{
    sort($catalogueIds);
    sort($excludedServiceIds);
    return hash('sha256', json_encode([
        'catalogues' => $catalogueIds,
        'excluded_services' => $excludedServiceIds,
    ], JSON_THROW_ON_ERROR));
}

function rmt_bulk_anonymize_matching_where(array $catalogueIds, array $excludedServiceIds): array
{
    $cataloguePlaceholders = implode(', ', array_fill(0, count($catalogueIds), '?'));
    $where = "catalogueid IN ({$cataloguePlaceholders})";
    $types = str_repeat('i', count($catalogueIds));
    $params = $catalogueIds;

    if ($excludedServiceIds !== []) {
        $servicePlaceholders = implode(', ', array_fill(0, count($excludedServiceIds), '?'));
        $where .= " AND (serviceid IS NULL OR serviceid NOT IN ({$servicePlaceholders}))";
        $types .= str_repeat('i', count($excludedServiceIds));
        $params = array_merge($params, $excludedServiceIds);
    }

    return [$where, $types, $params];
}

function rmt_bulk_anonymize_count(mysqli $link, array $catalogueIds, array $excludedServiceIds): int
{
    [$where, $types, $params] = rmt_bulk_anonymize_matching_where($catalogueIds, $excludedServiceIds);
    $statement = rmt_db_execute($link, "SELECT COUNT(*) AS record_count FROM tbltriage WHERE {$where}", $types, $params);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($statement));
    mysqli_stmt_close($statement);

    return (int) ($row['record_count'] ?? 0);
}

function rmt_bulk_anonymize_run(mysqli $link, array $catalogueIds, array $excludedServiceIds, string $notes, string $auditNotes, string $language, int $creatorId): array
{
    if (!function_exists('isSuperAdmin') || !isSuperAdmin()) {
        throw new RuntimeException('Superadmin access is required for bulk anonymization.');
    }

    [$where, $types, $params] = rmt_bulk_anonymize_matching_where($catalogueIds, $excludedServiceIds);
    mysqli_begin_transaction($link);

    try {
        $selectStatement = rmt_db_execute($link, "SELECT id FROM tbltriage WHERE {$where} FOR UPDATE", $types, $params);
        $requestIds = [];
        $result = mysqli_stmt_get_result($selectStatement);
        while ($row = mysqli_fetch_assoc($result)) {
            $requestIds[] = (int) $row['id'];
        }
        mysqli_stmt_close($selectStatement);

        $triageStatement = mysqli_prepare(
            $link,
            'UPDATE tbltriage SET clientlname = ?, clientfname = ?, clientemail = ?, clientphone = ? WHERE id = ?'
        );
        $commlogStatement = mysqli_prepare($link, 'UPDATE tblcommlog SET notes = ? WHERE triageid = ?');
        $clientLastName = 'CLIENT';
        $clientFirstName = 'AAACT';
        $clientEmail = 'daiu-anci@ssc-spc.gc.ca';
        $clientPhone = '';
        foreach ($requestIds as $requestId) {
            mysqli_stmt_bind_param($triageStatement, 'ssssi', $clientLastName, $clientFirstName, $clientEmail, $clientPhone, $requestId);
            mysqli_stmt_execute($triageStatement);
            mysqli_stmt_bind_param($commlogStatement, 'si', $notes, $requestId);
            mysqli_stmt_execute($commlogStatement);
        }

        mysqli_stmt_close($triageStatement);
        mysqli_stmt_close($commlogStatement);

        $auditStatement = rmt_db_execute(
            $link,
            'INSERT INTO tbladminlog (triageid, dateadded, timeadded, notes, language_code, creatorid, status) VALUES (0, CURDATE(), NOW(), ?, ?, ?, 1)',
            'ssi',
            [$auditNotes, $language, $creatorId]
        );
        mysqli_stmt_close($auditStatement);
        mysqli_commit($link);

        return ['request_count' => count($requestIds)];
    } catch (Throwable $exception) {
        mysqli_rollback($link);
        throw $exception;
    }
}