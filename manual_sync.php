<?php
/**
 * Manual PostgreSQL Sync Trigger
 */

require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/services/DataSyncService.php';

echo "=== Manual PostgreSQL Sync ===\n\n";

try {
    $syncService = new DataSyncService();

    if (!$syncService->isPostgreSQLAvailable()) {
        echo "⚠️  PostgreSQL sync unavailable.\n";
        exit;
    }

    $results = $syncService->syncAllTables();

    echo "=== Sync Results ===\n";
    foreach ($results as $table => $result) {
        $icon = $result['status'] === 'success' ? '✓' : '⚠️';
        echo "$icon $table: {$result['status']} ({$result['records']} records)\n";
        if (isset($result['error'])) {
            echo "  Error: " . $result['error'] . "\n";
        }
    }

    echo "\n=== Sync History ===\n";
    foreach ($syncService->getSyncHistory(5) as $log) {
        echo "[{$log['sync_started_at']}] {$log['table_name']} — {$log['sync_status']} ({$log['records_synced']} records)\n";
        if ($log['error_message']) {
            echo "  Error: " . $log['error_message'] . "\n";
        }
    }

} catch (Exception $e) {
    echo "❌ Sync failed: " . $e->getMessage() . "\n";
}
?>
