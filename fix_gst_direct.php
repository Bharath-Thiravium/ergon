<?php
require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/controllers/FinanceController.php';

try {
    $db = Database::connect();
    $controller = new FinanceController();
    $controller->createTables($db);
    
    echo "Direct GST Liability Fix\n";
    echo "=======================\n\n";
    
    // Get prefix
    $prefix = $controller->getCompanyPrefix();
    echo "Using prefix: '$prefix'\n\n";
    
    // Call the calculation method directly
    $reflection = new ReflectionClass($controller);
    $method = $reflection->getMethod('calculateStatCard3Pipeline');
    $method->setAccessible(true);
    
    echo "Running calculation...\n";
    $method->invoke($controller, $db, null, $prefix);
    echo "✓ Calculation completed\n\n";
    
    // Check results
    $stmt = $db->prepare("SELECT * FROM dashboard_stats ORDER BY generated_at DESC LIMIT 1");
    $stmt->execute();
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($stats) {
        echo "GST Liability Results:\n";
        echo "IGST Liability: ₹" . number_format($stats['igst_liability'], 2) . "\n";
        echo "CGST+SGST Total: ₹" . number_format($stats['cgst_sgst_total'], 2) . "\n";
        echo "Total GST Liability: ₹" . number_format($stats['gst_liability'], 2) . "\n\n";
        
        echo "Other Stats:\n";
        echo "Outstanding Amount: ₹" . number_format($stats['outstanding_amount'], 2) . "\n";
        echo "PO Commitments: ₹" . number_format($stats['po_commitments'], 2) . "\n";
        echo "Generated: " . $stats['generated_at'] . "\n";
        
        if ($stats['gst_liability'] > 0) {
            echo "\n✓ GST Liability calculation is working!\n";
        } else {
            echo "\n! GST Liability is zero - checking invoice data...\n";
            
            // Check invoice data
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM finance_data WHERE table_name = 'finance_invoices'");
            $stmt->execute();
            $invoiceCount = $stmt->fetchColumn();
            echo "Invoice records: $invoiceCount\n";
            
            if ($invoiceCount > 0) {
                $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_invoices' LIMIT 1");
                $stmt->execute();
                $sample = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($sample) {
                    $data = json_decode($sample['data'], true);
                    echo "Sample invoice fields: " . implode(', ', array_keys($data)) . "\n";
                }
            }
        }
    } else {
        echo "No dashboard stats found\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>