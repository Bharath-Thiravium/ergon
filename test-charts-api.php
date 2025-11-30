<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $mysql = Database::connect();
    echo "✓ Database connected\n";
    
    // Test quotations table
    $stmt = $mysql->prepare("SELECT COUNT(*) as count FROM finance_quotations");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✓ finance_quotations: " . $result['count'] . " records\n";
    
    // Test invoices table
    $stmt = $mysql->prepare("SELECT COUNT(*) as count FROM finance_invoices");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✓ finance_invoices: " . $result['count'] . " records\n";
    
    // Test purchase_orders table
    $stmt = $mysql->prepare("SELECT COUNT(*) as count FROM finance_purchase_orders");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✓ finance_purchase_orders: " . $result['count'] . " records\n";
    
    // Test payments table
    $stmt = $mysql->prepare("SELECT COUNT(*) as count FROM finance_payments");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✓ finance_payments: " . $result['count'] . " records\n";
    
    // Test customers table
    $stmt = $mysql->prepare("SELECT COUNT(*) as count FROM finance_customers");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✓ finance_customers: " . $result['count'] . " records\n";
    
    echo "\nAll tables accessible!\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
?>
