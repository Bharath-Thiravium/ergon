<?php

// Web-accessible auto-sync endpoint for external cron services
// URL: https://yourdomain.com/ergon/public/auto-sync.php?token=sync123

header('Content-Type: application/json');

// Security check
$validTokens = ['sync123', 'auto456']; // Change these tokens
$token = $_GET['token'] ?? '';

if (!in_array($token, $validTokens)) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid token']);
    exit;
}

try {
    require_once __DIR__ . '/../app/services/DataSyncService.php';
    
    $syncService = new DataSyncService();
    $results = $syncService->syncAllTables();
    
    $totalRecords = 0;
    $errors = [];
    
    foreach ($results as $result) {
        $totalRecords += $result['records'];
        if ($result['status'] === 'error') {
            $errors[] = $result['table'] . ': ' . $result['error'];
        }
    }
    
    echo json_encode([
        'success' => empty($errors),
        'timestamp' => date('Y-m-d H:i:s'),
        'records_synced' => $totalRecords,
        'tables' => $results,
        'errors' => $errors
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}