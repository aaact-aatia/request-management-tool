<?php
/**
 * BlobStorage Stub
 *
 * Azure Blob Storage has been removed. This stub keeps the app functional
 * while file storage is pending migration to local filesystem storage.
 *
 * See docs/future/ for the planned replacement implementation.
 *
 * Methods return safe no-op values so all pages load without errors.
 * File upload and download will be silently disabled until replaced.
 */
class AzureBlobStorageManager
{
    /**
     * Returns an empty string in place of a blob URL.
     * File links will render as href="" until local storage is implemented.
     */
    public function getFileUrl(string $blobName): string
    {
        return '';
    }

    /**
     * No-op upload. Returns false so callers can skip DB record creation.
     */
    public function uploadFile(string $filePath, string $blobName): bool
    {
        return false;
    }

    /**
     * No-op delete.
     */
    public function deleteBlob(string $blobName): void
    {
        // no-op
    }
}
