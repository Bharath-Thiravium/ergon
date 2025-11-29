<?php

require_once __DIR__ . '/../bootstrap.php';

use Ergon\FinanceSync\Api\RecentActivitiesController;

try {
    // Create MySQL connection and logger
    $mysqlConnection = createMysqlConnection();
    $logger = createLogger();
    
    // Create controller
    $controller = new RecentActivitiesController($mysqlConnection, $logger);
    
    // Handle the request
    $controller->handleRequest();
    
} catch (Exception $e) {
    // Log error and return generic error response
    if (isset($logger)) {
        $logger->error("API bootstrap failed: " . $e->getMessage());
    }
    
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error',
        'code' => 500,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}