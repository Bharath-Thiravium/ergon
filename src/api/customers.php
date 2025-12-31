<?php
require_once __DIR__ . '/../../app/config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    $db = Database::connect();
    
    $prefix = $_GET['prefix'] ?? '';
    
    if (empty($prefix)) {
        throw new Exception('Prefix is required');
    }
    
    $len = strlen($prefix);
    
    // Get unique customers from all finance tables for the given prefix
    $sql = "SELECT DISTINCT c.id, c.display_name 
            FROM finance_customer c
            WHERE c.id IN (
                SELECT DISTINCT customer_id FROM finance_invoices 
                WHERE customer_id IS NOT NULL AND LEFT(invoice_number, $len) = ?
                UNION
                SELECT DISTINCT customer_id FROM finance_quotations 
                WHERE customer_id IS NOT NULL AND LEFT(quotation_number, $len) = ?
                UNION
                SELECT DISTINCT customer_id FROM finance_purchase_orders 
                WHERE customer_id IS NOT NULL AND (LEFT(po_number, $len) = ? OR LEFT(internal_po_number, $len) = ?)
            )
            ORDER BY c.display_name";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$prefix, $prefix, $prefix, $prefix]);
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'customers' => $customers,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>
