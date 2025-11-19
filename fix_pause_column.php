<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    echo "Fixing pause duration column...\n";
    
    // Check if total_pause_duration exists
    $stmt = $db->prepare("SHOW COLUMNS FROM daily_tasks LIKE 'total_pause_duration'");
    $stmt->execute();
    $hasTotalPause = $stmt->fetch();
    
    // Check if pause_duration exists
    $stmt = $db->prepare("SHOW COLUMNS FROM daily_tasks LIKE 'pause_duration'");
    $stmt->execute();
    $hasPause = $stmt->fetch();
    
    if ($hasTotalPause && !$hasPause) {
        // Rename total_pause_duration to pause_duration
        $db->exec("ALTER TABLE daily_tasks CHANGE COLUMN total_pause_duration pause_duration INT DEFAULT 0");
        echo "✓ Renamed total_pause_duration to pause_duration\n";
    } elseif (!$hasTotalPause && !$hasPause) {
        // Add pause_duration column
        $db->exec("ALTER TABLE daily_tasks ADD COLUMN pause_duration INT DEFAULT 0");
        echo "✓ Added pause_duration column\n";
    } elseif ($hasPause) {
        echo "✓ pause_duration column already exists\n";
    }
    
    // Update any tasks that are currently on break to have proper pause tracking
    $db->exec("UPDATE daily_tasks SET pause_duration = COALESCE(pause_duration, 0) WHERE pause_duration IS NULL");
    
    echo "✓ Database structure fixed successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>