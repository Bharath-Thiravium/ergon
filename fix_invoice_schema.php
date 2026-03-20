<?php
require_once __DIR__ . '/app/config/database.php';
header('Content-Type: text/plain');

try {
    $db = Database::connect();

    // Add outstanding_amount to finance_invoices if missing
    $db->exec("ALTER TABLE finance_invoices ADD COLUMN IF NOT EXISTS outstanding_amount DECIMAL(18,2) DEFAULT 0.00");
    echo "finance_invoices.outstanding_amount: OK\n";

    // Show current columns for verification
    $cols = $db->query("SHOW COLUMNS FROM finance_invoices")->fetchAll(PDO::FETCH_COLUMN);
    echo "finance_invoices columns: " . implode(', ', $cols) . "\n\n";

    $cols2 = $db->query("SHOW COLUMNS FROM finance_purchase_orders")->fetchAll(PDO::FETCH_COLUMN);
    echo "finance_purchase_orders columns: " . implode(', ', $cols2) . "\n\n";

    $cols3 = $db->query("SHOW COLUMNS FROM finance_quotations")->fetchAll(PDO::FETCH_COLUMN);
    echo "finance_quotations columns: " . implode(', ', $cols3) . "\n\n";

    $cols4 = $db->query("SHOW COLUMNS FROM finance_payments")->fetchAll(PDO::FETCH_COLUMN);
    echo "finance_payments columns: " . implode(', ', $cols4) . "\n\n";

    echo "Done. Now run manual_sync.php to re-sync data.\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
