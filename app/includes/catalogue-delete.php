<?php

require_once __DIR__ . '/helpers.php';

function rmt_catalogue_delete_config(string $level): array {
    $configs = [
        'catalogue' => [
            'table' => 'tblcatalogue',
            'request_column' => 'catalogueid',
            'replacement_table' => 'tblcatalogue',
            'replacement_parent_sql' => '',
        ],
        'service' => [
            'table' => 'tblservices',
            'request_column' => 'serviceid',
            'replacement_table' => 'tblservices',
            'replacement_parent_sql' => ' AND catalogueid = ?',
        ],
        'subservice' => [
            'table' => 'tblsubservices',
            'request_column' => 'subserviceid',
            'replacement_table' => 'tblsubservices',
            'replacement_parent_sql' => ' AND serviceid = ?',
        ],
    ];

    if (!isset($configs[$level])) {
        throw new InvalidArgumentException('Unsupported catalogue hierarchy level.');
    }

    return $configs[$level];
}

function rmt_catalogue_delete_item(mysqli $link, string $level, int $itemId): ?array {
    $config = rmt_catalogue_delete_config($level);
    return rmt_db_fetch_one(
        $link,
        "SELECT * FROM {$config['table']} WHERE id = ?",
        'i',
        [$itemId]
    );
}

function rmt_catalogue_unresolved_request_count(mysqli $link, string $level, int $itemId): int {
    $config = rmt_catalogue_delete_config($level);
    $row = rmt_db_fetch_one(
        $link,
        "SELECT COUNT(*) AS total
         FROM tbltriage t
         LEFT JOIN tblstatus request_status ON request_status.id = t.statusid
         WHERE t.{$config['request_column']} = ?
           AND t.status = 1
           AND COALESCE(request_status.is_resolved, 0) = 0",
        'i',
        [$itemId]
    );

    return (int) ($row['total'] ?? 0);
}

function rmt_catalogue_replacement_options(
    mysqli $link,
    string $level,
    int $itemId,
    int $parentId,
    string $lang
): array {
    $config = rmt_catalogue_delete_config($level);
    $nameColumn = ($lang === 'fr') ? 'namefr' : 'nameen';
    $types = 'i';
    $params = [$itemId];
    if ($config['replacement_parent_sql'] !== '') {
        $types .= 'i';
        $params[] = $parentId;
    }

    $statement = rmt_db_execute(
        $link,
        "SELECT id, {$nameColumn} AS name
         FROM {$config['replacement_table']}
         WHERE id <> ? AND status = 1{$config['replacement_parent_sql']}
         ORDER BY {$nameColumn}",
        $types,
        $params
    );
    $result = mysqli_stmt_get_result($statement);
    $options = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
    mysqli_stmt_close($statement);
    return $options;
}

function rmt_archive_catalogue_request_names(mysqli $link, string $level, int $itemId): void {
    $config = rmt_catalogue_delete_config($level);
    $statement = rmt_db_execute(
        $link,
        "UPDATE tbltriage t
         LEFT JOIN tblcatalogue c ON c.id = t.catalogueid
         LEFT JOIN tblservices s ON s.id = t.serviceid
         LEFT JOIN tblsubservices ss ON ss.id = t.subserviceid
         SET t.cataloguenameen = c.nameen,
             t.cataloguenamefr = c.namefr,
             t.servicenameen = s.nameen,
             t.servicenamefr = s.namefr,
             t.subservicenameen = ss.nameen,
             t.subservicenamefr = ss.namefr
         WHERE t.{$config['request_column']} = ?",
        'i',
        [$itemId]
    );
    mysqli_stmt_close($statement);
}

function rmt_reassign_unresolved_catalogue_requests(
    mysqli $link,
    string $level,
    int $itemId,
    int $replacementId,
    int $parentId
): void {
    if ($replacementId <= 0) {
        return;
    }

    $config = rmt_catalogue_delete_config($level);
    $replacement = rmt_db_fetch_one(
        $link,
        "SELECT * FROM {$config['replacement_table']}
         WHERE id = ? AND status = 1{$config['replacement_parent_sql']}",
        $config['replacement_parent_sql'] === '' ? 'i' : 'ii',
        $config['replacement_parent_sql'] === '' ? [$replacementId] : [$replacementId, $parentId]
    );
    if ($replacement === null || $replacementId === $itemId) {
        throw new RuntimeException('The selected replacement is not valid.');
    }

    if ($level === 'catalogue') {
        $setSql = 't.catalogueid = ?, t.cataloguenameen = ?, t.cataloguenamefr = ?,
                   t.serviceid = 0, t.servicenameen = NULL, t.servicenamefr = NULL,
                   t.subserviceid = 0, t.subservicenameen = NULL, t.subservicenamefr = NULL';
    } elseif ($level === 'service') {
        $setSql = 't.serviceid = ?, t.servicenameen = ?, t.servicenamefr = ?,
                   t.subserviceid = 0, t.subservicenameen = NULL, t.subservicenamefr = NULL';
    } else {
        $setSql = 't.subserviceid = ?, t.subservicenameen = ?, t.subservicenamefr = ?';
    }

    $statement = rmt_db_execute(
        $link,
        "UPDATE tbltriage t
         LEFT JOIN tblstatus request_status ON request_status.id = t.statusid
         SET {$setSql}
         WHERE t.{$config['request_column']} = ?
           AND t.status = 1
           AND COALESCE(request_status.is_resolved, 0) = 0",
        'issi',
        [$replacementId, $replacement['nameen'], $replacement['namefr'], $itemId]
    );
    mysqli_stmt_close($statement);
}

function rmt_delete_notification_scopes(mysqli $link, array $serviceIds, array $subserviceIds): void {
    if (!rmt_db_table_exists($link, 'tblnotificationtemplates')) {
        return;
    }

    foreach ($subserviceIds as $subserviceId) {
        $statement = rmt_db_execute($link, 'DELETE FROM tblnotificationtemplates WHERE subservice_id = ?', 'i', [(int) $subserviceId]);
        mysqli_stmt_close($statement);
    }
    foreach ($serviceIds as $serviceId) {
        $statement = rmt_db_execute($link, 'DELETE FROM tblnotificationtemplates WHERE service_id = ?', 'i', [(int) $serviceId]);
        mysqli_stmt_close($statement);
    }
}

function rmt_delete_catalogue_hierarchy(
    mysqli $link,
    string $level,
    int $itemId,
    int $replacementId,
    int $parentId
): void {
    if (!rmt_db_column_exists($link, 'tbltriage', 'cataloguenameen')) {
        throw new RuntimeException('Database migration 025 must be applied before deleting catalogue items.');
    }

    mysqli_begin_transaction($link);
    try {
        $item = rmt_catalogue_delete_item($link, $level, $itemId);
        if ($item === null) {
            throw new RuntimeException('The catalogue item no longer exists.');
        }

        rmt_archive_catalogue_request_names($link, $level, $itemId);
        rmt_reassign_unresolved_catalogue_requests($link, $level, $itemId, $replacementId, $parentId);

        $serviceIds = [];
        $subserviceIds = [];
        if ($level === 'catalogue') {
            $services = mysqli_query($link, 'SELECT id FROM tblservices WHERE catalogueid = ' . $itemId);
            while ($service = mysqli_fetch_assoc($services)) {
                $serviceIds[] = (int) $service['id'];
            }
        } elseif ($level === 'service') {
            $serviceIds[] = $itemId;
        }

        if ($serviceIds !== []) {
            $serviceIdList = implode(',', $serviceIds);
            $subservices = mysqli_query($link, "SELECT id FROM tblsubservices WHERE serviceid IN ({$serviceIdList})");
            while ($subservice = mysqli_fetch_assoc($subservices)) {
                $subserviceIds[] = (int) $subservice['id'];
            }
        } elseif ($level === 'subservice') {
            $subserviceIds[] = $itemId;
        }

        rmt_delete_notification_scopes($link, $serviceIds, $subserviceIds);

        if ($subserviceIds !== []) {
            mysqli_query($link, 'DELETE FROM tblsubservices WHERE id IN (' . implode(',', $subserviceIds) . ')');
        }
        if ($serviceIds !== []) {
            mysqli_query($link, 'DELETE FROM tblservices WHERE id IN (' . implode(',', $serviceIds) . ')');
        }
        if ($level === 'catalogue') {
            $statement = rmt_db_execute($link, 'DELETE FROM tblcatalogue WHERE id = ?', 'i', [$itemId]);
            mysqli_stmt_close($statement);
        }

        mysqli_commit($link);
    } catch (Throwable $exception) {
        mysqli_rollback($link);
        throw $exception;
    }
}

function rmt_render_catalogue_delete_dialog(
    mysqli $link,
    string $level,
    int $itemId,
    int $parentId,
    string $lang,
    string $formAction
): void {
    $translations = require __DIR__ . "/../lang/{$lang}.php";
    $item = rmt_catalogue_delete_item($link, $level, $itemId);
    if ($item === null) {
        echo '<section id="filter-id" class="modal-dialog modal-content overlay-def">';
        echo '<header class="modal-header"><h2 class="modal-title">' . htmlspecialchars($translations['delete_catalogue_error_title']) . '</h2></header>';
        echo '<div class="modal-body"><p>' . htmlspecialchars($translations['delete_catalogue_error_message']) . '</p></div></section>';
        return;
    }

    if ($level === 'service') {
        $parentId = (int) $item['catalogueid'];
    } elseif ($level === 'subservice') {
        $parentId = (int) $item['serviceid'];
    }

    $nameColumn = ($lang === 'fr') ? 'namefr' : 'nameen';
    $name = (string) $item[$nameColumn];
    $count = rmt_catalogue_unresolved_request_count($link, $level, $itemId);
    $options = $count > 0
        ? rmt_catalogue_replacement_options($link, $level, $itemId, $parentId, $lang)
        : [];
    $title = sprintf($translations['delete_' . $level . '_title'], $name);
    $countMessage = sprintf(
        $translations[$count === 1 ? 'delete_catalogue_open_request_one' : 'delete_catalogue_open_request_many'],
        $count
    );
    ?>
<section id="filter-id" class="modal-dialog modal-content overlay-def">
    <header class="modal-header">
        <h2 class="modal-title"><?= htmlspecialchars($title) ?></h2>
    </header>
    <div class="modal-body">
        <form method="post" action="<?= htmlspecialchars($formAction) ?>">
            <p id="open-request-count"><?= htmlspecialchars($countMessage) ?></p>
            <p><?= htmlspecialchars($translations['delete_catalogue_archive_message']) ?></p>
            <p><strong><?= htmlspecialchars($translations['delete_catalogue_warning']) ?></strong></p>
            <?php if ($count > 0 && $options !== []): ?>
            <div class="form-group">
                <label for="replacement-id"><span class="field-name"><?= htmlspecialchars($translations['delete_catalogue_reassign_label']) ?></span></label>
                <select class="form-control" id="replacement-id" name="replacement_id" aria-describedby="open-request-count">
                    <option value="0"><?= htmlspecialchars($translations['delete_catalogue_keep_assignments']) ?></option>
                    <?php foreach ($options as $option): ?>
                    <option value="<?= (int) $option['id'] ?>"><?= htmlspecialchars($option['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <div class="form-group form-buttons">
                <button type="submit" class="btn btn-danger"><?= htmlspecialchars($translations['delete_catalogue_permanently']) ?></button>
                <button type="button" class="btn btn-default popup-modal-dismiss"><?= htmlspecialchars($translations['delete_catalogue_cancel']) ?></button>
            </div>
        </form>
    </div>
</section>
    <?php
}