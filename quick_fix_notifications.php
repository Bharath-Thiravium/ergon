<?php
// Quick Fix for Notification System
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'owner';

echo "<h1>Quick Fix Notification System</h1>";
echo "<style>body{font-family:Arial;margin:20px;} .success{color:green;} .error{color:red;}</style>";

try {
    require_once __DIR__ . '/app/config/database.php';
    $db = Database::connect();
    
    // Create all required tables
    $tables = [
        "CREATE TABLE IF NOT EXISTS users (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100), email VARCHAR(120), password VARCHAR(255), role ENUM('owner','admin','user') DEFAULT 'user', status ENUM('active','inactive') DEFAULT 'active', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)",
        "CREATE TABLE IF NOT EXISTS leaves (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT, leave_type VARCHAR(50), start_date DATE, end_date DATE, days_requested INT DEFAULT 1, reason TEXT, status VARCHAR(20) DEFAULT 'pending', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)",
        "CREATE TABLE IF NOT EXISTS expenses (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT, category VARCHAR(100), amount DECIMAL(10,2), description TEXT, status VARCHAR(20) DEFAULT 'pending', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)",
        "CREATE TABLE IF NOT EXISTS advances (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT, type VARCHAR(50), amount DECIMAL(10,2), reason TEXT, requested_date DATE DEFAULT (CURDATE()), status VARCHAR(20) DEFAULT 'pending', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)",
        "CREATE TABLE IF NOT EXISTS followups (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT, title VARCHAR(255), company_name VARCHAR(255), follow_up_date DATE, original_date DATE DEFAULT (CURDATE()), status ENUM('pending','completed') DEFAULT 'pending', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)",
        "CREATE TABLE IF NOT EXISTS tasks (id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(255), assigned_by INT, assigned_to INT, status ENUM('assigned','completed') DEFAULT 'assigned', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)",
        "CREATE TABLE IF NOT EXISTS attendance (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT, check_in DATETIME, status VARCHAR(20) DEFAULT 'present', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)",
        "CREATE TABLE IF NOT EXISTS notifications (id INT AUTO_INCREMENT PRIMARY KEY, sender_id INT, receiver_id INT, module_name VARCHAR(50), action_type VARCHAR(50), message TEXT, reference_id INT, is_read TINYINT(1) DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)"
    ];
    
    foreach ($tables as $sql) {
        $db->exec($sql);
    }
    
    // Insert test users
    $db->exec("INSERT IGNORE INTO users (id, name, email, password, role) VALUES (1, 'Test Owner', 'owner@test.com', 'password', 'owner')");
    $db->exec("INSERT IGNORE INTO users (id, name, email, password, role) VALUES (2, 'Test Employee', 'employee@test.com', 'password', 'user')");
    
    // Test notifications directly
    require_once __DIR__ . '/app/helpers/NotificationHelper.php';
    
    // Test leave notification
    $db->exec("INSERT INTO leaves (user_id, leave_type, start_date, end_date, days_requested, reason, status) VALUES (2, 'sick', CURDATE(), CURDATE(), 1, 'Test leave', 'pending')");
    $leaveId = $db->lastInsertId();
    NotificationHelper::notifyOwners(2, 'leave', 'request', 'Test Employee requested leave', $leaveId);
    
    // Test expense notification  
    $db->exec("INSERT INTO expenses (user_id, category, amount, description, status) VALUES (2, 'Travel', 100.00, 'Test expense', 'pending')");
    $expenseId = $db->lastInsertId();
    NotificationHelper::notifyOwners(2, 'expense', 'claim', 'Test Employee submitted expense claim', $expenseId);
    
    // Test advance notification
    $db->exec("INSERT INTO advances (user_id, type, amount, reason, requested_date, status) VALUES (2, 'Salary', 1000.00, 'Test advance', CURDATE(), 'pending')");
    $advanceId = $db->lastInsertId();
    NotificationHelper::notifyOwners(2, 'advance', 'request', 'Test Employee requested advance', $advanceId);
    
    // Test followup notification
    $db->exec("INSERT INTO followups (user_id, title, company_name, follow_up_date, original_date, status) VALUES (2, 'Test Followup', 'Test Company', CURDATE(), CURDATE(), 'pending')");
    $followupId = $db->lastInsertId();
    NotificationHelper::notifyOwners(2, 'followup', 'created', 'Test Employee created followup', $followupId);
    
    // Check notifications
    $stmt = $db->query("SELECT COUNT(*) FROM notifications");
    $count = $stmt->fetchColumn();
    
    echo "<div class='success'>✓ Fixed! Created $count notifications</div>";
    echo "<p><a href='/ergon/notifications'>View Notifications</a> | <a href='/ergon/test_complete_notification_system.php'>Run Tests</a></p>";
    
} catch (Exception $e) {
    echo "<div class='error'>Error: " . $e->getMessage() . "</div>";
}
?>