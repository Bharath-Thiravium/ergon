<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    // Get current user ID (assuming user ID 1 exists)
    $stmt = $db->query("SELECT id FROM users LIMIT 1");
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $userId = $user['id'] ?? 1;
    
    // Get current month dates
    $currentMonth = date('Y-m');
    $daysInMonth = date('t');
    
    // Sample task titles and priorities
    $taskTitles = [
        'Client Meeting - Project Review',
        'Database Optimization',
        'UI/UX Design Review',
        'Code Review Session',
        'Team Standup Meeting',
        'Bug Fix - Payment Gateway',
        'Feature Development - Dashboard',
        'Documentation Update',
        'Security Audit',
        'Performance Testing',
        'API Integration',
        'User Training Session',
        'System Backup',
        'Server Maintenance',
        'Content Creation',
        'Marketing Campaign Review',
        'Budget Planning',
        'Vendor Meeting',
        'Quality Assurance Testing',
        'Product Demo',
        'Customer Support Review',
        'Data Analysis Report',
        'Social Media Strategy',
        'Email Campaign Setup',
        'Website Updates'
    ];
    
    $priorities = ['low', 'medium', 'high'];
    $statuses = ['assigned', 'in_progress', 'completed'];
    
    // Clear existing dummy data for current month
    $stmt = $db->prepare("DELETE FROM tasks WHERE planned_date LIKE ? AND title LIKE 'DUMMY:%'");
    $stmt->execute([$currentMonth . '%']);
    
    $stmt = $db->prepare("DELETE FROM daily_planner WHERE DATE(created_at) LIKE ? AND title LIKE 'DUMMY:%'");
    $stmt->execute([$currentMonth . '%']);
    
    echo "Adding calendar dummy data...\n";
    
    // Add tasks for random dates in current month
    for ($i = 0; $i < 30; $i++) {
        $day = rand(1, $daysInMonth);
        $date = $currentMonth . '-' . sprintf('%02d', $day);
        $title = 'DUMMY: ' . $taskTitles[array_rand($taskTitles)];
        $priority = $priorities[array_rand($priorities)];
        $status = $statuses[array_rand($statuses)];
        
        $stmt = $db->prepare("
            INSERT INTO tasks (title, description, assigned_by, assigned_to, priority, planned_date, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $title,
            'Dummy task for calendar testing - ' . $title,
            $userId,
            $userId,
            $priority,
            $date,
            $status
        ]);
        
        $taskId = $db->lastInsertId();
        
        // Add corresponding daily planner entry (50% chance)
        if (rand(0, 1)) {
            $plannerTitles = [
                'DUMMY: Morning Planning Session',
                'DUMMY: Client Call Follow-up',
                'DUMMY: Code Review Prep',
                'DUMMY: Documentation Work',
                'DUMMY: Team Sync Meeting',
                'DUMMY: Project Status Update',
                'DUMMY: Bug Investigation',
                'DUMMY: Feature Planning',
                'DUMMY: Testing Session',
                'DUMMY: Admin Tasks'
            ];
            
            $plannerTitle = $plannerTitles[array_rand($plannerTitles)];
            $completionStatus = ['pending', 'in_progress', 'completed'][array_rand(['pending', 'in_progress', 'completed'])];
            
            $stmt = $db->prepare("
                INSERT INTO daily_planner (user_id, plan_date, title, description, priority, completion_status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $plannerPriority = $priorities[array_rand($priorities)];
            
            $stmt->execute([
                $userId,
                $date,
                $plannerTitle,
                'Daily planner entry for ' . $plannerTitle,
                $plannerPriority,
                $completionStatus
            ]);
        }
    }
    
    // Add some standalone daily planner entries (not linked to tasks)
    for ($i = 0; $i < 15; $i++) {
        $day = rand(1, $daysInMonth);
        $date = $currentMonth . '-' . sprintf('%02d', $day);
        
        $standaloneTitles = [
            'DUMMY: Personal Development Time',
            'DUMMY: Email Management',
            'DUMMY: Weekly Planning',
            'DUMMY: Research & Learning',
            'DUMMY: Administrative Tasks',
            'DUMMY: Break/Lunch Planning',
            'DUMMY: Meeting Preparation',
            'DUMMY: Follow-up Calls',
            'DUMMY: Report Writing',
            'DUMMY: System Updates'
        ];
        
        $title = $standaloneTitles[array_rand($standaloneTitles)];
        $completionStatus = ['pending', 'in_progress', 'completed'][array_rand(['pending', 'in_progress', 'completed'])];
        
        $stmt = $db->prepare("
            INSERT INTO daily_planner (user_id, plan_date, title, description, priority, completion_status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $plannerPriority = $priorities[array_rand($priorities)];
        
        $stmt->execute([
            $userId,
            $date,
            $title,
            'Standalone planner entry for ' . $title,
            $plannerPriority,
            $completionStatus
        ]);
    }
    
    echo "✅ Successfully added calendar dummy data!\n";
    echo "- Added 30 dummy tasks with various priorities and statuses\n";
    echo "- Added corresponding daily planner entries\n";
    echo "- Added 15 standalone daily planner entries\n";
    echo "- Data spread across current month: " . date('F Y') . "\n";
    echo "\nYou can now view the calendar at: http://localhost/ergon/workflow/calendar\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>