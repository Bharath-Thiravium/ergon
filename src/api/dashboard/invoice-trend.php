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

    $sql = "SELECT DATE(created_at) AS date, COALESCE(SUM(total_amount), 0) AS amount FROM finance_invoices WHERE LEFT(invoice_number, ?) = ? GROUP BY DATE(created_at) ORDER BY date ASC LIMIT 30";

    $len = strlen($resolvedPrefix);
    $stmt = $db->prepare($sql);
    $stmt->execute([$len, $resolvedPrefix]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $dates = [];
    $amounts = [];
    foreach ($rows as $row) {
        $dates[] = $row['date'];
        $amounts[] = (float)$row['amount'];
    }

    echo json_encode(['success' => true, 'data' => ['dates' => $dates, 'amounts' => $amounts]]);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}
