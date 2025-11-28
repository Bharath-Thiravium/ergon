<?php
/**
 * Comprehensive Notification System Diagnostic Tool
 * This script will identify and report all issues with the notification system
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 Notification System Diagnostic Report</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: #28a745; font-weight: bold; }
    .error { color: #dc3545; font-weight: bold; }
    .warning { color: #ffc107; font-weight: bold; }
    .info { color: #17a2b8; font-weight: bold; }
    table { border-collapse: collapse; width: 100%; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
    .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
</style>";

$issues = [];
$recommendations = [];

// 1. Check PHP Extensions
echo "<div class='section'>";
echo "<h2>1. PHP Environment Check</h2>";

$requiredExtensions = ['pdo', 'pdo_mysql', 'json', 'session'];
foreach ($requiredExtensions as $ext) {
    if (extension_loaded($ext)) {
        echo "<span class='success'>✅ {$ext} extension loaded</span><br>";
    } else {
        echo "<span class='error'>❌ {$ext} extension missing</span><br>";
        $issues[] = "Missing PHP extension: {$ext}";
        $recommendations[] = "Install/enable {$ext} extension in PHP configuration";
    }
}

echo "<p><strong>PHP Version:</strong> " . PHP_VERSION . "</p>";
echo "<p><strong>PDO Drivers:</strong> " . implode(', ', PDO::getAvailableDrivers()) . "</p>";
echo "</div>";

// 2. Database Connection Test
echo "<div class='section'>";
echo "<h2>2. Database Connection Test</h2>";

try {
    require_once __DIR__ . '/app/config/database.php';
    $db = Database::connect();
    echo "<span class='success'>✅ Database connection successful</span><br>";
    
    // Test basic query
    $stmt = $db->query("SELECT 1 as test");
    $result = $stmt->fetch();
    if ($result['test'] == 1) {
        echo "<span class='success'>✅ Database query execution successful</span><br>";
    }
    
} catch (Exception $e) {
    echo "<span class='error'>❌ Database connection failed: " . $e->getMessage() . "</span><br>";
    $issues[] = "Database connection failure: " . $e->getMessage();
    $recommendations[] = "Check database credentials and ensure MySQL service is running";
}
echo "</div>";

// 3. Notifications Table Structure Check
echo "<div class='section'>";
echo "<h2>3. Notifications Table Structure</h2>";

try {
    if (isset($db)) {
        // Check if table exists
        $stmt = $db->query("SHOW TABLES LIKE 'notifications'");
        if ($stmt->rowCount() > 0) {
            echo "<span class='success'>✅ Notifications table exists</span><br>";
            
            // Check table structure
            $stmt = $db->query("DESCRIBE notifications");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<h3>Current Table Structure:</h3>";
            echo "<table>";
            echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
            
            $requiredFields = ['id', 'sender_id', 'receiver_id', 'title', 'message', 'is_read', 'created_at'];
            $existingFields = [];
            
            foreach ($columns as $col) {
                echo "<tr>";
                echo "<td>{$col['Field']}</td>";
                echo "<td>{$col['Type']}</td>";
                echo "<td>{$col['Null']}</td>";
                echo "<td>{$col['Key']}</td>";
                echo "<td>{$col['Default']}</td>";
                echo "</tr>";
                $existingFields[] = $col['Field'];
            }
            echo "</table>";
            
            // Check for required fields
            foreach ($requiredFields as $field) {
                if (in_array($field, $existingFields)) {
                    echo "<span class='success'>✅ Required field '{$field}' exists</span><br>";
                } else {
                    echo "<span class='error'>❌ Required field '{$field}' missing</span><br>";
                    $issues[] = "Missing required field: {$field}";
                    $recommendations[] = "Add missing field '{$field}' to notifications table";
                }
            }
            
        } else {
            echo "<span class='error'>❌ Notifications table does not exist</span><br>";
            $issues[] = "Notifications table missing";
            $recommendations[] = "Create notifications table using the provided schema";
        }
    }
} catch (Exception $e) {
    echo "<span class='error'>❌ Error checking table structure: " . $e->getMessage() . "</span><br>";
}
echo "</div>";

// 4. Check Notification Data
echo "<div class='section'>";
echo "<h2>4. Notification Data Analysis</h2>";

try {
    if (isset($db)) {
        // Count total notifications
        $stmt = $db->query("SELECT COUNT(*) as total FROM notifications");
        $total = $stmt->fetch()['total'];
        echo "<p><strong>Total Notifications:</strong> {$total}</p>";
        
        if ($total > 0) {
            // Count by status
            $stmt = $db->query("SELECT is_read, COUNT(*) as count FROM notifications GROUP BY is_read");
            $statusCounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<h3>Notification Status Distribution:</h3>";
            foreach ($statusCounts as $status) {
                $readStatus = $status['is_read'] ? 'Read' : 'Unread';
                echo "<p>{$readStatus}: {$status['count']}</p>";
            }
            
            // Check for recent notifications
            $stmt = $db->query("SELECT COUNT(*) as recent FROM notifications WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
            $recent = $stmt->fetch()['recent'];
            echo "<p><strong>Notifications in last 7 days:</strong> {$recent}</p>";
            
            if ($recent == 0) {
                echo "<span class='warning'>⚠️ No recent notifications found - system may not be creating notifications</span><br>";
                $issues[] = "No recent notifications created";
                $recommendations[] = "Check notification creation logic in application modules";
            }
            
        } else {
            echo "<span class='warning'>⚠️ No notifications found in database</span><br>";
            $issues[] = "No notifications in database";
            $recommendations[] = "Test notification creation functionality";
        }
    }
} catch (Exception $e) {
    echo "<span class='error'>❌ Error analyzing notification data: " . $e->getMessage() . "</span><br>";
}
echo "</div>";

// 5. Check Users and Roles
echo "<div class='section'>";
echo "<h2>5. Users and Roles Check</h2>";

try {
    if (isset($db)) {
        $stmt = $db->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
        $roleCounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>User Role Distribution:</h3>";
        $hasOwners = false;
        $hasAdmins = false;
        
        foreach ($roleCounts as $role) {
            echo "<p>{$role['role']}: {$role['count']} users</p>";
            if ($role['role'] === 'owner') $hasOwners = true;
            if ($role['role'] === 'admin') $hasAdmins = true;
        }
        
        if (!$hasOwners) {
            echo "<span class='error'>❌ No owners found - approval notifications won't work</span><br>";
            $issues[] = "No owner users found";
            $recommendations[] = "Create at least one user with 'owner' role for approval notifications";
        }
        
        if (!$hasAdmins) {
            echo "<span class='warning'>⚠️ No admins found - some notifications may not work</span><br>";
            $issues[] = "No admin users found";
            $recommendations[] = "Consider creating admin users for better notification coverage";
        }
    }
} catch (Exception $e) {
    echo "<span class='error'>❌ Error checking users: " . $e->getMessage() . "</span><br>";
}
echo "</div>";

// 6. Check API Endpoints
echo "<div class='section'>";
echo "<h2>6. API Endpoints Check</h2>";

$apiFiles = [
    '/api/notifications.php',
    '/api/notifications_v2.php', 
    '/api/fetch_notifications.php'
];

foreach ($apiFiles as $api) {
    $fullPath = __DIR__ . $api;
    if (file_exists($fullPath)) {
        echo "<span class='success'>✅ {$api} exists</span><br>";
        
        // Check file permissions
        if (is_readable($fullPath)) {
            echo "<span class='success'>✅ {$api} is readable</span><br>";
        } else {
            echo "<span class='error'>❌ {$api} is not readable</span><br>";
            $issues[] = "API file not readable: {$api}";
        }
    } else {
        echo "<span class='error'>❌ {$api} missing</span><br>";
        $issues[] = "Missing API file: {$api}";
        $recommendations[] = "Restore missing API file: {$api}";
    }
}
echo "</div>";

// 7. Check Model and Helper Files
echo "<div class='section'>";
echo "<h2>7. Core Files Check</h2>";

$coreFiles = [
    '/app/models/Notification.php',
    '/app/controllers/NotificationController.php',
    '/app/helpers/NotificationHelper.php',
    '/app/services/NotificationService.php'
];

foreach ($coreFiles as $file) {
    $fullPath = __DIR__ . $file;
    if (file_exists($fullPath)) {
        echo "<span class='success'>✅ {$file} exists</span><br>";
    } else {
        echo "<span class='error'>❌ {$file} missing</span><br>";
        $issues[] = "Missing core file: {$file}";
        $recommendations[] = "Restore missing core file: {$file}";
    }
}
echo "</div>";

// 8. Test Notification Creation
echo "<div class='section'>";
echo "<h2>8. Notification Creation Test</h2>";

try {
    if (isset($db)) {
        require_once __DIR__ . '/app/models/Notification.php';
        
        $notification = new Notification();
        
        // Try to create a test notification
        $testData = [
            'sender_id' => 1,
            'receiver_id' => 1,
            'title' => 'System Test',
            'message' => 'This is a test notification created by the diagnostic tool',
            'category' => 'system'
        ];
        
        $result = $notification->create($testData);
        
        if ($result) {
            echo "<span class='success'>✅ Test notification created successfully</span><br>";
            
            // Clean up test notification
            $stmt = $db->prepare("DELETE FROM notifications WHERE title = 'System Test' AND message LIKE '%diagnostic tool%'");
            $stmt->execute();
            echo "<span class='info'>ℹ️ Test notification cleaned up</span><br>";
        } else {
            echo "<span class='error'>❌ Failed to create test notification</span><br>";
            $issues[] = "Notification creation failed";
            $recommendations[] = "Check Notification model create() method";
        }
    }
} catch (Exception $e) {
    echo "<span class='error'>❌ Error testing notification creation: " . $e->getMessage() . "</span><br>";
    $issues[] = "Notification creation test failed: " . $e->getMessage();
}
echo "</div>";

// 9. Summary Report
echo "<div class='section'>";
echo "<h2>9. Summary Report</h2>";

if (empty($issues)) {
    echo "<span class='success'>🎉 No critical issues found! Notification system appears to be working correctly.</span>";
} else {
    echo "<h3 class='error'>❌ Issues Found (" . count($issues) . "):</h3>";
    echo "<ol>";
    foreach ($issues as $issue) {
        echo "<li class='error'>{$issue}</li>";
    }
    echo "</ol>";
    
    echo "<h3 class='info'>💡 Recommendations:</h3>";
    echo "<ol>";
    foreach ($recommendations as $rec) {
        echo "<li class='info'>{$rec}</li>";
    }
    echo "</ol>";
}

echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li>Address all critical issues listed above</li>";
echo "<li>Run this diagnostic again after fixes</li>";
echo "<li>Test notification creation from each module (leaves, expenses, advances)</li>";
echo "<li>Verify notifications appear for correct users</li>";
echo "<li>Test notification marking as read functionality</li>";
echo "</ol>";

echo "</div>";

echo "<hr>";
echo "<p><em>Diagnostic completed at: " . date('Y-m-d H:i:s') . "</em></p>";
?>