<?php

require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/RecentActivitiesController.php';

use Ergon\FinanceSync\Api\RecentActivitiesController;

// Simple logger class implementing PSR-3 interface
class SimpleLogger implements \Psr\Log\LoggerInterface {
    public function emergency($message, array $context = []) { error_log("EMERGENCY: $message"); }
    public function alert($message, array $context = []) { error_log("ALERT: $message"); }
    public function critical($message, array $context = []) { error_log("CRITICAL: $message"); }
    public function error($message, array $context = []) { error_log("ERROR: $message"); }
    public function warning($message, array $context = []) { error_log("WARNING: $message"); }
    public function notice($message, array $context = []) { error_log("NOTICE: $message"); }
    public function info($message, array $context = []) { error_log("INFO: $message"); }
    public function debug($message, array $context = []) { error_log("DEBUG: $message"); }
    public function log($level, $message, array $context = []) { error_log("$level: $message"); }
}

try {
    // Use existing database connection
    $mysqlConnection = Database::connect();
    $logger = new SimpleLogger();
    
    // Create controller
    $controller = new RecentActivitiesController($mysqlConnection, $logger);
    
    // Handle the request
    $controller->handleRequest();
    
} catch (Exception $e) {
    // Log error and return generic error response
    error_log("API bootstrap failed: " . $e->getMessage());
    
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error',
        'code' => 500,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}