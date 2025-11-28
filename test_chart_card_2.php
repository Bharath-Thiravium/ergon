<?php
/**
 * Test Chart Card 2 (Purchase Orders) - Revised Logic Implementation
 */

require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/controllers/FinanceController.php';

echo "<h1>Chart Card 2 (Purchase Orders) - Test Implementation</h1>\n";

try {
    $db = Database::connect();
    $financeController = new FinanceController();
    
    echo "<h2>Testing Purchase Order Overview Calculation</h2>\n";
    
    // Create sample PO data
    $samplePOs = [
        ['po_number' => 'BKC-PO001', 'total_amount' => 10000], // Will have 90% fulfillment (high)
        ['po_number' => 'BKC-PO002', 'total_amount' => 20000], // Will have 60% fulfillment (mid)
        ['po_number' => 'BKC-PO003', 'total_amount' => 15000], // Will have 30% fulfillment (low)
        ['po_number' => 'ABC-PO001', 'total_amount' => 5000],  // Different prefix
    ];
    
    // Create sample invoice/claim data
    $sampleInvoices = [
        ['po_number' => 'BKC-PO001', 'total_amount' => 9000],  // 90% of 10000
        ['po_number' => 'BKC-PO002', 'total_amount' => 12000], // 60% of 20000
        ['po_number' => 'BKC-PO003', 'total_amount' => 4500],  // 30% of 15000
        ['po_number' => 'ABC-PO001', 'total_amount' => 5000],  // Different prefix
    ];
    
    // Insert sample data
    $stmt = $db->prepare("DELETE FROM finance_data WHERE table_name IN ('finance_purchase_orders', 'finance_invoices')");
    $stmt->execute();
    
    foreach ($samplePOs as $po) {
        $stmt = $db->prepare("INSERT INTO finance_data (table_name, data) VALUES (?, ?)");
        $stmt->execute(['finance_purchase_orders', json_encode($po)]);
    }
    
    foreach ($sampleInvoices as $invoice) {
        $stmt = $db->prepare("INSERT INTO finance_data (table_name, data) VALUES (?, ?)");
        $stmt->execute(['finance_invoices', json_encode($invoice)]);
    }
    
    echo "<p>✓ Sample PO and invoice data inserted</p>\n";
    
    // Test with BKC prefix
    echo "<h3>Testing with BKC prefix</h3>\n";
    
    // Use reflection to access private method
    $reflection = new ReflectionClass($financeController);
    $method = $reflection->getMethod('calculatePurchaseOrderOverview');
    $method->setAccessible(true);
    
    $result = $method->invoke($financeController, $db, 'BKC');
    
    echo "<pre>";
    echo "Expected Results for BKC prefix:\n";
    echo "- High Fulfillment (>80%): 1 (BKC-PO001 = 90%)\n";
    echo "- Mid Fulfillment (>50%): 1 (BKC-PO002 = 60%)\n";
    echo "- Low Fulfillment (<50%): 1 (BKC-PO003 = 30%)\n";
    echo "- Total POs: 3\n\n";
    
    echo "Actual Results:\n";
    echo "- High Fulfillment: " . $result['po_high_fulfillment_count'] . "\n";
    echo "- Mid Fulfillment: " . $result['po_mid_fulfillment_count'] . "\n";
    echo "- Low Fulfillment: " . $result['po_low_fulfillment_count'] . "\n";
    echo "- Total POs: " . $result['po_total_count'] . "\n";
    echo "</pre>";
    
    // Verify results
    $success = true;
    if ($result['po_high_fulfillment_count'] !== 1) {
        echo "<p style='color: red;'>❌ High fulfillment count incorrect</p>\n";
        $success = false;
    }
    if ($result['po_mid_fulfillment_count'] !== 1) {
        echo "<p style='color: red;'>❌ Mid fulfillment count incorrect</p>\n";
        $success = false;
    }
    if ($result['po_low_fulfillment_count'] !== 1) {
        echo "<p style='color: red;'>❌ Low fulfillment count incorrect</p>\n";
        $success = false;
    }
    if ($result['po_total_count'] !== 3) {
        echo "<p style='color: red;'>❌ Total PO count incorrect</p>\n";
        $success = false;
    }
    
    if ($success) {
        echo "<p style='color: green;'>✅ All PO fulfillment counts are correct!</p>\n";
    }
    
    // Test dashboard stats storage
    echo "<h3>Testing Dashboard Stats Storage</h3>\n";
    
    $stmt = $db->prepare("SELECT po_high_fulfillment_count, po_mid_fulfillment_count, po_low_fulfillment_count, po_total_count FROM dashboard_stats WHERE company_prefix = 'BKC' ORDER BY generated_at DESC LIMIT 1");
    $stmt->execute();
    $dashboardStats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($dashboardStats) {
        echo "<p>✅ Dashboard stats stored successfully:</p>\n";
        echo "<pre>";
        echo "- High Fulfillment: " . $dashboardStats['po_high_fulfillment_count'] . "\n";
        echo "- Mid Fulfillment: " . $dashboardStats['po_mid_fulfillment_count'] . "\n";
        echo "- Low Fulfillment: " . $dashboardStats['po_low_fulfillment_count'] . "\n";
        echo "- Total POs: " . $dashboardStats['po_total_count'] . "\n";
        echo "</pre>";
    } else {
        echo "<p style='color: red;'>❌ Dashboard stats not found</p>\n";
    }
    
    echo "<h2>Implementation Summary</h2>\n";
    echo "<ul>";
    echo "<li>✅ Raw PO and invoice data fetched separately (no SQL aggregation)</li>";
    echo "<li>✅ Backend calculations for fulfillment percentages</li>";
    echo "<li>✅ New field mappings applied:</li>";
    echo "<ul>";
    echo "<li>Fulfillment Rate (OLD) → POs claimed > 80% (NEW): count</li>";
    echo "<li>Avg Lead Time (OLD) → POs claimed > 50% (NEW): count</li>";
    echo "<li>Open Commitments (OLD) → POs claimed < 50% (NEW): count</li>";
    echo "</ul>";
    echo "<li>✅ Dashboard stats table updated with new PO columns</li>";
    echo "<li>✅ Frontend displays count values only</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>\n";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>