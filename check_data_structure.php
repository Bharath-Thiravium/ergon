<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    echo "<h2>Data Structure Analysis</h2>";
    
    // Check quotations
    $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_quotations' LIMIT 1");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result) {
        $data = json_decode($result['data'], true);
        echo "<h3>Quotations Sample:</h3>";
        echo "<pre>" . print_r($data, true) . "</pre>";
    }
    
    // Check purchase orders
    $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_purchase_orders' LIMIT 1");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result) {
        $data = json_decode($result['data'], true);
        echo "<h3>Purchase Orders Sample:</h3>";
        echo "<pre>" . print_r($data, true) . "</pre>";
    }
    
    // Check invoices
    $stmt = $db->prepare("SELECT data FROM finance_data WHERE table_name = 'finance_invoices' LIMIT 1");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result) {
        $data = json_decode($result['data'], true);
        echo "<h3>Invoices Sample:</h3>";
        echo "<pre>" . print_r($data, true) . "</pre>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>