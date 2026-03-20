<?php
require_once __DIR__ . '/app/config/database.php';
header('Content-Type: text/plain');

try {
    $db = Database::connect();

    $db->exec("CREATE TABLE IF NOT EXISTS finance_companies (
        company_id INT PRIMARY KEY,
        company_prefix VARCHAR(32) NOT NULL,
        company_name VARCHAR(255) NOT NULL,
        INDEX idx_prefix (company_prefix)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "finance_companies: created\n";

    foreach (['finance_quotations','finance_purchase_orders','finance_invoices','finance_payments'] as $t) {
        try {
            $db->exec("ALTER TABLE $t ADD INDEX idx_company_id (company_id)");
            echo "$t: index added\n";
        } catch (Exception $e) {
            echo "$t: index already exists or skipped\n";
        }
    }

    echo "\nDone. Now run manual_sync.php\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
