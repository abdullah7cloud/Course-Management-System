<?php
/**
 * DATABASE.PHP
 * 
 * Database Connection Class
 * This class provides a centralized, reusable database connection using PDO
 * Implements singleton-like pattern for efficient connection management
 * Handles connection errors gracefully and sets appropriate PDO attributes
 */

/**
 * Database Class
 * 
 * Responsibilities:
 * - Manage database connection parameters
 * - Establish secure PDO connections
 * - Set proper connection attributes
 * - Handle connection errors gracefully
 * - Provide consistent UTF-8 encoding
 */
class Database {
    /**
     * Database Connection Configuration
     * Private properties to encapsulate database credentials
     */
    private $host = "127.0.0.1";        // Database server host (localhost IP)
    private $db_name = "school_management"; // Database name
    private $username = "root";          // Database username
    private $password = "";              // Database password (empty for local development)
    public $conn;                        // Public connection property for reuse

    /**
     * getConnection() Method
     * 
     * Purpose:
     * - Establish and return a PDO database connection
     * - Configure connection settings for security and error handling
     * - Handle connection failures gracefully with error messages
     * 
     * Features:
     * - UTF-8 character encoding for proper text handling
     * - Exception mode for better error handling
     * - Secure PDO connection with parameterized queries support
     * 
     * @return PDO|null - PDO connection object on success, null on failure
     */
    public function getConnection() {
        // Initialize connection to null to ensure clean state
        $this->conn = null;
        
        try {
            /**
             * Create PDO Connection
             * 
             * DSN (Data Source Name) format: "mysql:host=HOST;dbname=DBNAME"
             * - mysql: Database driver
             * - host: Database server location
             * - dbname: Specific database to connect to
             */
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name, 
                $this->username, 
                $this->password
            );
            
            /**
             * Configure Connection Settings
             */
            
            // Set character encoding to UTF-8 for proper international character support
            $this->conn->exec("set names utf8");
            
            /**
             * Set PDO Attributes for Better Security and Error Handling
             */
            
            // PDO::ATTR_ERRMODE: Error reporting mode
            // PDO::ERRMODE_EXCEPTION: Throw exceptions on errors (recommended for security)
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            /**
             * Additional Recommended PDO Attributes (commented out but available)
             * 
             * // PDO::ATTR_DEFAULT_FETCH_MODE: Set default fetch mode to associative array
             * $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
             * 
             * // PDO::ATTR_EMULATE_PREPARES: Disable prepared statement emulation for security
             * $this->conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
             * 
             * // PDO::ATTR_PERSISTENT: Persistent connections (use with caution)
             * $this->conn->setAttribute(PDO::ATTR_PERSISTENT, true);
             */
            
        } catch(PDOException $exception) {
            /**
             * Error Handling
             * 
             * Catch PDO exceptions and display user-friendly error message
             * In production, you might want to log this instead of echoing
             */
            echo "Connection error: " . $exception->getMessage();
            
            // Optionally, you could also:
            // - Log the error to a file
            // - Send an email to administrator
            // - Redirect to a maintenance page
            // - Return a custom error page
        }
        
        // Return connection object (null if connection failed)
        return $this->conn;
    }
    
    /**
     * Additional Useful Methods (commented out for future expansion)
     * 
     * public function closeConnection() {
     *     $this->conn = null;
     * }
     * 
     * public function testConnection() {
     *     return $this->conn !== null;
     * }
     * 
     * public function getConnectionInfo() {
     *     return [
     *         'host' => $this->host,
     *         'database' => $this->db_name,
     *         'username' => $this->username
     *     ];
     * }
     */
}

/**
 * USAGE EXAMPLE:
 * 
 * $database = new Database();
 * $db = $database->getConnection();
 * 
 * if ($db) {
 *     // Connection successful, proceed with database operations
 *     $stmt = $db->prepare("SELECT * FROM users");
 *     $stmt->execute();
 *     $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
 * } else {
 *     // Handle connection failure
 *     echo "Unable to connect to database";
 * }
 */

?>