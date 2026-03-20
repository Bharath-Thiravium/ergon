<?php
require_once __DIR__ . '/app/config/database.php';
header('Content-Type: text/plain');

try {
    $db = Database::connect();

    $tables = ['finance_quotations', 'finance_purchase_orders', 'finance_invoices', 'finance_payments'];

    foreach ($tables as $t) {
        // Check if company_id column exists
        $cols = $db->query("SHOW COLUMNS FROM $t LIKE 'company_id'")->fetchAll();
        if (empty($cols)) {
            $db->exec("ALTER TABLE $t ADD COLUMN company_id INT DEFAULT NULL");
            echo "$t: company_id column ADDED\n";
        } else {
            echo "$t: company_id column already exists\n";
        }
    }

    echo "\nNow running sync...\n\n";

    // Run sync directly
    require_once __DIR__ . '/app/services/DataSyncService.php';
    $sync = new DataSyncService();
    $results = $sync->syncAllTables();

    foreach ($results as $table => $result) {
        $status = $result['status'];
        $records = $result['records'];
        $error = $result['error'] ?? '';
        echo "$table: $status ($records records)" . ($error ? " ERROR: $error" : '') . "\n";
    }

    echo "\n=== MySQL counts after sync ===\n";
    foreach (['finance_quotations','finance_purchase_orders','finance_invoices','finance_payments'] as $t) {
        $count = $db->query("SELECT COUNT(*) FROM $t")->fetchColumn();
        $byCompany = $db->query("SELECT company_id, COUNT(*) as cnt FROM $t GROUP BY company_id")->fetchAll(PDO::FETCH_ASSOC);
        echo "$t: $count total\n";
        foreach ($byCompany as $r) echo "  company_id={$r['company_id']}: {$r['cnt']}\n";
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
