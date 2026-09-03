# Plan 007: File Storage Backends

- **Status**: Implemented; Azure App Service configuration remains environment-specific
- **Date Planned**: 2026-05-01
- **Last Updated**: 2026-08-25
- **Estimated Remaining Effort**: App Service private-network and SAS configuration
- **Blocked by**: Environment-specific Azure configuration and validation

## Overview

`app/BlobStorage.php` provides multiple storage backends behind one application interface. Local Docker uses a named filesystem volume mounted at `/var/uploads/rmt`. Azure App Service uses direct Azure Blob Storage with a container-scoped SAS and separate development and production containers.

## Current State

`AzureBlobStorageManager` supports four modes controlled by the `FILE_STORAGE_MODE` environment variable:

| Mode | Behaviour |
| --- | --- |
| `local` | Reads/writes files to `FILE_STORAGE_LOCAL_PATH` on the container filesystem |
| `azure_secret` | Azure Blob Storage via a container-scoped SAS token for App Service |
| `azure_mi` | Reserved for Azure managed identity; uploads and reads currently fail closed |
| `disabled` | File uploads are fully disabled — upload UI is hidden, validator rejects any submission |

The default is `local` outside production and `disabled` in production. Production deployments must explicitly choose and configure a persistent backend before uploads are enabled.

### Files that reference `AzureBlobStorageManager`

- `app/viewrequest.php` — displays file download/preview links
- `app/editrequest.php` (via `app/includes/editrequest-files-section.php`) — displays file links in edit view
- `app/includes/editrequest-processing.php` — handles file uploads when editing a request
- `app/openrequest3.php` — handles file uploads on new request submission
- `app/version-history.php` — requires the class (no method calls)

Database table `tblfiles` stores file metadata: `code` (unique filename), `name`, `type`, `size`, `uploadedby`, `status`, and `requestid`.

## Environment Variables Reference

All file storage behaviour is controlled by these environment variables:

### `FILE_STORAGE_MODE`

**Required.** Controls the active storage backend.

| Value | Description |
| --- | --- |
| `local` | Store files on the local filesystem at `FILE_STORAGE_LOCAL_PATH` |
| `disabled` | Disable file uploads entirely — upload UI is hidden, no files are written |
| `azure_secret` | Azure Blob via a container-scoped SAS token |
| `azure_mi` | Reserved; currently fails closed because managed identity is not implemented |

- **Local dev default**: `local`
- **Production default if unset**: `disabled`

### `FILE_STORAGE_LOCAL_PATH`

**Required when `FILE_STORAGE_MODE=local`.** Absolute path to the upload directory inside the container.

- Must be outside the webroot (`/var/www/html`) — files are never served directly
- Must be writable by the web server process (`www-data`)
- Must use a persistent volume in any deployed environment that selects `local` mode
- Default: `/var/uploads/rmt`

### `FILE_UPLOAD_MAX_FILES`

Maximum number of files allowed per upload submission.
Default: `5`

### `FILE_UPLOAD_MAX_SIZE_MB`

Maximum size in MB per individual file.
Default: `10`

## Azure App Service Setup

Follow the [Azure App Service Blob storage runbook](../AZURE-APP-SERVICE-BLOB-STORAGE.md). Configure each App Service with its environment-specific container and SAS. Do not configure `FILE_STORAGE_LOCAL_PATH` for direct Blob mode.

## Filesystem Deployment Alternative

For a deployment with a persistent filesystem mounted at `/mnt/uploads/rmt`:

1. **Set env vars** in the hosting platform:

   ```env
   FILE_STORAGE_MODE=local
   FILE_STORAGE_LOCAL_PATH=/mnt/uploads/rmt
   ```

2. **Ensure the directory exists and is writable.** The included `entrypoint.sh` handles the configured path:

   ```bash
   mkdir -p /mnt/uploads/rmt
   chown www-data:www-data /mnt/uploads/rmt
   ```

3. **For local Docker dev**, mount the volume in `docker-compose.yml`:

   ```yaml
   volumes:
     - rmt_uploads:/var/uploads/rmt
   ```

4. **Validate** - test file upload on a new request and file download from view/edit.

## Disabling Uploads Temporarily (pre-production)

Set `FILE_STORAGE_MODE=disabled` to disable the feature entirely until persistent storage is available:

- The upload field is hidden on the new request form (`openrequest2.php`) and the edit request page (`editrequest-files-section.php`)
- Any upload submission that reaches the validator is rejected with a user-visible error
- Existing file records in `tblfiles` and download links for previously attached files are unaffected

## Notes

- Migration `027-add-file-uploader.sql` adds `tblfiles.uploadedby`, allowing employees to delete their own uploads and managers/team leads to delete uploads within their scope.
- `app/download.php` serves files through a PHP controller that verifies access to the owning request; storage credentials and direct Blob URLs are never sent to the browser.
- `app/includes/delete-file.php` verifies CSRF, request access, uploader ownership, and role scope before deleting storage and metadata.
- Development and production Blob containers require separate SAS tokens and independent App Service settings.
