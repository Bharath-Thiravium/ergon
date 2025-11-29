<?php
// public/recent_activities.php - API endpoint for recent activities
declare(strict_types=1);
require_once __DIR__ . '/../inc/functions.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

$prefix = $_GET['prefix'] ?? null;
if (!$prefix) {
    http_response_code(400);
    echo json_encode(['error' => 'prefix parameter required']);
    exit;
}

$limit = min(100, max(1, (int)($_GET['limit'] ?? 20)));
$recordType = $_GET['record_type'] ?? null;

try {
    $pdo = getPdo();
    
    $sql = "SELECT record_type, document_number, customer_id, customer_name, status, amount, outstanding_amount, created_at, updated_at
            FROM finance_consolidated 
            WHERE company_prefix = :prefix";
    
    $params = [':prefix' => $prefix];
    
    if ($recordType) {
        $sql .= " AND record_type = :record_type";
        $params[':record_type'] = $recordType;
    }
    
    $sql .= " ORDER BY created_at DESC LIMIT :limit";
    
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Add formatted amounts and icons
    foreach ($activities as &$activity) {
        $activity['formatted_amount'] = number_format((float)$activity['amount'], 2);
        $activity['icon'] = [
            'invoice' => '💰',
            'quotation' => '📝',
            'purchase_order' => '🛒', 
            'payment' => '💳'
        ][$activity['record_type']] ?? '📄';
    }
    
    echo json_encode([
        'success' => true,
        'data' => $activities,
        'count' => count($activities),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}