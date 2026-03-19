<?php
/**
 * Manual PostgreSQL Sync Trigger
 * Use this to manually trigger sync and debug issues
 */

require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/services/DataSyncService.php';

echo "=== Manual PostgreSQL Sync ===\n\n";

try {
    echo "Initializing sync service...\n";
    $syncService = new DataSyncService();
    
    echo "Starting sync process...\n\n";
    $results = $syncService->syncAllTables();
    
    echo "=== Sync Results ===\n";
    foreach ($results as $table => $result) {
        echo "Table: $table\n";
        echo "Status: " . $result['status'] . "\n";
        echo "Records: " . $result['records'] . "\n";
        if (isset($result['error'])) {
            echo "Error: " . $result['error'] . "\n";
        }
        echo "---\n";
    }
    
    echo "\n=== Sync History ===\n";
    $history = $syncService->getSyncHistory(5);
    foreach ($history as $log) {
        echo "Table: " . $log['table_name'] . "\n";
        echo "Records: " . $log['records_synced'] . "\n";
        echo "Status: " . $log['sync_status'] . "\n";
        echo "Started: " . $log['sync_started_at'] . "\n";
        if ($log['error_message']) {
            echo "Error: " . $log['error_message'] . "\n";
        }
        echo "---\n";
    }
    
} catch (Exception $e) {
    echo "❌ Sync failed: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Next Steps ===\n";
echo "1. If connection fails, run test_postgres_connection.php first\n";
echo "2. Check the sync_log table in MySQL for detailed logs\n";
echo "3. Verify PostgreSQL tables have data to sync\n";
echo "4. Set up cron job to run this automatically\n";
?>