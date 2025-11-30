<?php
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../services/CashFlowService.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    $db = Database::connect();
    $service = new CashFlowService($db);
    
    $prefix = $_GET['prefix'] ?? null;
    $result = $service->getCashFlow($prefix);
    
    echo json_encode(['success' => true, 'data' => $result, 'timestamp' => date('Y-m-d H:i:s')]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage(), 'timestamp' => date('Y-m-d H:i:s')]);
}
