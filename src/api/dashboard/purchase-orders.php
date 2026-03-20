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

    $sql = "SELECT COALESCE(SUM(po_total_value), 0) AS total_value, COALESCE(SUM(CASE WHEN UPPER(po_status) IN ('CLOSED','COMPLETED','FULFILLED') THEN 1 ELSE 0 END), 0) AS fulfilled, COALESCE(SUM(CASE WHEN UPPER(po_status) NOT IN ('CLOSED','COMPLETED','FULFILLED') THEN 1 ELSE 0 END), 0) AS open_count FROM finance_purchase_orders po WHERE EXISTS (SELECT 1 FROM finance_invoices i WHERE LEFT(i.invoice_number, ?) = ? AND i.customer_id = po.customer_id)";

    $len = strlen($resolvedPrefix);
    $stmt = $db->prepare($sql);
    $stmt->execute([$len, $resolvedPrefix]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    $fulfilled = (int)($row['fulfilled'] ?? 0);
    $open = (int)($row['open_count'] ?? 0);
    $total = $fulfilled + $open;
    $rate = $total > 0 ? round(($fulfilled / $total) * 100, 2) : 0;

    echo json_encode(['success' => true, 'data' => ['total_value' => (float)($row['total_value'] ?? 0), 'fulfilled' => $fulfilled, 'open_count' => $open, 'rate' => $rate]]);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}
