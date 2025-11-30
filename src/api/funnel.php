<?php
require_once __DIR__ . '/../../app/config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    $db = Database::connect();
    
    $prefix = $_GET['prefix'] ?? '';
    $customer_id = $_GET['customer_id'] ?? '';
    
    if (empty($prefix)) {
        throw new Exception('Prefix is required');
    }
    
    $len = strlen($prefix);
    
    // CONTAINER 1 - QUOTATIONS
    $sql1 = "SELECT COUNT(*) AS quotation_count, COALESCE(SUM(total_amount),0) AS quotation_value 
             FROM finance_quotations 
             WHERE LEFT(quotation_number, $len) = ?";
    $params1 = [$prefix];
    if ($customer_id) {
        $sql1 .= " AND customer_id = ?";
        $params1[] = $customer_id;
    }
    
    $stmt1 = $db->prepare($sql1);
    $stmt1->execute($params1);
    $container1 = $stmt1->fetch(PDO::FETCH_ASSOC);
    
    // CONTAINER 2 - PURCHASE ORDERS
    $sql2 = "SELECT COUNT(*) AS po_count, COALESCE(SUM(total_amount),0) AS po_value 
             FROM finance_purchase_orders 
             WHERE (LEFT(po_number, $len) = ? OR LEFT(internal_po_number, $len) = ?)";
    $params2 = [$prefix, $prefix];
    if ($customer_id) {
        $sql2 .= " AND customer_id = ?";
        $params2[] = $customer_id;
    }
    
    $stmt2 = $db->prepare($sql2);
    $stmt2->execute($params2);
    $container2 = $stmt2->fetch(PDO::FETCH_ASSOC);
    
    // CONTAINER 3 - INVOICES
    $sql3 = "SELECT COUNT(*) AS invoice_count, COALESCE(SUM(total_amount),0) AS invoice_value 
             FROM finance_invoices 
             WHERE LEFT(invoice_number, $len) = ?";
    $params3 = [$prefix];
    if ($customer_id) {
        $sql3 .= " AND customer_id = ?";
        $params3[] = $customer_id;
    }
    
    $stmt3 = $db->prepare($sql3);
    $stmt3->execute($params3);
    $container3 = $stmt3->fetch(PDO::FETCH_ASSOC);
    
    // CONTAINER 4 - PAYMENTS
    $sql4 = "SELECT COUNT(*) AS payment_count, COALESCE(SUM(paid_amount),0) AS received_amount 
             FROM finance_invoices 
             WHERE LEFT(invoice_number, $len) = ? AND paid_amount > 0";
    $params4 = [$prefix];
    if ($customer_id) {
        $sql4 .= " AND customer_id = ?";
        $params4[] = $customer_id;
    }
    
    $stmt4 = $db->prepare($sql4);
    $stmt4->execute($params4);
    $container4 = $stmt4->fetch(PDO::FETCH_ASSOC);
    
    // Calculate conversion rates
    $quotation_to_po = $container1['quotation_count'] > 0 ? 
        round(($container2['po_count'] / $container1['quotation_count']) * 100, 2) : 0;
    
    $po_to_invoice = $container2['po_count'] > 0 ? 
        round(($container3['invoice_count'] / $container2['po_count']) * 100, 2) : 0;
    
    $invoice_to_payment = $container3['invoice_value'] > 0 ? 
        round(($container4['received_amount'] / $container3['invoice_value']) * 100, 2) : 0;
    
    echo json_encode([
        'success' => true,
        'data' => [
            'container1' => [
                'quotation_count' => (int)$container1['quotation_count'],
                'quotation_value' => (float)$container1['quotation_value']
            ],
            'container2' => [
                'po_count' => (int)$container2['po_count'],
                'po_value' => (float)$container2['po_value'],
                'conversion_rate' => $quotation_to_po
            ],
            'container3' => [
                'invoice_count' => (int)$container3['invoice_count'],
                'invoice_value' => (float)$container3['invoice_value'],
                'conversion_rate' => $po_to_invoice
            ],
            'container4' => [
                'payment_count' => (int)$container4['payment_count'],
                'received_amount' => (float)$container4['received_amount'],
                'conversion_rate' => $invoice_to_payment
            ]
        ],
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