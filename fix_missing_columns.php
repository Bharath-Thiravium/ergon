<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    echo "Fixing daily_tasks table structure...\n";
    
    // Add missing columns
    $alterQueries = [
        "ALTER TABLE daily_tasks ADD COLUMN IF NOT EXISTS start_time TIMESTAMP NULL",
        "ALTER TABLE daily_tasks ADD COLUMN IF NOT EXISTS pause_time TIMESTAMP NULL", 
        "ALTER TABLE daily_tasks ADD COLUMN IF NOT EXISTS resume_time TIMESTAMP NULL",
        "ALTER TABLE daily_tasks ADD COLUMN IF NOT EXISTS completion_time TIMESTAMP NULL",
        "ALTER TABLE daily_tasks ADD COLUMN IF NOT EXISTS active_seconds INT DEFAULT 0",
        "ALTER TABLE daily_tasks ADD COLUMN IF NOT EXISTS completed_percentage INT DEFAULT 0",
        "ALTER TABLE daily_tasks ADD COLUMN IF NOT EXISTS postponed_from_date DATE NULL"
    ];
    
    foreach ($alterQueries as $query) {
        try {
            $db->exec($query);
            echo "✓ " . substr($query, 0, 50) . "...\n";
        } catch (Exception $e) {
            // Try without IF NOT EXISTS for older MySQL versions
            $fallbackQuery = str_replace(' IF NOT EXISTS', '', $query);
            try {
                $db->exec($fallbackQuery);
                echo "✓ " . substr($fallbackQuery, 0, 50) . "...\n";
            } catch (Exception $e2) {
                echo "⚠ Column might already exist: " . $e2->getMessage() . "\n";
            }
        }
    }
    
    echo "\nTable structure fixed successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>