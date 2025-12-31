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

    $sql = "SELECT COALESCE(SUM(amount), 0) AS total_paid, COALESCE(COUNT(*), 0) AS payment_count, COALESCE(AVG(amount), 0) AS avg_payment FROM finance_payments WHERE LEFT(COALESCE(payment_number, ''), ?) = ?";

    $len = strlen($resolvedPrefix);
    $stmt = $db->prepare($sql);
    $stmt->execute([$len, $resolvedPrefix]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    echo json_encode(['success' => true, 'data' => ['total_paid' => (float)($row['total_paid'] ?? 0), 'payment_count' => (int)($row['payment_count'] ?? 0), 'avg_payment' => (float)($row['avg_payment'] ?? 0)]]);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}
