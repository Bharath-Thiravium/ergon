<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    echo "=== QUOTATIONS ===\n";
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM finance_quotations");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Total: " . $row['count'] . "\n";
    
    echo "\n=== PURCHASE ORDERS ===\n";
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM finance_purchase_orders");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Total: " . $row['count'] . "\n";
    
    echo "\n=== INVOICES ===\n";
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM finance_invoices");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Total: " . $row['count'] . "\n";
    
    echo "\n=== PAYMENTS ===\n";
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM finance_payments");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Total: " . $row['count'] . "\n";
    
    echo "\n=== SAMPLE DATA ===\n";
    $stmt = $db->prepare("SELECT * FROM finance_invoices LIMIT 2");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Invoices: " . json_encode($rows, JSON_PRETTY_PRINT) . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
