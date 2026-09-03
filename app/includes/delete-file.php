<?php
require_once('../sql.php');
require_once('../BlobStorage.php');
require_once('csrf.php');
require_once('helpers.php');
require_once('loggedincheck.php');

$lang = $_SESSION['lang'] ?? 'en';
$fileId = (int) ($_POST['file_id'] ?? 0);
$requestId = (int) ($_POST['request_id'] ?? 0);

if ($fileId <= 0 || $requestId <= 0 || !rmt_csrf_token_is_valid('file-delete', (string) ($_POST['csrf_token'] ?? ''))) {
    http_response_code(400);
    exit;
}

$file = rmt_db_fetch_one(
    $link,
    'SELECT f.*, t.catalogueid, t.serviceid, t.subserviceid, t.workerid
     FROM tblfiles f
     INNER JOIN tbltriage t ON t.requestid = f.requestid
     WHERE f.id = ? AND t.id = ?
     LIMIT 1',
    'ii',
    [$fileId, $requestId]
);

if ($file === null || !rmt_can_delete_file($link, $file)) {
    http_response_code(403);
    exit;
}

$storage = new AzureBlobStorageManager();
$deleted = $storage->deleteBlob((string) $file['code']);
if (!$deleted) {
    error_log('File deletion failed in storage for file ID ' . $fileId);
    http_response_code(502);
    exit('The file could not be deleted from storage. Please contact an administrator.');
}

$deleteStatement = rmt_db_execute($link, 'DELETE FROM tblfiles WHERE id = ? LIMIT 1', 'i', [$fileId]);
mysqli_stmt_close($deleteStatement);

header("Location: /editrequest.php?lang={$lang}&id={$requestId}&status=filesuccess&focus=files");
exit;