<?php
require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/controllers/FinanceController.php';

echo "Testing GST Liability Fix\n";
echo "========================\n\n";

try {
    $db = Database::connect();
    $controller = new FinanceController();
    
    // Create tables
    $controller->createTables($db);
    
    echo "1. Checking current dashboard stats...\n";
    $stmt = $db->prepare("SELECT igst_liability, cgst_sgst_total, gst_liability FROM dashboard_stats ORDER BY generated_at DESC LIMIT 1");
    $stmt->execute();
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($stats) {
        echo "   Current GST values:\n";
        echo "   IGST Liability: ₹{$stats['igst_liability']}\n";
        echo "   CGST+SGST Total: ₹{$stats['cgst_sgst_total']}\n";
        echo "   Total GST Liability: ₹{$stats['gst_liability']}\n\n";
    } else {
        echo "   No dashboard stats found\n\n";
    }
    
    echo "2. Refreshing stats...\n";
    
    // Trigger stats refresh
    $response = file_get_contents('http://localhost/ergon/finance/refresh-stats');
    $result = json_decode($response, true);
    
    if ($result && $result['success']) {
        echo "   ✓ Stats refresh successful\n\n";
        
        // Check updated values
        $stmt = $db->prepare("SELECT igst_liability, cgst_sgst_total, gst_liability FROM dashboard_stats ORDER BY generated_at DESC LIMIT 1");
        $stmt->execute();
        $newStats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($newStats) {
            echo "3. Updated GST values:\n";
            echo "   IGST Liability: ₹{$newStats['igst_liability']}\n";
            echo "   CGST+SGST Total: ₹{$newStats['cgst_sgst_total']}\n";
            echo "   Total GST Liability: ₹{$newStats['gst_liability']}\n\n";
            
            if ($newStats['gst_liability'] > 0) {
                echo "✓ GST Liability calculation is working!\n";
            } else {
                echo "✗ GST Liability is still zero - may need data with outstanding invoices\n";
            }
        }
    } else {
        echo "   ✗ Stats refresh failed: " . ($result['error'] ?? 'Unknown error') . "\n";
    }
    
    echo "\n4. Testing dashboard API...\n";
    $dashboardResponse = file_get_contents('http://localhost/ergon/finance/dashboard-stats');
    $dashboardData = json_decode($dashboardResponse, true);
    
    if ($dashboardData) {
        echo "   API Response GST fields:\n";
        echo "   IGST Liability: ₹" . ($dashboardData['igstLiability'] ?? 'missing') . "\n";
        echo "   CGST+SGST Total: ₹" . ($dashboardData['cgstSgstTotal'] ?? 'missing') . "\n";
        echo "   Total GST Liability: ₹" . ($dashboardData['gstLiability'] ?? 'missing') . "\n";
        
        if (isset($dashboardData['igstLiability']) && isset($dashboardData['cgstSgstTotal']) && isset($dashboardData['gstLiability'])) {
            echo "   ✓ All GST fields present in API response\n";
        } else {
            echo "   ✗ Missing GST fields in API response\n";
        }
    } else {
        echo "   ✗ Failed to get dashboard API response\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>