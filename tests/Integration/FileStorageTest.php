<?php

$applicationRoot = is_file('/var/www/html/BlobStorage.php')
    ? '/var/www/html'
    : dirname(__DIR__, 2) . '/app';
require_once $applicationRoot . '/BlobStorage.php';

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

@unlink($sourcePath);
@rmdir($storageDirectory);

echo "Passed: " . ($assertions - $failures) . "; Failed: {$failures}\n";
exit($failures === 0 ? 0 : 1);