<?php
header('Content-Type: application/json');

try {
    $dsn = 'pgsql:host=72.60.218.167;port=5432;dbname=modernsap';
    $pdo = new PDO($dsn, 'postgres', 'mango', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    
    $table = $_GET['table'] ?? 'finance_customershippingaddress';
    
    $query = "
        SELECT column_name, data_type, is_nullable, column_default
        FROM information_schema.columns
        WHERE table_name = :table
        ORDER BY ordinal_position
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([':table' => $table]);
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'table' => $table, 'columns' => $columns], JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
