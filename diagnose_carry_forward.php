<?php
require_once __DIR__ . '/app/config/database.php';

echo "Carry Forward Diagnosis\n";
echo "======================\n";

try {
    $db = Database::connect();
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    
    echo "Today: $today\n";
    echo "Yesterday: $yesterday\n\n";
    
    // 1. Check if tasks table has planned_date column
    echo "1. Checking tasks table structure:\n";
    $stmt = $db->query("SHOW COLUMNS FROM tasks LIKE 'planned_date'");
    if ($stmt->rowCount() > 0) {
        echo "   ✓ planned_date column exists\n";
    } else {
        echo "   ✗ planned_date column missing - run fix_planned_date_workflow.php\n";
        exit;
    }
    
    // 2. Check for any tasks with planned dates
    echo "\n2. Checking tasks with planned dates:\n";
    $stmt = $db->query("SELECT COUNT(*) as count FROM tasks WHERE planned_date IS NOT NULL");
    $count = $stmt->fetchColumn();
    echo "   Total tasks with planned dates: $count\n";
    
    if ($count == 0) {
        echo "   No tasks have planned dates set. Create a test task:\n";
        echo "   INSERT INTO tasks (title, assigned_to, assigned_by, planned_date, status) VALUES ('Test Task', 1, 1, '$yesterday', 'assigned');\n";
        exit;
    }
    
    // 3. Check for pending tasks from past dates
    echo "\n3. Checking pending tasks from past dates:\n";
    $stmt = $db->prepare("
        SELECT id, title, planned_date, status, assigned_to 
        FROM tasks 
        WHERE status IN ('assigned', 'not_started') 
        AND planned_date < ?
        AND planned_date IS NOT NULL
        LIMIT 5
    ");
    $stmt->execute([$today]);
    $pendingTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($pendingTasks)) {
        echo "   No pending tasks from past dates found\n";
        echo "   Create a test task with yesterday's date:\n";
        echo "   INSERT INTO tasks (title, assigned_to, assigned_by, planned_date, status) VALUES ('Test Pending Task', 1, 1, '$yesterday', 'assigned');\n";
    } else {
        echo "   Found " . count($pendingTasks) . " pending tasks from past dates:\n";
        foreach ($pendingTasks as $task) {
            echo "   - #{$task['id']}: {$task['title']} (user: {$task['assigned_to']}, planned: {$task['planned_date']}, status: {$task['status']})\n";
        }
        
        // 4. Test carry forward for user 1
        echo "\n4. Testing carry forward for user 1:\n";
        $stmt = $db->prepare("
            UPDATE tasks SET planned_date = ? 
            WHERE assigned_to = 1 
            AND status IN ('assigned', 'not_started') 
            AND planned_date < ? 
            AND planned_date IS NOT NULL
        ");
        $result = $stmt->execute([$today, $today]);
        $movedCount = $stmt->rowCount();
        
        echo "   Moved $movedCount tasks to today for user 1\n";
        
        if ($movedCount > 0) {
            echo "   ✓ Carry forward is working!\n";
            echo "   Check today's planner at: /ergon/workflow/daily-planner\n";
        }
    }
    
    // 5. Show what should appear in today's planner for user 1
    echo "\n5. Tasks that should appear in today's planner for user 1:\n";
    $stmt = $db->prepare("
        SELECT id, title, planned_date, DATE(created_at) as created_date, status 
        FROM tasks 
        WHERE assigned_to = 1 
        AND status != 'completed'
        AND (
            planned_date = ? OR 
            (planned_date IS NULL AND DATE(created_at) = ?)
        )
        ORDER BY 
            CASE WHEN planned_date IS NOT NULL THEN 1 ELSE 2 END,
            created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$today, $today]);
    $todayTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($todayTasks)) {
        echo "   No tasks found for today's planner\n";
    } else {
        foreach ($todayTasks as $task) {
            $source = $task['planned_date'] ? "planned: {$task['planned_date']}" : "created: {$task['created_date']}";
            echo "   - #{$task['id']}: {$task['title']} ($source, status: {$task['status']})\n";
        }
    }
    
    echo "\nDiagnosis complete. If tasks are showing above, carry forward is working.\n";
    echo "Visit /ergon/workflow/daily-planner to see the daily planner.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>