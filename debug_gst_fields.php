<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    echo "Checking GST fields in finance_invoices data...\n";
    
    $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_invoices' LIMIT 3");
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($results as $i => $row) {
        $data = json_decode($row['data'], true);
        echo "\nInvoice " . ($i + 1) . " fields:\n";
        echo "Available keys: " . implode(', ', array_keys($data)) . "\n";
        
        // Check for GST-related fields
        $gstFields = ['igst', 'cgst', 'sgst', 'gst_amount', 'tax_amount', 'total_tax'];
        foreach ($gstFields as $field) {
            if (isset($data[$field])) {
                echo "  $field: " . $data[$field] . "\n";
            }
        }
        
        // Check other important fields
        $importantFields = ['invoice_number', 'taxable_amount', 'total_amount', 'amount_paid', 'outstanding_amount'];
        foreach ($importantFields as $field) {
            if (isset($data[$field])) {
                echo "  $field: " . $data[$field] . "\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>