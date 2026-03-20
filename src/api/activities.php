<?php
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../api/dashboard/prefix-resolver.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    $db = Database::connect();
    $prefix     = $_GET['prefix'] ?? '';
    $recordType = $_GET['record_type'] ?? '';

    if (empty($prefix)) throw new Exception('Prefix is required');

    $companyId = resolveCompanyId($prefix, $db);
    if (!$companyId) { echo json_encode(['success' => true, 'data' => []]); exit; }

    $activities = [];

    if (!$recordType || $recordType === 'invoice') {
        $stmt = $db->prepare("SELECT 'invoice' AS record_type, i.invoice_number AS document_number,
            i.customer_id, COALESCE(c.customer_name, CAST(i.customer_id AS CHAR)) AS customer_name,
            i.total_amount AS amount, i.status, i.invoice_date AS created_at
            FROM finance_invoices i
            LEFT JOIN finance_customers c ON c.customer_id = i.customer_id
            WHERE i.company_id = ? ORDER BY i.invoice_date DESC LIMIT 50");
        $stmt->execute([$companyId]);
        $activities = array_merge($activities, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    if (!$recordType || $recordType === 'quotation') {
        $stmt = $db->prepare("SELECT 'quotation' AS record_type, q.quotation_number AS document_number,
            q.customer_id, COALESCE(c.customer_name, CAST(q.customer_id AS CHAR)) AS customer_name,
            q.quotation_amount AS amount, q.status, q.quotation_date AS created_at
            FROM finance_quotations q
            LEFT JOIN finance_customers c ON c.customer_id = q.customer_id
            WHERE q.company_id = ? ORDER BY q.quotation_date DESC LIMIT 50");
        $stmt->execute([$companyId]);
        $activities = array_merge($activities, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    if (!$recordType || $recordType === 'purchase_order') {
        $stmt = $db->prepare("SELECT 'purchase_order' AS record_type, p.po_number AS document_number,
            p.customer_id, COALESCE(c.customer_name, CAST(p.customer_id AS CHAR)) AS customer_name,
            p.po_total_value AS amount, p.po_status AS status, p.po_date AS created_at
            FROM finance_purchase_orders p
            LEFT JOIN finance_customers c ON c.customer_id = p.customer_id
            WHERE p.company_id = ? ORDER BY p.po_date DESC LIMIT 50");
        $stmt->execute([$companyId]);
        $activities = array_merge($activities, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    if (!$recordType || $recordType === 'payment') {
        $stmt = $db->prepare("SELECT 'payment' AS record_type, py.payment_number AS document_number,
            py.customer_id, COALESCE(c.customer_name, CAST(py.customer_id AS CHAR)) AS customer_name,
            py.amount, py.status, py.payment_date AS created_at
            FROM finance_payments py
            LEFT JOIN finance_customers c ON c.customer_id = py.customer_id
            WHERE py.company_id = ? ORDER BY py.payment_date DESC LIMIT 50");
        $stmt->execute([$companyId]);
        $activities = array_merge($activities, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    usort($activities, fn($a, $b) => strtotime($b['created_at'] ?? '1970-01-01') - strtotime($a['created_at'] ?? '1970-01-01'));
    $activities = array_slice($activities, 0, 20);

    $icons = ['quotation' => '📝', 'purchase_order' => '🛒', 'invoice' => '💰', 'payment' => '💳'];
    foreach ($activities as &$a) {
        $a['icon'] = $icons[$a['record_type']] ?? '📈';
        $a['formatted_amount'] = number_format($a['amount'] ?? 0, 2);
    }

    echo json_encode(['success' => true, 'data' => $activities, 'timestamp' => date('Y-m-d H:i:s')]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
