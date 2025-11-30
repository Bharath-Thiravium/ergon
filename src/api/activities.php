<?php
require_once __DIR__ . '/../../app/config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    $db = Database::connect();
    $prefix = $_GET['prefix'] ?? '';
    $recordType = $_GET['record_type'] ?? '';
    
    $activities = [];
    $likePattern = $prefix ? $prefix . '%' : '%';
    
    // Invoices (with shipping address)
    if (!$recordType || $recordType === 'invoice') {
        $sql = "SELECT 'invoice' as record_type, i.invoice_number as document_number, i.customer_id, COALESCE(c.name, CAST(i.customer_id AS CHAR)) as customer_name, i.total_amount as amount, i.status, i.invoice_date as created_at, CONCAT_WS(', ', sa.shipping_address_line1, sa.shipping_address_line2, sa.shipping_city, sa.shipping_state) AS shipping_address
                FROM finance_invoices i 
                LEFT JOIN finance_customer c ON i.customer_id = c.id 
                LEFT JOIN finance_customer sa ON sa.id = i.customer_id
                WHERE i.invoice_number LIKE ? 
                ORDER BY i.invoice_date DESC LIMIT 100";
        $stmt = $db->prepare($sql);
        $stmt->execute([$likePattern]);
        $activities = array_merge($activities, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }
    
    // Quotations
    if (!$recordType || $recordType === 'quotation') {
        $sql = "SELECT 'quotation' as record_type, q.quotation_number as document_number, q.customer_id, COALESCE(c.name, CAST(q.customer_id AS CHAR)) as customer_name, q.total_amount as amount, q.status, q.quotation_date as created_at, CONCAT_WS(', ', sa.shipping_address_line1, sa.shipping_address_line2, sa.shipping_city, sa.shipping_state) AS shipping_address
                FROM finance_quotations q 
                LEFT JOIN finance_customer c ON q.customer_id = c.id 
                LEFT JOIN finance_customer sa ON sa.id = q.customer_id
                WHERE q.quotation_number LIKE ? 
                ORDER BY q.quotation_date DESC LIMIT 100";
        $stmt = $db->prepare($sql);
        $stmt->execute([$likePattern]);
        $activities = array_merge($activities, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }
    
    // Purchase Orders
    if (!$recordType || $recordType === 'purchase_order') {
        $sql = "SELECT 'purchase_order' as record_type, p.po_number as document_number, p.customer_id, COALESCE(c.name, CAST(p.customer_id AS CHAR)) as customer_name, p.total_amount as amount, p.status, p.po_date as created_at, CONCAT_WS(', ', sa.shipping_address_line1, sa.shipping_address_line2, sa.shipping_city, sa.shipping_state) AS shipping_address
                FROM finance_purchase_orders p 
                LEFT JOIN finance_customer c ON p.customer_id = c.id 
                LEFT JOIN finance_customer sa ON sa.id = p.customer_id
                WHERE p.internal_po_number LIKE ? 
                ORDER BY p.po_date DESC LIMIT 100";
        $stmt = $db->prepare($sql);
        $stmt->execute([$likePattern]);
        $activities = array_merge($activities, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }
    
    // Payments
    if (!$recordType || $recordType === 'payment') {
        $sql = "SELECT 'payment' as record_type, COALESCE(p.reference_number, p.payment_number) as document_number, p.customer_id, COALESCE(c.name, CAST(p.customer_id AS CHAR)) as customer_name, p.amount, p.status, p.payment_date as created_at, CONCAT_WS(', ', sa.shipping_address_line1, sa.shipping_address_line2, sa.shipping_city, sa.shipping_state) AS shipping_address
                FROM finance_payments p 
                LEFT JOIN finance_customer c ON p.customer_id = c.id 
                LEFT JOIN finance_customer sa ON sa.id = p.customer_id
                WHERE p.payment_number LIKE ? OR p.reference_number LIKE ? 
                ORDER BY p.payment_date DESC LIMIT 100";
        $stmt = $db->prepare($sql);
        $stmt->execute([$likePattern, $likePattern]);
        $activities = array_merge($activities, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }
    
    // Sort by created_at DESC
    usort($activities, function($a, $b) {
        $timeA = strtotime($a['created_at'] ?? '1970-01-01');
        $timeB = strtotime($b['created_at'] ?? '1970-01-01');
        return $timeB - $timeA;
    });
    
    // Return max 20
    $activities = array_slice($activities, 0, 20);
    
    $icons = ['quotation' => '📝', 'purchase_order' => '🛒', 'invoice' => '💰', 'payment' => '💳'];
    
    foreach ($activities as &$activity) {
        $activity['icon'] = $icons[$activity['record_type']] ?? '📈';
        $activity['formatted_amount'] = number_format($activity['amount'] ?? 0, 2);
    }
    
    echo json_encode(['success' => true, 'data' => $activities, 'timestamp' => date('Y-m-d H:i:s')]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage(), 'timestamp' => date('Y-m-d H:i:s')]);
}
?>
