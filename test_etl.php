<?php
/**
 * Test ETL Finance Module
 * Run this to test the new ETL functionality
 */

require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/services/FinanceETLService.php';

echo "🚀 Testing Finance ETL Module\n";
echo "================================\n\n";

try {
    $etlService = new FinanceETLService();
    
    // Test 1: Run ETL for BKC prefix
    echo "📊 Test 1: Running ETL for BKC prefix...\n";
    $result = $etlService->runETL('BKC');
    
    if ($result['success']) {
        echo "✅ ETL Success: {$result['records_processed']} records processed\n";
    } else {
        echo "❌ ETL Failed: {$result['error']}\n";
    }
    
    echo "\n";
    
    // Test 2: Get Analytics
    echo "📈 Test 2: Getting analytics data...\n";
    $analytics = $etlService->getAnalytics('BKC');
    
    echo "📋 Funnel Data:\n";
    foreach ($analytics['funnel'] as $item) {
        echo "  - {$item['record_type']}: {$item['count']} records, ₹{$item['total_amount']}\n";
    }
    
    echo "\n⚠️  Outstanding Invoices: " . count($analytics['outstanding_invoices']) . " invoices\n";
    
    if (!empty($analytics['outstanding_invoices'])) {
        echo "Top 3 Outstanding:\n";
        for ($i = 0; $i < min(3, count($analytics['outstanding_invoices'])); $i++) {
            $invoice = $analytics['outstanding_invoices'][$i];
            echo "  - {$invoice['document_number']}: ₹{$invoice['outstanding_amount']}\n";
        }
    }
    
    echo "\n";
    
    // Test 3: Check consolidated table
    echo "🗄️  Test 3: Checking consolidated table...\n";
    $db = Database::connect();
    
    $stmt = $db->prepare("
        SELECT 
            record_type, 
            COUNT(*) as count,
            SUM(amount) as total_amount
        FROM finance_consolidated 
        WHERE company_prefix = 'BKC'
        GROUP BY record_type
    ");
    $stmt->execute();
    $consolidatedData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Consolidated Table Data:\n";
    foreach ($consolidatedData as $row) {
        echo "  - {$row['record_type']}: {$row['count']} records, ₹{$row['total_amount']}\n";
    }
    
    echo "\n";
    
    // Test 4: Check dashboard_stats
    echo "📊 Test 4: Checking dashboard_stats...\n";
    $stmt = $db->prepare("SELECT * FROM dashboard_stats WHERE company_prefix = 'BKC'");
    $stmt->execute();
    $dashboardStats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($dashboardStats) {
        echo "Dashboard Stats Available:\n";
        echo "  - Total Revenue: ₹{$dashboardStats['total_revenue']}\n";
        echo "  - Outstanding: ₹{$dashboardStats['outstanding_amount']}\n";
        echo "  - PO Commitments: ₹{$dashboardStats['po_commitments']}\n";
        echo "  - Generated: {$dashboardStats['generated_at']}\n";
    } else {
        echo "❌ No dashboard stats found\n";
    }
    
    echo "\n✅ ETL Test Completed Successfully!\n";
    echo "\n🎯 Next Steps:\n";
    echo "1. Visit: https://athenas.co.in/ergon/finance\n";
    echo "2. Click 'Sync Data' to run ETL\n";
    echo "3. Analytics will be served from SQL tables (fast!)\n";
    
} catch (Exception $e) {
    echo "❌ Test Failed: " . $e->getMessage() . "\n";
    echo "\n🔧 Troubleshooting:\n";
    echo "1. Check SAP PostgreSQL connection\n";
    echo "2. Verify MySQL database permissions\n";
    echo "3. Check error logs\n";
}

echo "\n================================\n";
echo "🏁 Test Complete\n";
?>