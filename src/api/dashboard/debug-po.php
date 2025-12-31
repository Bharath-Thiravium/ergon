<?php
require_once __DIR__ . '/../../../app/config/database.php';
header('Content-Type: application/json');

try {
    $db = Database::connect();
    
    $pos = $db->query("SELECT COUNT(*) as count, SUM(total_amount) as total, LEFT(po_number, 2) as prefix FROM finance_purchase_orders GROUP BY LEFT(po_number, 2)")->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['purchase_orders' => $pos]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
