# Plan 007: Local File Storage

**Status**: Partially implemented — local mode works; persistent volume for production not yet provisioned  
**Date Planned**: 2026-05-01  
**Last Updated**: 2026-07-14  
**Estimated Remaining Effort**: < 1 day (volume provisioning + entrypoint wiring)  
**Blocked by**: Persistent storage volume provisioned for the production hosting environment

## Overview

Azure Blob Storage was removed when the Azure VM deployment was retired. `app/BlobStorage.php` was updated to support local filesystem storage as a replacement. File uploads work in local development; production deployment requires a persistent volume to be mounted at the configured path.

## Current State

`AzureBlobStorageManager` supports three modes controlled by the `FILE_STORAGE_MODE` environment variable:

| Mode | Behaviour |
|---|---|
| `local` | Reads/writes files to `FILE_STORAGE_LOCAL_PATH` on the container filesystem |
| `azure_secret` | Azure Blob Storage via SAS token (legacy — not in use) |
| `disabled` | File uploads are fully disabled — upload UI is hidden, validator rejects any submission |

The default mode when `APP_ENV=production` and `FILE_STORAGE_MODE` is unset is `azure_secret`, so **`FILE_STORAGE_MODE` must be set explicitly in all environments**.

### Files that reference `AzureBlobStorageManager`

- `app/viewrequest.php` — displays file download/preview links
- `app/editrequest.php` (via `app/includes/editrequest-files-section.php`) — displays file links in edit view
- `app/includes/editrequest-processing.php` — handles file uploads when editing a request
- `app/openrequest3.php` — handles file uploads on new request submission
- `app/version-history.php` — requires the class (no method calls)

Database table `tblfiles` stores file metadata: `code` (unique filename), `name`, `type`, `size`, `date`, `requestid`.

## Environment Variables Reference

All file storage behaviour is controlled by these environment variables:

### `FILE_STORAGE_MODE`
**Required.** Controls the active storage backend.

| Value | Description |
|---|---|
| `local` | Store files on the local filesystem at `FILE_STORAGE_LOCAL_PATH` |
| `disabled` | Disable file uploads entirely — upload UI is hidden, no files are written |
| `azure_secret` | Azure Blob via SAS token (not in active use) |

- **Local dev default**: `local`
- **Production default if unset**: `azure_secret` — always set this explicitly in production

### `FILE_STORAGE_LOCAL_PATH`
**Required when `FILE_STORAGE_MODE=local`.** Absolute path to the upload directory inside the container.

- Must be outside the webroot (`/var/www/html`) — files are never served directly
- Must be writable by the web server process (`www-data`)
- **Must be a persistent volume in production** — the container filesystem is ephemeral on Azure App Service and similar platforms
- Default: `/var/uploads/rmt`

### `FILE_UPLOAD_MAX_FILES`
Maximum number of files allowed per upload submission.  
Default: `5`

### `FILE_UPLOAD_MAX_SIZE_MB`
Maximum size in MB per individual file.  
Default: `10`

## Production Setup (when volume is available)

Once a persistent volume is provisioned and mounted (e.g. at `/mnt/uploads/rmt`):

1. **Set env vars** in the hosting platform:
   ```
   FILE_STORAGE_MODE=local
   FILE_STORAGE_LOCAL_PATH=/mnt/uploads/rmt
   ```

2. **Ensure the directory exists and is writable** — add to `entrypoint.sh`:
   ```bash
   mkdir -p /mnt/uploads/rmt
   chown www-data:www-data /mnt/uploads/rmt
   ```

3. **For local Docker dev**, mount the volume in `docker-compose.yml`:
   ```yaml
   volumes:
     - rmt_uploads:/var/uploads/rmt
   ```

4. **Validate** — test file upload on a new request, file download from view/edit, and file delete from edit request.

## Disabling Uploads Temporarily (pre-production)

Set `FILE_STORAGE_MODE=disabled` to disable the feature entirely until persistent storage is available:

- The upload field is hidden on the new request form (`openrequest2.php`) and the edit request page (`editrequest-files-section.php`)
- Any upload submission that reaches the validator is rejected with a user-visible error
- Existing file records in `tblfiles` and download links for previously attached files are unaffected

## Notes

- No schema changes required — `tblfiles.code` already stores the unique filename used as the storage key.
- `app/download.php` serves files through a PHP controller using session-authorised file codes; it is not affected by the storage mode toggle.
- If the app is later deployed to a platform without persistent disk (e.g. Azure Container Apps), consider replacing with S3-compatible object storage (MinIO, Azure Blob, AWS S3) using the existing `AzureBlobStorageManager` interface.
