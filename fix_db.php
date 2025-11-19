<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    // Add pause_duration column
    $db->exec("ALTER TABLE daily_tasks ADD COLUMN pause_duration INT DEFAULT 0");
    echo "Added pause_duration column";
    
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "Column already exists";
    } else {
        echo "Error: " . $e->getMessage();
    }
}
?>