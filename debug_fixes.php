<?php
// Debug script to test all fixes
require_once __DIR__ . '/app/config/database.php';

echo "<h1>ERGON Debug Report</h1>";

try {
    $db = Database::connect();
    echo "<p style='color: green;'>✅ Database connection successful</p>";
    
    // Test 1: Check tables exist
    echo "<h2>1. Database Tables Check</h2>";
    $tables = ['users', 'departments', 'leaves', 'expenses', 'advances', 'tasks', 'followups', 'settings'];
    foreach ($tables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->fetchColumn()) {
            echo "<p style='color: green;'>✅ Table '$table' exists</p>";
        } else {
            echo "<p style='color: red;'>❌ Table '$table' missing</p>";
        }
    }
    
    // Test 2: Check settings table structure
    echo "<h2>2. Settings Table Structure</h2>";
    $stmt = $db->query("DESCRIBE settings");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $column) {
        echo "<p>Column: {$column['Field']} - Type: {$column['Type']}</p>";
    }
    
    // Test 3: Check users table structure
    echo "<h2>3. Users Table Structure</h2>";
    $stmt = $db->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $column) {
        echo "<p>Column: {$column['Field']} - Type: {$column['Type']}</p>";
    }
    
    // Test 4: Check sample data
    echo "<h2>4. Sample Data Check</h2>";
    
    // Users
    $stmt = $db->query("SELECT COUNT(*) as count FROM users");
    $count = $stmt->fetch()['count'];
    echo "<p>Users count: $count</p>";
    
    // Departments
    $stmt = $db->query("SELECT COUNT(*) as count FROM departments");
    $count = $stmt->fetch()['count'];
    echo "<p>Departments count: $count</p>";
    
    // Sample departments
    $stmt = $db->query("SELECT id, name FROM departments LIMIT 5");
    $depts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>Sample departments:</p><ul>";
    foreach ($depts as $dept) {
        echo "<li>ID: {$dept['id']}, Name: {$dept['name']}</li>";
    }
    echo "</ul>";
    
    // Test 5: Check pending approvals
    echo "<h2>5. Pending Approvals Check</h2>";
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM leaves WHERE status = 'pending'");
    $count = $stmt->fetch()['count'];
    echo "<p>Pending leaves: $count</p>";
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM expenses WHERE status = 'pending'");
    $count = $stmt->fetch()['count'];
    echo "<p>Pending expenses: $count</p>";
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM advances WHERE status = 'pending'");
    $count = $stmt->fetch()['count'];
    echo "<p>Pending advances: $count</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
}

echo "<h2>6. File Permissions Check</h2>";
$files = [
    'app/controllers/OwnerController.php',
    'app/controllers/SettingsController.php', 
    'app/controllers/UsersController.php',
    'app/controllers/TasksController.php',
    'app/controllers/FollowupController.php',
    'check_reminders.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "<p style='color: green;'>✅ File exists: $file</p>";
    } else {
        echo "<p style='color: red;'>❌ File missing: $file</p>";
    }
}

echo "<h2>7. Routes Test</h2>";
$routes = [
    '/owner/approve-request',
    '/owner/reject-request', 
    '/reports/approvals-export',
    '/settings',
    '/users/create',
    '/tasks/create',
    '/followups'
];

foreach ($routes as $route) {
    echo "<p>Route: $route</p>";
}

echo "<p><strong>Debug completed at " . date('Y-m-d H:i:s') . "</strong></p>";
?>