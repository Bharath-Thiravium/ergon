<?php
require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/controllers/FinanceController.php';

echo "<h1>🔍 Funnel Data Debug</h1>\n";

try {
    $db = Database::connect();
    $controller = new FinanceController();
    $prefix = $controller->getCompanyPrefix();
    
    echo "<h3>Company Prefix: " . ($prefix ?: 'NONE') . "</h3>\n";
    
    // Check raw data tables
    echo "<h3>📊 Raw Data Check</h3>\n";
    $tables = ['finance_quotations', 'finance_purchase_orders', 'finance_invoices'];
    
    foreach ($tables as $table) {
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM finance_data WHERE table_name = ?");
        $stmt->execute([$table]);
        $count = $stmt->fetchColumn();
        echo "• {$table}: {$count} records<br>\n";
        
        if ($count > 0) {
            // Show sample data
            $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = ? LIMIT 1");
            $stmt->execute([$table]);
            $sample = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($sample) {
                $data = json_decode($sample['data'], true);
                echo "  Sample keys: " . implode(', ', array_keys($data)) . "<br>\n";
                
                // Check prefix matching
                if ($table === 'finance_quotations') {
                    $number = $data['quotation_number'] ?? 'N/A';
                    $matches = !$prefix || strpos($number, $prefix) === 0;
                    echo "  Sample quotation_number: {$number} (matches prefix: " . ($matches ? 'YES' : 'NO') . ")<br>\n";
                } elseif ($table === 'finance_purchase_orders') {
                    $number = $data['po_number'] ?? 'N/A';
                    $matches = !$prefix || strpos($number, $prefix) === 0;
                    echo "  Sample po_number: {$number} (matches prefix: " . ($matches ? 'YES' : 'NO') . ")<br>\n";
                } elseif ($table === 'finance_invoices') {
                    $number = $data['invoice_number'] ?? 'N/A';
                    $matches = !$prefix || strpos($number, $prefix) === 0;
                    echo "  Sample invoice_number: {$number} (matches prefix: " . ($matches ? 'YES' : 'NO') . ")<br>\n";
                }
            }
        }
        echo "<br>\n";
    }
    
    // Test funnel service directly
    echo "<h3>🔧 Direct Service Test</h3>\n";
    require_once __DIR__ . '/app/services/FunnelStatsService.php';
    $funnelService = new FunnelStatsService();
    
    $stats = $funnelService->calculateFunnelStats($prefix);
    echo "<pre>" . json_encode($stats, JSON_PRETTY_PRINT) . "</pre>\n";
    
} catch (Exception $e) {
    echo "<div style='color: red;'>Error: " . $e->getMessage() . "</div>\n";
    echo "<div>File: " . $e->getFile() . " (Line: " . $e->getLine() . ")</div>\n";
}
?>