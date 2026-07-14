<?php
require_once('sql.php');
require_once('BlobStorage.php');
require_once('includes/helpers.php');

/** @var mysqli $link */

require_once __DIR__ . '/includes/session_start.php';

if (!isset($_GET['code']) || trim((string) $_GET['code']) === '') {
    http_response_code(400);
    exit;
}

$fileCode = mysqli_real_escape_string($link, trim((string) $_GET['code']));
$inlineRequested = isset($_GET['inline']) && $_GET['inline'] === '1';

$resultFiles = mysqli_query($link, "SELECT * FROM tblfiles WHERE code = '$fileCode' LIMIT 1");
if (!$resultFiles) {
    error_log('Error fetching file metadata: ' . mysqli_error($link));
    http_response_code(500);
    exit;
}

if (mysqli_num_rows($resultFiles) === 0) {
    http_response_code(404);
    exit;
}

$file = mysqli_fetch_assoc($resultFiles);
$originalFileName = (string) ($file['name'] ?? 'download.bin');
$fileExtension = strtolower((string) ($file['type'] ?? ''));
$sanitizedFileName = sanitizeFileName($originalFileName);

$blobStorage = new AzureBlobStorageManager();
$content = $blobStorage->readFile($fileCode);
if ($content === null) {
    http_response_code(404);
    exit;
}

$mimeType = detectMimeTypeFromExtension($fileExtension);
$disposition = 'attachment';
if ($inlineRequested && in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'], true)) {
    $disposition = 'inline';
}

header('Content-Type: ' . $mimeType);
header('Content-Disposition: ' . $disposition . '; filename="' . $sanitizedFileName . '"');
header('Content-Length: ' . strlen($content));
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

if (ob_get_level() > 0) {
    ob_end_clean();
}

echo $content;
exit;

// Function to sanitize the file name
function sanitizeFileName($fileName) {
    // Replace spaces, apostrophes, and dashes with underscores
    $sanitizedName = preg_replace('/[\'\s-]/', '_', $fileName);
    // Remove any other special characters
    $sanitizedName = preg_replace('/[^A-Za-z0-9_.]/', '', $sanitizedName);
    return $sanitizedName;
}

function detectMimeTypeFromExtension(string $extension): string {
    $mimeMap = [
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'ppt' => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'txt' => 'text/plain',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'bmp' => 'image/bmp',
        'webp' => 'image/webp',
        'svg' => 'image/svg+xml',
    ];

    return $mimeMap[$extension] ?? 'application/octet-stream';
}
?>
