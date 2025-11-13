<?php
// Test script to add a task for today's date
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    $today = date('Y-m-d');
    echo "Adding test task for today: $today\n";
    
    // Add a test task for today
    $stmt = $db->prepare("
        INSERT INTO tasks (title, description, assigned_by, assigned_to, planned_date, priority, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $result = $stmt->execute([
        'Test Task for Today - Calendar Fix',
        'This is a test task to verify calendar functionality',
        1, // assigned_by (owner)
        1, // assigned_to (owner)
        $today, // planned_date
        'high', // priority
        'assigned' // status
    ]);
    
    if ($result) {
        $taskId = $db->lastInsertId();
        echo "✅ Successfully added test task with ID: $taskId\n";
        
        // Also add a daily_plans entry for today
        $stmt2 = $db->prepare("
            INSERT INTO daily_plans (user_id, plan_date, title, description, priority, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $result2 = $stmt2->execute([
            1, // user_id
            $today, // plan_date
            'Daily Plan for Today - Calendar Test',
            'Test daily plan entry for calendar verification',
            'medium', // priority
            'pending' // status
        ]);
        
        if ($result2) {
            $planId = $db->lastInsertId();
            echo "✅ Successfully added daily plan with ID: $planId\n";
        }
        
        // Test the calendar query
        $month = date('m');
        $year = date('Y');
        
        echo "\nTesting calendar query for month: $month, year: $year\n";
        
        $stmt3 = $db->prepare("
            SELECT 
                t.id, t.title, t.priority, t.status, 
                COALESCE(t.planned_date, DATE(t.created_at)) as date,
                u.name as assigned_user, 'task' as type
            FROM tasks t
            LEFT JOIN users u ON t.assigned_to = u.id
            WHERE ((MONTH(t.planned_date) = ? AND YEAR(t.planned_date) = ?) 
                   OR (t.planned_date IS NULL AND MONTH(t.created_at) = ? AND YEAR(t.created_at) = ?))
            AND (t.assigned_to = ? OR t.assigned_by = ?)
            
            UNION ALL
            
            SELECT 
                dp.id, dp.title, dp.priority, 'planned' as status, dp.plan_date as date,
                u.name as assigned_user, 'planner' as type
            FROM daily_planner dp
            LEFT JOIN users u ON dp.user_id = u.id
            WHERE MONTH(dp.plan_date) = ? AND YEAR(dp.plan_date) = ?
            AND dp.user_id = ?
            
            UNION ALL
            
            SELECT 
                dpl.id, dpl.title, dpl.priority, dpl.status, dpl.plan_date as date,
                u.name as assigned_user, 'daily_plan' as type
            FROM daily_plans dpl
            LEFT JOIN users u ON dpl.user_id = u.id
            WHERE MONTH(dpl.plan_date) = ? AND YEAR(dpl.plan_date) = ?
            AND dpl.user_id = ?
            
            ORDER BY date
        ");
        
        $stmt3->execute([
            $month, $year, $month, $year, 1, 1,
            $month, $year, 1,
            $month, $year, 1
        ]);
        
        $results = $stmt3->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Found " . count($results) . " calendar entries:\n";
        foreach ($results as $entry) {
            echo "- {$entry['date']}: {$entry['title']} ({$entry['type']})\n";
        }
        
    } else {
        echo "❌ Failed to add test task\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>