<?php
require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/services/DataSyncService.php';
header('Content-Type: text/plain');

$db = Database::connect();
$ok = 0; $skip = 0;

function run($db, $label, $sql) {
    global $ok, $skip;
    try {
        $db->exec($sql);
        echo "OK:   $label\n";
        $ok++;
    } catch (Exception $e) {
        $msg = $e->getMessage();
        // Ignore "already exists" / "duplicate" errors
        if (stripos($msg, 'already exists') !== false || stripos($msg, 'Duplicate') !== false || stripos($msg, 'Multiple') !== false) {
            echo "SKIP: $label (already exists)\n";
            $skip++;
        } else {
            echo "ERR:  $label — $msg\n";
        }
    }
}

function addCol($db, $table, $column, $definition) {
    global $ok, $skip;
    try {
        $rows = $db->query("SHOW COLUMNS FROM `$table` LIKE '$column'")->fetchAll();
    } catch (Exception $e) {
        echo "ERR:  $table.$column — table missing: " . $e->getMessage() . "\n";
        return;
    }
    $exists = $rows;
    if ($exists) {
        echo "SKIP: $table.$column (already exists)\n";
        $skip++;
    } else {
        try {
            $db->exec("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
            echo "OK:   $table.$column added\n";
            $ok++;
        } catch (Exception $e) {
            echo "ERR:  $table.$column — " . $e->getMessage() . "\n";
        }
    }
}

function addIndex($db, $table, $index, $column) {
    global $ok, $skip;
    try {
        $rows = $db->query("SHOW INDEX FROM `$table` WHERE Key_name = '$index'")->fetchAll();
    } catch (Exception $e) {
        echo "ERR:  $table.$index — table missing: " . $e->getMessage() . "\n";
        return;
    }
    $exists = $rows;
    if ($exists) {
        echo "SKIP: $table.$index (already exists)\n";
        $skip++;
    } else {
        try {
            $db->exec("ALTER TABLE `$table` ADD INDEX `$index` (`$column`)");
            echo "OK:   $table.$index added\n";
            $ok++;
        } catch (Exception $e) {
            echo "ERR:  $table.$index — " . $e->getMessage() . "\n";
        }
    }
}

echo "=== STEP 1: Create finance_companies table ===\n";
run($db, 'CREATE finance_companies', "CREATE TABLE IF NOT EXISTS finance_companies (
    company_id INT PRIMARY KEY,
    company_prefix VARCHAR(32) NOT NULL,
    company_name VARCHAR(255) NOT NULL,
    INDEX idx_prefix (company_prefix)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

echo "\n=== STEP 2: Create sync_log table ===\n";
run($db, 'CREATE sync_log', "CREATE TABLE IF NOT EXISTS sync_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    table_name VARCHAR(64) NOT NULL,
    records_synced INT DEFAULT 0,
    sync_status VARCHAR(32) DEFAULT 'completed',
    error_message TEXT DEFAULT NULL,
    sync_started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    sync_completed_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

echo "\n=== STEP 3: Add missing columns ===\n";
// finance_invoices
addCol($db, 'finance_invoices', 'outstanding_amount', 'DECIMAL(18,2) DEFAULT 0.00');
addCol($db, 'finance_invoices', 'taxable_amount',     'DECIMAL(18,2) DEFAULT 0.00');
addCol($db, 'finance_invoices', 'amount_paid',        'DECIMAL(18,2) DEFAULT 0.00');
addCol($db, 'finance_invoices', 'company_id',         'BIGINT DEFAULT NULL');

// finance_quotations
addCol($db, 'finance_quotations', 'quotation_amount', 'DECIMAL(18,2) DEFAULT 0.00');
addCol($db, 'finance_quotations', 'company_id',       'BIGINT DEFAULT NULL');

// finance_purchase_orders
addCol($db, 'finance_purchase_orders', 'po_total_value', 'DECIMAL(18,2) DEFAULT 0.00');
addCol($db, 'finance_purchase_orders', 'po_status',      'VARCHAR(64) DEFAULT NULL');
addCol($db, 'finance_purchase_orders', 'company_id',     'BIGINT DEFAULT NULL');

// finance_payments
addCol($db, 'finance_payments', 'company_id',     'BIGINT DEFAULT NULL');
addCol($db, 'finance_payments', 'payment_id',     'VARCHAR(128) DEFAULT NULL');
addCol($db, 'finance_payments', 'receipt_number', 'VARCHAR(128) DEFAULT NULL');

// finance_customers
addCol($db, 'finance_customers', 'customer_id', 'BIGINT DEFAULT NULL');

echo "\n=== STEP 4: Add missing indexes ===\n";
addIndex($db, 'finance_invoices',        'idx_company_id', 'company_id');
addIndex($db, 'finance_quotations',      'idx_company_id', 'company_id');
addIndex($db, 'finance_purchase_orders', 'idx_company_id', 'company_id');
addIndex($db, 'finance_payments',        'idx_company_id', 'company_id');

echo "\n=== STEP 5: Sync all tables from PostgreSQL ===\n";
try {
$sync = new DataSyncService();
} catch (Exception $e) {
    echo "ERR: DataSyncService init failed — " . $e->getMessage() . "\n";
    goto verify;
}
if (!$sync->isPostgreSQLAvailable()) {
    echo "ERR: PostgreSQL not available\n";
} else {
    $results = $sync->syncAllTables();
    foreach ($results as $table => $r) {
        $err = !empty($r['error']) ? " — {$r['error']}" : '';
        echo "{$r['status']}: $table ({$r['records']} records)$err\n";
    }
}

verify:
echo "\n=== STEP 6: Verify counts ===\n";
foreach (['finance_companies','finance_customers','finance_quotations','finance_purchase_orders','finance_invoices','finance_payments','sync_log'] as $t) {
    $count = $db->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
    echo "$t: $count rows\n";
}

echo "\n=== DONE: $ok applied, $skip skipped ===\n";
echo "Safe to delete this file after running.\n";
