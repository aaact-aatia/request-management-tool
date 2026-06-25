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
    
    /**
     * Constructor
     * 
     * @param mysqli $link MySQL database connection
     * @param int $lifetime Session lifetime in seconds (default: 24 hours)
     */
    public function __construct(mysqli $link, int $lifetime = 86400)
    {
        $this->link = $link;
        $this->sessionLifetime = $lifetime;
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
        return true;
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
            return '';
        }
        
        try {
            $id = mysqli_real_escape_string($this->link, $id);
            $query = "SELECT data FROM {$this->tableName} WHERE id = '$id' LIMIT 1";
            
            $result = mysqli_query($this->link, $query);
            if (!($result instanceof mysqli_result)) {
                error_log('MySQLSessionHandler: Query failed: ' . mysqli_error($this->link));
                return '';
            }
            
            if (mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
                return $row['data'] ?? '';
            }
            
            return '';
        } catch (Throwable $e) {
            error_log('MySQLSessionHandler: Read failed: ' . $e->getMessage());
            return '';
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
        
        try {
            // Check if session already exists
            $id = mysqli_real_escape_string($this->link, $id);
            $checkQuery = "SELECT 1 FROM {$this->tableName} WHERE id = '$id' LIMIT 1";
            
            $result = mysqli_query($this->link, $checkQuery);
            if (!($result instanceof mysqli_result)) {
                error_log('MySQLSessionHandler: Check query failed: ' . mysqli_error($this->link));
                return false;
            }
            
            $sessionExists = mysqli_num_rows($result) > 0;
            
            // Escape data
            $escapedData = mysqli_real_escape_string($this->link, $data);
            
            if ($sessionExists) {
                // Update existing session
                $query = "UPDATE {$this->tableName} 
                          SET data = '$escapedData', accessed_at = CURRENT_TIMESTAMP 
                          WHERE id = '$id'";
            } else {
                // Insert new session
                $query = "INSERT INTO {$this->tableName} (id, data, accessed_at) 
                          VALUES ('$id', '$escapedData', CURRENT_TIMESTAMP)";
            }
            
            if (!mysqli_query($this->link, $query)) {
                error_log('MySQLSessionHandler: Write query failed: ' . mysqli_error($this->link));
                return false;
            }
            
            return true;
        } catch (Throwable $e) {
            error_log('MySQLSessionHandler: Write failed (connection may be closed): ' . $e->getMessage());
            return true;  // Return true to suppress PHP's session write warning during shutdown
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
