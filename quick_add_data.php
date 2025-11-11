<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    // Add minimal data that works
    $sql = file_get_contents(__DIR__ . '/database/minimal_dummy_data.sql');
    $db->exec($sql);
    
    echo "✅ Minimal dummy data added!\n";
    
    $taskCount = $db->query("SELECT COUNT(*) FROM tasks WHERE title LIKE '%Email%'")->fetchColumn();
    echo "Tasks added: $taskCount\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>