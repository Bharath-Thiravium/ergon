<?php
require_once __DIR__ . '/../../../app/config/database.php';
require_once __DIR__ . '/prefix-resolver.php';
header('Content-Type: application/json');

try {
    $db = Database::connect();
    $companyId = resolveCompanyId($_GET['prefix'] ?? '', $db);
    if (!$companyId) { echo json_encode(['success' => true, 'data' => ['bucket_0_30' => 0, 'bucket_31_60' => 0, 'bucket_61_90' => 0, 'bucket_90_plus' => 0]]); exit; }

    $stmt = $db->prepare("SELECT
        COALESCE(SUM(CASE WHEN ABS(DATEDIFF(CURDATE(), COALESCE(due_date, invoice_date))) BETWEEN 0 AND 30 THEN outstanding_amount ELSE 0 END), 0) AS bucket_0_30,
        COALESCE(SUM(CASE WHEN ABS(DATEDIFF(CURDATE(), COALESCE(due_date, invoice_date))) BETWEEN 31 AND 60 THEN outstanding_amount ELSE 0 END), 0) AS bucket_31_60,
        COALESCE(SUM(CASE WHEN ABS(DATEDIFF(CURDATE(), COALESCE(due_date, invoice_date))) BETWEEN 61 AND 90 THEN outstanding_amount ELSE 0 END), 0) AS bucket_61_90,
        COALESCE(SUM(CASE WHEN ABS(DATEDIFF(CURDATE(), COALESCE(due_date, invoice_date))) > 90 THEN outstanding_amount ELSE 0 END), 0) AS bucket_90_plus
        FROM finance_invoices WHERE company_id = ? AND outstanding_amount > 0");
    $stmt->execute([$companyId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    echo json_encode(['success' => true, 'data' => [
        'bucket_0_30'   => (float)($row['bucket_0_30'] ?? 0),
        'bucket_31_60'  => (float)($row['bucket_31_60'] ?? 0),
        'bucket_61_90'  => (float)($row['bucket_61_90'] ?? 0),
        'bucket_90_plus'=> (float)($row['bucket_90_plus'] ?? 0),
    ]]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
