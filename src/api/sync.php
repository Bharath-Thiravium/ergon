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
    
    // Clear existing MySQL data
    $mysql->exec('TRUNCATE TABLE finance_invoices');
    $mysql->exec('TRUNCATE TABLE finance_purchase_orders');
    $mysql->exec('TRUNCATE TABLE finance_customer');
    $mysql->exec('TRUNCATE TABLE finance_payments');
    $mysql->exec('TRUNCATE TABLE finance_quotations');
    
    $invoiceCount = $poCount = $customerCount = 0;
    
    $paymentCount = $quotationCount = 0;
    
    // Direct 1:1 sync - all columns
    $tables = ['finance_invoices', 'finance_purchase_orders', 'finance_customer', 'finance_payments', 'finance_quotations'];
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
                if (in_array($key, ['is_filed_in_gstr1', 'reverse_charge_applicable', 'is_rejected', 'is_revised', 'shipping_same_as_billing', 'is_active', 'is_gst_registered', 'statement_import_enabled', 'is_tds_received', 'tds_certificate_issued', 'invoice_created', 'po_created', 'proforma_created'])) {
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
    
    echo json_encode([
        'success' => true,
        'message' => "Synced {$invoiceCount} invoices, {$poCount} POs, {$customerCount} customers, {$paymentCount} payments, {$quotationCount} quotations from PostgreSQL"
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'PostgreSQL sync error: ' . $e->getMessage()
    ]);
}