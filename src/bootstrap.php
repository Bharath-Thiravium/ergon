<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Required environment variables
$dotenv->required(['PG_DSN', 'PG_USER', 'PG_PASS', 'MYSQL_DSN', 'MYSQL_USER', 'MYSQL_PASS']);

/**
 * Create PostgreSQL PDO connection
 */
function createPostgresConnection(): PDO
{
    try {
        $pdo = new PDO(
            $_ENV['PG_DSN'],
            $_ENV['PG_USER'],
            $_ENV['PG_PASS'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        throw new Exception("PostgreSQL connection failed: " . $e->getMessage());
    }
}

/**
 * Create MySQL PDO connection
 */
function createMysqlConnection(): PDO
{
    try {
        $pdo = new PDO(
            $_ENV['MYSQL_DSN'],
            $_ENV['MYSQL_USER'],
            $_ENV['MYSQL_PASS'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        throw new Exception("MySQL connection failed: " . $e->getMessage());
    }
}

/**
 * Create logger instance
 */
function createLogger(): Logger
{
    $logger = new Logger('finance_sync');
    
    // Console handler
    $logger->pushHandler(new StreamHandler('php://stdout', Logger::INFO));
    
    // File handler if LOG_FILE is set
    if (!empty($_ENV['LOG_FILE'])) {
        $logger->pushHandler(new RotatingFileHandler($_ENV['LOG_FILE'], 0, Logger::DEBUG));
    }
    
    return $logger;
}

/**
 * Get configuration values with defaults
 */
function getConfig(): array
{
    return [
        'company_prefix' => $_ENV['COMPANY_PREFIX'] ?? 'ERGN',
        'batch_size' => (int)($_ENV['BATCH_SIZE'] ?? 500),
        'sync_table' => $_ENV['LAST_SYNC_LOOKUP_TABLE'] ?? 'sync_metadata'
    ];
}