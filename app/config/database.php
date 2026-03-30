<?php
/**
 * Database Configuration
 * ergon - Employee Tracker & Task Manager
 */

require_once __DIR__ . '/environment.php';

// Load environment variables - use .env.production only on production servers
$_envProd = __DIR__ . '/../../.env.production';
$_envDev  = __DIR__ . '/../../.env';
$_envFile = (Environment::isProduction() && file_exists($_envProd)) ? $_envProd : $_envDev;
if (file_exists($_envFile)) {
    $lines = file($_envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $key   = trim($key);
            $value = trim($value);
            $_ENV[$key] = $value;
        }
    }
}

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $conn;
    private static $instance = null;
    
    public function __construct() {
        $this->host     = $_ENV['DB_HOST'] ?? 'localhost';
        $this->db_name  = $_ENV['DB_NAME'] ?? '';
        $this->username = $_ENV['DB_USER'] ?? '';
        $this->password = $_ENV['DB_PASS'] ?? '';
    }

    public function getConnection() {
        $this->conn = null;
        
        try {
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
                PDO::ATTR_PERSISTENT => true,
                PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
            ];
            
            if (!Environment::isDevelopment()) {
                $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
                $options[PDO::ATTR_TIMEOUT] = 30;
            }
            
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password,
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
        return Environment::isDevelopment() ? 'development' : 'production';
    }
    
    // PostgreSQL configuration for sync services
    public static function getPostgreSQLConfig() {
        return [
            'postgresql' => [
                'host'     => $_ENV['SAP_PG_HOST'] ?? $_ENV['PG_HOST'] ?? '72.60.218.167',
                'port'     => $_ENV['SAP_PG_PORT'] ?? $_ENV['PG_PORT'] ?? 5432,
                'database' => $_ENV['SAP_PG_DB']   ?? $_ENV['PG_DATABASE'] ?? 'modernsap',
                'username' => $_ENV['SAP_PG_USER'] ?? $_ENV['PG_USER'] ?? 'postgres',
                'password' => $_ENV['SAP_PG_PASS'] ?? $_ENV['PG_PASS'] ?? ''
            ],
            'mysql' => [
                'host'     => $_ENV['DB_HOST'] ?? 'localhost',
                'port'     => $_ENV['DB_PORT'] ?? 3306,
                'database' => $_ENV['DB_NAME'] ?? '',
                'username' => $_ENV['DB_USER'] ?? '',
                'password' => $_ENV['DB_PASS'] ?? ''
            ]
        ];
    }
}
?>
