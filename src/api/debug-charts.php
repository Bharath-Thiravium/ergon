<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../app/config/database.php';

$prefix = $_GET['prefix'] ?? 'ERGN';
$db = Database::connect();

$debug = [
    'prefix' => $prefix,
    'table_exists' => false,
    'row_count' => 0,
    'sample_data' => [],
    'chart_quotations' => [],
    'chart_invoices' => [],
    'errors' => []
];

try {
    // Check if table exists
    $sql = "SELECT COUNT(*) as cnt FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'finance_consolidated'";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $debug['table_exists'] = $result['cnt'] > 0;
    
    // Count total rows
    $sql = "SELECT COUNT(*) as cnt FROM finance_consolidated";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $debug['total_rows'] = $result['cnt'];
    
    // Count rows for prefix
    $sql = "SELECT COUNT(*) as cnt FROM finance_consolidated WHERE company_prefix = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$prefix]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $debug['rows_for_prefix'] = $result['cnt'];
    
    // Get sample data
    $sql = "SELECT company_prefix, record_type, status, amount FROM finance_consolidated LIMIT 5";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $debug['sample_data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Test quotations query
    $sql = "SELECT COALESCE(status, 'unknown') as status, COUNT(*) as count, COALESCE(SUM(amount), 0) as total FROM finance_consolidated WHERE company_prefix = ? AND record_type = 'quotation' GROUP BY status";
    $stmt = $db->prepare($sql);
    $stmt->execute([$prefix]);
    $debug['chart_quotations'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Test invoices query
    $sql = "SELECT COALESCE(status, 'unknown') as status, COUNT(*) as count, COALESCE(SUM(amount), 0) as total FROM finance_consolidated WHERE company_prefix = ? AND record_type = 'invoice' GROUP BY status";
    $stmt = $db->prepare($sql);
    $stmt->execute([$prefix]);
    $debug['chart_invoices'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $debug['errors'][] = $e->getMessage();
}

echo json_encode($debug, JSON_PRETTY_PRINT);
?>
