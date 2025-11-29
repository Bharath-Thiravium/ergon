<?php
header('Content-Type: application/json');

try {
    $config = require_once __DIR__ . '/../app/config/database.php';
    $mysql = $config['mysql'];
    
    $pdo = new PDO(
        "mysql:host={$mysql['host']};port={$mysql['port']};dbname={$mysql['database']};charset=utf8mb4",
        $mysql['username'],
        $mysql['password']
    );
    
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM dashboard_stats');
    $result = $stmt->fetch();
    
    echo json_encode([
        'status' => 'connected',
        'database' => $mysql['database'],
        'dashboard_stats_count' => $result['count']
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'failed',
        'error' => $e->getMessage()
    ]);
}
?>