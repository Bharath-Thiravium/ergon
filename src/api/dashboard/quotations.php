<?php
require_once __DIR__ . '/../../../app/config/database.php';
require_once __DIR__ . '/prefix-resolver.php';
header('Content-Type: application/json');

try {
    $db = Database::connect();
    $companyId = resolveCompanyId($_GET['prefix'] ?? '', $db);
    if (!$companyId) { echo json_encode(['success' => true, 'data' => ['total_value' => 0, 'pending_count' => 0, 'placed_count' => 0, 'rejected_count' => 0]]); exit; }

    $stmt = $db->prepare("SELECT
        COALESCE(SUM(quotation_amount), 0) AS total_value,
        COALESCE(SUM(CASE WHEN UPPER(status) = 'DRAFT' THEN 1 ELSE 0 END), 0) AS pending_count,
        COALESCE(SUM(CASE WHEN UPPER(status) = 'SENT' THEN 1 ELSE 0 END), 0) AS placed_count,
        COALESCE(SUM(CASE WHEN UPPER(status) = 'APPROVED' THEN 1 ELSE 0 END), 0) AS rejected_count
        FROM finance_quotations WHERE company_id = ?");
    $stmt->execute([$companyId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    echo json_encode(['success' => true, 'data' => [
        'total_value'    => (float)($row['total_value'] ?? 0),
        'pending_count'  => (int)($row['pending_count'] ?? 0),
        'placed_count'   => (int)($row['placed_count'] ?? 0),
        'rejected_count' => (int)($row['rejected_count'] ?? 0),
    ]]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
