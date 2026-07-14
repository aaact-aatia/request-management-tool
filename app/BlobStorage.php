<?php
require_once __DIR__ . '/env.php';

/**
 * Storage manager supporting local filesystem and Azure Blob Storage.
 *
 * FILE_STORAGE_MODE:
 * - local        : default for local development
 * - azure_secret : Azure Blob with SAS token
 * - azure_mi     : reserved for managed identity (not enabled yet)
 */
class AzureBlobStorageManager
{
    private string $mode;
    private string $localPath;
    private string $azureAccount;
    private string $azureContainer;
    private string $azurePrefix;
    private string $azureSasToken;
    private string $azureEndpointSuffix;

    public function __construct()
    {
        $defaultMode = app_is_production() ? 'azure_secret' : 'local';
        $mode = strtolower(trim((string) app_env('FILE_STORAGE_MODE', $defaultMode)));
        if (!in_array($mode, ['local', 'azure_secret', 'azure_mi'], true)) {
            $mode = $defaultMode;
        }

        $this->mode = $mode;
        $this->localPath = rtrim((string) app_env('FILE_STORAGE_LOCAL_PATH', '/var/uploads/rmt'), '/');
        $this->azureAccount = trim((string) app_env('AZURE_STORAGE_ACCOUNT', ''));
        $this->azureContainer = trim((string) app_env('AZURE_STORAGE_CONTAINER', ''));
        $this->azurePrefix = trim((string) app_env('AZURE_STORAGE_PREFIX', ''));
        $this->azureSasToken = ltrim(trim((string) app_env('AZURE_STORAGE_SAS_TOKEN', '')), '?');
        $this->azureEndpointSuffix = trim((string) app_env('AZURE_STORAGE_ENDPOINT_SUFFIX', 'core.windows.net'));
    }

    public function getFileUrl(string $blobName): string
    {
        return '/download.php?code=' . urlencode($blobName);
    }

    public function getInlineFileUrl(string $blobName): string
    {
        return '/download.php?code=' . urlencode($blobName) . '&inline=1';
    }

    public function uploadFile(string $filePath, string $blobName): bool
    {
        if ($this->mode === 'azure_mi') {
            error_log('Azure managed identity upload mode is not implemented yet.');
            return false;
        }

        if ($this->mode === 'azure_secret') {
            return $this->uploadAzureWithSas($filePath, $blobName);
        }

        return $this->uploadLocal($filePath, $blobName);
    }

    public function readFile(string $blobName): ?string
    {
        if ($this->mode === 'azure_mi') {
            error_log('Azure managed identity read mode is not implemented yet.');
            return null;
        }

        if ($this->mode === 'azure_secret') {
            $url = $this->buildAzureBlobUrl($blobName, true);
            if ($url === null) {
                return null;
            }

            $content = @file_get_contents($url);
            return ($content === false) ? null : $content;
        }

        $path = $this->buildLocalPath($blobName);
        if (!is_file($path)) {
            return null;
        }

        $content = @file_get_contents($path);
        return ($content === false) ? null : $content;
    }

    public function getFileLastModified(string $blobName): ?string
    {
        if ($this->mode === 'azure_mi') {
            return null;
        }

        if ($this->mode === 'azure_secret') {
            $url = $this->buildAzureBlobUrl($blobName, true);
            if ($url === null) {
                return null;
            }

            $headers = @get_headers($url, true);
            if (!is_array($headers)) {
                return null;
            }

            foreach ($headers as $headerName => $headerValue) {
                if (!is_string($headerName) || strcasecmp($headerName, 'Last-Modified') !== 0) {
                    continue;
                }

                $rawValue = is_array($headerValue) ? (string) end($headerValue) : (string) $headerValue;
                $timestamp = strtotime($rawValue);
                if ($timestamp === false) {
                    return null;
                }

                return date('Y-m-d H:i', $timestamp);
            }

            return null;
        }

        $path = $this->buildLocalPath($blobName);
        if (!is_file($path)) {
            return null;
        }

        $timestamp = @filemtime($path);
        if ($timestamp === false) {
            return null;
        }

        return date('Y-m-d H:i', $timestamp);
    }

    public function deleteBlob(string $blobName): void
    {
        if ($this->mode === 'azure_secret') {
            // Delete is intentionally deferred for phase 1.
            return;
        }

        if ($this->mode === 'local') {
            $path = $this->buildLocalPath($blobName);
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }

    private function uploadLocal(string $filePath, string $blobName): bool
    {
        if (!is_dir($this->localPath) && !@mkdir($this->localPath, 0775, true) && !is_dir($this->localPath)) {
            error_log('Failed to create local upload directory: ' . $this->localPath);
            return false;
        }

        $destinationPath = $this->buildLocalPath($blobName);

        if (@move_uploaded_file($filePath, $destinationPath)) {
            return true;
        }

        return @copy($filePath, $destinationPath);
    }

    private function uploadAzureWithSas(string $filePath, string $blobName): bool
    {
        $url = $this->buildAzureBlobUrl($blobName, true);
        if ($url === null || !is_file($filePath)) {
            return false;
        }

        $fileHandle = fopen($filePath, 'rb');
        if ($fileHandle === false) {
            return false;
        }

        $fileSize = filesize($filePath);
        $mimeType = mime_content_type($filePath) ?: 'application/octet-stream';

        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_PUT => true,
            CURLOPT_INFILE => $fileHandle,
            CURLOPT_INFILESIZE => $fileSize,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'x-ms-blob-type: BlockBlob',
                'x-ms-version: 2021-12-02',
                'Content-Type: ' . $mimeType,
                'Content-Length: ' . $fileSize,
            ],
        ]);

        $response = curl_exec($curl);
        $statusCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);

        curl_close($curl);
        fclose($fileHandle);

        if ($response === false || $statusCode < 200 || $statusCode >= 300) {
            error_log('Azure upload failed for blob ' . $blobName . ': HTTP ' . $statusCode . ' ' . $error);
            return false;
        }

        return true;
    }

    private function buildLocalPath(string $blobName): string
    {
        $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', $blobName);
        return $this->localPath . '/' . $safeName;
    }

    private function buildAzureBlobUrl(string $blobName, bool $withSas): ?string
    {
        if ($this->azureAccount === '' || $this->azureContainer === '') {
            error_log('Azure blob configuration missing account or container.');
            return null;
        }

        if ($withSas && $this->azureSasToken === '') {
            error_log('Azure blob SAS token is not configured.');
            return null;
        }

        $fullName = $blobName;
        if ($this->azurePrefix !== '') {
            $fullName = trim($this->azurePrefix, '/') . '/' . ltrim($blobName, '/');
        }

        $encodedPath = implode('/', array_map('rawurlencode', explode('/', $fullName)));
        $baseUrl = sprintf(
            'https://%s.blob.%s/%s/%s',
            $this->azureAccount,
            $this->azureEndpointSuffix,
            rawurlencode($this->azureContainer),
            $encodedPath
        );

        if (!$withSas) {
            return $baseUrl;
        }

        return $baseUrl . '?' . $this->azureSasToken;
    }
}
