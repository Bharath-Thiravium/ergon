<?php
header('Content-Type: application/json');

try {
    $dsn = 'pgsql:host=72.60.218.167;port=5432;dbname=modernsap';
    $pdo = new PDO($dsn, 'postgres', 'mango', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    
    $query = "
        SELECT table_name 
        FROM information_schema.tables 
        WHERE table_schema = 'public'
        ORDER BY table_name
    ";
    
    $stmt = $pdo->query($query);
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo json_encode(['success' => true, 'tables' => $tables], JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
