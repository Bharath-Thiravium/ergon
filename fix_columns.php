<?php
// One-time column fix migration - DELETE AFTER RUNNING
require_once __DIR__ . '/app/config/database.php';

$db = Database::connect();
$results = [];

$fixes = [
    // finance_quotations - add quotation_amount if missing
    "ALTER TABLE finance_quotations ADD COLUMN IF NOT EXISTS quotation_amount DECIMAL(18,2) DEFAULT 0.00",
    "ALTER TABLE finance_quotations ADD COLUMN IF NOT EXISTS quotation_date DATE DEFAULT NULL",
    "ALTER TABLE finance_quotations ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP",

    // finance_purchase_orders - add po_total_value, po_status if missing
    "ALTER TABLE finance_purchase_orders ADD COLUMN IF NOT EXISTS po_total_value DECIMAL(18,2) DEFAULT 0.00",
    "ALTER TABLE finance_purchase_orders ADD COLUMN IF NOT EXISTS po_date DATE DEFAULT NULL",
    "ALTER TABLE finance_purchase_orders ADD COLUMN IF NOT EXISTS po_status VARCHAR(64) DEFAULT NULL",
    "ALTER TABLE finance_purchase_orders ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP",

    // finance_invoices - add missing columns
    "ALTER TABLE finance_invoices ADD COLUMN IF NOT EXISTS taxable_amount DECIMAL(18,2) DEFAULT 0.00",
    "ALTER TABLE finance_invoices ADD COLUMN IF NOT EXISTS amount_paid DECIMAL(18,2) DEFAULT 0.00",
    "ALTER TABLE finance_invoices ADD COLUMN IF NOT EXISTS igst_amount DECIMAL(18,2) DEFAULT 0.00",
    "ALTER TABLE finance_invoices ADD COLUMN IF NOT EXISTS cgst_amount DECIMAL(18,2) DEFAULT 0.00",
    "ALTER TABLE finance_invoices ADD COLUMN IF NOT EXISTS sgst_amount DECIMAL(18,2) DEFAULT 0.00",
    "ALTER TABLE finance_invoices ADD COLUMN IF NOT EXISTS due_date DATE DEFAULT NULL",
    "ALTER TABLE finance_invoices ADD COLUMN IF NOT EXISTS invoice_date DATE DEFAULT NULL",
    "ALTER TABLE finance_invoices ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP",

    // finance_payments - add missing columns
    "ALTER TABLE finance_payments ADD COLUMN IF NOT EXISTS payment_id VARCHAR(128) DEFAULT NULL",
    "ALTER TABLE finance_payments ADD COLUMN IF NOT EXISTS receipt_number VARCHAR(128) DEFAULT NULL",
    "ALTER TABLE finance_payments ADD COLUMN IF NOT EXISTS payment_date DATE DEFAULT NULL",
    "ALTER TABLE finance_payments ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP",
];

foreach ($fixes as $sql) {
    try {
        $db->exec($sql);
        $results[] = "✓ " . substr($sql, 0, 80);
    } catch (Exception $e) {
        $results[] = "✗ " . substr($sql, 0, 80) . "\n  Error: " . $e->getMessage();
    }
}

echo "=== Column Fix Migration ===\n\n";
foreach ($results as $r) echo "$r\n";
echo "\nDELETE this file now, then run manual_sync.php\n";
?>
