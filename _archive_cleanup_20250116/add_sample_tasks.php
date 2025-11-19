<?php
session_start();
require_once __DIR__ . '/app/config/database.php';

// Simulate user session if not set
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // Change this to your user ID
    $_SESSION['role'] = 'owner';
}

try {
    $db = Database::connect();
    $userId = $_SESSION['user_id'];
    $today = date('Y-m-d');
    
    echo "<h2>Adding Sample Tasks for Daily Planner</h2>";
    echo "<p>User ID: <strong>$userId</strong></p>";
    echo "<p>Today's Date: <strong>$today</strong></p>";
    echo "<hr>";
    
    // Ensure we have at least 2 users for testing "assigned by others"
    $stmt = $db->prepare("SELECT COUNT(*) FROM users");
    $stmt->execute();
    $userCount = $stmt->fetchColumn();
    
    if ($userCount < 2) {
        echo "<p style='color: orange;'>⚠ Only $userCount user(s) found. Creating additional test users...</p>";
        
        // Create test users
        $testUsers = [
            ['name' => 'Manager Test', 'email' => 'manager@test.com', 'role' => 'admin'],
            ['name' => 'Colleague Test', 'email' => 'colleague@test.com', 'role' => 'user']
        ];
        
        foreach ($testUsers as $user) {
            $stmt = $db->prepare("
                INSERT IGNORE INTO users (name, email, password, role, employee_id, status, created_at) 
                VALUES (?, ?, ?, ?, ?, 'active', NOW())
            ");
            $stmt->execute([
                $user['name'], 
                $user['email'], 
                password_hash('password123', PASSWORD_DEFAULT),
                $user['role'],
                'EMP' . str_pad(rand(100, 999), 3, '0', STR_PAD_LEFT)
            ]);
        }
        echo "<p style='color: green;'>✅ Created test users</p>";
    }
    
    // Get available users for assigning tasks
    $stmt = $db->prepare("SELECT id, name FROM users WHERE id != ? LIMIT 2");
    $stmt->execute([$userId]);
    $otherUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $assignerId = !empty($otherUsers) ? $otherUsers[0]['id'] : $userId;
    
    // Sample tasks to add
    $tasks = [
        // Tasks assigned by others (high priority for daily planner)
        [
            'title' => 'Review Client Proposal - Urgent',
            'description' => 'Review and provide feedback on the new client proposal document for ABC Corp. Deadline is today.',
            'assigned_by' => $assignerId,
            'assigned_to' => $userId,
            'priority' => 'high',
            'status' => 'assigned',
            'deadline' => $today,
            'planned_date' => $today
        ],
        [
            'title' => 'Prepare Monthly Report',
            'description' => 'Compile and prepare the monthly performance report for management review.',
            'assigned_by' => $assignerId,
            'assigned_to' => $userId,
            'priority' => 'medium',
            'status' => 'assigned',
            'deadline' => $today,
            'planned_date' => $today
        ],
        [
            'title' => 'Team Meeting Follow-up',
            'description' => 'Follow up on action items from yesterday\'s team meeting and send updates to stakeholders.',
            'assigned_by' => $assignerId,
            'assigned_to' => $userId,
            'priority' => 'medium',
            'status' => 'assigned',
            'deadline' => $today,
            'planned_date' => null
        ],
        
        // Self-assigned tasks
        [
            'title' => 'Update Project Documentation',
            'description' => 'Update the project documentation with latest changes and improvements.',
            'assigned_by' => $userId,
            'assigned_to' => $userId,
            'priority' => 'low',
            'status' => 'assigned',
            'deadline' => $today,
            'planned_date' => $today
        ],
        [
            'title' => 'Code Review - Feature Branch',
            'description' => 'Review the new feature branch code and provide feedback to the development team.',
            'assigned_by' => $userId,
            'assigned_to' => $userId,
            'priority' => 'medium',
            'status' => 'assigned',
            'deadline' => $today,
            'planned_date' => null
        ],
        [
            'title' => 'Personal Development - Training',
            'description' => 'Complete the online training module for new project management tools.',
            'assigned_by' => $userId,
            'assigned_to' => $userId,
            'priority' => 'low',
            'status' => 'assigned',
            'deadline' => date('Y-m-d', strtotime('+1 day')),
            'planned_date' => $today
        ],
        
        // In-progress task (should always show in daily planner)
        [
            'title' => 'Database Optimization Project',
            'description' => 'Ongoing work to optimize database queries and improve system performance.',
            'assigned_by' => $assignerId,
            'assigned_to' => $userId,
            'priority' => 'high',
            'status' => 'in_progress',
            'deadline' => date('Y-m-d', strtotime('+2 days')),
            'planned_date' => date('Y-m-d', strtotime('-1 day'))
        ]
    ];
    
    echo "<h3>Adding Sample Tasks...</h3>";
    
    $stmt = $db->prepare("
        INSERT INTO tasks (title, description, assigned_by, assigned_to, priority, status, deadline, planned_date, created_at, assigned_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");
    
    $addedCount = 0;
    foreach ($tasks as $task) {
        try {
            $stmt->execute([
                $task['title'],
                $task['description'],
                $task['assigned_by'],
                $task['assigned_to'],
                $task['priority'],
                $task['status'],
                $task['deadline'],
                $task['planned_date']
            ]);
            $addedCount++;
            
            $source = ($task['assigned_by'] != $task['assigned_to']) ? '👥 From Others' : '👤 Self-Assigned';
            $priorityColor = $task['priority'] === 'high' ? '#dc3545' : ($task['priority'] === 'medium' ? '#ffc107' : '#28a745');
            
            echo "<div style='background: white; padding: 10px; margin: 5px 0; border-left: 4px solid $priorityColor; border-radius: 4px;'>";
            echo "<strong>{$task['title']}</strong> ($source, Priority: {$task['priority']})<br>";
            echo "<small style='color: #666;'>{$task['description']}</small>";
            echo "</div>";
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Error adding task '{$task['title']}': " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4>✅ Successfully Added $addedCount Tasks</h4>";
    echo "<p>These tasks are now available in your Daily Planner and should appear when you visit:</p>";
    echo "<p><strong>http://localhost/ergon/workflow/daily-planner</strong></p>";
    echo "</div>";
    
    // Show breakdown
    $fromOthers = count(array_filter($tasks, fn($t) => $t['assigned_by'] != $t['assigned_to']));
    $selfAssigned = count(array_filter($tasks, fn($t) => $t['assigned_by'] == $t['assigned_to']));
    $todayDeadline = count(array_filter($tasks, fn($t) => $t['deadline'] === $today));
    $inProgress = count(array_filter($tasks, fn($t) => $t['status'] === 'in_progress'));
    
    echo "<div style='background: #e9ecef; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4>Task Breakdown:</h4>";
    echo "<p>👥 <strong>Assigned by Others:</strong> $fromOthers tasks</p>";
    echo "<p>👤 <strong>Self-Assigned:</strong> $selfAssigned tasks</p>";
    echo "<p>📅 <strong>Due Today:</strong> $todayDeadline tasks</p>";
    echo "<p>🔄 <strong>In Progress:</strong> $inProgress tasks</p>";
    echo "</div>";
    
    echo "<hr>";
    
    // Quick actions
    echo "<h3>Next Steps:</h3>";
    echo "<div style='text-align: center; margin: 20px 0;'>";
    echo "<a href='/ergon/workflow/daily-planner' style='background: #007cba; color: white; padding: 15px 30px; text-decoration: none; border-radius: 6px; margin: 10px; display: inline-block; font-weight: bold;'>🗓 Open Daily Planner</a>";
    echo "<a href='test_daily_planner_fix.php' style='background: #28a745; color: white; padding: 15px 30px; text-decoration: none; border-radius: 6px; margin: 10px; display: inline-block; font-weight: bold;'>🔍 Test & Debug</a>";
    echo "<a href='/ergon/tasks' style='background: #6c757d; color: white; padding: 15px 30px; text-decoration: none; border-radius: 6px; margin: 10px; display: inline-block; font-weight: bold;'>📋 View All Tasks</a>";
    echo "</div>";
    
    // Clear option
    if (isset($_GET['clear'])) {
        $stmt = $db->prepare("DELETE FROM tasks WHERE assigned_to = ?");
        $stmt->execute([$userId]);
        $stmt = $db->prepare("DELETE FROM daily_tasks WHERE user_id = ?");
        $stmt->execute([$userId]);
        echo "<p style='color: green; text-align: center; margin-top: 20px;'>✅ All tasks cleared. <a href='add_sample_tasks.php'>Add new tasks</a></p>";
    } else {
        echo "<p style='text-align: center; margin-top: 20px;'>";
        echo "<a href='?clear=1' style='background: #dc3545; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px; font-size: 14px;'>🗑 Clear All Tasks (Reset)</a>";
        echo "</p>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4>❌ Database Error</h4>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
?>

<style>
body { 
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; 
    margin: 20px; 
    line-height: 1.6;
    background: #f8f9fa;
    max-width: 800px;
    margin: 20px auto;
}
h2, h3 {
    color: #495057;
}
hr {
    border: none;
    height: 1px;
    background: #dee2e6;
    margin: 30px 0;
}
</style>