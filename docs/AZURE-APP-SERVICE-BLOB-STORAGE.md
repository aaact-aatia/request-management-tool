# Azure App Service Blob Storage

This runbook configures persistent request attachments for Azure App Service using an environment-specific Blob container.

Local Docker continues to use the filesystem backend. Azure App Service uses the application's direct Blob backend with a container-scoped shared access signature (SAS).

Do not store storage account keys, SAS tokens, connection strings, or complete signed URLs in this repository, screenshots, tickets, or terminal history.

## Required Azure Resources

Record the environment-specific values in an approved configuration system, not in this repository.

The following table describes the required resources.

| Resource | Requirement |
| --- | --- |
| Storage account | A `StorageV2` account approved for the application environment |
| Account type | `StorageV2` |
| Region | The approved deployment region |
| Public network access | Disabled |
| Blob public access | Disabled |
| Shared Key access | Enabled |
| Private endpoint | An approved endpoint for the `blob` subresource |
| Blob endpoint hostname | `<storage-account>.blob.core.windows.net` |
| Blob container | A private container dedicated to the environment |

Development and production must each have a dedicated container and an independently scoped SAS. Never reuse a container or SAS across environments.

## Verify Private Networking

Because public network access is disabled, the development App Service must reach Blob Storage through the private endpoint.

Confirm the following configuration with the cloud team:

1. The private endpoint targets the `blob` subresource and its connection state is `Approved`.
2. The App Service has VNet integration with a subnet that can route to the private endpoint.
3. Network security rules allow outbound HTTPS on port 443 from the App Service integration subnet.
4. The private DNS zone `privatelink.blob.core.windows.net` is linked to the App Service VNet.
5. If the VNet uses custom DNS, its DNS service forwards or resolves queries for `privatelink.blob.core.windows.net`.
6. The storage account DNS name resolves to the private endpoint address from the App Service network.

After the App Service is running, use its SSH console to check DNS without exposing credentials:

```sh
getent hosts <storage-account>.blob.core.windows.net
```

The result must contain a private IP address. Do not place a SAS token in an SSH command because it can remain in terminal history and diagnostic logs.

## Create a Container SAS

In the Azure portal:

Your Azure role must permit viewing the container and generating a service SAS. Generating a SAS signed with an account key requires permission to list the storage account keys. With public access disabled, Portal data browsing must also originate from an allowed private-network path and use appropriate Blob data-plane permissions.

1. Open the storage account for the target environment.
2. Go to **Data storage > Containers**.
3. Open the container dedicated to the target environment.
4. Open the container's **Shared access tokens** page.
5. Allow HTTPS only.
6. Select these permissions:
   - Read
   - Create
   - Write
7. Do not select Delete or List unless a separately reviewed feature requires them.
8. Choose the shortest practical expiry, no longer than 90 days. The account is configured to log SAS-policy violations; do not treat logging as enforcement.
9. Generate the SAS token.
10. Copy the SAS token into the App Service setting described below. Do not commit it or place it in tickets or chat.

The current application needs Read for downloads and metadata checks, and Create and Write for block-blob uploads. Add applies to append blobs and is not required. The application does not currently delete Azure blobs.

## Configure App Service Settings

In the target App Service, go to **Settings > Environment variables > App settings** and add or update these settings.

The following table defines the direct Blob configuration.

| Setting | Value | Deployment slot setting |
| --- | --- | --- |
| `FILE_STORAGE_MODE` | `azure_secret` | Enabled when deployment slots are used |
| `AZURE_STORAGE_ACCOUNT` | Environment-specific storage account name | Enabled when deployment slots are used |
| `AZURE_STORAGE_CONTAINER` | Environment-specific container name | Enabled when deployment slots are used |
| `AZURE_STORAGE_PREFIX` | Empty, unless a prefix was approved | Enabled when deployment slots are used |
| `AZURE_STORAGE_SAS_TOKEN` | Container SAS token with Read, Create, Write, and Delete permissions | Enabled when deployment slots are used |
| `AZURE_STORAGE_ENDPOINT_SUFFIX` | `core.windows.net` | Enabled when deployment slots are used |
| `FILE_UPLOAD_MAX_FILES` | `5` | Enabled when deployment slots are used |
| `FILE_UPLOAD_MAX_SIZE_MB` | `10` | Enabled when deployment slots are used |

The application accepts the SAS token with or without its leading question mark. Store only the token, not the full container URL.

`FILE_STORAGE_LOCAL_PATH` is not used in `azure_secret` mode and does not need to be configured in App Service.

Save the settings and restart the App Service.

Repeat this configuration separately for every environment. Use its dedicated container and SAS; do not copy values between environments.

## Validate Storage

Validate through the application rather than exposing the SAS token in command-line tests:

1. Open a test request in the target environment and upload a small permitted file.
2. Confirm the upload succeeds and a corresponding row exists in `tblfiles`.
3. In the Azure portal, confirm the new blob appears only in the expected container.
4. Download the attachment from the request view and compare it with the original.
5. Restart the App Service.
6. Download the same attachment again to confirm persistence outside the container instance.
7. Select multiple attachments and confirm **Download all** returns one ZIP archive.

## Troubleshooting

Use the following symptoms to identify the likely configuration problem.

| Symptom | Likely cause | Check |
| --- | --- | --- |
| Upload controls are hidden | Storage mode is disabled or invalid | `FILE_STORAGE_MODE=azure_secret` |
| Upload returns an error immediately | Missing or incorrect Blob setting | Account, container, and SAS settings |
| Connection times out | App Service cannot reach the private endpoint | VNet integration, routes, network security rules, and port 443 |
| DNS resolves publicly | Private DNS is missing or not linked | `privatelink.blob.core.windows.net` VNet link and A record |
| Azure returns 403 | SAS is expired, scoped incorrectly, or missing permission | Container scope, expiry, and Read/Create/Write permissions |
| Upload works but download fails | SAS lacks Read permission | Regenerate the environment's container SAS with Read permission |

Set `FILE_STORAGE_MODE=disabled` while network or credential problems are corrected. Never temporarily enable public Blob access to work around private endpoint configuration.
