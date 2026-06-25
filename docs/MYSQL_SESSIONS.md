# MySQL-Backed Session Handler

## Why This Was Added

This app now stores PHP sessions in MySQL so authentication state is not tied to local container files.

This addresses Azure App Service scenarios where request routing may pass through additional proxy/WAF paths and does not consistently expose ARRAffinity cookies. With MySQL-backed sessions, all app instances share the same session store.

## Session Storage Table

Session data is stored in tblphp_sessions.

```sql
CREATE TABLE IF NOT EXISTS tblphp_sessions (
  id VARCHAR(128) PRIMARY KEY,
  data LONGBLOB NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  accessed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY accessed_at (accessed_at)
);
```

Notes:
- id uses VARCHAR(128) to support different PHP session id lengths.
- accessed_at is indexed for efficient garbage collection.

## Code Location

- Handler class: app/includes/MySQLSessionHandler.php
- Session bootstrap: app/includes/session.php
- Bootstrap wiring: app/sql.php
- SQL file: database/session_handler.sql

## Runtime Behavior by Environment

- Production (APP_ENV=production): fail fast if MySQL session storage cannot be used.
  - Missing session table, DB/session bootstrap errors, or handler registration failures return HTTP 500 and log an error.
- Local/dev/test: log an error and allow fallback to default file-based PHP sessions.

This keeps production behavior explicit and avoids silent degradation.

## Deployment and Migration

### Local Docker Compose

docker-compose.yml mounts database/session_handler.sql into MySQL init scripts.

Important: MySQL init scripts only run when the local MySQL data volume is first initialized.
They do not re-run automatically for existing volumes.

### Azure / Managed MySQL

For Azure App Service + Azure MySQL (or any managed DB), you must run database/session_handler.sql separately as part of deployment or migrations.

Example:

```bash
mysql -u <user> -p <database> < database/session_handler.sql
```

Do not rely on docker-compose init behavior for Azure.

## Validation and Operations

Check that table exists:

```sql
SHOW TABLES LIKE 'tblphp_sessions';
```

Check active row count:

```sql
SELECT COUNT(*) FROM tblphp_sessions;
```

Optional cleanup query:

```sql
DELETE FROM tblphp_sessions
WHERE accessed_at < DATE_SUB(NOW(), INTERVAL 24 HOUR);
```

## References

- https://www.php.net/manual/en/class.sessionhandlerinterface.php
- https://owasp.org/www-community/attacks/Session_management
