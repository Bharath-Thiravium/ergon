<?php
/**
 * Test Script to Verify ERGON Fixes
 */

echo "ERGON Fix Verification Test\n";
echo "===========================\n\n";

// Test 1: Database Connection
echo "1. Testing database connection...\n";
try {
    require_once __DIR__ . '/app/config/database.php';
    $db = Database::connect();
    echo "   ✅ Database connection successful\n";
} catch (Exception $e) {
    echo "   ❌ Database connection failed: " . $e->getMessage() . "\n";
    exit;
}

// Test 2: Check Required Tables
echo "\n2. Checking required tables exist...\n";
$requiredTables = ['users', 'tasks', 'attendance', 'advances', 'expenses', 'leaves', 'settings', 'activity_logs', 'followups', 'departments'];

foreach ($requiredTables as $table) {
    $stmt = $db->query("SHOW TABLES LIKE '$table'");
    if ($stmt->rowCount() > 0) {
        echo "   ✅ Table '$table' exists\n";
    } else {
        echo "   ❌ Table '$table' missing\n";
    }
}

// Test 3: Check User Table Structure
echo "\n3. Checking user table structure for personal details...\n";
$stmt = $db->query("DESCRIBE users");
$columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

$personalFields = ['date_of_birth', 'gender', 'address', 'emergency_contact', 'joining_date', 'designation', 'salary', 'department_id'];

foreach ($personalFields as $field) {
    if (in_array($field, $columns)) {
        echo "   ✅ Field '$field' exists in users table\n";
    } else {
        echo "   ❌ Field '$field' missing from users table\n";
    }
}

// Test 4: Check Controller Files
echo "\n4. Checking controller files exist...\n";
$controllers = [
    'UsersController.php',
    'TasksController.php',
    'FollowupController.php',
    'SettingsController.php',
    'NotificationController.php',
    'AdvanceController.php',
    'ExpenseController.php',
    'LeaveController.php',
    'AttendanceController.php',
    'ReportsController.php'
];

foreach ($controllers as $controller) {
    $path = __DIR__ . '/app/controllers/' . $controller;
    if (file_exists($path)) {
        echo "   ✅ Controller '$controller' exists\n";
    } else {
        echo "   ❌ Controller '$controller' missing\n";
    }
}

// Test 5: Check Route Configuration
echo "\n5. Testing route configuration...\n";
$routeFile = __DIR__ . '/app/config/routes.php';
if (file_exists($routeFile)) {
    $routeContent = file_get_contents($routeFile);
    
    // Check for critical routes
    $criticalRoutes = [
        '/admin/export',
        '/api/notifications/mark-all-read',
        '/settings',
        '/tasks',
        '/followups',
        '/advances/create'
    ];
    
    foreach ($criticalRoutes as $route) {
        if (strpos($routeContent, $route) !== false) {
            echo "   ✅ Route '$route' configured\n";
        } else {
            echo "   ❌ Route '$route' missing\n";
        }
    }
} else {
    echo "   ❌ Routes configuration file missing\n";
}

// Test 6: Check Settings Functionality
echo "\n6. Testing settings functionality...\n";
try {
    $stmt = $db->query("SELECT * FROM settings LIMIT 1");
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($settings) {
        echo "   ✅ Settings table has data\n";
        
        // Test update
        $stmt = $db->prepare("UPDATE settings SET company_name = ? WHERE id = ?");
        if ($stmt->execute(['Test Update', $settings['id']])) {
            echo "   ✅ Settings update functionality working\n";
            
            // Revert
            $stmt = $db->prepare("UPDATE settings SET company_name = ? WHERE id = ?");
            $stmt->execute([$settings['company_name'], $settings['id']]);
        }
    } else {
        echo "   ❌ Settings table empty\n";
    }
} catch (Exception $e) {
    echo "   ❌ Settings test failed: " . $e->getMessage() . "\n";
}

// Test 7: Check Activity Log Functionality
echo "\n7. Testing activity log functionality...\n";
try {
    require_once __DIR__ . '/app/models/ActivityLog.php';
    $activityLog = new ActivityLog();
    
    // Test logging (use user ID 1 if exists)
    $stmt = $db->query("SELECT id FROM users LIMIT 1");
    $user = $stmt->fetch();
    if ($user) {
        if ($activityLog->log($user['id'], 'test_action', 'Test log entry')) {
            echo "   ✅ Activity logging working\n";
            
            // Clean up test log
            $db->exec("DELETE FROM activity_logs WHERE action = 'test_action' AND details = 'Test log entry'");
        } else {
            echo "   ❌ Activity logging failed\n";
        }
    } else {
        echo "   ⚠️  No users found to test activity logging\n";
    }
} catch (Exception $e) {
    echo "   ❌ Activity log test failed: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "Test Summary:\n";
echo "- Database connectivity: Working\n";
echo "- Required tables: Check individual results above\n";
echo "- User personal fields: Check individual results above\n";
echo "- Controller files: Check individual results above\n";
echo "- Route configuration: Check individual results above\n";
echo "- Settings functionality: Check result above\n";
echo "- Activity logging: Check result above\n";

echo "\nRecommendations:\n";
echo "1. Run fix_all_issues.php if any tables are missing\n";
echo "2. Check file permissions if controllers are missing\n";
echo "3. Test web interface functionality manually\n";
echo "4. Monitor error logs for any remaining issues\n";

echo "\nTest completed.\n";
?>