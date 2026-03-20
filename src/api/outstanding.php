<?php
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../api/dashboard/prefix-resolver.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    $db    = Database::connect();
    $prefix = $_GET['prefix'] ?? '';
    $limit  = (int)($_GET['limit'] ?? 50);

    if (empty($prefix)) throw new Exception('Prefix is required');

    $companyId = resolveCompanyId($prefix, $db);
    if (!$companyId) { echo json_encode(['success' => true, 'data' => [], 'count' => 0]); exit; }

    $stmt = $db->prepare("SELECT
        i.invoice_number,
        i.customer_id,
        COALESCE(c.customer_name, CAST(i.customer_id AS CHAR)) AS customer_name,
        i.invoice_date,
        i.total_amount,
        i.outstanding_amount,
        DATEDIFF(CURDATE(), i.due_date) AS days_overdue,
        CASE WHEN i.outstanding_amount > 0 AND i.due_date < CURDATE() THEN 'Overdue' ELSE i.status END AS status
        FROM finance_invoices i
        LEFT JOIN finance_customers c ON c.customer_id = i.customer_id
        WHERE i.company_id = ? AND i.outstanding_amount > 0
        ORDER BY days_overdue DESC, i.outstanding_amount DESC
        LIMIT ?");
    $stmt->execute([$companyId, $limit]);
    $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $invoices, 'count' => count($invoices)]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
