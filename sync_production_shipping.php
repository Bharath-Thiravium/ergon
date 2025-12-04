<?php
// Sync shipping addresses to production
require_once __DIR__ . '/src/services/PostgreSQLSyncService.php';

try {
    $syncService = new PostgreSQLSyncService();
    $result = $syncService->syncCustomerShippingAddress();
    
    if ($result['success']) {
        echo "✅ Production shipping sync completed\n";
        echo "Synced: {$result['synced']} addresses\n";
        echo "Skipped: {$result['skipped']} duplicates\n";
    } else {
        echo "❌ Sync failed: {$result['error']}\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>