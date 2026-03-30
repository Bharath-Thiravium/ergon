<?php
ini_set('max_execution_time', 300);
ini_set('memory_limit', '256M');
require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/services/DataSyncService.php';
header('Content-Type: text/plain');
header('X-Accel-Buffering: no');
if (ob_get_level()) ob_end_flush();
ob_implicit_flush(true);

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

echo "=== STEP 0: Create core tables if missing ===\n";
run($db, 'CREATE finance_customers', "CREATE TABLE IF NOT EXISTS finance_customers (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    customer_id BIGINT DEFAULT NULL,
    customer_name VARCHAR(255) NOT NULL,
    customer_gstin VARCHAR(64) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_customer_id (customer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
run($db, 'CREATE finance_quotations', "CREATE TABLE IF NOT EXISTS finance_quotations (
    id BIGINT PRIMARY KEY, quotation_number VARCHAR(128), customer_id BIGINT, company_id BIGINT,
    quotation_amount DECIMAL(18,2) DEFAULT 0.00, quotation_date DATE, status VARCHAR(64),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX idx_company_id (company_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
run($db, 'CREATE finance_purchase_orders', "CREATE TABLE IF NOT EXISTS finance_purchase_orders (
    id BIGINT PRIMARY KEY, po_number VARCHAR(128), customer_id BIGINT, company_id BIGINT,
    po_total_value DECIMAL(18,2) DEFAULT 0.00, po_date DATE, po_status VARCHAR(64),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX idx_company_id (company_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
run($db, 'CREATE finance_invoices', "CREATE TABLE IF NOT EXISTS finance_invoices (
    id BIGINT PRIMARY KEY, invoice_number VARCHAR(128), customer_id BIGINT, company_id BIGINT,
    total_amount DECIMAL(18,2) DEFAULT 0.00, taxable_amount DECIMAL(18,2) DEFAULT 0.00,
    amount_paid DECIMAL(18,2) DEFAULT 0.00, outstanding_amount DECIMAL(18,2) DEFAULT 0.00,
    igst_amount DECIMAL(18,2) DEFAULT 0.00, cgst_amount DECIMAL(18,2) DEFAULT 0.00,
    sgst_amount DECIMAL(18,2) DEFAULT 0.00, due_date DATE, invoice_date DATE, status VARCHAR(64),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX idx_company_id (company_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
run($db, 'CREATE finance_payments', "CREATE TABLE IF NOT EXISTS finance_payments (
    id BIGINT PRIMARY KEY, payment_number VARCHAR(128), customer_id BIGINT, company_id BIGINT,
    amount DECIMAL(18,2) DEFAULT 0.00, payment_date DATE, receipt_number VARCHAR(128), status VARCHAR(64),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX idx_company_id (company_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

echo "\n=== STEP 1: Create finance_companies table ===\n";
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
// Upgrade idx_customer_id to UNIQUE if not already
try {
    $idxRows = $db->query("SHOW INDEX FROM finance_customers WHERE Key_name = 'uniq_customer_id'")->fetchAll();
    if (!$idxRows) {
        // Dedupe first: keep only the lowest id per customer_id
        $db->exec("DELETE fc1 FROM finance_customers fc1 INNER JOIN finance_customers fc2 WHERE fc1.id > fc2.id AND fc1.customer_id = fc2.customer_id AND fc1.customer_id IS NOT NULL");
        // Drop old non-unique index if present
        $oldIdx = $db->query("SHOW INDEX FROM finance_customers WHERE Key_name = 'idx_customer_id'")->fetchAll();
        if ($oldIdx) $db->exec("ALTER TABLE finance_customers DROP INDEX idx_customer_id");
        $db->exec("ALTER TABLE finance_customers ADD UNIQUE KEY uniq_customer_id (customer_id)");
        echo "OK:   finance_customers.uniq_customer_id (deduped + unique key added)\n";
        $ok++;
    } else {
        echo "SKIP: finance_customers.uniq_customer_id (already exists)\n";
        $skip++;
    }
} catch (Exception $e) {
    echo "ERR:  finance_customers unique key — " . $e->getMessage() . "\n";
}

echo "\n=== STEP 4: Add missing indexes ===\n";
addIndex($db, 'finance_invoices',        'idx_company_id', 'company_id');
addIndex($db, 'finance_quotations',      'idx_company_id', 'company_id');
addIndex($db, 'finance_purchase_orders', 'idx_company_id', 'company_id');
addIndex($db, 'finance_payments',        'idx_company_id', 'company_id');

echo "\n=== STEP 5: Sync all tables from PostgreSQL ===\n";
flush();
try {
    $sync = new DataSyncService();
} catch (Exception $e) {
    echo "ERR: DataSyncService init failed — " . $e->getMessage() . "\n";
    goto verify;
}
echo "DataSyncService created\n"; flush();
if (!$sync->isPostgreSQLAvailable()) {
    echo "ERR: PostgreSQL not available — pdo_pgsql driver missing or connection refused\n";
} else {
    echo "PostgreSQL connected\n"; flush();
    $results = $sync->syncAllTables();
    foreach ($results as $table => $r) {
        $err = !empty($r['error']) ? " — {$r['error']}" : '';
        echo "{$r['status']}: $table ({$r['records']} records)$err\n"; flush();
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
