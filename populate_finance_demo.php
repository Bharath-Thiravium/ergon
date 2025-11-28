<?php
// Quick Finance Demo Data Population
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    // Create tables
    $db->exec("CREATE TABLE IF NOT EXISTS finance_tables (
        id INT AUTO_INCREMENT PRIMARY KEY,
        table_name VARCHAR(100) UNIQUE,
        record_count INT,
        last_sync TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        company_prefix VARCHAR(10) DEFAULT 'BKC'
    )");
    
    $db->exec("CREATE TABLE IF NOT EXISTS finance_data (
        id INT AUTO_INCREMENT PRIMARY KEY,
        table_name VARCHAR(100),
        data JSON,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX(table_name)
    )");
    
    // Sample data
    $invoices = [];
    $quotations = [];
    $customers = [];
    
    // Generate customers
    for ($i = 1; $i <= 10; $i++) {
        $customers[] = [
            'id' => $i,
            'name' => 'Customer ' . $i,
            'display_name' => 'Customer ' . $i,
            'gstin' => '29ABCDE' . str_pad($i, 4, '0', STR_PAD_LEFT) . 'F1Z5'
        ];
    }
    
    // Generate invoices
    for ($i = 1; $i <= 25; $i++) {
        $customerId = rand(1, 10);
        $totalAmount = rand(25000, 200000);
        $outstanding = rand(0, 1) ? rand(0, $totalAmount) : 0;
        
        $invoices[] = [
            'invoice_number' => 'BKC-INV-' . str_pad($i, 3, '0', STR_PAD_LEFT),
            'customer_id' => $customerId,
            'customer_name' => 'Customer ' . $customerId,
            'total_amount' => $totalAmount,
            'outstanding_amount' => $outstanding,
            'due_date' => date('Y-m-d', strtotime('-' . rand(0, 60) . ' days')),
            'payment_status' => $outstanding > 0 ? 'unpaid' : 'paid',
            'gst_rate' => 0.18
        ];
    }
    
    // Generate quotations
    for ($i = 1; $i <= 15; $i++) {
        $customerId = rand(1, 10);
        $quotations[] = [
            'quotation_number' => 'BKC-Q-' . str_pad($i, 3, '0', STR_PAD_LEFT),
            'customer_id' => $customerId,
            'customer_name' => 'Customer ' . $customerId,
            'total_amount' => rand(30000, 250000),
            'status' => ['draft', 'revised', 'converted'][rand(0, 2)],
            'valid_until' => date('Y-m-d', strtotime('+' . rand(15, 45) . ' days'))
        ];
    }
    
    // Insert data
    $tables = [
        'finance_invoices' => $invoices,
        'finance_quotations' => $quotations,
        'finance_customers' => $customers
    ];
    
    foreach ($tables as $tableName => $records) {
        // Clear existing
        $stmt = $db->prepare("DELETE FROM finance_data WHERE table_name = ?");
        $stmt->execute([$tableName]);
        
        // Insert new
        foreach ($records as $record) {
            $stmt = $db->prepare("INSERT INTO finance_data (table_name, data) VALUES (?, ?)");
            $stmt->execute([$tableName, json_encode($record)]);
        }
        
        // Update count
        $stmt = $db->prepare("INSERT INTO finance_tables (table_name, record_count) VALUES (?, ?) 
                             ON DUPLICATE KEY UPDATE record_count = ?, last_sync = NOW()");
        $stmt->execute([$tableName, count($records), count($records)]);
    }
    
    echo "✅ Demo finance data populated successfully!\n";
    echo "📊 Invoices: " . count($invoices) . "\n";
    echo "📝 Quotations: " . count($quotations) . "\n";
    echo "👥 Customers: " . count($customers) . "\n";
    echo "\n🔗 Visit: https://athenas.co.in/ergon/finance\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>