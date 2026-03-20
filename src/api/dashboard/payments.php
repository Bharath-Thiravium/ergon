<?php
require_once __DIR__ . '/../../../app/config/database.php';
require_once __DIR__ . '/prefix-resolver.php';
header('Content-Type: application/json');

try {
    $db = Database::connect();
    $companyId = resolveCompanyId($_GET['prefix'] ?? '', $db);
    if (!$companyId) { echo json_encode(['success' => true, 'data' => ['total_paid' => 0, 'payment_count' => 0, 'avg_payment' => 0]]); exit; }

    $stmt = $db->prepare("SELECT
        COALESCE(SUM(amount), 0) AS total_paid,
        COUNT(*) AS payment_count,
        COALESCE(AVG(amount), 0) AS avg_payment
        FROM finance_payments WHERE company_id = ?");
    $stmt->execute([$companyId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    echo json_encode(['success' => true, 'data' => [
        'total_paid'    => (float)($row['total_paid'] ?? 0),
        'payment_count' => (int)($row['payment_count'] ?? 0),
        'avg_payment'   => (float)($row['avg_payment'] ?? 0),
    ]]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
