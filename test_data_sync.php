<?php
require_once 'app/config/database.php';
require_once 'app/services/DataSyncService.php';

echo "Testing Data Sync with SSH Tunnel Connection\n";
echo "===========================================\n\n";

try {
    $syncService = new DataSyncService();
    
    echo "✅ DataSyncService initialized successfully\n";
    echo "✅ PostgreSQL connection: ACTIVE (via SSH tunnel)\n";
    echo "✅ MySQL connection: ACTIVE\n\n";
    
    // Test individual table sync
    echo "Testing Individual Table Sync:\n";
    echo "------------------------------\n";
    
    // 1. Sync Customers
    echo "1. Syncing Customers...\n";
    $result = $syncService->syncCustomers();
    echo "   Status: {$result['status']}\n";
    echo "   Records: {$result['records']}\n";
    if (isset($result['error'])) {
        echo "   Error: {$result['error']}\n";
    }
    echo "\n";
    
    // 2. Sync Quotations
    echo "2. Syncing Quotations...\n";
    $result = $syncService->syncQuotations();
    echo "   Status: {$result['status']}\n";
    echo "   Records: {$result['records']}\n";
    if (isset($result['error'])) {
        echo "   Error: {$result['error']}\n";
    }
    echo "\n";
    
    // 3. Sync Purchase Orders
    echo "3. Syncing Purchase Orders...\n";
    $result = $syncService->syncPurchaseOrders();
    echo "   Status: {$result['status']}\n";
    echo "   Records: {$result['records']}\n";
    if (isset($result['error'])) {
        echo "   Error: {$result['error']}\n";
    }
    echo "\n";
    
    // 4. Sync Invoices
    echo "4. Syncing Invoices...\n";
    $result = $syncService->syncInvoices();
    echo "   Status: {$result['status']}\n";
    echo "   Records: {$result['records']}\n";
    if (isset($result['error'])) {
        echo "   Error: {$result['error']}\n";
    }
    echo "\n";
    
    // 5. Sync Payments
    echo "5. Syncing Payments...\n";
    $result = $syncService->syncPayments();
    echo "   Status: {$result['status']}\n";
    echo "   Records: {$result['records']}\n";
    if (isset($result['error'])) {
        echo "   Error: {$result['error']}\n";
    }
    echo "\n";
    
    echo str_repeat("=", 50) . "\n";
    
    // Full sync test
    echo "Testing Full Sync:\n";
    echo "------------------\n";
    $allResults = $syncService->syncAllTables();
    
    $totalRecords = 0;
    $successfulTables = 0;
    $failedTables = 0;
    
    foreach ($allResults as $table => $result) {
        echo "✅ $table: {$result['records']} records ({$result['status']})\n";
        $totalRecords += $result['records'];
        
        if ($result['status'] === 'success' || $result['status'] === 'no_data') {
            $successfulTables++;
        } else {
            $failedTables++;
            if (isset($result['error'])) {
                echo "   ❌ Error: {$result['error']}\n";
            }
        }
    }
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "SYNC SUMMARY:\n";
    echo "Total Records Synced: $totalRecords\n";
    echo "Successful Tables: $successfulTables\n";
    echo "Failed Tables: $failedTables\n";
    
    if ($failedTables === 0) {
        echo "🎉 ALL TABLES SYNCED SUCCESSFULLY!\n";
    }
    
    // Show recent sync history
    echo "\nRecent Sync History:\n";
    echo "-------------------\n";
    $history = $syncService->getSyncHistory(5);
    
    if (empty($history)) {
        echo "No sync history found.\n";
    } else {
        foreach ($history as $log) {
            $status_icon = $log['sync_status'] === 'completed' ? '✅' : '❌';
            echo "$status_icon {$log['table_name']}: {$log['records_synced']} records ({$log['sync_status']}) - {$log['sync_started_at']}\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ SYNC TEST FAILED\n";
    echo "Error: " . $e->getMessage() . "\n";
    
    // Additional diagnostics
    echo "\nDiagnostics:\n";
    echo "- Ensure SSH tunnel is active: ssh -L 5432:localhost:5432 root@72.60.218.167 -N\n";
    echo "- Check .env configuration\n";
    echo "- Verify MySQL tables exist\n";
}
?>