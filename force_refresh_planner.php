<?php
session_start();
require_once __DIR__ . '/app/config/database.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
}

try {
    $db = Database::connect();
    $userId = $_SESSION['user_id'];
    $today = date('Y-m-d');
    
    echo "<h2>Force Refresh Daily Planner</h2>";
    
    // Step 1: Clear existing daily tasks for today
    $stmt = $db->prepare("DELETE FROM daily_tasks WHERE user_id = ? AND scheduled_date = ?");
    $stmt->execute([$userId, $today]);
    $deleted = $stmt->rowCount();
    echo "<p>✅ Cleared $deleted existing daily tasks</p>";
    
    // Step 2: Get tasks for today
    $stmt = $db->prepare("
        SELECT * FROM tasks 
        WHERE assigned_to = ? 
        AND (
            DATE(created_at) = ? OR
            DATE(deadline) = ? OR
            DATE(planned_date) = ? OR
            status = 'in_progress' OR
            (assigned_by != assigned_to AND DATE(COALESCE(assigned_at, created_at)) = ?)
        )
        AND status != 'completed' 
        ORDER BY 
            CASE 
                WHEN assigned_by != assigned_to THEN 1
                ELSE 2
            END,
            CASE priority
                WHEN 'high' THEN 1
                WHEN 'medium' THEN 2
                WHEN 'low' THEN 3
                ELSE 4
            END,
            created_at DESC 
        LIMIT 15
    ");
    $stmt->execute([$userId, $today, $today, $today, $today]);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Found " . count($tasks) . " tasks for today</p>";
    
    // Step 3: Create daily tasks
    if (!empty($tasks)) {
        foreach ($tasks as $task) {
            $taskSource = ($task['assigned_by'] != $task['assigned_to']) ? 'assigned_by_others' : 'self_assigned';
            $taskTitle = $task['title'];
            
            if ($taskSource === 'assigned_by_others') {
                $taskTitle = "[From Others] " . $taskTitle;
            } else {
                $taskTitle = "[Self] " . $taskTitle;
            }
            
            $stmt = $db->prepare("
                INSERT INTO daily_tasks (user_id, task_id, scheduled_date, title, description, planned_duration, priority, status, created_at)
                VALUES (?, ?, ?, ?, ?, 60, ?, 'not_started', NOW())
            ");
            $stmt->execute([
                $userId, 
                $task['id'], 
                $today, 
                $taskTitle, 
                $task['description'], 
                $task['priority'] ?? 'medium'
            ]);
        }
        
        echo "<p>✅ Created " . count($tasks) . " daily tasks</p>";
        
        // Verify
        $stmt = $db->prepare("SELECT COUNT(*) FROM daily_tasks WHERE user_id = ? AND scheduled_date = ?");
        $stmt->execute([$userId, $today]);
        $dailyCount = $stmt->fetchColumn();
        
        echo "<p>✅ Verified: $dailyCount daily tasks now exist</p>";
        
        echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h4>✅ Daily Planner Refreshed Successfully!</h4>";
        echo "<p>Tasks should now appear in the daily planner.</p>";
        echo "</div>";
    } else {
        echo "<p style='color: red;'>❌ No tasks found for today</p>";
    }
    
    echo "<p><a href='/ergon/workflow/daily-planner' style='background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>View Daily Planner</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
</style>