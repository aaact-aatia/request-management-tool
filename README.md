# Request Management Tool
Version: 2.0.0  
Last updated: 2026-05-25  
Author: Muna Adan
Editor: Shawn Thompson

## Overview
The RMT is a multi-page PHP web application backed by MySQL that handles the administration of accessibility requests from clients. A request comes in via the `new-request` page and can be assigned a status, client, and attached to an employee to triage and/or fulfill the client request.

The RMT is built on top of the WET framework (Web Experience Toolkit) by the treasury board of Canada.

The life cycle of a request is as follows:

**Under construction**

1. A user makes as request via the RMT. They select a category (called a catalogue in the code), a service, and potential one of two sub services, depending on each category.
2. An administrator is then responsible for initial triage and assigning an employee to the request. The types of requests are:
```
Client Needs Assessment
Adaptive Technology Support
Loan B ank Services
A11y (accessibility) services
ACP
ICT audits
Doc audits
Advice and recommendations
Procurement
EPMO
```


### Technologies
1. PHP 8.2
2. JavaScript (jQuery)
3. MySQL 5.7
4. WET4 (Web Experience Toolkit)
5. Docker

## Local Development Setup

### Prerequisites
- Docker and Docker Compose installed on your machine
- Git for version control

### Environment Configuration

The application uses environment variables for configuration. Before running the application, you need to set up your `.env` file:

1. **Copy the example environment file:**
   ```bash
   cp .env.example .env
   ```

2. **Edit the `.env` file** with your local settings:
   ```env
   # Database Configuration
   DB_HOST=aaact-rmt-db          # Docker MySQL container name
   DB_USER=your_database_user     # Your database username
   DB_PASS=your_database_password # Your database password
   DB_NAME=aaact                  # Database name
   MYSQL_ROOT_PASSWORD=your_mysql_root_password

   # Application Configuration
   PORT=8080                      # Local development port

   # Timezone Configuration
   TZ=America/New_York            # Required for SLA calculations
   ```

3. **Important:** Never commit your actual `.env` file to version control. Only `.env.example` should be committed.

### Running the Application with Docker

The application is containerized using Docker for consistent development environments:

1. **Start the application:**
   ```bash
docker compose up -d
   ```

   This command will:
   - Build the PHP 8.2 Apache container
   - Start a MySQL 5.7 database container
    - Run the app on the `PORT` value from `.env` (default `8080`)
   - Mount your local `app/` directory for live code updates
    - Initialize MySQL from split SQL files (first run only):
      - `database/schema.sql`
      - `database/reference.sql`
      - `database/sample-dev.sql`

2. **Access the application:**
    - Open your browser to `http://localhost:${PORT}`.
    - Example local URL: `http://localhost:8080`

3. **View logs:**
   ```bash
docker compose logs -f
   ```

4. **Stop the application:**
   ```bash
docker compose down
   ```

5. **Rebuild containers (after Dockerfile changes):**
   ```bash
docker compose up -d --build
   ```

### Database Bootstrap Files

The repository contains database bootstrap files with distinct responsibilities:

- `database/schema.sql`: database structure only (`CREATE TABLE`, indexes, and constraints)
- `database/reference.sql`: production-safe lookup and configuration data required by the app
- `database/sample-dev.sql`: local/development sample data only (non-production)

### Local Initialization Behavior

On first MySQL container initialization in local Docker, the split files are imported in this order:

1. `database/schema.sql`
2. `database/reference.sql`
3. `database/sample-dev.sql`

Important: MySQL init files run only when the `dbdata` volume is created. If the volume already exists, `docker compose up` will not re-run imports.

To force re-initialization and re-import bootstrap SQL files:

```bash
docker compose down -v && docker compose up -d --build
```

The `-v` flag removes the `dbdata` volume.

### Sample Local Users

#### Test Users (all passwords: `password`)

| Email | Account Type | Description |
|-------|-------------|-------------|
| superadmin@example.com | Super Admin (1) | Full system access, can delete requests |
| admin@example.com | Admin (2) | Administrative access |
| manager@example.com | Manager (3) | Can edit team requests |
| tl@example.com | Team Lead (4) | Can edit team requests |
| employee@example.com | Employee (5) | Regular employee access |
| external@example.com | Director (6) | Read-only reporting access |

### Docker Services

The `docker-compose.yml` defines two services:

- **web**: PHP 8.2 with Apache, includes mysqli extension and Composer
- **db**: MySQL 5.7 database with persistent volume storage

### Database Connection

Database connections are handled through:
- `sql.php` — Sets up session management, CORS, and includes `db.php`
- `db.php` — Establishes mysqli connection using `app_env_required()` for DB credentials
- `env.php` — Runtime environment helpers (`app_env()`, `app_env_required()`, `app_is_production()`)

All PHP files requiring database access should include:
```php
require('sql.php');  // This gives you access to $link
```

In local development, set credentials in your `.env` file and Docker Compose injects them as environment variables. In production (Azure App Service), credentials come from Application Settings — no `.env` file is used.

## Data Model

The application's data model consists of the following tables:

1.  **tblaccounttype**: Stores account type information for logged-in users, including the account type description in English and French.
2.  **tbladminlog**: Logs administrative activities or actions performed by administrators or privileged users. Each log entry includes the admin's ID, the request being triaged, and its corresponding status.
3.  **tblcatalogue**: Contains a catalog or directory of services offered by the RMT (Request Management Tool). The data from this table populates the first dropdown on the "New Request" page.
4.  **tblcommlog**: Logs non-administrative communication-related activities within the application, such as messages, notifications, or emails sent.
5.  **tblteams**: Stores team information and escalation contacts used for request routing and team assignment.
6.  **tblcss**: Stores feedback collected from the RMT tool.
7.  **tblservices**: Contains details about various services offered or managed by the application. In some cases, the data from this table populates the second dropdown on the "New Request" page.
8.  **tblsources**: Relates to sub-services used within the application. In some cases, the data from this table populates the third dropdown on the "New Request" page.
9.  **tblstatus**: Stores a list of possible states or statuses that an RMT request can have, including an admin-configurable `is_resolved` flag used by request workflow logic.
10.  **tblsubservices**: Contains information about sub-services of the main services offered by the application. The data from this table populates the second dropdown on the "New Request" page.
11.  **tbltriage**: Used for triaging or prioritizing tasks, issues, or requests within the application.
12.  **tblusers**: Stores user account information and authentication data for the application's users.

### Status Admin Option: Resolved Trigger

In Status Admin (`status.php`), administrators can mark a status as the resolved trigger via the **Use this status as Resolved** option when adding or editing a status.

This setting controls request workflow behavior in edit processing, including:
- whether setting that status auto-populates the resolved date when empty
- whether transitioning into that status triggers the survey-link flow

By default in seed/reference data, the "Resolved / Résolu" row is configured as the resolved trigger.

This documentation provides an overview of the purpose and usage of each table within the application's data model. It serves as a reference for understanding the relationships between different data entities and their roles in supporting the application's functionality.

## SLA Calculation

SLA setup and calculation behavior is documented in detail here:

- [docs/sla-calculation.md](docs/sla-calculation.md)

Quick notes:

- SLA elapsed time is calculated in business days from status history.
- Weekends and active holidays in `tblholidays` are excluded.
- On hold and pending-like statuses pause SLA elapsed counting.
- Status changes can log SLA snapshots (clock start, due date, elapsed days) when StatusHistory columns are present.
- Existing databases should run migration `database/migrations/011-add-statushistory-audit-sla-columns.sql`.

## AJAX actions

The RMT takes actions via AJAX to fill in extra information required to search or add a new request.

For example, this is the flow of selecting a sub-service ID for a given catalogue with added comments.

*note*: Comments like the ones below would help developers onboard onto the project faster.

```php
<?php
/**
 * Sub-Service Dropdown Generator
 *
 * This script is responsible for generating an HTML dropdown menu populated
 * with active sub-services based on a provided service ID. It is used as part of
 * the "add request" feature or form in the application.
 *
 * Dependencies:
 * - sql.php (included for database connection and utility functions)
 *
 * Flow:
 * 1. Retrieve the service ID from the GET request parameter 'v1'
 * 2. Construct an SQL query to fetch active sub-services for the given service ID
 * 3. Execute the query and generate the HTML dropdown menu
 * 4. Populate the dropdown options with sub-service names from the query results
 * 5. Render the results on the page
 */

require('sql.php');

// Grab the catalogue id
if (!empty($_GET['v1'])) {
    $serviceid = mysqli_real_escape_string($link, $_GET['v1']);
} else {
    $serviceid = "";
}

// Check if results, otherwise return empty result
$sql = "SELECT * FROM tblsubservices WHERE serviceid='$serviceid' AND status='1' ORDER BY nameen ASC";
$result = mysqli_query($link, $sql);

// List it
if (mysqli_num_rows($result) > 0) {
?>
    <label for="subserviceid"><span class="field-name">Sub-service name:</span></label>
    <select class="form-control" id="subserviceid" name="subserviceid">
        <option value="">Select a sub-service name</option>
        <?php
        $sql2 = "SELECT * FROM tblsubservices WHERE serviceid='$serviceid' AND status='1' ORDER BY nameen ASC";
        $result2 = mysqli_query($link, $sql2);
        while ($row = mysqli_fetch_array($result2)) {
        ?>
            <option value="<?php echo $row['id']; ?>"><?php echo $row['nameen']; ?></option>
        <?php
        }
        ?>
    </select>
<?php
}

// Close connection
mysqli_close($link);
?>
```

## Recommendations

To improve the maintainability, scalability, and overall health of the application, the following recommendations are proposed:

### 1. Initiate a Refactoring Process

It is recommended to initiate a refactoring process to address any outstanding technical debt and reduce the administrative burden on staff. This refactoring should focus on:

-   Untangling the tightly coupled and hardcoded application logic, making it more modular and easier to modify.
-   Transitioning from hardcoded logic to a more flexible and extensible architecture that utilizes POST requests to pass necessary information, thereby enhancing the code's editability.

The refactoring effort is expected to increase bug resolution and feature development velocity by improving the codebase's overall quality and maintainability.

### 2. Implement a Staged Deployment Process

To mitigate the risk of introducing bugs or downtime during the refactoring process, it is recommended to implement a staged deployment process with the following steps:

1.  **Pilot Phase**: Conduct a pilot or proof-of-concept implementation on a separate deployment environment to validate the refactoring approach and identify potential issues before applying changes to the production environment.
2.  **Tiered Environment System**: Establish a tiered environment system comprising separate instances for development, testing, staging, and production. This will ensure that new development and bug fixes can be thoroughly tested and validated before being deployed to the production environment, minimizing the risk of negative impact on uptime.

### 3. Evaluate Modern Web Frameworks

To enhance developer productivity and leverage industry-standard best practices, it is recommended to evaluate modern web frameworks like Laravel for potential adoption. Laravel offers a robust set of features and tools that can streamline the development process, improve code organization, and facilitate easier maintenance and scalability.

### 4. Implement Better Git Practices

To maintain a clean and organized codebase, it is essential to implement more streamlined git practices.

This includes:

-   Adopting a consistent branching strategy (e.g., Git Flow or Trunk-Based Development) to manage code changes and releases.
-   Enforcing code reviews and merging policies to ensure code quality and prevent regressions.
-   Adhering to commit message conventions for better traceability and understanding of code changes.
-   Leveraging continuous integration and automated testing to catch issues early in the development cycle.

### 5. Deployment

The application is deployed as a custom container on **Azure App Service** (Linux).

The CI/CD model is:

- GitHub Actions builds Docker images.
- GitHub Actions publishes images to GitHub Container Registry (GHCR).
- GitHub Actions does not deploy directly to Azure App Service.
- Azure App Service pulls the selected image from GHCR.
- The publish workflow logs into Azure and restarts the matching App Service after image publish:
    - Push to `dev` restarts the configured dev App Service.
    - Push to `main` restarts the configured prod App Service.

Branch-to-image mapping:

- Pushing to `main` → builds and pushes `ghcr.io/aaact-aatia/request-management-tool:prod`
- Pushing to `dev` → builds and pushes `ghcr.io/aaact-aatia/request-management-tool:dev`

#### GitHub Actions workflow settings required for restart automation

Configure these repository **Secrets**:

- `AZUREAPPSERVICE_CLIENTID`
- `AZUREAPPSERVICE_TENANTID`
- `AZUREAPPSERVICE_SUBSCRIPTIONID`

Configure these repository **Variables**:

- `AZURE_RESOURCE_GROUP`
- `AZURE_WEBAPP_NAME_DEV`
- `AZURE_WEBAPP_NAME_PROD`

The branch controls which web app is restarted:

- `dev` branch → `AZURE_WEBAPP_NAME_DEV`
- `main` branch → `AZURE_WEBAPP_NAME_PROD`

If any required restart setting is missing, the publish workflow still builds and pushes the image and logs a warning that App Service restart was skipped.

### Database Import Order by Environment

Production database bootstrap order:

1. `database/schema.sql`
2. `database/reference.sql`

Development database bootstrap order:

1. `database/schema.sql`
2. `database/reference.sql`
3. `database/sample-dev.sql`

#### Azure App Service — Required Application Settings

Configure the following under **Settings → Environment variables** in the Azure Portal (or via Azure CLI `az webapp config appsettings set`):

| Setting | Example value | Notes |
|---|---|---|
| `APP_ENV` | `production` | Controls error display and env-missing behaviour |
| `DB_HOST` | `your-server.mysql.database.azure.com` | Azure Database for MySQL Flexible Server hostname |
| `DB_PORT` | `3306` | Default; omit to accept the default |
| `DB_USER` | `rmtuser` | MySQL login |
| `DB_PASS` | *(secret)* | MySQL password |
| `DB_NAME` | `aaact` | Database name |
| `DB_SSL_MODE` | `REQUIRED` | Use `REQUIRED` for Azure MySQL |
| `DB_SSL_CA` | *(path or empty)* | Optional CA bundle path for TLS trust |
| `TZ` | `America/Toronto` | Timezone for SLA calculations |
| `WEBSITES_PORT` | `80` | Required for Azure App Service custom container port mapping |
| `CORS_ALLOWED_ORIGINS` | `https://your-app.azurewebsites.net` | Comma-separated allowed origins |
| `GCNOTIFY_API_KEY` | *(secret)* | GC Notify API key for the current environment |
| `NOTIFY_MODE` | `live` | `live`, `redirect`, or `disabled`; production should use `live` |
| `NOTIFY_OVERRIDE_EMAIL` | *(optional email)* | Fallback redirect recipient used in non-production when no logged-in user email is available |
| `NOTIFY_OVERRIDE_CLIENT_EMAIL` | *(optional email)* | Client-specific redirect target for non-production testing |
| `NOTIFY_OVERRIDE_INTERNAL_EMAIL` | *(optional email)* | Internal/team redirect target for non-production testing |

Also configure the container registry under **Deployment Center** → **Registry settings**:
- Registry: `ghcr.io`
- Image: `aaact-aatia/request-management-tool`
- Tag: `prod` (or `dev` for staging)
- Authentication: use a GitHub Personal Access Token (PAT) with `read:packages` scope as the registry password.

For development environments, prefer `NOTIFY_MODE=redirect` so request notifications go to a safe test inbox instead of real client or team addresses. The redirect target defaults to the logged-in user's email when available and otherwise falls back to the configured override addresses.

#### Known limitations

- **File storage**: File upload/download is stubbed (`BlobStorage.php` is a no-op). Uploaded files are not persisted. See `docs/future/007-local-file-storage.md`.
- **Sessions**: Default PHP file-based sessions do not share state across multiple App Service instances. Enable sticky sessions (ARR Affinity) in the Azure Portal or switch to a shared session store before scaling out.
- **Current Azure blocker as of setup**: Existing Azure App Services must be configured or recreated as Linux Web App for Containers so the GHCR image runs as the main application container. The current sidecar-only container option is not sufficient for this deployment model.

### Details About RMT
    Project Structure:
        Each administrative role (such as contact, user, sources, status, subservice, catalogue, and service) has its own PHP file.
        These files contain CRUD (Create, Read, Update, Delete) operations specific to each role.
        Additionally, there are static pages (like the footer, header, and navigation bar) within a certain folder.
        The folder also contains an algorithm (matrix) for calculating business days to estimate request completion time.
    Webpages and APIs:
        Outside the administrative role folder, there are various webpages presented on the website.
        Some PHP files act as APIs.
        Let’s focus on two specific scenarios involving APIs:
            addrequest:
                Composed of two APIs: “ajax1” and “ajax2.”
                These APIs work together on the advanced search page (triage) called “Search Requests.”
                After selecting a specific catalogue, users can choose services related to that catalogue.
                If no services exist for the selected catalogue, the second API (“ajax2”) won’t be called.
            addrequest2:
                Composed of four APIs.
                Used for creating a new request (in openrequest.php).
                    Here are the steps:
                        Choose a product/topic. If the product contains services, proceed to step 2.
                        Select services related to the chosen topic. If a service requires additional information, move to step 3.
                        Make a choice related to the presented service. If more information is needed, proceed to step 4.
                        Choose the answer that best fits the situation.
                        After completing these steps, users are directed to openrequest2 and then openrequest3.
        Additional Notes:
            The project structure may be complex, so detailed explanations are helpful for developers.