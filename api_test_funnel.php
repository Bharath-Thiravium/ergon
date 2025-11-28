<?php
// Simple API test for funnel containers
require_once __DIR__ . '/app/controllers/FinanceController.php';

// Set proper headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

try {
    $controller = new FinanceController();
    
    // Get the action from URL parameter
    $action = $_GET['action'] ?? 'containers';
    
    switch ($action) {
        case 'containers':
            $controller->getFunnelContainers();
            break;
            
        case 'stats':
            $controller->getFunnelStats();
            break;
            
        case 'refresh':
            $controller->refreshFunnelStats();
            break;
            
        default:
            echo json_encode([
                'error' => 'Invalid action',
                'available_actions' => ['containers', 'stats', 'refresh'],
                'usage' => [
                    'containers' => '?action=containers - Get 4-box funnel containers',
                    'stats' => '?action=stats - Get raw funnel statistics',
                    'refresh' => '?action=refresh - Recalculate funnel stats'
                ]
            ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>