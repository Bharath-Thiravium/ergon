<?php
require_once __DIR__ . '/../../app/config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // MySQL connection
    $mysql = Database::connect();
    
    // PostgreSQL connection with SSL and timeout
    $pgDsn = "pgsql:host=72.60.218.167;port=5432;dbname=modernsap;sslmode=require";
    $pgOptions = [
        PDO::ATTR_TIMEOUT => 30,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ];
    
    $pg = new PDO($pgDsn, 'postgres', 'mango', $pgOptions);
    
    // Test PostgreSQL connection
    $testQuery = $pg->query("SELECT 1 as test");
    if (!$testQuery) {
        throw new Exception('PostgreSQL connection test failed');
    }
    
    // Create shipping table if not exists
    $mysql->exec("CREATE TABLE IF NOT EXISTS `finance_customershippingaddress` (
      `id` bigint NOT NULL AUTO_INCREMENT,
      `label` varchar(255) NOT NULL,
      `address_line1` varchar(255) NOT NULL,
      `address_line2` varchar(255) DEFAULT NULL,
      `city` varchar(255) NOT NULL,
      `state` varchar(255) NOT NULL,
      `pincode` varchar(20) NOT NULL,
      `country` varchar(255) NOT NULL DEFAULT 'India',
      `is_default` tinyint(1) NOT NULL DEFAULT '0',
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      `customer_id` bigint NOT NULL,
      PRIMARY KEY (`id`),
      KEY `idx_customer_id` (`customer_id`),
      KEY `idx_is_default` (`is_default`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    // Clear existing MySQL data
    $mysql->exec('TRUNCATE TABLE finance_invoices');
    $mysql->exec('TRUNCATE TABLE finance_purchase_orders');
    $mysql->exec('TRUNCATE TABLE finance_customer');
    $mysql->exec('TRUNCATE TABLE finance_payments');
    $mysql->exec('TRUNCATE TABLE finance_quotations');
    $mysql->exec('TRUNCATE TABLE finance_customershippingaddress');
    
    $invoiceCount = $poCount = $customerCount = 0;
    
    $paymentCount = $quotationCount = 0;
    
    // Direct 1:1 sync - all columns
    $tables = ['finance_invoices', 'finance_purchase_orders', 'finance_customer', 'finance_payments', 'finance_quotations', 'finance_customershippingaddress'];
    $counts = [];
    
    foreach ($tables as $table) {
        $pgStmt = $pg->query("SELECT * FROM $table LIMIT 1000");
        $rows = $pgStmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($rows)) {
            $counts[$table] = 0;
            continue;
        }
        
        $columns = array_keys($rows[0]);
        $placeholders = str_repeat('?,', count($columns) - 1) . '?';
        $columnList = implode(',', $columns);
        
        $mysqlStmt = $mysql->prepare("INSERT INTO $table ($columnList) VALUES ($placeholders)");
        
        $count = 0;
        foreach ($rows as $row) {
            // Convert PostgreSQL data to MySQL format
            foreach ($row as $key => $value) {
                // Convert timestamps
                if ($value && (strpos($key, '_at') !== false || strpos($key, '_date') !== false)) {
                    if (preg_match('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', $value)) {
                        $row[$key] = date('Y-m-d H:i:s', strtotime($value));
                    }
                }
                // Convert booleans
                if (in_array($key, ['is_filed_in_gstr1', 'reverse_charge_applicable', 'is_rejected', 'is_revised', 'shipping_same_as_billing', 'is_active', 'is_gst_registered', 'statement_import_enabled', 'is_tds_received', 'tds_certificate_issued', 'invoice_created', 'po_created', 'proforma_created', 'is_default'])) {
                    if ($value === 't' || $value === true || $value === '1') {
                        $row[$key] = 1;
                    } else {
                        $row[$key] = 0;
                    }
                }
            }
            $mysqlStmt->execute(array_values($row));
            $count++;
        }
        $counts[$table] = $count;
    }
    
    $invoiceCount = $counts['finance_invoices'] ?? 0;
    $poCount = $counts['finance_purchase_orders'] ?? 0;
    $customerCount = $counts['finance_customer'] ?? 0;
    $paymentCount = $counts['finance_payments'] ?? 0;
    $quotationCount = $counts['finance_quotations'] ?? 0;
    $shippingCount = $counts['finance_customershippingaddress'] ?? 0;
    
    echo json_encode([
        'success' => true,
        'message' => "Synced {$invoiceCount} invoices, {$poCount} POs, {$customerCount} customers, {$paymentCount} payments, {$quotationCount} quotations, {$shippingCount} shipping addresses from PostgreSQL"
    ]);
    
} catch (Exception $e) {
    // Fallback: If PostgreSQL fails, try shipping address sync only
    try {
        $mysql = Database::connect();
        $pg_dsn = 'pgsql:host=72.60.218.167;port=5432;dbname=modernsap';
        $pg = new PDO($pg_dsn, 'postgres', 'mango', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        
        $query = "SELECT id, label, address_line1, address_line2, city, state, pincode, country, is_default, created_at, updated_at, customer_id FROM finance_customershippingaddress";
        $stmt = $pg->query($query);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $inserted = 0;
        foreach ($rows as $row) {
            $row['is_default'] = $row['is_default'] ? 1 : 0;
            $row['created_at'] = substr($row['created_at'], 0, 19);
            $row['updated_at'] = substr($row['updated_at'], 0, 19);
            
            $sql = "INSERT INTO finance_customershippingaddress (id, label, address_line1, address_line2, city, state, pincode, country, is_default, created_at, updated_at, customer_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE label = VALUES(label), address_line1 = VALUES(address_line1), address_line2 = VALUES(address_line2), city = VALUES(city), state = VALUES(state), pincode = VALUES(pincode), country = VALUES(country), is_default = VALUES(is_default), updated_at = VALUES(updated_at)";
            
            $stmt = $mysql->prepare($sql);
            $stmt->execute([$row['id'], $row['label'], $row['address_line1'], $row['address_line2'], $row['city'], $row['state'], $row['pincode'], $row['country'], $row['is_default'], $row['created_at'], $row['updated_at'], $row['customer_id']]);
            $inserted++;
        }
        
        echo json_encode([
            'success' => true,
            'message' => "PostgreSQL sync failed, but synced {$inserted} shipping addresses separately"
        ]);
        
    } catch (Exception $fallbackError) {
        echo json_encode([
            'success' => false,
            'message' => 'PostgreSQL sync failed: ' . $e->getMessage()
        ]);
    }
}
