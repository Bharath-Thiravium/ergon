<?php
// Test script to verify admin attendance functionality
session_start();

// Simulate admin session for testing
$_SESSION['user_id'] = 1; // Assuming admin user ID is 1
$_SESSION['role'] = 'admin';
$_SESSION['user_name'] = 'Test Admin';

require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/helpers/TimezoneHelper.php';

try {
    $db = Database::connect();
    
    echo "<h2>Admin Attendance Test</h2>";
    
    // Check if admin user exists
    $stmt = $db->prepare("SELECT id, name, role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        echo "<p>✅ Admin user found: {$admin['name']} (Role: {$admin['role']})</p>";
    } else {
        echo "<p>❌ Admin user not found. Creating test admin...</p>";
        
        // Create test admin user
        $stmt = $db->prepare("INSERT INTO users (name, email, role, status, password) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(['Test Admin', 'admin@test.com', 'admin', 'active', password_hash('admin123', PASSWORD_DEFAULT)]);
        $_SESSION['user_id'] = $db->lastInsertId();
        echo "<p>✅ Test admin created with ID: {$_SESSION['user_id']}</p>";
    }
    
    // Check current attendance
    $currentDate = TimezoneHelper::getCurrentDate();
    $stmt = $db->prepare("SELECT * FROM attendance WHERE user_id = ? AND DATE(check_in) = ?");
    $stmt->execute([$_SESSION['user_id'], $currentDate]);
    $attendance = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($attendance) {
        echo "<p>✅ Admin attendance record found for today:</p>";
        echo "<ul>";
        echo "<li>Check In: " . ($attendance['check_in'] ?? 'Not set') . "</li>";
        echo "<li>Check Out: " . ($attendance['check_out'] ?? 'Not set') . "</li>";
        echo "<li>Status: " . ($attendance['status'] ?? 'Unknown') . "</li>";
        echo "</ul>";
    } else {
        echo "<p>⚠️ No attendance record found for admin today. Creating test record...</p>";
        
        // Create test attendance record
        $currentTime = TimezoneHelper::nowIst();
        $stmt = $db->prepare("INSERT INTO attendance (user_id, check_in, status, location_name, created_at) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $currentTime, 'present', 'Test Location', $currentTime]);
        
        echo "<p>✅ Test attendance record created</p>";
    }
    
    // Test the controller query
    echo "<h3>Testing Controller Query</h3>";
    
    $userId = $_SESSION['user_id'];
    $selectedDate = $currentDate;
    $roleFilter = "AND (u.role IN ('user') OR u.id = $userId)";
    
    $stmt = $db->prepare("
        SELECT 
            u.id as user_id,
            u.name,
            u.email,
            u.role,
            a.id as attendance_id,
            a.check_in,
            a.check_out,
            CASE 
                WHEN a.check_in IS NOT NULL THEN 'Present'
                ELSE 'Absent'
            END as status,
            COALESCE(TIME_FORMAT(a.check_in, '%H:%i'), '00:00') as check_in_time,
            COALESCE(TIME_FORMAT(a.check_out, '%H:%i'), '00:00') as check_out_time,
            CASE 
                WHEN a.check_in IS NOT NULL AND a.check_out IS NOT NULL THEN 
                    CONCAT(TIMESTAMPDIFF(HOUR, a.check_in, a.check_out), 'h ', 
                           TIMESTAMPDIFF(MINUTE, a.check_in, a.check_out) % 60, 'm')
                ELSE '0h 0m'
            END as working_hours
        FROM users u
        LEFT JOIN attendance a ON u.id = a.user_id AND (DATE(a.check_in) = ? OR DATE(a.created_at) = ?)
        WHERE u.status = 'active' $roleFilter
        ORDER BY u.role DESC, u.name
    ");
    
    $stmt->execute([$selectedDate, $selectedDate]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Query returned " . count($records) . " records:</p>";
    
    foreach ($records as $record) {
        echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 5px;'>";
        echo "<strong>{$record['name']}</strong> ({$record['role']})<br>";
        echo "Status: {$record['status']}<br>";
        echo "Check In: " . ($record['check_in'] ?? 'Not set') . "<br>";
        echo "Check Out: " . ($record['check_out'] ?? 'Not set') . "<br>";
        echo "Working Hours: {$record['working_hours']}<br>";
        echo "</div>";
    }
    
    echo "<p><a href='/ergon/attendance'>Go to Attendance Page</a></p>";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?>