<?php
require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/services/FunnelStatsService.php';
require_once __DIR__ . '/app/controllers/FinanceController.php';

header('Content-Type: application/json');

try {
    $db = Database::connect();
    $controller = new FinanceController();
    $prefix = $controller->getCompanyPrefix();
    
    // Check if we have any finance data
    $stmt = $db->prepare("SELECT COUNT(*) FROM finance_data WHERE table_name IN ('finance_quotations', 'finance_purchase_orders', 'finance_invoices')");
    $stmt->execute();
    $totalRecords = $stmt->fetchColumn();
    
    if ($totalRecords == 0) {
        echo json_encode([
            'success' => false,
            'error' => 'No finance data found. Please sync data first.',
            'containers' => [
                'container1' => ['title' => 'Quotations', 'quotations_count' => 0, 'quotations_total_value' => 0],
                'container2' => ['title' => 'Purchase Orders', 'po_count' => 0, 'po_total_value' => 0, 'po_conversion_rate' => 0],
                'container3' => ['title' => 'Invoices', 'invoice_count' => 0, 'invoice_total_value' => 0, 'invoice_conversion_rate' => 0],
                'container4' => ['title' => 'Payments', 'payment_count' => 0, 'total_payment_received' => 0, 'payment_conversion_rate' => 0]
            ]
        ]);
        exit;
    }
    
    // Force recalculation
    $funnelService = new FunnelStatsService();
    $stats = $funnelService->calculateFunnelStats($prefix);
    $containers = $funnelService->getFunnelContainers($prefix);
    
    echo json_encode([
        'success' => true,
        'message' => 'Funnel data fixed and recalculated',
        'prefix' => $prefix,
        'total_records' => $totalRecords,
        'raw_stats' => $stats,
        'containers' => $containers
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'containers' => [
            'container1' => ['title' => 'Quotations', 'quotations_count' => 0, 'quotations_total_value' => 0],
            'container2' => ['title' => 'Purchase Orders', 'po_count' => 0, 'po_total_value' => 0, 'po_conversion_rate' => 0],
            'container3' => ['title' => 'Invoices', 'invoice_count' => 0, 'invoice_total_value' => 0, 'invoice_conversion_rate' => 0],
            'container4' => ['title' => 'Payments', 'payment_count' => 0, 'total_payment_received' => 0, 'payment_conversion_rate' => 0]
        ]
    ]);
}
?>