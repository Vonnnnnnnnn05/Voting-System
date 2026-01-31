<?php
/**
 * Database Connection using PDO
 * Provides a singleton PDO instance with exception error mode enabled
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'voting_system');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

/**
 * Get PDO database connection
 * @return PDO
 * @throws PDOException
 */
function getDB() {
    static $pdo = null;
    
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            // Show detailed error for debugging (remove in production)
            throw new PDOException("Database connection failed: " . $e->getMessage());
        }
    }
    
    return $pdo;
}
