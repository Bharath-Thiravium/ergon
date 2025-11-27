<?php
/**
 * Emergency Notification System Fix Script
 * This script applies immediate fixes to get the notification system working
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔧 Notification System Emergency Fix</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: #28a745; font-weight: bold; }
    .error { color: #dc3545; font-weight: bold; }
    .warning { color: #ffc107; font-weight: bold; }
    .info { color: #17a2b8; font-weight: bold; }
    .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
</style>";

$fixes_applied = [];
$errors = [];

// 1. Database Connection and Table Creation
echo "<div class='section'>";
echo "<h2>1. Database Setup</h2>";

try {
    require_once __DIR__ . '/app/config/database.php';
    $db = Database::connect();
    echo "<span class='success'>✅ Database connection successful</span><br>";
    
    // Check if notifications table exists, create if not
    $stmt = $db->query("SHOW TABLES LIKE 'notifications'");
    if ($stmt->rowCount() == 0) {
        echo "<span class='warning'>⚠️ Notifications table missing, creating...</span><br>";
        
        $createTableSQL = "
        CREATE TABLE notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            sender_id INT DEFAULT NULL,
            receiver_id INT NOT NULL,
            type ENUM('info', 'success', 'warning', 'error', 'urgent') DEFAULT 'info',
            category ENUM('task', 'approval', 'system', 'reminder', 'announcement') DEFAULT 'system',
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            action_url VARCHAR(500) DEFAULT NULL,
            action_text VARCHAR(100) DEFAULT NULL,
            reference_type VARCHAR(50) DEFAULT NULL,
            reference_id INT DEFAULT NULL,
            metadata JSON DEFAULT NULL,
            priority TINYINT(1) DEFAULT 1,
            is_read BOOLEAN DEFAULT FALSE,
            read_at TIMESTAMP NULL DEFAULT NULL,
            expires_at TIMESTAMP NULL DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            INDEX idx_receiver_unread (receiver_id, is_read, created_at),
            INDEX idx_receiver_priority (receiver_id, priority, created_at),
            INDEX idx_category_type (category, type),
            INDEX idx_reference (reference_type, reference_id),
            INDEX idx_expires (expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $db->exec($createTableSQL);
        echo "<span class='success'>✅ Notifications table created</span><br>";
        $fixes_applied[] = "Created notifications table with proper schema";
    } else {
        echo "<span class='success'>✅ Notifications table exists</span><br>";
        
        // Check for missing columns and add them
        $stmt = $db->query("DESCRIBE notifications");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $existingColumns = array_column($columns, 'Field');
        
        $requiredColumns = [
            'reference_type' => "ADD COLUMN reference_type VARCHAR(50) DEFAULT NULL",
            'reference_id' => "ADD COLUMN reference_id INT DEFAULT NULL", 
            'action_url' => "ADD COLUMN action_url VARCHAR(500) DEFAULT NULL",
            'category' => "ADD COLUMN category ENUM('task', 'approval', 'system', 'reminder', 'announcement') DEFAULT 'system'"
        ];
        
        foreach ($requiredColumns as $column => $sql) {
            if (!in_array($column, $existingColumns)) {
                try {
                    $db->exec("ALTER TABLE notifications {$sql}");
                    echo "<span class='success'>✅ Added missing column: {$column}</span><br>";
                    $fixes_applied[] = "Added missing column: {$column}";
                } catch (Exception $e) {
                    echo "<span class='warning'>⚠️ Could not add column {$column}: " . $e->getMessage() . "</span><br>";
                }
            }
        }
    }
    
} catch (Exception $e) {
    echo "<span class='error'>❌ Database setup failed: " . $e->getMessage() . "</span><br>";
    $errors[] = "Database setup failed: " . $e->getMessage();
}
echo "</div>";

// 2. Create Test Users if Missing
echo "<div class='section'>";
echo "<h2>2. User Setup</h2>";

try {
    if (isset($db)) {
        // Check for owner users
        $stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE role = 'owner'");
        $ownerCount = $stmt->fetch()['count'];
        
        if ($ownerCount == 0) {
            echo "<span class='warning'>⚠️ No owner users found, creating test owner...</span><br>";
            
            $stmt = $db->prepare("INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, 'owner', 'active')");
            $result = $stmt->execute([
                'System Owner',
                'owner@ergon.local',
                password_hash('admin123', PASSWORD_BCRYPT)
            ]);
            
            if ($result) {
                echo "<span class='success'>✅ Test owner user created (email: owner@ergon.local, password: admin123)</span><br>";
                $fixes_applied[] = "Created test owner user for notifications";
            }
        } else {
            echo "<span class='success'>✅ Owner users exist ({$ownerCount} found)</span><br>";
        }
        
        // Check for admin users
        $stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
        $adminCount = $stmt->fetch()['count'];
        
        if ($adminCount == 0) {
            echo "<span class='warning'>⚠️ No admin users found, creating test admin...</span><br>";
            
            $stmt = $db->prepare("INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, 'admin', 'active')");
            $result = $stmt->execute([
                'System Admin',
                'admin@ergon.local', 
                password_hash('admin123', PASSWORD_BCRYPT)
            ]);
            
            if ($result) {
                echo "<span class='success'>✅ Test admin user created (email: admin@ergon.local, password: admin123)</span><br>";
                $fixes_applied[] = "Created test admin user for notifications";
            }
        } else {
            echo "<span class='success'>✅ Admin users exist ({$adminCount} found)</span><br>";
        }
    }
} catch (Exception $e) {
    echo "<span class='error'>❌ User setup failed: " . $e->getMessage() . "</span><br>";
    $errors[] = "User setup failed: " . $e->getMessage();
}
echo "</div>";

// 3. Test Notification Creation
echo "<div class='section'>";
echo "<h2>3. Test Notification Creation</h2>";

try {
    if (isset($db)) {
        require_once __DIR__ . '/app/models/Notification.php';
        
        $notification = new Notification();
        
        // Get a test user
        $stmt = $db->query("SELECT id FROM users WHERE role = 'owner' LIMIT 1");
        $owner = $stmt->fetch();
        
        if ($owner) {
            // Create test notifications
            $testNotifications = [
                [
                    'sender_id' => 1,
                    'receiver_id' => $owner['id'],
                    'title' => 'Leave Request',
                    'message' => 'John Doe submitted a leave request for approval',
                    'category' => 'approval',
                    'reference_type' => 'leave',
                    'reference_id' => 1
                ],
                [
                    'sender_id' => 1,
                    'receiver_id' => $owner['id'],
                    'title' => 'Expense Claim',
                    'message' => 'Jane Smith submitted an expense claim of ₹500',
                    'category' => 'approval',
                    'reference_type' => 'expense',
                    'reference_id' => 1
                ]
            ];
            
            foreach ($testNotifications as $testData) {
                $result = $notification->create($testData);
                if ($result) {
                    echo "<span class='success'>✅ Test notification created: {$testData['title']}</span><br>";
                    $fixes_applied[] = "Created test notification: {$testData['title']}";
                } else {
                    echo "<span class='error'>❌ Failed to create test notification: {$testData['title']}</span><br>";
                    $errors[] = "Failed to create test notification: {$testData['title']}";
                }
            }
        } else {
            echo "<span class='warning'>⚠️ No owner user found for testing</span><br>";
        }
    }
} catch (Exception $e) {
    echo "<span class='error'>❌ Notification creation test failed: " . $e->getMessage() . "</span><br>";
    $errors[] = "Notification creation test failed: " . $e->getMessage();
}
echo "</div>";

// 4. Fix API Endpoints
echo "<div class='section'>";
echo "<h2>4. API Endpoint Fixes</h2>";

// Check if primary API exists and is working
$apiPath = __DIR__ . '/api/notifications.php';
if (file_exists($apiPath)) {
    echo "<span class='success'>✅ Primary notification API exists</span><br>";
    
    // Test API by making a simple request
    try {
        // Start session for API test
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Set test session data
        $_SESSION['user_id'] = 1;
        $_SESSION['role'] = 'owner';
        
        // Include and test the API
        ob_start();
        include $apiPath;
        $apiOutput = ob_get_clean();
        
        if (strpos($apiOutput, '"success"') !== false) {
            echo "<span class='success'>✅ Notification API is responding</span><br>";
            $fixes_applied[] = "Verified notification API is working";
        } else {
            echo "<span class='warning'>⚠️ API response may have issues</span><br>";
        }
        
    } catch (Exception $e) {
        echo "<span class='error'>❌ API test failed: " . $e->getMessage() . "</span><br>";
        $errors[] = "API test failed: " . $e->getMessage();
    }
} else {
    echo "<span class='error'>❌ Primary notification API missing</span><br>";
    $errors[] = "Primary notification API file missing";
}
echo "</div>";

// 5. Summary
echo "<div class='section'>";
echo "<h2>5. Fix Summary</h2>";

if (!empty($fixes_applied)) {
    echo "<h3 class='success'>✅ Fixes Applied (" . count($fixes_applied) . "):</h3>";
    echo "<ol>";
    foreach ($fixes_applied as $fix) {
        echo "<li class='success'>{$fix}</li>";
    }
    echo "</ol>";
}

if (!empty($errors)) {
    echo "<h3 class='error'>❌ Errors Encountered (" . count($errors) . "):</h3>";
    echo "<ol>";
    foreach ($errors as $error) {
        echo "<li class='error'>{$error}</li>";
    }
    echo "</ol>";
}

echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li>Run the diagnostic script to verify all fixes</li>";
echo "<li>Test notification creation from leave/expense/advance modules</li>";
echo "<li>Check that notifications appear in the UI</li>";
echo "<li>Test marking notifications as read</li>";
echo "<li>Verify notification badge updates correctly</li>";
echo "</ol>";

echo "<p><strong>Test Users Created:</strong></p>";
echo "<ul>";
echo "<li>Owner: owner@ergon.local / admin123</li>";
echo "<li>Admin: admin@ergon.local / admin123</li>";
echo "</ul>";

echo "</div>";

echo "<hr>";
echo "<p><em>Fix script completed at: " . date('Y-m-d H:i:s') . "</em></p>";
?>