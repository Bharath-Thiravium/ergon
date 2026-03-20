<?php
require_once __DIR__ . '/../../../app/config/database.php';
require_once __DIR__ . '/prefix-resolver.php';
header('Content-Type: application/json');

try {
    $db = Database::connect();
    $companyId = resolveCompanyId($_GET['prefix'] ?? '', $db);
    if (!$companyId) { echo json_encode(['success' => true, 'data' => []]); exit; }

    $stmt = $db->prepare("SELECT
        i.customer_id,
        COALESCE(c.customer_name, i.customer_id) AS customer_name,
        COALESCE(SUM(i.outstanding_amount), 0) AS outstanding
        FROM finance_invoices i
        LEFT JOIN finance_customers c ON c.customer_id = i.customer_id
        WHERE i.company_id = ? AND i.outstanding_amount > 0
        GROUP BY i.customer_id, c.customer_name
        ORDER BY outstanding DESC
        LIMIT 50");
    $stmt->execute([$companyId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as &$r) $r['outstanding'] = (float)$r['outstanding'];

    echo json_encode(['success' => true, 'data' => $rows]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
