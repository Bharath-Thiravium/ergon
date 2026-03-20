<?php
require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/services/DataSyncService.php';
header('Content-Type: text/plain');

try {
    $sync = new DataSyncService();

    if (!$sync->isPostgreSQLAvailable()) {
        echo "PostgreSQL NOT available\n";
        exit;
    }
    echo "PostgreSQL: connected\n\n";

    $results = $sync->syncAllTables();
    foreach ($results as $table => $r) {
        echo "$table: {$r['status']} ({$r['records']} records)";
        if (!empty($r['error'])) echo " ERROR: {$r['error']}";
        echo "\n";
    }

    echo "\n=== MySQL counts ===\n";
    $db = Database::connect();
    foreach (['finance_companies','finance_customers','finance_quotations','finance_purchase_orders','finance_invoices','finance_payments'] as $t) {
        $count = $db->query("SELECT COUNT(*) FROM $t")->fetchColumn();
        echo "$t: $count\n";
    }

    echo "\n=== Sync log (last 10) ===\n";
    $rows = $db->query("SELECT table_name, sync_status, records_synced, error_message, sync_started_at FROM sync_log ORDER BY sync_started_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        echo "[{$r['sync_started_at']}] {$r['table_name']}: {$r['sync_status']} ({$r['records_synced']})";
        if ($r['error_message']) echo " ERR: {$r['error_message']}";
        echo "\n";
    }

} catch (Exception $e) {
    echo "FATAL: " . $e->getMessage();
}
