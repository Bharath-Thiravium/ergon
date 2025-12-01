<?php
require_once __DIR__ . '/../../../app/config/database.php';
header('Content-Type: application/json');

try {
    $db = Database::connect();
    
    $invoices = $db->query("SELECT COUNT(*) as count, MIN(due_date) as min_date, MAX(due_date) as max_date FROM finance_invoices")->fetch(PDO::FETCH_ASSOC);
    $payments = $db->query("SELECT COUNT(*) as count, SUM(amount) as total FROM finance_payments")->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode(['invoices' => $invoices, 'payments' => $payments]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
