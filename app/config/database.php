<?php
/**
 * Database Configuration
 * ergon - Employee Tracker & Task Manager
 */

require_once __DIR__ . '/environment.php';

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $conn;
    private static $instance = null;
    
    public function __construct() {
        try {
            // Auto-detect environment and set database credentials
            if (Environment::isDevelopment()) {
                // Local development settings
                $this->host = 'localhost';
                $this->db_name = 'ergon_db';
                $this->username = 'root';
                $this->password = '';
            } else {
                // Production settings
                $this->host = 'localhost';
                $this->db_name = 'u494785662_ergon';
                $this->username = 'u494785662_ergon';
                $this->password = '@Admin@2025@';
            }
        } catch (Exception $e) {
            error_log('Database configuration error: ' . $e->getMessage());
            throw new Exception('Database configuration failed');
        }
    }

    public function getConnection() {
        $this->conn = null;
        
        try {
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ];
            
            // Add MySQL-specific options only if available
            if (defined('PDO::MYSQL_ATTR_INIT_COMMAND')) {
                $options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES utf8mb4";
            }
            
            if (!Environment::isDevelopment()) {
                if (defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')) {
                    $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
                }
                $options[PDO::ATTR_TIMEOUT] = 30;
            }
            
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password ?? '',
                $options
            );
        } catch(PDOException $e) {
            error_log("Connection error: " . $e->getMessage());
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
        
        return $this->conn;
    }
    
    public static function connect() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance->getConnection();
    }
    
    public function getEnvironment() {
        return $_ENV['APP_ENV'] ?? 'development';
    }
}
?>
