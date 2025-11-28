<?php
require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/controllers/FinanceController.php';

echo "Debugging refresh-stats error\n";
echo "============================\n\n";

try {
    $db = Database::connect();
    $controller = new FinanceController();
    $controller->createTables($db);
    $prefix = $controller->getCompanyPrefix();
    
    echo "Company prefix: '$prefix'\n\n";
    
    // Test the calculation directly
    echo "Testing calculateStatCard3Pipeline directly...\n";
    
    // Use reflection to call private method
    $reflection = new ReflectionClass($controller);
    $method = $reflection->getMethod('calculateStatCard3Pipeline');
    $method->setAccessible(true);
    
    $method->invoke($controller, $db, null, $prefix);
    
    echo "✓ Direct calculation completed\n\n";
    
    // Check results
    $stmt = $db->prepare("SELECT igst_liability, cgst_sgst_total, gst_liability FROM dashboard_stats ORDER BY generated_at DESC LIMIT 1");
    $stmt->execute();
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($stats) {
        echo "Results:\n";
        echo "IGST Liability: ₹{$stats['igst_liability']}\n";
        echo "CGST+SGST Total: ₹{$stats['cgst_sgst_total']}\n";
        echo "Total GST Liability: ₹{$stats['gst_liability']}\n";
    } else {
        echo "No stats found\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>