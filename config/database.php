<?php
/**
 * Database Configuration
 * ERGON - Employee Tracker & Task Manager
 */

require_once __DIR__ . '/environment.php';

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $conn;
    
    public function __construct() {
        if (Environment::isDevelopment()) {
            // Development settings (Laragon/XAMPP)
            $this->host = 'localhost';
            $this->db_name = 'ergon_db';
            $this->username = 'root';
            $this->password = '';
        } else {
            // Production settings (Hostinger)
            $this->host = 'localhost';
            $this->db_name = 'u494785662_ergon';
            $this->username = 'u494785662_ergon';
            $this->password = '@Admin@2025@';
        }
    }

    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
                ]
            );
        } catch(PDOException $e) {
            error_log("Connection error: " . $e->getMessage());
            throw new Exception("Database connection failed");
        }
        
        return $this->conn;
    }
    
    public function getEnvironment() {
        return $this->isLocalhost() ? 'development' : 'production';
    }
    
    public function getConfig() {
        return [
            'environment' => $this->getEnvironment(),
            'host' => $this->host,
            'database' => $this->db_name,
            'username' => $this->username
        ];
    }
}
?>