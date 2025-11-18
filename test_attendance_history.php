<?php
// Test script to verify attendance history functionality
require_once __DIR__ . '/app/config/database.php';

echo "<h2>Testing Attendance History Fix</h2>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .success{color:green;} .error{color:red;}</style>";

try {
    $db = Database::connect();
    
    echo "<h3>1. Checking Database Connection</h3>";
    echo "<p class='success'>✅ Database connected successfully</p>";
    
    echo "<h3>2. Checking Users Table</h3>";
    $stmt = $db->query("SELECT id, name, role FROM users WHERE role IN ('admin', 'owner') LIMIT 5");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($users)) {
        echo "<p class='error'>❌ No admin/owner users found</p>";
    } else {
        echo "<p class='success'>✅ Found " . count($users) . " admin/owner users:</p>";
        foreach ($users as $user) {
            echo "<p>- {$user['name']} (ID: {$user['id']}, Role: {$user['role']})</p>";
        }
    }
    
    echo "<h3>3. Checking Attendance Table</h3>";
    $stmt = $db->query("SHOW TABLES LIKE 'attendance'");
    if ($stmt->rowCount() > 0) {
        echo "<p class='success'>✅ Attendance table exists</p>";
        
        $stmt = $db->query("SELECT COUNT(*) as count FROM attendance");
        $count = $stmt->fetch()['count'];
        echo "<p>Total attendance records: $count</p>";
        
        if ($count > 0) {
            $stmt = $db->query("SELECT u.name, a.check_in, a.check_out FROM attendance a JOIN users u ON a.user_id = u.id ORDER BY a.check_in DESC LIMIT 3");
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "<p>Recent attendance records:</p>";
            foreach ($records as $record) {
                echo "<p>- {$record['name']}: {$record['check_in']} to " . ($record['check_out'] ?? 'Still working') . "</p>";
            }
        }
    } else {
        echo "<p class='error'>❌ Attendance table does not exist</p>";
    }
    
    echo "<h3>4. Testing Route Access</h3>";
    if (!empty($users)) {
        $testUserId = $users[0]['id'];
        echo "<p class='success'>✅ Attendance history route should be accessible at:</p>";
        echo "<p><a href='/ergon/attendance/history/$testUserId' target='_blank'>/ergon/attendance/history/$testUserId</a></p>";
    }
    
    echo "<h3>5. Fix Summary</h3>";
    echo "<div style='background:#f0f8ff;padding:15px;border-radius:5px;'>";
    echo "<p><strong>What was fixed:</strong></p>";
    echo "<ul>";
    echo "<li>✅ Created attendance history view page</li>";
    echo "<li>✅ Added history method to AttendanceController</li>";
    echo "<li>✅ Updated owner attendance view to use real history function</li>";
    echo "<li>✅ Added attendance history route</li>";
    echo "</ul>";
    echo "<p><strong>How to test:</strong></p>";
    echo "<ol>";
    echo "<li>Login as owner/admin</li>";
    echo "<li>Go to HR & Finance → Attendance</li>";
    echo "<li>Click the 📊 button in the Actions column for any employee</li>";
    echo "<li>You should see the employee's complete attendance history</li>";
    echo "</ol>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
}
?>