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

    $sql = "SELECT i.customer_id, COALESCE(c.customer_name, i.customer_id) AS customer_name, COALESCE(SUM(i.outstanding_amount), 0) AS outstanding FROM finance_invoices i LEFT JOIN finance_customers c ON c.customer_id = i.customer_id WHERE LEFT(i.invoice_number, ?) = ? AND i.outstanding_amount > 0 GROUP BY i.customer_id, c.customer_name HAVING outstanding > 0 ORDER BY outstanding DESC LIMIT 50";

    $len = strlen($resolvedPrefix);
    $stmt = $db->prepare($sql);
    $stmt->execute([$len, $resolvedPrefix]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    foreach ($rows as &$r) {
        $r['outstanding'] = (float)($r['outstanding'] ?? 0);
    }

    echo json_encode(['success' => true, 'data' => $rows]);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}
