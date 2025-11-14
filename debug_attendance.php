<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    // Check table structure
    echo "=== ATTENDANCE TABLE STRUCTURE ===\n";
    $stmt = $db->query("DESCRIBE attendance");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo "{$col['Field']}: {$col['Type']} | Null: {$col['Null']} | Default: {$col['Default']}\n";
    }
    
    // Check current attendance records
    echo "\n=== CURRENT ATTENDANCE RECORDS ===\n";
    $stmt = $db->query("SELECT * FROM attendance ORDER BY id DESC LIMIT 5");
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($records as $record) {
        print_r($record);
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>