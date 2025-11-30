<?php
require_once __DIR__ . '/../../app/config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    $db = Database::connect();
    $prefix = $_GET['prefix'] ?? '';
    $recordType = $_GET['record_type'] ?? '';
    $limit = min(50, max(1, intval($_GET['limit'] ?? 20)));
    $len = strlen($prefix);
    
    $activities = [];
    
    // Quotations
    if (!$recordType || $recordType === 'quotation') {
        $sql = "SELECT 'quotation' as record_type, q.quotation_number as document_number, 
                       COALESCE(c.display_name, c.name) as customer_name, q.status, 
                       q.total_amount as amount, q.quotation_date as created_at
                FROM finance_quotations q 
                LEFT JOIN finance_customer c ON q.customer_id = c.id
                WHERE LEFT(q.quotation_number, $len) = ?
                ORDER BY q.quotation_date DESC LIMIT $limit";
        $stmt = $db->prepare($sql);
        $stmt->execute([$prefix]);
        $activities = array_merge($activities, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }
    
    // Purchase Orders
    if (!$recordType || $recordType === 'purchase_order') {
        $sql = "SELECT 'purchase_order' as record_type, p.po_number as document_number,
                       COALESCE(c.display_name, c.name) as customer_name, p.status,
                       p.total_amount as amount, p.po_date as created_at
                FROM finance_purchase_orders p
                LEFT JOIN finance_customer c ON p.customer_id = c.id
                WHERE LEFT(p.po_number, $len) = ?
                ORDER BY p.po_date DESC LIMIT $limit";
        $stmt = $db->prepare($sql);
        $stmt->execute([$prefix]);
        $activities = array_merge($activities, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }
    
    // Invoices
    if (!$recordType || $recordType === 'invoice') {
        $sql = "SELECT 'invoice' as record_type, i.invoice_number as document_number,
                       COALESCE(c.display_name, c.name) as customer_name, i.status,
                       i.total_amount as amount, i.invoice_date as created_at
                FROM finance_invoices i
                LEFT JOIN finance_customer c ON i.customer_id = c.id
                WHERE LEFT(i.invoice_number, $len) = ?
                ORDER BY i.invoice_date DESC LIMIT $limit";
        $stmt = $db->prepare($sql);
        $stmt->execute([$prefix]);
        $activities = array_merge($activities, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }
    
    // Payments
    if (!$recordType || $recordType === 'payment') {
        $sql = "SELECT 'payment' as record_type, p.payment_number as document_number,
                       COALESCE(c.display_name, c.name) as customer_name, p.status,
                       p.amount, p.payment_date as created_at
                FROM finance_payments p
                LEFT JOIN finance_customer c ON p.customer_id = c.id
                WHERE LEFT(p.payment_number, $len) = ?
                ORDER BY p.payment_date DESC LIMIT $limit";
        $stmt = $db->prepare($sql);
        $stmt->execute([$prefix]);
        $activities = array_merge($activities, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }
    
    // Sort by created_at DESC and limit
    usort($activities, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    $activities = array_slice($activities, 0, $limit);
    
    $icons = [
        'quotation' => '📝',
        'purchase_order' => '🛒', 
        'invoice' => '💰',
        'payment' => '💳'
    ];
    
    foreach ($activities as &$activity) {
        $activity['icon'] = $icons[$activity['record_type']] ?? '📈';
        $activity['formatted_amount'] = number_format($activity['amount'], 2);
    }
    
    echo json_encode([
        'success' => true,
        'data' => $activities,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}