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

    $sql = "SELECT COALESCE(SUM(total_amount), 0) AS total_value, COALESCE(SUM(CASE WHEN UPPER(status) = 'DRAFT' THEN 1 ELSE 0 END), 0) AS pending_count, COALESCE(SUM(CASE WHEN UPPER(status) = 'SENT' THEN 1 ELSE 0 END), 0) AS placed_count, COALESCE(SUM(CASE WHEN UPPER(status) = 'APPROVED' THEN 1 ELSE 0 END), 0) AS rejected_count FROM finance_quotations WHERE LEFT(quotation_number, ?) = ?";

    $len = strlen($resolvedPrefix);
    $stmt = $db->prepare($sql);
    $stmt->execute([$len, $resolvedPrefix]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    echo json_encode(['success' => true, 'data' => ['total_value' => (float)($row['total_value'] ?? 0), 'pending_count' => (int)($row['pending_count'] ?? 0), 'placed_count' => (int)($row['placed_count'] ?? 0), 'rejected_count' => (int)($row['rejected_count'] ?? 0)]]);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}
