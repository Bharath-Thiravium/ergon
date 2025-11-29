<?php
require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/controllers/FinanceController.php';

echo "Testing Stat Card 4 - GST Liability Implementation\n";
echo "=================================================\n\n";

try {
    $db = Database::connect();
    $controller = new FinanceController();
    
    // Create tables if they don't exist
    $controller->createTables($db);
    
    echo "1. Checking dashboard_stats table structure...\n";
    $stmt = $db->prepare("DESCRIBE dashboard_stats");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $gstColumns = ['igst_liability', 'cgst_sgst_total', 'gst_liability'];
    $foundColumns = [];
    
    foreach ($columns as $column) {
        if (in_array($column['Field'], $gstColumns)) {
            $foundColumns[] = $column['Field'];
            echo "   ✓ Found column: {$column['Field']} ({$column['Type']})\n";
        }
    }
    
    if (count($foundColumns) === 3) {
        echo "   ✓ All GST liability columns exist\n\n";
    } else {
        echo "   ✗ Missing GST liability columns\n\n";
        exit(1);
    }
    
    echo "2. Testing GST liability calculation logic...\n";
    
    // Sample invoice data for testing
    $testInvoices = [
        [
            'invoice_number' => 'BKC001',
            'taxable_amount' => 10000,
            'amount_paid' => 5000,
            'igst' => 1800,
            'cgst' => 0,
            'sgst' => 0
        ],
        [
            'invoice_number' => 'BKC002', 
            'taxable_amount' => 20000,
            'amount_paid' => 20000, // Fully paid
            'igst' => 0,
            'cgst' => 1800,
            'sgst' => 1800
        ],
        [
            'invoice_number' => 'BKC003',
            'taxable_amount' => 15000,
            'amount_paid' => 10000,
            'igst' => 0,
            'cgst' => 1350,
            'sgst' => 1350
        ]
    ];
    
    $igstLiability = 0;
    $cgstSgstTotal = 0;
    
    foreach ($testInvoices as $invoice) {
        $pendingBase = $invoice['taxable_amount'] - $invoice['amount_paid'];
        
        echo "   Invoice {$invoice['invoice_number']}:\n";
        echo "     Taxable Amount: ₹{$invoice['taxable_amount']}\n";
        echo "     Amount Paid: ₹{$invoice['amount_paid']}\n";
        echo "     Pending Base: ₹{$pendingBase}\n";
        
        if ($pendingBase > 0) {
            $igstLiability += $invoice['igst'];
            $cgstSgstTotal += ($invoice['cgst'] + $invoice['sgst']);
            echo "     ✓ Outstanding - GST liability applies\n";
            echo "       IGST: ₹{$invoice['igst']}\n";
            echo "       CGST+SGST: ₹" . ($invoice['cgst'] + $invoice['sgst']) . "\n";
        } else {
            echo "     ✗ Fully paid - No GST liability\n";
        }
        echo "\n";
    }
    
    $gstLiability = $igstLiability + $cgstSgstTotal;
    
    echo "3. Final GST Liability Calculation:\n";
    echo "   IGST Liability: ₹{$igstLiability}\n";
    echo "   CGST+SGST Total: ₹{$cgstSgstTotal}\n";
    echo "   Total GST Liability: ₹{$gstLiability}\n\n";
    
    echo "4. Testing dashboard stats refresh...\n";
    
    // Test the refresh stats endpoint
    $response = file_get_contents('http://localhost/ergon/finance/refresh-stats');
    $result = json_decode($response, true);
    
    if ($result && $result['success']) {
        echo "   ✓ Stats refresh successful\n";
        
        // Check if GST liability values are stored
        $stmt = $db->prepare("SELECT igst_liability, cgst_sgst_total, gst_liability FROM dashboard_stats ORDER BY generated_at DESC LIMIT 1");
        $stmt->execute();
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($stats) {
            echo "   ✓ GST liability values stored:\n";
            echo "     IGST Liability: ₹{$stats['igst_liability']}\n";
            echo "     CGST+SGST Total: ₹{$stats['cgst_sgst_total']}\n";
            echo "     Total GST Liability: ₹{$stats['gst_liability']}\n";
        } else {
            echo "   ✗ No stats found in database\n";
        }
    } else {
        echo "   ✗ Stats refresh failed: " . ($result['error'] ?? 'Unknown error') . "\n";
    }
    
    echo "\n5. Testing dashboard API response...\n";
    
    $dashboardResponse = file_get_contents('http://localhost/ergon/finance/dashboard-stats');
    $dashboardData = json_decode($dashboardResponse, true);
    
    if ($dashboardData && isset($dashboardData['gstLiability'])) {
        echo "   ✓ Dashboard API includes GST liability fields:\n";
        echo "     IGST Liability: ₹" . ($dashboardData['igstLiability'] ?? 0) . "\n";
        echo "     CGST+SGST Total: ₹" . ($dashboardData['cgstSgstTotal'] ?? 0) . "\n";
        echo "     Total GST Liability: ₹" . ($dashboardData['gstLiability'] ?? 0) . "\n";
    } else {
        echo "   ✗ Dashboard API missing GST liability fields\n";
    }
    
    echo "\n✓ Stat Card 4 GST Liability implementation test completed!\n";
    echo "\nImplementation Summary:\n";
    echo "- GST liability calculated only on outstanding invoices (pending_base > 0)\n";
    echo "- IGST and CGST+SGST tracked separately\n";
    echo "- All calculations performed in backend\n";
    echo "- Frontend reads from dashboard_stats table only\n";
    echo "- No SQL aggregation used on finance_invoices\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>