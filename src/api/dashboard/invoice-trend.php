<?php
require_once __DIR__ . '/../../../app/config/database.php';
require_once __DIR__ . '/prefix-resolver.php';
header('Content-Type: application/json');

try {
    $db = Database::connect();
    $companyId = resolveCompanyId($_GET['prefix'] ?? '', $db);
    if (!$companyId) { echo json_encode(['success' => true, 'data' => ['dates' => [], 'amounts' => []]]); exit; }

    $stmt = $db->prepare("SELECT DATE(invoice_date) AS date, COALESCE(SUM(total_amount), 0) AS amount
        FROM finance_invoices WHERE company_id = ?
        GROUP BY DATE(invoice_date) ORDER BY date ASC LIMIT 30");
    $stmt->execute([$companyId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $dates = array_column($rows, 'date');
    $amounts = array_map('floatval', array_column($rows, 'amount'));

    echo json_encode(['success' => true, 'data' => ['dates' => $dates, 'amounts' => $amounts]]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
