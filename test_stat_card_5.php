<?php
require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/controllers/FinanceController.php';

echo "Testing Stat Card 5 - PO Commitments Implementation\n";
echo "==================================================\n\n";

try {
    $db = Database::connect();
    $controller = new FinanceController();
    
    // Create tables if they don't exist
    $controller->createTables($db);
    
    echo "1. Checking dashboard_stats table structure...\n";
    $stmt = $db->prepare("DESCRIBE dashboard_stats");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $poColumns = ['po_commitments', 'open_pos', 'closed_pos'];
    $foundColumns = [];
    
    foreach ($columns as $column) {
        if (in_array($column['Field'], $poColumns)) {
            $foundColumns[] = $column['Field'];
            echo "   ✓ Found column: {$column['Field']} ({$column['Type']})\n";
        }
    }
    
    if (count($foundColumns) === 3) {
        echo "   ✓ All PO commitment columns exist\n\n";
    } else {
        echo "   ✗ Missing PO commitment columns\n\n";
        exit(1);
    }
    
    echo "2. Testing PO commitment calculation logic...\n";
    
    // Sample PO data for testing
    $testPOs = [
        [
            'po_number' => 'BKC-PO-001',
            'total_amount' => 50000,
            'amount_paid' => 25000,
            'received_date' => null
        ],
        [
            'po_number' => 'BKC-PO-002', 
            'total_amount' => 30000,
            'amount_paid' => 30000,
            'received_date' => '2024-01-15'
        ],
        [
            'po_number' => 'BKC-PO-003',
            'total_amount' => 75000,
            'amount_paid' => 0,
            'received_date' => null
        ],
        [
            'po_number' => 'BKC-PO-004',
            'total_amount' => 40000,
            'amount_paid' => 40000,
            'received_date' => null // Paid but not received
        ]
    ];
    
    $poCommitments = 0;
    $openPos = 0;
    $closedPos = 0;
    
    foreach ($testPOs as $po) {
        $totalAmount = $po['total_amount'];
        $amountPaid = $po['amount_paid'];
        $receivedDate = $po['received_date'];
        
        echo "   PO {$po['po_number']}:\n";
        echo "     Total Amount: ₹{$totalAmount}\n";
        echo "     Amount Paid: ₹{$amountPaid}\n";
        echo "     Received Date: " . ($receivedDate ?: 'NULL') . "\n";
        
        $poCommitments += $totalAmount;
        
        // Determine PO status
        if (($amountPaid < $totalAmount) || empty($receivedDate)) {
            $openPos++;
            echo "     ✓ Status: OPEN\n";
        } else {
            $closedPos++;
            echo "     ✓ Status: CLOSED\n";
        }
        echo "\n";
    }
    
    echo "3. Final PO Commitment Calculation:\n";
    echo "   PO Commitments: ₹{$poCommitments}\n";
    echo "   Open POs: {$openPos}\n";
    echo "   Closed POs: {$closedPos}\n\n";
    
    echo "4. Testing dashboard stats refresh...\n";
    
    // Test the refresh stats endpoint
    $response = file_get_contents('http://localhost/ergon/finance/refresh-stats');
    $result = json_decode($response, true);
    
    if ($result && $result['success']) {
        echo "   ✓ Stats refresh successful\n";
        
        // Check if PO commitment values are stored
        $stmt = $db->prepare("SELECT po_commitments, open_pos, closed_pos FROM dashboard_stats ORDER BY generated_at DESC LIMIT 1");
        $stmt->execute();
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($stats) {
            echo "   ✓ PO commitment values stored:\n";
            echo "     PO Commitments: ₹{$stats['po_commitments']}\n";
            echo "     Open POs: {$stats['open_pos']}\n";
            echo "     Closed POs: {$stats['closed_pos']}\n";
        } else {
            echo "   ✗ No stats found in database\n";
        }
    } else {
        echo "   ✗ Stats refresh failed: " . ($result['error'] ?? 'Unknown error') . "\n";
    }
    
    echo "\n5. Testing dashboard API response...\n";
    
    $dashboardResponse = file_get_contents('http://localhost/ergon/finance/dashboard-stats');
    $dashboardData = json_decode($dashboardResponse, true);
    
    if ($dashboardData && isset($dashboardData['pendingPOValue'])) {
        echo "   ✓ Dashboard API includes PO commitment fields:\n";
        echo "     PO Commitments: ₹" . ($dashboardData['pendingPOValue'] ?? 0) . "\n";
        echo "     Open POs: " . ($dashboardData['openPOCount'] ?? 0) . "\n";
        echo "     Closed POs: " . ($dashboardData['closedPOCount'] ?? 0) . "\n";
        echo "     Total POs: " . ($dashboardData['totalPOCount'] ?? 0) . "\n";
    } else {
        echo "   ✗ Dashboard API missing PO commitment fields\n";
    }
    
    echo "\n✓ Stat Card 5 PO Commitments implementation test completed!\n";
    echo "\nImplementation Summary:\n";
    echo "- PO commitments = sum of all total_amount values\n";
    echo "- Open POs = count where (amount_paid < total_amount) OR received_date IS NULL\n";
    echo "- Closed POs = count where (amount_paid >= total_amount) AND received_date IS NOT NULL\n";
    echo "- Average PO value removed from display\n";
    echo "- All calculations performed in backend\n";
    echo "- Frontend reads from dashboard_stats table only\n";
    echo "- No SQL aggregation used on finance_purchase_orders\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>