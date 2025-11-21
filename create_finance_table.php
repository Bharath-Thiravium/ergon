<?php
require_once 'app/config/database.php';

try {
    $db = Database::connect();
    
    $sql = "CREATE TABLE IF NOT EXISTS finance_data (
        id INT AUTO_INCREMENT PRIMARY KEY,
        source_table VARCHAR(100) NOT NULL,
        data JSON NOT NULL,
        synced_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX(source_table),
        INDEX(synced_at)
    )";
    
    $db->exec($sql);
    echo "Finance table created successfully!\n";
    
    // Test insert
    $testData = [['name' => 'Test', 'amount' => 100]];
    $stmt = $db->prepare("INSERT INTO finance_data (source_table, data) VALUES (?, ?)");
    $stmt->execute(['test_table', json_encode($testData)]);
    echo "Test data inserted successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>