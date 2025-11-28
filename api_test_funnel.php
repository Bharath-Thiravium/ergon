<?php
// Simple API test for funnel containers
require_once __DIR__ . '/app/controllers/FinanceController.php';

// Set proper headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // Check if finance data exists first
    require_once __DIR__ . '/app/config/database.php';
    $db = Database::connect();
    $stmt = $db->prepare("SELECT COUNT(*) FROM finance_data WHERE table_name IN ('finance_quotations', 'finance_purchase_orders', 'finance_invoices')");
    $stmt->execute();
    $hasData = $stmt->fetchColumn() > 0;
    
    if (!$hasData) {
        echo json_encode([
            'success' => false,
            'error' => 'No finance data available. Please sync data first.',
            'containers' => [
                'container1' => ['title' => 'Quotations', 'quotations_count' => 0, 'quotations_total_value' => 0],
                'container2' => ['title' => 'Purchase Orders', 'po_count' => 0, 'po_total_value' => 0, 'po_conversion_rate' => 0],
                'container3' => ['title' => 'Invoices', 'invoice_count' => 0, 'invoice_total_value' => 0, 'invoice_conversion_rate' => 0],
                'container4' => ['title' => 'Payments', 'payment_count' => 0, 'total_payment_received' => 0, 'payment_conversion_rate' => 0]
            ]
        ]);
        exit;
    }
    
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