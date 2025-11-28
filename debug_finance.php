<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    echo "=== DEBUG FINANCE DATA ===\n\n";
    
    // Check what tables we have
    $stmt = $db->prepare("SELECT table_name, record_count FROM finance_tables");
    $stmt->execute();
    $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Available tables:\n";
    foreach ($tables as $table) {
        echo "- {$table['table_name']}: {$table['record_count']} records\n";
    }
    
    echo "\n=== COMPANY PREFIX ===\n";
    $stmt = $db->prepare("SELECT company_prefix FROM finance_tables WHERE table_name = 'settings'");
    $stmt->execute();
    $prefix = $stmt->fetchColumn();
    echo "Current prefix: " . ($prefix ?: 'NOT SET') . "\n";
    
    echo "\n=== SAMPLE INVOICE DATA ===\n";
    $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_invoices' LIMIT 3");
    $stmt->execute();
    $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($invoices as $i => $row) {
        $data = json_decode($row['data'], true);
        echo "Invoice " . ($i+1) . ":\n";
        echo "- invoice_number: " . ($data['invoice_number'] ?? 'NULL') . "\n";
        echo "- customer_id: " . ($data['customer_id'] ?? 'NULL') . "\n";
        echo "- outstanding_amount: " . ($data['outstanding_amount'] ?? 'NULL') . "\n";
        echo "- payment_status: " . ($data['payment_status'] ?? 'NULL') . "\n";
        echo "\n";
    }
    
    echo "=== SAMPLE CUSTOMER DATA ===\n";
    $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_customer' LIMIT 3");
    $stmt->execute();
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($customers as $i => $row) {
        $data = json_decode($row['data'], true);
        echo "Customer " . ($i+1) . ":\n";
        echo "- id: " . ($data['id'] ?? 'NULL') . "\n";
        echo "- customer_code: " . ($data['customer_code'] ?? 'NULL') . "\n";
        echo "- name: " . ($data['name'] ?? 'NULL') . "\n";
        echo "- display_name: " . ($data['display_name'] ?? 'NULL') . "\n";
        echo "- gstin: " . ($data['gstin'] ?? 'NULL') . "\n";
        echo "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>