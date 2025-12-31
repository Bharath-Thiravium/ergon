<?php
require_once __DIR__ . '/../../../app/config/database.php';
require_once __DIR__ . '/../../services/AllStatCardsService.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

try {
    $db = Database::connect();
    $service = new AllStatCardsService($db);
    
    $prefix = $_GET['prefix'] ?? 'ERGN';
    $stats = $service->getAllStats($prefix);
    
    echo json_encode([
        'success' => true,
        'data' => $stats,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
