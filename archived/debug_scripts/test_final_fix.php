<?php
/**
 * Final Test - Verify Planner Fix Works
 */

session_start();
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // Mock for testing
}

require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/models/DailyPlanner.php';

try {
    $db = Database::connect();
    $planner = new DailyPlanner();
    
    $userId = $_SESSION['user_id'];
    $today = date('Y-m-d');
    
    echo "<h1>Final Planner Fix Test</h1>";
    echo "<style>body{font-family:Arial,sans-serif;margin:20px;}.success{color:green;font-weight:bold;}.error{color:red;font-weight:bold;}.info{color:blue;}</style>";
    
    echo "<p><strong>Testing Date:</strong> {$today}</p>";
    echo "<p><strong>User ID:</strong> {$userId}</p>";
    
    // Ensure we have at least one test task
    $stmt = $db->prepare("SELECT COUNT(*) FROM tasks WHERE assigned_to = ? AND (planned_date = ? OR DATE(created_at) = ?) AND status NOT IN ('completed', 'cancelled', 'deleted')");
    $stmt->execute([$userId, $today, $today]);
    $taskCount = $stmt->fetchColumn();
    
    if ($taskCount == 0) {
        echo "<p class='info'>Creating test task...</p>";
        $stmt = $db->prepare("INSERT INTO tasks (title, description, planned_date, assigned_to, assigned_by, status, sla_hours, created_at) VALUES (?, ?, ?, ?, ?, 'assigned', 0.25, NOW())");
        $stmt->execute([
            'Final Test Task - ' . date('H:i:s'),
            'This task was created to test the planner fix',
            $today,
            $userId,
            $userId
        ]);
        echo "<p class='success'>✅ Test task created</p>";
    }
    
    // Test the fixed getTasksForDate method
    echo "<h2>Testing getTasksForDate Method</h2>";
    
    $startTime = microtime(true);
    $plannedTasks = $planner->getTasksForDate($userId, $today);
    $endTime = microtime(true);
    
    $executionTime = round(($endTime - $startTime) * 1000, 2);
    
    echo "<p>Execution time: {$executionTime}ms</p>";
    echo "<p>Tasks returned: " . count($plannedTasks) . "</p>";
    
    if (empty($plannedTasks)) {
        echo "<p class='error'>❌ FAILED: No tasks returned</p>";
        
        // Debug information
        echo "<h3>Debug Information</h3>";
        
        // Check tasks table
        $stmt = $db->prepare("SELECT COUNT(*) FROM tasks WHERE assigned_to = ?");
        $stmt->execute([$userId]);
        echo "<p>Total tasks in tasks table: " . $stmt->fetchColumn() . "</p>";
        
        // Check daily_tasks table
        $stmt = $db->prepare("SELECT COUNT(*) FROM daily_tasks WHERE user_id = ? AND scheduled_date = ?");
        $stmt->execute([$userId, $today]);
        echo "<p>Tasks in daily_tasks table for today: " . $stmt->fetchColumn() . "</p>";
        
    } else {
        echo "<p class='success'>✅ SUCCESS: Found " . count($plannedTasks) . " tasks!</p>";
        
        echo "<h3>Task Details:</h3>";
        echo "<table border='1' style='border-collapse:collapse;width:100%;'>";
        echo "<tr style='background:#f2f2f2;'><th>ID</th><th>Title</th><th>Status</th><th>Priority</th><th>SLA Hours</th><th>Source</th></tr>";
        
        foreach ($plannedTasks as $task) {
            echo "<tr>";
            echo "<td>{$task['id']}</td>";
            echo "<td>" . htmlspecialchars($task['title']) . "</td>";
            echo "<td>{$task['status']}</td>";
            echo "<td>{$task['priority']}</td>";
            echo "<td>" . ($task['sla_hours'] ?? '0.25') . "</td>";
            echo "<td>" . ($task['source_field'] ?? 'N/A') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Test API endpoint
    echo "<h2>Testing API Endpoint</h2>";
    
    $apiUrl = "http://localhost/ergon/api/daily_planner_workflow.php?action=get_tasks&date={$today}&user_id={$userId}";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "<p>API HTTP Code: {$httpCode}</p>";
    
    if ($httpCode == 200) {
        $apiData = json_decode($response, true);
        if ($apiData && isset($apiData['success']) && $apiData['success']) {
            echo "<p class='success'>✅ API Success: " . ($apiData['count'] ?? 0) . " tasks returned</p>";
        } else {
            echo "<p class='error'>❌ API Error: " . ($apiData['message'] ?? 'Unknown error') . "</p>";
        }
    } else {
        echo "<p class='error'>❌ API Failed with HTTP {$httpCode}</p>";
        echo "<p>Response: " . htmlspecialchars(substr($response, 0, 200)) . "</p>";
    }
    
    // Final verdict
    echo "<h2>Final Result</h2>";
    
    if (count($plannedTasks) > 0) {
        echo "<div style='padding:20px;background:#d4edda;border:1px solid #c3e6cb;border-radius:5px;'>";
        echo "<h3 style='color:#155724;margin:0 0 10px 0;'>🎉 SUCCESS!</h3>";
        echo "<p style='margin:0;'>The Planner module is now working correctly and displaying " . count($plannedTasks) . " tasks.</p>";
        echo "<p style='margin:10px 0 0 0;'><strong>Next step:</strong> Visit <a href='/ergon/workflow/daily-planner/{$today}' target='_blank'>Daily Planner</a> to see the results.</p>";
        echo "</div>";
    } else {
        echo "<div style='padding:20px;background:#f8d7da;border:1px solid #f5c6cb;border-radius:5px;'>";
        echo "<h3 style='color:#721c24;margin:0 0 10px 0;'>❌ Issue Still Exists</h3>";
        echo "<p style='margin:0;'>The fix did not resolve the issue. Please run the comprehensive fix script.</p>";
        echo "<p style='margin:10px 0 0 0;'><strong>Next step:</strong> Run <a href='fix_planner_complete.php' target='_blank'>Complete Fix Script</a></p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>