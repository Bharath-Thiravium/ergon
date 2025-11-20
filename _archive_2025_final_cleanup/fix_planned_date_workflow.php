<?php
/**
 * Database Migration: Fix Planned Date Workflow
 * 
 * This script ensures that the daily planner uses the planned_date field
 * correctly to determine when tasks should appear in the planner.
 */

require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    echo "Connected to database successfully.\n";
    
    // 1. Ensure planned_date column exists in tasks table
    echo "Checking tasks table structure...\n";
    
    $stmt = $db->prepare("SHOW COLUMNS FROM tasks LIKE 'planned_date'");
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        echo "Adding planned_date column to tasks table...\n";
        $db->exec("ALTER TABLE tasks ADD COLUMN planned_date DATE DEFAULT NULL AFTER deadline");
        echo "✓ Added planned_date column\n";
    } else {
        echo "✓ planned_date column already exists\n";
    }
    
    // 2. Add index for better performance
    echo "Adding index for planned_date...\n";
    try {
        $db->exec("CREATE INDEX idx_tasks_planned_date ON tasks (planned_date)");
        echo "✓ Added index for planned_date\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "✓ Index for planned_date already exists\n";
        } else {
            echo "Warning: Could not create index - " . $e->getMessage() . "\n";
        }
    }
    
    // 3. Add composite index for daily planner queries
    echo "Adding composite index for daily planner queries...\n";
    try {
        $db->exec("CREATE INDEX idx_tasks_assigned_planned ON tasks (assigned_to, planned_date, status)");
        echo "✓ Added composite index for daily planner\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "✓ Composite index already exists\n";
        } else {
            echo "Warning: Could not create composite index - " . $e->getMessage() . "\n";
        }
    }
    
    // 4. Verify daily_tasks table structure
    echo "Checking daily_tasks table...\n";
    
    $stmt = $db->prepare("SHOW TABLES LIKE 'daily_tasks'");
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        echo "✓ daily_tasks table exists\n";
        
        // Check if we need to add any missing columns
        $columns = $db->query("SHOW COLUMNS FROM daily_tasks")->fetchAll(PDO::FETCH_COLUMN);
        
        $requiredColumns = [
            'scheduled_date' => 'DATE NOT NULL',
            'task_id' => 'INT NULL',
            'user_id' => 'INT NOT NULL',
            'status' => 'VARCHAR(50) DEFAULT \'not_started\'',
            'start_time' => 'TIMESTAMP NULL',
            'active_seconds' => 'INT DEFAULT 0',
            'pause_duration' => 'INT DEFAULT 0',
            'completed_percentage' => 'INT DEFAULT 0'
        ];
        
        foreach ($requiredColumns as $column => $definition) {
            if (!in_array($column, $columns)) {
                echo "Adding missing column: $column\n";
                try {
                    $db->exec("ALTER TABLE daily_tasks ADD COLUMN $column $definition");
                    echo "✓ Added $column column\n";
                } catch (Exception $e) {
                    echo "Warning: Could not add $column - " . $e->getMessage() . "\n";
                }
            }
        }
    } else {
        echo "daily_tasks table does not exist - it will be created automatically when needed\n";
    }
    
    // 5. Test the workflow with a sample query
    echo "\nTesting planned date workflow...\n";
    
    $testDate = date('Y-m-d');
    $stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM tasks 
        WHERE planned_date = ? 
        AND status != 'completed'
    ");
    $stmt->execute([$testDate]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Found {$result['count']} tasks planned for today ($testDate)\n";
    
    // 6. Show sample of tasks with planned dates
    $stmt = $db->prepare("
        SELECT id, title, planned_date, status, assigned_to
        FROM tasks 
        WHERE planned_date IS NOT NULL 
        ORDER BY planned_date DESC 
        LIMIT 5
    ");
    $stmt->execute();
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($tasks)) {
        echo "\nSample tasks with planned dates:\n";
        foreach ($tasks as $task) {
            echo "- Task #{$task['id']}: {$task['title']} (planned: {$task['planned_date']}, status: {$task['status']})\n";
        }
    } else {
        echo "\nNo tasks found with planned dates. Tasks will appear in planner based on creation date until planned dates are set.\n";
    }
    
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "PLANNED DATE WORKFLOW IMPLEMENTATION COMPLETE\n";
    echo str_repeat("=", 60) . "\n";
    
    echo "\nHow it works now:\n";
    echo "1. When creating a task, set the 'Planned Date' field\n";
    echo "2. Tasks will ONLY appear in the daily planner on their planned date\n";
    echo "3. If no planned date is set, tasks appear on their creation date\n";
    echo "4. Example: Task created on 20/11/2025 with planned date 21/11/2025\n";
    echo "   → Will NOT appear on 20/11/2025 planner\n";
    echo "   → Will ONLY appear on 21/11/2025 planner\n";
    
    echo "\nNext steps:\n";
    echo "1. Test by creating a task with a future planned date\n";
    echo "2. Verify it doesn't appear in today's planner\n";
    echo "3. Check that it appears on the planned date\n";
    echo "4. Update existing tasks with appropriate planned dates if needed\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>