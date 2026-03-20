<?php
/**
 * Manual PostgreSQL Sync Trigger
 * Use this to manually trigger sync and debug issues
 */

require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/services/DataSyncService.php';

echo "=== Manual PostgreSQL Sync ===\n\n";
echo "pdo_pgsql loaded: " . (extension_loaded('pdo_pgsql') ? 'YES' : 'NO') . "\n\n";


try {
    echo "Initializing sync service...\n";
    $syncService = new DataSyncService();

    if (!$syncService->isPostgreSQLAvailable()) {
        echo "⚠️  PostgreSQL sync unavailable: pdo_pgsql driver is not installed on this server.\n";
        echo "The rest of the application continues to work normally.\n";
        echo "To enable sync, ask your hosting provider to enable the pdo_pgsql PHP extension.\n";
        exit;
    }

    echo "Starting sync process...\n\n";
    $results = $syncService->syncAllTables();
    
    echo "=== Sync Results ===\n";
    foreach ($results as $table => $result) {
        $icon = $result['status'] === 'success' ? '✓' : '⚠️';
        echo "$icon Table: $table | Status: {$result['status']} | Records: {$result['records']}\n";
        if (isset($result['error'])) {
            echo "  Error: " . $result['error'] . "\n";
        }
    }
    
    echo "\n=== Sync History ===\n";
    $history = $syncService->getSyncHistory(5);
    foreach ($history as $log) {
        echo "[{$log['sync_started_at']}] {$log['table_name']} — {$log['sync_status']} ({$log['records_synced']} records)\n";
        if ($log['error_message']) {
            echo "  Error: " . $log['error_message'] . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Sync failed: " . $e->getMessage() . "\n";
}
?>