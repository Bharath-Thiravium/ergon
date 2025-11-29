<?php
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../services/PostgreSQLSyncService.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

try {
    $db = Database::connect();
    $syncService = new PostgreSQLSyncService($db);
    
    $result = $syncService->syncAll();
    
    echo json_encode($result);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}