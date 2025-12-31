<?php

require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/RecentActivitiesController.php';
require_once __DIR__ . '/AnalyticsController.php';
require_once __DIR__ . '/SimpleLogger.php';

use Ergon\FinanceSync\Api\RecentActivitiesController;
use Ergon\FinanceSync\Api\AnalyticsController;
use Ergon\FinanceSync\Api\SimpleLogger;

try {
    $mysqlConnection = Database::connect();
    $logger = new SimpleLogger();
    
    $action = $_GET['action'] ?? 'activities';
    
    if ($action === 'analytics') {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');
        
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
        
        $controller = new AnalyticsController($mysqlConnection, $logger);
        $response = $controller->getAnalytics($_GET);
        http_response_code($response['success'] ? 200 : ($response['code'] ?? 400));
        echo json_encode($response);
    } else {
        $controller = new RecentActivitiesController($mysqlConnection, $logger);
        $controller->handleRequest();
    }
    
} catch (Exception $e) {
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
