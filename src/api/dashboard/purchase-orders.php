<?php
require_once __DIR__ . '/../../../app/config/database.php';
require_once __DIR__ . '/prefix-resolver.php';
header('Content-Type: application/json');

try {
    $db = Database::connect();
    $companyId = resolveCompanyId($_GET['prefix'] ?? '', $db);
    if (!$companyId) { echo json_encode(['success' => true, 'data' => ['total_value' => 0, 'fulfilled' => 0, 'open_count' => 0, 'rate' => 0]]); exit; }

    $stmt = $db->prepare("SELECT
        COALESCE(SUM(po_total_value), 0) AS total_value,
        COALESCE(SUM(CASE WHEN UPPER(po_status) IN ('CLOSED','COMPLETED','FULFILLED') THEN 1 ELSE 0 END), 0) AS fulfilled,
        COALESCE(SUM(CASE WHEN UPPER(po_status) NOT IN ('CLOSED','COMPLETED','FULFILLED') THEN 1 ELSE 0 END), 0) AS open_count
        FROM finance_purchase_orders WHERE company_id = ?");
    $stmt->execute([$companyId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    $fulfilled = (int)($row['fulfilled'] ?? 0);
    $open      = (int)($row['open_count'] ?? 0);
    $total     = $fulfilled + $open;
    $rate      = $total > 0 ? round(($fulfilled / $total) * 100, 2) : 0;

    echo json_encode(['success' => true, 'data' => [
        'total_value' => (float)($row['total_value'] ?? 0),
        'fulfilled'   => $fulfilled,
        'open_count'  => $open,
        'rate'        => $rate,
    ]]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
