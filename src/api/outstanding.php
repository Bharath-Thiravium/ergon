<?php
require_once __DIR__ . '/../../app/config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    $db = Database::connect();
    
    $prefix = $_GET['prefix'] ?? '';
    $limit = $_GET['limit'] ?? 50;
    
    if (empty($prefix)) {
        throw new Exception('Prefix is required');
    }
    
    $len = strlen($prefix);
    
    $sql = "SELECT 
                i.invoice_number,
                COALESCE(c.display_name, c.name, i.customer_id) AS customer_name,
                i.invoice_date,
                i.total_amount,
                (i.total_amount - i.paid_amount) AS outstanding_amount,
                DATEDIFF(CURDATE(), i.due_date) AS days_overdue,
                CASE 
                    WHEN (i.total_amount - i.paid_amount) > 0 AND i.due_date < CURDATE() THEN 'Overdue'
                    ELSE i.status
                END AS status,
                CONCAT_WS(', ', sa.shipping_address_line1, sa.shipping_address_line2, sa.shipping_city, sa.shipping_state) AS shipping_address
            FROM finance_invoices i
            LEFT JOIN finance_customer c ON i.customer_id = c.id
            LEFT JOIN finance_customer sa ON sa.id = i.customer_id
            WHERE LEFT(i.invoice_number, $len) = ?
              AND (i.total_amount - i.paid_amount) > 0
            ORDER BY days_overdue DESC, outstanding_amount DESC
            LIMIT ?";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$prefix, (int)$limit]);
    $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $invoices,
        'count' => count($invoices),
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
