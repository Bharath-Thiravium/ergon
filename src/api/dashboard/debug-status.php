<?php
require_once __DIR__ . '/../../../app/config/database.php';
header('Content-Type: application/json');

try {
    $db = Database::connect();
    
    $quotations = $db->query("SELECT DISTINCT UPPER(status) as status, COUNT(*) as count FROM finance_quotations GROUP BY UPPER(status)")->fetchAll(PDO::FETCH_ASSOC);
    $invoices = $db->query("SELECT DISTINCT UPPER(payment_status) as status, COUNT(*) as count FROM finance_invoices GROUP BY UPPER(payment_status)")->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['quotations' => $quotations, 'invoices' => $invoices]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
