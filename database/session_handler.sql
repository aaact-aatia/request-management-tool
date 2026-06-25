-- PHP Session Storage Table for MySQL-backed Session Handler
-- 
-- This table stores PHP session data persistently in MySQL instead of relying on
-- file-based sessions or container-specific storage. This allows sessions to work
-- reliably across multiple container instances, behind load balancers, proxies,
-- and in cloud environments like Azure App Service where session affinity cannot
-- be guaranteed.
--
-- Created for: feature/mysql-backed-sessions
-- Issue: Inconsistent login/session behavior in Azure App Service with proxies
--

CREATE TABLE IF NOT EXISTS `tblphp_sessions` (
  `id` VARCHAR(128) PRIMARY KEY COMMENT 'PHP session ID (PHPSESSID cookie value)',
  `data` LONGBLOB NOT NULL COMMENT 'Session data serialized by PHP',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Session creation timestamp',
  `accessed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last access time for garbage collection',
  KEY `accessed_at` (`accessed_at`) COMMENT 'Index for garbage collection queries'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Stores PHP session data for MySQL-backed session handler';

-- Note: This table is safe for both development and production:
-- - LONGBLOB can store up to 4GB (vastly more than needed)
-- - accessed_at index enables efficient garbage collection
-- - utf8mb4 charset ensures proper handling of multilingual user data
-- - Small table footprint initially, grows only with active sessions
