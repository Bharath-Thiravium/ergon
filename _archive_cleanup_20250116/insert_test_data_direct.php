<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    // Get Harini's user ID or create user
    $stmt = $db->prepare("SELECT id FROM users WHERE email = 'harini@athenas.co.in'");
    $stmt->execute();
    $harini = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$harini) {
        $stmt = $db->prepare("INSERT INTO users (name, email, password, role, status, created_at) VALUES (?, ?, ?, 'user', 'active', NOW())");
        $stmt->execute(['Harini Kumar', 'harini@athenas.co.in', password_hash('password123', PASSWORD_DEFAULT)]);
        $harini_id = $db->lastInsertId();
    } else {
        $harini_id = $harini['id'];
    }
    
    // Get admin ID
    $stmt = $db->prepare("SELECT id FROM users WHERE role IN ('admin', 'owner') LIMIT 1");
    $stmt->execute();
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    $admin_id = $admin['id'] ?? 1;
    
    // Create departments
    $db->exec("INSERT IGNORE INTO departments (name, description, status) VALUES 
        ('Marketing', 'Marketing Department', 'active'),
        ('Sales', 'Sales Department', 'active')");
    
    $stmt = $db->prepare("SELECT id FROM departments WHERE name = 'Marketing'");
    $stmt->execute();
    $marketing_dept = $stmt->fetchColumn();
    
    // Create projects
    $db->exec("INSERT IGNORE INTO projects (name, description, status, start_date, end_date) VALUES 
        ('Website Redesign', 'Complete website overhaul', 'active', '2024-01-01', '2024-06-30'),
        ('Mobile App', 'New mobile app development', 'active', '2024-02-01', '2024-08-31')");
    
    $stmt = $db->prepare("SELECT id FROM projects WHERE name = 'Website Redesign'");
    $stmt->execute();
    $project1 = $stmt->fetchColumn();
    
    // Insert Tasks
    $tasks = [
        ['Complete Website Content Review', 'Review and update website content', 'high', 'in_progress', 65, '2024-12-25 17:00:00'],
        ['Social Media Campaign Setup', 'Set up Q1 social media campaigns', 'medium', 'assigned', 0, '2024-12-30 12:00:00'],
        ['Customer Feedback Analysis', 'Analyze customer feedback and prepare report', 'high', 'completed', 100, '2024-12-28 15:00:00'],
        ['Email Newsletter Design', 'Design monthly newsletter template', 'low', 'assigned', 0, '2025-01-05 10:00:00'],
        ['Market Research Report', 'Conduct market research for new product', 'high', 'suspended', 30, '2024-12-27 16:00:00']
    ];
    
    foreach ($tasks as $task) {
        $stmt = $db->prepare("INSERT INTO tasks (title, description, assigned_by, assigned_to, priority, status, progress, deadline, department_id, project_id, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$task[0], $task[1], $admin_id, $harini_id, $task[2], $task[3], $task[4], $task[5], $marketing_dept, $project1]);
    }
    
    // Insert Daily Tasks
    $daily_tasks = [
        ['Morning Email Review', 'Check and respond to priority emails', 'completed', 100],
        ['Content Strategy Meeting', 'Weekly content strategy meeting', 'in_progress', 50],
        ['Social Media Posts', 'Create and schedule social media posts', 'not_started', 0],
        ['Website Analytics Review', 'Review website performance metrics', 'not_started', 0]
    ];
    
    foreach ($daily_tasks as $task) {
        $stmt = $db->prepare("INSERT INTO daily_tasks (user_id, scheduled_date, title, description, status, completed_percentage, created_at) VALUES (?, CURDATE(), ?, ?, ?, ?, NOW())");
        $stmt->execute([$harini_id, $task[0], $task[1], $task[2], $task[3]]);
    }
    
    // Insert Follow-ups
    $followups = [
        ['Follow up with ABC Corp', 'Discuss partnership opportunities', 'ABC Corporation', 'John Smith', '+1-555-0123', 'pending'],
        ['Client Feedback Collection', 'Collect campaign feedback', 'XYZ Marketing', 'Sarah Johnson', '+1-555-0456', 'pending'],
        ['Product Demo Scheduling', 'Schedule product demonstration', 'Tech Solutions Inc', 'Mike Davis', '+1-555-0789', 'completed'],
        ['Contract Renewal Discussion', 'Discuss renewal terms', 'Global Enterprises', 'Lisa Wilson', '+1-555-0321', 'postponed']
    ];
    
    foreach ($followups as $followup) {
        $stmt = $db->prepare("INSERT INTO followups (user_id, title, description, company_name, contact_person, contact_phone, follow_up_date, status, created_at) VALUES (?, ?, ?, ?, ?, ?, CURDATE(), ?, NOW())");
        $stmt->execute([$harini_id, $followup[0], $followup[1], $followup[2], $followup[3], $followup[4], $followup[5]]);
    }
    
    // Insert Leave Applications
    $leaves = [
        ['annual', '2024-12-30', '2025-01-02', 4, 'Year-end vacation', 'approved'],
        ['sick', '2024-12-20', '2024-12-20', 1, 'Medical appointment', 'approved'],
        ['personal', '2025-01-15', '2025-01-15', 1, 'Personal errands', 'pending']
    ];
    
    foreach ($leaves as $leave) {
        $stmt = $db->prepare("INSERT INTO leave_applications (user_id, leave_type, start_date, end_date, days_requested, reason, status, applied_date) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$harini_id, $leave[0], $leave[1], $leave[2], $leave[3], $leave[4], $leave[5]]);
    }
    
    // Insert Expense Claims
    $expenses = [
        ['travel', 250.00, 'Client meeting travel expenses', 'approved'],
        ['meals', 85.50, 'Business lunch with client', 'approved'],
        ['office_supplies', 45.75, 'Marketing materials', 'pending'],
        ['software', 99.00, 'Design software subscription', 'pending']
    ];
    
    foreach ($expenses as $expense) {
        $stmt = $db->prepare("INSERT INTO expense_claims (user_id, expense_type, amount, description, expense_date, status, submitted_date) VALUES (?, ?, ?, ?, CURDATE(), ?, NOW())");
        $stmt->execute([$harini_id, $expense[0], $expense[1], $expense[2], $expense[3]]);
    }
    
    // Insert Advance Requests
    $advances = [
        [1000.00, 'Conference attendance', 'approved'],
        [500.00, 'Client entertainment', 'pending'],
        [750.00, 'Training program fees', 'rejected']
    ];
    
    foreach ($advances as $advance) {
        $stmt = $db->prepare("INSERT INTO advance_requests (user_id, amount, purpose, requested_date, status) VALUES (?, ?, ?, NOW(), ?)");
        $stmt->execute([$harini_id, $advance[0], $advance[1], $advance[2]]);
    }
    
    // Insert Attendance Records
    for ($i = 1; $i <= 7; $i++) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $status = ($i == 4) ? 'absent' : 'present';
        $check_in = ($status == 'present') ? '09:00:00' : null;
        $check_out = ($status == 'present') ? '18:00:00' : null;
        $hours = ($status == 'present') ? 8.0 : 0;
        
        $stmt = $db->prepare("INSERT INTO attendance (user_id, date, check_in_time, check_out_time, total_hours, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$harini_id, $date, $check_in, $check_out, $hours, $status]);
    }
    
    // Insert Notifications
    $notifications = [
        ['task', 'assigned', 'You have been assigned a new task: Complete Website Content Review'],
        ['task', 'deadline_reminder', 'Task deadline approaching: Social Media Campaign Setup'],
        ['leave', 'approved', 'Your leave application has been approved'],
        ['expense', 'approved', 'Your travel expense claim has been approved']
    ];
    
    foreach ($notifications as $notif) {
        $stmt = $db->prepare("INSERT INTO notifications (user_id, sender_id, module_name, action_type, message, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$harini_id, $admin_id, $notif[0], $notif[1], $notif[2]]);
    }
    
    echo "✅ Test data inserted successfully for harini@athenas.co.in!\n";
    echo "📊 Data includes:\n";
    echo "- 5 Tasks (various statuses)\n";
    echo "- 4 Daily Tasks\n";
    echo "- 4 Follow-ups\n";
    echo "- 3 Leave Applications\n";
    echo "- 4 Expense Claims\n";
    echo "- 3 Advance Requests\n";
    echo "- 7 Attendance Records\n";
    echo "- 4 Notifications\n";
    echo "\n🎯 User can now test all modules and functions!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>