<?php
require_once __DIR__ . '/../../../app/config/database.php';
header('Content-Type: application/json');

try {
    $db = Database::connect();
    
    $tables = ['finance_quotations', 'finance_invoices', 'finance_purchase_orders', 'finance_payments'];
    $result = [];
    
    foreach ($tables as $table) {
        $stmt = $db->query("DESCRIBE $table");
        $result[$table] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    echo json_encode($result, JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
