<?php
require_once __DIR__ . '/../../../app/config/database.php';
require_once __DIR__ . '/prefix-resolver.php';
header('Content-Type: application/json');

try {
    $db = Database::connect();
    $companyId = resolveCompanyId($_GET['prefix'] ?? '', $db);
    if (!$companyId) { echo json_encode(['success' => true, 'data' => ['total_value' => 0, 'paid' => 0, 'unpaid' => 0, 'overdue' => 0]]); exit; }

    $stmt = $db->prepare("SELECT
        COALESCE(SUM(total_amount), 0) AS total_value,
        COALESCE(SUM(CASE WHEN UPPER(status) = 'PAID' THEN 1 ELSE 0 END), 0) AS paid,
        COALESCE(SUM(CASE WHEN UPPER(status) != 'PAID' THEN 1 ELSE 0 END), 0) AS unpaid,
        COALESCE(SUM(CASE WHEN due_date < CURDATE() AND UPPER(status) != 'PAID' THEN 1 ELSE 0 END), 0) AS overdue
        FROM finance_invoices WHERE company_id = ?");
    $stmt->execute([$companyId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    echo json_encode(['success' => true, 'data' => [
        'total_value' => (float)($row['total_value'] ?? 0),
        'paid'        => (int)($row['paid'] ?? 0),
        'unpaid'      => (int)($row['unpaid'] ?? 0),
        'overdue'     => (int)($row['overdue'] ?? 0),
    ]]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
