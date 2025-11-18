<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    // Check if table exists
    $stmt = $db->prepare("SHOW TABLES LIKE 'daily_tasks'");
    $stmt->execute();
    $tableExists = $stmt->fetch();
    
    if (!$tableExists) {
        echo "Table 'daily_tasks' does not exist!\n";
        exit;
    }
    
    // Show current table structure
    $stmt = $db->prepare("DESCRIBE daily_tasks");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Current daily_tasks table structure:\n";
    foreach ($columns as $column) {
        echo "- {$column['Field']} ({$column['Type']})\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>