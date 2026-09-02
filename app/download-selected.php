<?php
require_once('sql.php');
require_once('BlobStorage.php');
require_once('includes/helpers.php');

/** @var mysqli $link */

$requestedCodes = $_GET['codes'] ?? [];
if (!is_array($requestedCodes)) {
    http_response_code(400);
    exit;
}

$fileCodes = array_values(array_unique(array_filter(array_map(
    static fn($code): string => trim((string) $code),
    $requestedCodes
))));

if ($fileCodes === []) {
    http_response_code(400);
    exit;
}

$storage = new AzureBlobStorageManager();
$filesToArchive = [];
$usedNames = [];
$archiveRequestId = null;

foreach ($fileCodes as $fileCode) {
    $file = rmt_db_fetch_one(
        $link,
        'SELECT f.*, t.catalogueid, t.serviceid, t.subserviceid, t.workerid
         FROM tblfiles f
         INNER JOIN tbltriage t ON t.requestid = f.requestid
         WHERE f.code = ?
         LIMIT 1',
        's',
        [$fileCode]
    );

    if ($file === null) {
        http_response_code(404);
        exit;
    }

    if (!rmt_can_access_request($link, $file)) {
        http_response_code(403);
        exit;
    }

    $fileRequestId = (string) ($file['requestid'] ?? '');
    if ($archiveRequestId === null) {
        $archiveRequestId = $fileRequestId;
    } elseif ($fileRequestId !== $archiveRequestId) {
        http_response_code(400);
        exit;
    }

    $originalName = basename((string) ($file['name'] ?? 'attachment'));
    $safeName = preg_replace('/[^A-Za-z0-9_.-]/', '_', $originalName) ?: 'attachment';
    $candidateName = $safeName;
    $suffix = 2;
    while (isset($usedNames[strtolower($candidateName)])) {
        $pathInfo = pathinfo($safeName);
        $baseName = $pathInfo['filename'] ?? 'attachment';
        $extension = isset($pathInfo['extension']) ? '.' . $pathInfo['extension'] : '';
        $candidateName = $baseName . '-' . $suffix . $extension;
        $suffix++;
    }

    $usedNames[strtolower($candidateName)] = true;
    $filesToArchive[] = [
        'code' => $fileCode,
        'name' => $candidateName,
    ];
}

$temporaryPath = tempnam(sys_get_temp_dir(), 'rmt-download-');
if ($temporaryPath === false) {
    http_response_code(500);
    exit;
}

@unlink($temporaryPath);
$archivePath = $temporaryPath . '.zip';

try {
    $archive = new PharData($archivePath, 0, null, Phar::ZIP);
    foreach ($filesToArchive as $fileToArchive) {
        $content = $storage->readFile($fileToArchive['code']);
        if ($content === null) {
            unset($archive);
            @unlink($archivePath);
            http_response_code(404);
            exit;
        }

        $archive->addFromString($fileToArchive['name'], $content);
        unset($content);
    }
    unset($archive);

    $safeRequestId = preg_replace('/[^A-Za-z0-9_.-]/', '_', (string) $archiveRequestId) ?: 'request';
    $archiveFileName = 'a11y-' . $safeRequestId . '-attachments.zip';

    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $archiveFileName . '"');
    header('Content-Length: ' . filesize($archivePath));
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    if (ob_get_level() > 0) {
        ob_end_clean();
    }

    readfile($archivePath);
} catch (Throwable $exception) {
    error_log('Attachment archive creation failed: ' . $exception->getMessage());
    if (!headers_sent()) {
        http_response_code(500);
    }
} finally {
    @unlink($archivePath);
}

exit;