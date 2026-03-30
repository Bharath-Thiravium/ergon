<?php
/**
 * Simple Mock Finance Data Creator
 * Creates basic finance data for testing
 */

require_once __DIR__ . '/app/config/database.php';

try {
    $mysql = Database::connect();
    
    echo "Creating simplified mock finance data...\n\n";
    
    // Create basic finance tables
    $tables = [
        'finance_customers' => "CREATE TABLE IF NOT EXISTS finance_customers (
            customer_id VARCHAR(64) PRIMARY KEY,
            customer_name VARCHAR(255),
            customer_gstin VARCHAR(64),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",
        
        'finance_invoices' => "CREATE TABLE IF NOT EXISTS finance_invoices (
            invoice_number VARCHAR(128) PRIMARY KEY,
            customer_id VARCHAR(64),
            total_amount DECIMAL(18,2) DEFAULT 0.00,
            amount_paid DECIMAL(18,2) DEFAULT 0.00,
            due_date DATE,
            invoice_date DATE,
            status VARCHAR(64),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",
        
        'finance_purchase_orders' => "CREATE TABLE IF NOT EXISTS finance_purchase_orders (
            po_number VARCHAR(128) PRIMARY KEY,
            customer_id VARCHAR(64),
            po_total_value DECIMAL(18,2) DEFAULT 0.00,
            po_date DATE,
            po_status VARCHAR(64),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",
        
        'finance_payments' => "CREATE TABLE IF NOT EXISTS finance_payments (
            payment_id VARCHAR(128) PRIMARY KEY,
            customer_id VARCHAR(64),
            amount DECIMAL(18,2) DEFAULT 0.00,
            payment_date DATE,
            status VARCHAR(64),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )"
    ];
    
    foreach ($tables as $tableName => $sql) {
        $mysql->exec($sql);
        echo "✅ Created table: $tableName\n";
    }
    
    echo "\nInserting mock data...\n";
    
    // Insert customers
    $customers = [
        ['CUST001', 'ABC Industries Ltd', '29ABCDE1234F1Z5'],
        ['CUST002', 'XYZ Corporation', '29FGHIJ5678K1L5'],
        ['CUST003', 'PQR Enterprises', '29MNOPQ9012R1S5']
    ];
    
    $stmt = $mysql->prepare("INSERT INTO finance_customers (customer_id, customer_name, customer_gstin) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE customer_name = VALUES(customer_name)");
    foreach ($customers as $customer) {
        $stmt->execute($customer);
    }
    echo "✅ Inserted " . count($customers) . " customers\n";
    
    // Insert invoices
    $invoices = [
        ['INV-001', 'CUST001', 50000.00, 25000.00, '2024-02-15', '2024-01-15', 'partially_paid'],
        ['INV-002', 'CUST002', 75000.00, 75000.00, '2024-02-20', '2024-01-20', 'paid'],
        ['INV-003', 'CUST003', 120000.00, 0.00, '2024-03-10', '2024-02-10', 'pending']
    ];
    
    $stmt = $mysql->prepare("INSERT INTO finance_invoices (invoice_number, customer_id, total_amount, amount_paid, due_date, invoice_date, status) VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE total_amount = VALUES(total_amount)");
    foreach ($invoices as $invoice) {
        $stmt->execute($invoice);
    }
    echo "✅ Inserted " . count($invoices) . " invoices\n";
    
    // Insert purchase orders
    $pos = [
        ['PO-001', 'CUST001', 60000.00, '2024-01-10', 'confirmed'],
        ['PO-002', 'CUST002', 80000.00, '2024-01-15', 'confirmed'],
        ['PO-003', 'CUST003', 150000.00, '2024-02-05', 'pending']
    ];
    
    $stmt = $mysql->prepare("INSERT INTO finance_purchase_orders (po_number, customer_id, po_total_value, po_date, po_status) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE po_total_value = VALUES(po_total_value)");
    foreach ($pos as $po) {
        $stmt->execute($po);
    }
    echo "✅ Inserted " . count($pos) . " purchase orders\n";
    
    // Insert payments
    $payments = [
        ['PAY-001', 'CUST001', 25000.00, '2024-01-25', 'received'],
        ['PAY-002', 'CUST002', 75000.00, '2024-02-05', 'received']
    ];
    
    $stmt = $mysql->prepare("INSERT INTO finance_payments (payment_id, customer_id, amount, payment_date, status) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE amount = VALUES(amount)");
    foreach ($payments as $payment) {
        $stmt->execute($payment);
    }
    echo "✅ Inserted " . count($payments) . " payments\n";
    
    // Log the mock data creation
    $totalRecords = count($customers) + count($invoices) + count($pos) + count($payments);
    $stmt = $mysql->prepare("INSERT INTO sync_log (table_name, records_synced, sync_status, error_message, sync_started_at, sync_completed_at) VALUES (?, ?, ?, ?, NOW(), NOW())");
    $stmt->execute(['mock_data', $totalRecords, 'completed', 'Mock finance data created successfully']);
    
    echo "\n🎉 Mock finance data created successfully!\n";
    echo "📊 Total records: $totalRecords\n";
    echo "💡 Your finance module is now ready for testing!\n";
    echo "🔄 When PostgreSQL connection is restored, run the sync to get real data.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>