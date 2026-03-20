<?php
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../api/dashboard/prefix-resolver.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    $db = Database::connect();

    $prefix     = $_GET['prefix'] ?? '';
    $customerId = $_GET['customer_id'] ?? '';

    if (empty($prefix)) throw new Exception('Prefix is required');

    $companyId = resolveCompanyId($prefix, $db);
    if (!$companyId) {
        echo json_encode(['success' => true, 'data' => [
            'container1' => ['quotation_count' => 0, 'quotation_value' => 0],
            'container2' => ['po_count' => 0, 'po_value' => 0, 'conversion_rate' => 0],
            'container3' => ['invoice_count' => 0, 'invoice_value' => 0, 'conversion_rate' => 0],
            'container4' => ['payment_count' => 0, 'received_amount' => 0, 'conversion_rate' => 0],
        ]]);
        exit;
    }

    $customerFilter = $customerId ? ' AND customer_id = ?' : '';

    // Container 1 — Quotations
    $p1 = [$companyId];
    if ($customerId) $p1[] = $customerId;
    $s1 = $db->prepare("SELECT COUNT(*) AS quotation_count, COALESCE(SUM(quotation_amount),0) AS quotation_value
        FROM finance_quotations WHERE company_id = ?$customerFilter");
    $s1->execute($p1);
    $c1 = $s1->fetch(PDO::FETCH_ASSOC);

    // Container 2 — Purchase Orders
    $p2 = [$companyId];
    if ($customerId) $p2[] = $customerId;
    $s2 = $db->prepare("SELECT COUNT(*) AS po_count, COALESCE(SUM(po_total_value),0) AS po_value
        FROM finance_purchase_orders WHERE company_id = ?$customerFilter");
    $s2->execute($p2);
    $c2 = $s2->fetch(PDO::FETCH_ASSOC);

    // Container 3 — Invoices
    $p3 = [$companyId];
    if ($customerId) $p3[] = $customerId;
    $s3 = $db->prepare("SELECT COUNT(*) AS invoice_count, COALESCE(SUM(total_amount),0) AS invoice_value
        FROM finance_invoices WHERE company_id = ?$customerFilter");
    $s3->execute($p3);
    $c3 = $s3->fetch(PDO::FETCH_ASSOC);

    // Container 4 — Payments received (from finance_payments)
    $p4 = [$companyId];
    if ($customerId) $p4[] = $customerId;
    $s4 = $db->prepare("SELECT COUNT(*) AS payment_count, COALESCE(SUM(amount),0) AS received_amount
        FROM finance_payments WHERE company_id = ?$customerFilter");
    $s4->execute($p4);
    $c4 = $s4->fetch(PDO::FETCH_ASSOC);

    $quotation_to_po   = $c1['quotation_count'] > 0 ? round(($c2['po_count']        / $c1['quotation_count']) * 100, 2) : 0;
    $po_to_invoice     = $c2['po_count']         > 0 ? round(($c3['invoice_count']   / $c2['po_count'])        * 100, 2) : 0;
    $invoice_to_payment= $c3['invoice_value']    > 0 ? round(($c4['received_amount'] / $c3['invoice_value'])   * 100, 2) : 0;

    echo json_encode(['success' => true, 'data' => [
        'container1' => ['quotation_count' => (int)$c1['quotation_count'], 'quotation_value' => (float)$c1['quotation_value']],
        'container2' => ['po_count' => (int)$c2['po_count'], 'po_value' => (float)$c2['po_value'], 'conversion_rate' => $quotation_to_po],
        'container3' => ['invoice_count' => (int)$c3['invoice_count'], 'invoice_value' => (float)$c3['invoice_value'], 'conversion_rate' => $po_to_invoice],
        'container4' => ['payment_count' => (int)$c4['payment_count'], 'received_amount' => (float)$c4['received_amount'], 'conversion_rate' => $invoice_to_payment],
    ], 'timestamp' => date('Y-m-d H:i:s')]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
