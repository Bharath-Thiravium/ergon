<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    $userId = 1; // Replace with actual user ID
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    
    echo "Debug Carry Forward Issue\n";
    echo "========================\n";
    echo "User ID: $userId\n";
    echo "Today: $today\n";
    echo "Yesterday: $yesterday\n\n";
    
    // 1. Check if there are pending tasks from yesterday
    echo "1. Checking pending tasks from past dates:\n";
    $stmt = $db->prepare("
        SELECT id, title, planned_date, status, assigned_to 
        FROM tasks 
        WHERE assigned_to = ? 
        AND status IN ('assigned', 'not_started') 
        AND planned_date < ? 
        AND planned_date IS NOT NULL
    ");
    $stmt->execute([$userId, $today]);
    $pendingTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($pendingTasks)) {
        echo "   No pending tasks found from past dates\n";
        
        // Check if there are any tasks with yesterday's date
        $stmt = $db->prepare("
            SELECT id, title, planned_date, status 
            FROM tasks 
            WHERE assigned_to = ? AND planned_date = ?
        ");
        $stmt->execute([$userId, $yesterday]);
        $yesterdayTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($yesterdayTasks)) {
            echo "   No tasks found with yesterday's planned date\n";
            echo "   Create a test task with yesterday's date first\n";
        } else {
            echo "   Found tasks with yesterday's date:\n";
            foreach ($yesterdayTasks as $task) {
                echo "   - #{$task['id']}: {$task['title']} (status: {$task['status']})\n";
            }
        }
    } else {
        echo "   Found " . count($pendingTasks) . " pending tasks:\n";
        foreach ($pendingTasks as $task) {
            echo "   - #{$task['id']}: {$task['title']} (planned: {$task['planned_date']}, status: {$task['status']})\n";
        }
        
        // 2. Execute carry forward
        echo "\n2. Executing carry forward:\n";
        $stmt = $db->prepare("
            UPDATE tasks SET planned_date = ? 
            WHERE assigned_to = ? 
            AND status IN ('assigned', 'not_started') 
            AND planned_date < ? 
            AND planned_date IS NOT NULL
        ");
        $result = $stmt->execute([$today, $userId, $today]);
        $movedCount = $stmt->rowCount();
        
        echo "   Moved $movedCount tasks to today\n";
    }
    
    // 3. Check what should appear in today's planner
    echo "\n3. Tasks that should appear in today's planner:\n";
    $stmt = $db->prepare("
        SELECT id, title, planned_date, DATE(created_at) as created_date, status 
        FROM tasks 
        WHERE assigned_to = ? 
        AND status != 'completed'
        AND (
            planned_date = ? OR 
            (planned_date IS NULL AND DATE(created_at) = ?)
        )
        ORDER BY 
            CASE WHEN planned_date IS NOT NULL THEN 1 ELSE 2 END,
            created_at DESC
    ");
    $stmt->execute([$userId, $today, $today]);
    $todayTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($todayTasks)) {
        echo "   No tasks found for today's planner\n";
    } else {
        foreach ($todayTasks as $task) {
            $source = $task['planned_date'] ? "planned: {$task['planned_date']}" : "created: {$task['created_date']}";
            echo "   - #{$task['id']}: {$task['title']} ($source, status: {$task['status']})\n";
        }
    }
    
    // 4. Check daily_tasks table
    echo "\n4. Checking daily_tasks table:\n";
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM daily_tasks WHERE user_id = ? AND scheduled_date = ?");
    $stmt->execute([$userId, $today]);
    $dailyTasksCount = $stmt->fetchColumn();
    echo "   Daily tasks entries for today: $dailyTasksCount\n";
    
    if ($dailyTasksCount > 0) {
        $stmt = $db->prepare("SELECT id, title, status FROM daily_tasks WHERE user_id = ? AND scheduled_date = ?");
        $stmt->execute([$userId, $today]);
        $dailyTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($dailyTasks as $task) {
            echo "   - Daily Task #{$task['id']}: {$task['title']} (status: {$task['status']})\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>