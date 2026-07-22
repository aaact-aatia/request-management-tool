<?php
/**
 * MySQL Session Handler for PHP
 * 
 * Implements SessionHandlerInterface to store PHP session data in MySQL.
 * This allows sessions to persist across multiple container instances,
 * load balancers, and cloud environments where file-based sessions are unreliable.
 * 
 * Usage:
 *   $handler = new MySQLSessionHandler($mysqli_link);
 *   session_set_save_handler($handler, true);
 *   session_start();
 * 
 * @see https://www.php.net/manual/en/class.sessionhandler.php
 */
class MySQLSessionHandler implements SessionHandlerInterface
{
    private ?mysqli $link;
    private string $tableName = 'tblphp_sessions';
    private int $sessionLifetime = 86400; // 24 hours
    private int $lockTimeoutSeconds = 30;
    private ?string $lockName = null;
    
    /**
     * Constructor
     * 
     * @param mysqli $link MySQL database connection
     * @param int $lifetime Session lifetime in seconds (default: 24 hours)
     * @param int $lockTimeoutSeconds Maximum time to wait for the session lock
     */
    public function __construct(mysqli $link, int $lifetime = 86400, int $lockTimeoutSeconds = 30)
    {
        $this->link = $link;
        $this->sessionLifetime = $lifetime;
        $this->lockTimeoutSeconds = max(1, min(300, $lockTimeoutSeconds));
    }
    
    /**
     * Initialize session handler
     * Called by session_start()
     */
    public function open(string $path, string $name): bool
    {
        // Verify database connection exists
        if (!($this->link instanceof mysqli)) {
            error_log('MySQLSessionHandler: Database connection is not valid');
            return false;
        }
        
        // Verify session table exists
        try {
            $tableCheck = sprintf(
                "SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '%s' LIMIT 1",
                mysqli_real_escape_string($this->link, $this->tableName)
            );
            
            $result = mysqli_query($this->link, $tableCheck);
            if (!($result instanceof mysqli_result) || mysqli_num_rows($result) === 0) {
                error_log("MySQLSessionHandler: Session table '{$this->tableName}' does not exist");
                return false;
            }
        } catch (Throwable $e) {
            error_log('MySQLSessionHandler: Failed to verify session table: ' . $e->getMessage());
            return false;
        }
        
        return true;
    }
    
    /**
     * Close session handler
     * Called by session_write_close()
     */
    public function close(): bool
    {
        return $this->releaseSessionLock();
    }

    /**
     * Acquire a connection-scoped advisory lock for one PHP session.
     *
     * The lock is held from read() until write()/close(), matching PHP's
     * normal session lifecycle and preventing concurrent requests from
     * loading and then overwriting the same session state.
     */
    private function acquireSessionLock(string $id): bool
    {
        if (!($this->link instanceof mysqli)) {
            return false;
        }

        $lockName = 'rmt_session_' . substr(hash('sha256', $id), 0, 48);
        if ($this->lockName === $lockName) {
            return true;
        }

        if ($this->lockName !== null && !$this->releaseSessionLock()) {
            return false;
        }

        try {
            $stmt = mysqli_prepare($this->link, 'SELECT GET_LOCK(?, ?) AS acquired');
            if (!$stmt) {
                error_log('MySQLSessionHandler: Failed to prepare session lock query: ' . mysqli_error($this->link));
                return false;
            }

            mysqli_stmt_bind_param($stmt, 'si', $lockName, $this->lockTimeoutSeconds);
            $executed = mysqli_stmt_execute($stmt);
            $result = $executed ? mysqli_stmt_get_result($stmt) : false;
            $row = $result instanceof mysqli_result ? mysqli_fetch_assoc($result) : null;
            mysqli_stmt_close($stmt);

            if ((int) ($row['acquired'] ?? 0) !== 1) {
                error_log("MySQLSessionHandler: Timed out waiting for session lock '{$lockName}'");
                return false;
            }

            $this->lockName = $lockName;
            return true;
        } catch (Throwable $e) {
            error_log('MySQLSessionHandler: Failed to acquire session lock: ' . $e->getMessage());
            return false;
        }
    }

    /** Release the advisory lock held by this handler instance. */
    private function releaseSessionLock(): bool
    {
        if ($this->lockName === null) {
            return true;
        }

        if (!($this->link instanceof mysqli)) {
            return false;
        }

        $lockName = $this->lockName;

        try {
            $stmt = mysqli_prepare($this->link, 'SELECT RELEASE_LOCK(?) AS released');
            if (!$stmt) {
                error_log('MySQLSessionHandler: Failed to prepare session unlock query: ' . mysqli_error($this->link));
                return false;
            }

            mysqli_stmt_bind_param($stmt, 's', $lockName);
            $executed = mysqli_stmt_execute($stmt);
            $result = $executed ? mysqli_stmt_get_result($stmt) : false;
            $row = $result instanceof mysqli_result ? mysqli_fetch_assoc($result) : null;
            mysqli_stmt_close($stmt);

            if ((int) ($row['released'] ?? 0) !== 1) {
                return false;
            }

            $this->lockName = null;
            return true;
        } catch (Throwable $e) {
            error_log('MySQLSessionHandler: Failed to release session lock: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Read session data from database
     * 
     * @param string $id Session ID (PHPSESSID)
     * @return string Serialized session data (empty string if not found)
     */
    public function read(string $id): string|false
    {
        if (!($this->link instanceof mysqli)) {
            error_log('MySQLSessionHandler: Database connection is not valid');
            return false;
        }

        if (!$this->acquireSessionLock($id)) {
            return false;
        }
        
        try {
            $id = mysqli_real_escape_string($this->link, $id);
            $query = "SELECT data FROM {$this->tableName} WHERE id = '$id' LIMIT 1";
            
            $result = mysqli_query($this->link, $query);
            if (!($result instanceof mysqli_result)) {
                error_log('MySQLSessionHandler: Query failed: ' . mysqli_error($this->link));
                return false;
            }
            
            if (mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
                return $row['data'] ?? '';
            }
            
            return '';
        } catch (Throwable $e) {
            error_log('MySQLSessionHandler: Read failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Write session data to database
     * 
     * @param string $id Session ID (PHPSESSID)
     * @param string $data Serialized session data
     * @return bool True on success, false on failure
     */
    public function write(string $id, string $data): bool
    {
        if (!($this->link instanceof mysqli)) {
            error_log('MySQLSessionHandler: Database connection is not valid');
            return false;
        }

        if (!$this->acquireSessionLock($id)) {
            return false;
        }
        
        try {
            $id = mysqli_real_escape_string($this->link, $id);
            $escapedData = mysqli_real_escape_string($this->link, $data);
            $query = "INSERT INTO {$this->tableName} (id, data, accessed_at)
                      VALUES ('$id', '$escapedData', CURRENT_TIMESTAMP)
                      ON DUPLICATE KEY UPDATE
                        data = VALUES(data),
                        accessed_at = CURRENT_TIMESTAMP";
            
            if (!mysqli_query($this->link, $query)) {
                error_log('MySQLSessionHandler: Write query failed: ' . mysqli_error($this->link));
                return false;
            }
            
            return true;
        } catch (Throwable $e) {
            error_log('MySQLSessionHandler: Write failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Destroy session data
     * Called by session_destroy() or on logout
     * 
     * @param string $id Session ID (PHPSESSID)
     * @return bool True on success, false on failure
     */
    public function destroy(string $id): bool
    {
        if (!($this->link instanceof mysqli)) {
            error_log('MySQLSessionHandler: Database connection is not valid');
            return false;
        }
        
        try {
            $id = mysqli_real_escape_string($this->link, $id);
            $query = "DELETE FROM {$this->tableName} WHERE id = '$id'";
            
            if (!mysqli_query($this->link, $query)) {
                error_log('MySQLSessionHandler: Destroy query failed: ' . mysqli_error($this->link));
                return false;
            }
            
            return true;
        } catch (Throwable $e) {
            error_log('MySQLSessionHandler: Destroy failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Garbage collection - remove expired sessions
     * Called periodically by PHP (frequency controlled by session.gc_probability and session.gc_divisor)
     * 
     * @param int $maxLifetime Session lifetime in seconds
     * @return int|false Number of deleted sessions, or false on failure
     */
    public function gc(int $maxLifetime): int|false
    {
        if (!($this->link instanceof mysqli)) {
            error_log('MySQLSessionHandler: Database connection is not valid');
            return false;
        }
        
        try {
            // Calculate expiration time
            $expirationTime = time() - $maxLifetime;
            $expirationTimestamp = date('Y-m-d H:i:s', $expirationTime);
            
            // Delete expired sessions
            $query = "DELETE FROM {$this->tableName} WHERE accessed_at < '$expirationTimestamp'";
            
            if (!mysqli_query($this->link, $query)) {
                error_log('MySQLSessionHandler: GC query failed: ' . mysqli_error($this->link));
                return false;
            }
            
            return mysqli_affected_rows($this->link);
        } catch (Throwable $e) {
            error_log('MySQLSessionHandler: GC failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update session timestamp to keep it alive
     * Can be called manually to extend session lifetime
     * 
     * @param string $id Session ID
     * @return bool True on success
     */
    public function touch(string $id): bool
    {
        if (!($this->link instanceof mysqli)) {
            return false;
        }
        
        try {
            $id = mysqli_real_escape_string($this->link, $id);
            $query = "UPDATE {$this->tableName} SET accessed_at = CURRENT_TIMESTAMP WHERE id = '$id'";
            
            return (bool) mysqli_query($this->link, $query);
        } catch (Throwable $e) {
            error_log('MySQLSessionHandler: Touch failed: ' . $e->getMessage());
            return false;
        }
    }
}
