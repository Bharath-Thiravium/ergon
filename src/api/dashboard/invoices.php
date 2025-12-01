<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/../../../app/config/database.php';
$companyPrefixes = require __DIR__ . '/company-prefixes.php';
require_once __DIR__ . '/prefix-resolver.php';

header('Content-Type: application/json');

try {
    $rawPrefix = $_GET['prefix'] ?? '';
    $resolvedPrefix = resolveCompanyPrefix($rawPrefix, $companyPrefixes);
    $db = Database::connect();

    $sql = "SELECT COALESCE(SUM(total_amount), 0) AS total_value, COALESCE(SUM(CASE WHEN UPPER(payment_status) = 'PAID' THEN 1 ELSE 0 END), 0) AS paid, COALESCE(SUM(CASE WHEN UPPER(payment_status) = 'UNPAID' THEN 1 ELSE 0 END), 0) AS unpaid, COALESCE(SUM(CASE WHEN due_date < CURDATE() AND UPPER(payment_status) = 'UNPAID' THEN 1 ELSE 0 END), 0) AS overdue FROM finance_invoices WHERE LEFT(invoice_number, ?) = ?";

    $len = strlen($resolvedPrefix);
    $stmt = $db->prepare($sql);
    $stmt->execute([$len, $resolvedPrefix]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    echo json_encode(['success' => true, 'data' => ['total_value' => (float)($row['total_value'] ?? 0), 'paid' => (int)($row['paid'] ?? 0), 'unpaid' => (int)($row['unpaid'] ?? 0), 'overdue' => (int)($row['overdue'] ?? 0)]]);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}
