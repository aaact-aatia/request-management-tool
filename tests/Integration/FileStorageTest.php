<?php

$applicationRoot = is_file('/var/www/html/BlobStorage.php')
    ? '/var/www/html'
    : dirname(__DIR__, 2) . '/app';
require_once $applicationRoot . '/BlobStorage.php';
require_once $applicationRoot . '/includes/helpers.php';

$failures = 0;
$assertions = 0;

function check(bool $condition, string $message): void
{
    global $assertions, $failures;
    $assertions++;

    if (!$condition) {
        $failures++;
        echo "FAIL: {$message}\n";
        return;
    }

    echo "PASS: {$message}\n";
}

$storageDirectory = sys_get_temp_dir() . '/rmt-storage-' . bin2hex(random_bytes(6));
$sourcePath = tempnam(sys_get_temp_dir(), 'rmt-upload-');
if ($sourcePath === false) {
    throw new RuntimeException('Unable to create temporary upload fixture.');
}

file_put_contents($sourcePath, 'local storage test');
putenv('APP_ENV=testing');
putenv('FILE_STORAGE_MODE=local');
putenv('FILE_STORAGE_LOCAL_PATH=' . $storageDirectory);

$storage = new AzureBlobStorageManager();
check($storage->uploadFile($sourcePath, 'test-file.txt'), 'local upload succeeds');
check($storage->readFile('test-file.txt') === 'local storage test', 'local file round-trip succeeds');
check($storage->uploadFile($sourcePath, '../outside.txt') === false, 'unsafe storage key is rejected');
check(!file_exists(dirname($storageDirectory) . '/outside.txt'), 'unsafe key cannot escape storage directory');

$storage->deleteBlob('test-file.txt');
check($storage->readFile('test-file.txt') === null, 'local delete removes stored file');

putenv('FILE_STORAGE_MODE=disabled');
$disabledStorage = new AzureBlobStorageManager();
check($disabledStorage->uploadFile($sourcePath, 'disabled.txt') === false, 'disabled mode rejects upload');
check(!file_exists($storageDirectory . '/disabled.txt'), 'disabled mode writes no file');

putenv('APP_ENV=production');
putenv('FILE_STORAGE_MODE');
unset($_SERVER['FILE_STORAGE_MODE'], $_ENV['FILE_STORAGE_MODE']);
check(app_file_storage_mode() === 'disabled', 'production defaults to disabled storage');

putenv('APP_ENV=testing');
putenv('FILE_STORAGE_MODE=local');
putenv('FILE_UPLOAD_MAX_FILES=2');
putenv('FILE_UPLOAD_MAX_SIZE_MB=1');

$validUpload = [
    'name' => ['accepted.docx'],
    'tmp_name' => ['/tmp/accepted.docx'],
    'size' => [1024],
    'error' => [UPLOAD_ERR_OK],
];
$validResult = rmt_validate_uploaded_files($validUpload, 'en');
check(count($validResult['files']) === 1 && $validResult['errors'] === [], 'valid upload passes validation');

$invalidType = $validUpload;
$invalidType['name'] = ['blocked.exe'];
$invalidTypeResult = rmt_validate_uploaded_files($invalidType, 'en');
check(count($invalidTypeResult['files']) === 0 && str_contains($invalidTypeResult['errors'][0] ?? '', 'not allowed'), 'invalid extension is rejected');

$oversizedUpload = $validUpload;
$oversizedUpload['size'] = [2 * 1024 * 1024];
$oversizedResult = rmt_validate_uploaded_files($oversizedUpload, 'en');
check(count($oversizedResult['files']) === 0 && str_contains($oversizedResult['errors'][0] ?? '', 'exceeds'), 'oversized upload is rejected');

$tooManyUploads = $validUpload;
$configuredMaxFiles = rmt_file_upload_policy()['max_files'];
$tooManyUploads['name'] = array_fill(0, $configuredMaxFiles + 1, 'extra.docx');
$tooManyUploads['tmp_name'] = array_fill(0, $configuredMaxFiles + 1, '/tmp/extra.docx');
$tooManyUploads['size'] = array_fill(0, $configuredMaxFiles + 1, 1024);
$tooManyUploads['error'] = array_fill(0, $configuredMaxFiles + 1, UPLOAD_ERR_OK);
$tooManyResult = rmt_validate_uploaded_files($tooManyUploads, 'en');
check(count($tooManyResult['files']) === 0 && str_contains($tooManyResult['errors'][0] ?? '', 'maximum of ' . $configuredMaxFiles), 'upload count limit is enforced');

$failedUpload = $validUpload;
$failedUpload['error'] = [UPLOAD_ERR_INI_SIZE];
$failedResult = rmt_validate_uploaded_files($failedUpload, 'en');
check(str_contains($failedResult['errors'][0] ?? '', 'Upload failed'), 'PHP upload errors are reported');

$frenchInvalidTypeResult = rmt_validate_uploaded_files($invalidType, 'fr');
check(str_contains($frenchInvalidTypeResult['errors'][0] ?? '', 'Type de fichier'), 'French validation errors are returned');

@unlink($sourcePath);
@rmdir($storageDirectory);

echo "Passed: " . ($assertions - $failures) . "; Failed: {$failures}\n";
exit($failures === 0 ? 0 : 1);