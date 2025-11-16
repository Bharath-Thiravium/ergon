<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    // Clear existing task data
    echo "Clearing existing task data...\n";
    $db->exec("DELETE FROM daily_tasks");
    $db->exec("DELETE FROM tasks");
    $db->exec("DELETE FROM followups");
    
    // Get user ID (assuming user exists)
    $stmt = $db->prepare("SELECT id FROM users WHERE role IN ('user', 'admin') LIMIT 1");
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $userId = $user['id'] ?? 1;
    
    // Sample task data
    $tasks = [
        ['Client Meeting Preparation', 'Prepare presentation materials for ABC Corp meeting', 'high', 'assigned', 2, 'meeting'],
        ['Database Optimization', 'Optimize database queries for better performance', 'medium', 'in_progress', 4, 'development'],
        ['Email Campaign Setup', 'Create and schedule monthly newsletter campaign', 'medium', 'assigned', 3, 'marketing'],
        ['Security Audit Review', 'Review security audit findings and create action plan', 'high', 'assigned', 6, 'security'],
        ['User Interface Updates', 'Update dashboard UI based on user feedback', 'medium', 'in_progress', 5, 'development'],
        ['Budget Report Analysis', 'Analyze Q4 budget reports and prepare summary', 'low', 'assigned', 2, 'finance'],
        ['Team Training Session', 'Conduct training on new project management tools', 'medium', 'assigned', 4, 'training'],
        ['Server Maintenance', 'Perform routine server maintenance and updates', 'high', 'assigned', 3, 'maintenance'],
        ['Customer Feedback Review', 'Review and categorize customer feedback from last month', 'low', 'assigned', 2, 'support'],
        ['Product Documentation', 'Update product documentation for new features', 'medium', 'assigned', 4, 'documentation'],
        ['Sales Report Generation', 'Generate monthly sales reports for management', 'medium', 'assigned', 3, 'reporting'],
        ['Website Content Update', 'Update website content and fix broken links', 'low', 'assigned', 2, 'content'],
        ['API Integration Testing', 'Test new API integrations with third-party services', 'high', 'assigned', 5, 'testing'],
        ['Employee Onboarding', 'Prepare onboarding materials for new employees', 'medium', 'assigned', 3, 'hr'],
        ['Backup System Check', 'Verify backup systems are working correctly', 'high', 'assigned', 2, 'maintenance'],
        ['Social Media Strategy', 'Develop social media strategy for next quarter', 'medium', 'assigned', 4, 'marketing'],
        ['Code Review Process', 'Review and approve pending code changes', 'medium', 'in_progress', 3, 'development'],
        ['Vendor Contract Review', 'Review contracts with software vendors', 'low', 'assigned', 2, 'legal'],
        ['Performance Metrics', 'Analyze team performance metrics and KPIs', 'medium', 'assigned', 3, 'analytics'],
        ['Project Planning', 'Plan next sprint activities and resource allocation', 'high', 'assigned', 4, 'planning']
    ];
    
    echo "Inserting 20 test tasks...\n";
    
    $taskIds = [];
    foreach ($tasks as $index => $task) {
        $deadline = date('Y-m-d H:i:s', strtotime('+' . rand(1, 14) . ' days'));
        $plannedDate = date('Y-m-d', strtotime('+' . rand(0, 7) . ' days'));
        
        $stmt = $db->prepare("
            INSERT INTO tasks (title, description, assigned_by, assigned_to, task_type, priority, 
                             deadline, status, progress, sla_hours, task_category, planned_date, created_at)
            VALUES (?, ?, ?, ?, 'ad-hoc', ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $task[0], // title
            $task[1], // description
            $userId,  // assigned_by
            $userId,  // assigned_to
            $task[2], // priority
            $deadline,
            $task[3], // status
            rand(0, 30), // progress
            $task[4], // sla_hours
            $task[5], // task_category
            $plannedDate
        ]);
        
        $taskIds[] = $db->lastInsertId();
    }
    
    echo "Creating daily tasks for today...\n";
    
    // Create daily tasks for today (first 8 tasks)
    $today = date('Y-m-d');
    for ($i = 0; $i < 8; $i++) {
        $taskId = $taskIds[$i];
        $task = $tasks[$i];
        
        $stmt = $db->prepare("
            INSERT INTO daily_tasks (user_id, task_id, scheduled_date, title, description, 
                                   planned_duration, priority, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'not_started', NOW())
        ");
        
        $stmt->execute([
            $userId,
            $taskId,
            $today,
            $task[0],
            $task[1],
            $task[4] * 60, // convert hours to minutes
            $task[2]
        ]);
    }
    
    echo "Creating follow-ups...\n";
    
    // Create follow-ups (tasks with follow-up category or overdue tasks)
    $followupTasks = array_slice($taskIds, 10, 6); // Use tasks 11-16 for follow-ups
    
    foreach ($followupTasks as $index => $taskId) {
        $followupDate = date('Y-m-d', strtotime('+' . rand(1, 5) . ' days'));
        $task = $tasks[10 + $index];
        
        $stmt = $db->prepare("
            INSERT INTO followups (user_id, task_id, title, description, company_name, 
                                 follow_up_date, reminder_time, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, '09:00:00', 'pending', NOW())
        ");
        
        $stmt->execute([
            $userId,
            $taskId,
            'Follow-up: ' . $task[0],
            'Follow-up required for: ' . $task[1],
            'Sample Company ' . ($index + 1),
            $followupDate
        ]);
    }
    
    echo "Test data setup completed successfully!\n";
    echo "Created:\n";
    echo "- 20 tasks in tasks table\n";
    echo "- 8 daily tasks for today\n";
    echo "- 6 follow-ups\n";
    echo "- Tasks distributed across calendar dates\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>