# Future Plan 007: Replace Azure Blob Storage with Local File Storage

**Status**: Planned — Future Work  
**Date Planned**: 2026-05-01  
**Estimated Effort**: 1–2 days  
**Blocked by**: Hosting environment decision (local disk vs. managed object storage)

## Overview

Azure Blob Storage was removed when the Azure VM deployment was retired. A stub `app/BlobStorage.php` was left in place so the app loads cleanly, but file upload and download are currently silently disabled (`uploadFile()` returns `false`, `getFileUrl()` returns `''`).

## Current State

The `AzureBlobStorageManager` class is referenced in:

- `app/viewrequest.php` — displays file download/preview links
- `app/editrequest.php` (via `app/includes/editrequest-files-section.php`) — displays file links in edit view
- `app/includes/editrequest-processing.php` — handles file uploads when editing a request
- `app/openrequest3.php` — handles file uploads on new request submission
- `app/version-history.php` — requires the class (no method calls)

Database table `tblfiles` stores file metadata: `code` (unique filename), `name`, `type`, `size`, `date`, `requestid`.

## Proposed Replacement: Local Filesystem

Store uploaded files in a directory outside the webroot, served through a PHP download controller.

### Directory Structure

```
/var/uploads/rmt/          ← outside /var/www/html, not web-accessible
  REQ-26-001-abc123.pdf
  REQ-26-002-def456.docx
```

### Implementation Steps

1. **Create upload directory** — add to `entrypoint.sh`:
   ```bash
   mkdir -p /var/uploads/rmt
   chown www-data:www-data /var/uploads/rmt
   ```

2. **Add env var** for upload path in `.env` / `.env.example`:
   ```
   UPLOAD_PATH=/var/uploads/rmt
   ```

3. **Replace `AzureBlobStorageManager`** in `app/BlobStorage.php` with a `LocalFileStorageManager` class (keep the same class name to avoid touching all call sites):
   ```php
   class AzureBlobStorageManager
   {
       private string $uploadPath;

       public function __construct()
       {
           $this->uploadPath = rtrim($_ENV['UPLOAD_PATH'] ?? '/var/uploads/rmt', '/');
       }

       public function uploadFile(string $filePath, string $blobName): bool
       {
           return move_uploaded_file($filePath, $this->uploadPath . '/' . $blobName);
       }

       public function getFileUrl(string $blobName): string
       {
           return '/download.php?code=' . urlencode($blobName);
       }

       public function deleteBlob(string $blobName): void
       {
           $path = $this->uploadPath . '/' . $blobName;
           if (file_exists($path)) {
               unlink($path);
           }
       }
   }
   ```

4. **Update `app/download.php`** — currently uses `$_GET['code']` to look up the file; update to serve from `UPLOAD_PATH` instead of Azure:
   ```php
   $code = mysqli_real_escape_string($link, $_GET['code']);
   $result = mysqli_query($link, "SELECT * FROM tblfiles WHERE code = '$code'");
   if ($row = mysqli_fetch_assoc($result)) {
       $filePath = rtrim($_ENV['UPLOAD_PATH'], '/') . '/' . $row['code'];
       if (file_exists($filePath)) {
           header('Content-Type: application/octet-stream');
           header('Content-Disposition: attachment; filename="' . $row['name'] . '"');
           readfile($filePath);
           exit;
       }
   }
   http_response_code(404);
   ```

5. **Update Docker Compose** to mount the upload volume:
   ```yaml
   volumes:
     - ./app:/var/www/html
     - .env:/var/www/html/.env
     - rmt_uploads:/var/uploads/rmt   # ← add this
   ```

6. **Validate** — test file upload on new request, file download from view/edit request, file delete from edit request.

## Notes

- No changes needed to `tblfiles` schema — the `code` column already stores the unique filename used as the storage key.
- If this app is later deployed to a cloud platform without persistent disk (e.g., Azure Container Apps, AWS ECS), consider replacing with S3-compatible object storage (MinIO, Azure Blob, AWS S3) using the same interface.
