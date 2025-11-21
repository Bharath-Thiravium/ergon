<?php
require_once 'app/config/database.php';

try {
    $pdo = Database::connect();
    
    // Add pause_duration column if it doesn't exist
    $sql = "ALTER TABLE daily_tasks ADD COLUMN pause_duration INT DEFAULT 0 AFTER active_seconds";
    $pdo->exec($sql);
    
    echo "✅ pause_duration column added successfully\n";
    
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "✅ pause_duration column already exists\n";
    } else {
        echo "❌ Error: " . $e->getMessage() . "\n";
    }
}
?>