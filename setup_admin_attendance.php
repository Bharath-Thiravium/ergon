<?php
// Setup script to ensure admin attendance functionality works properly
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    echo "<h2>Admin Attendance Setup</h2>";
    
    // Ensure attendance table exists with proper structure
    $db->exec("CREATE TABLE IF NOT EXISTS attendance (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        check_in DATETIME NOT NULL,
        check_out DATETIME NULL,
        latitude DECIMAL(10, 8) NULL,
        longitude DECIMAL(11, 8) NULL,
        location_name VARCHAR(255) DEFAULT 'Office',
        status VARCHAR(20) DEFAULT 'present',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_user_id (user_id),
        INDEX idx_check_in_date (check_in),
        INDEX idx_created_at_date (created_at)
    )");
    
    echo "<p>✅ Attendance table structure verified</p>";
    
    // Check if there are any admin users
    $stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin' AND status = 'active'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<p>Found {$result['count']} active admin users</p>";
    
    // List all admin users
    $stmt = $db->query("SELECT id, name, email, role FROM users WHERE role IN ('admin', 'owner') AND status = 'active'");
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($admins)) {
        echo "<h3>Admin Users:</h3>";
        foreach ($admins as $admin) {
            echo "<p>ID: {$admin['id']}, Name: {$admin['name']}, Email: {$admin['email']}, Role: {$admin['role']}</p>";
            
            // Check if admin has any attendance records
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM attendance WHERE user_id = ?");
            $stmt->execute([$admin['id']]);
            $attendanceCount = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo "<p>  - Attendance records: {$attendanceCount['count']}</p>";
        }
    }
    
    // Check today's attendance for all users
    $today = date('Y-m-d');
    $stmt = $db->prepare("
        SELECT u.name, u.role, a.check_in, a.check_out, a.status 
        FROM users u 
        LEFT JOIN attendance a ON u.id = a.user_id AND DATE(a.check_in) = ?
        WHERE u.status = 'active' AND u.role IN ('admin', 'owner', 'user')
        ORDER BY u.role DESC, u.name
    ");
    $stmt->execute([$today]);
    $todayAttendance = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Today's Attendance ({$today}):</h3>";
    foreach ($todayAttendance as $record) {
        $status = $record['check_in'] ? 'Present' : 'Absent';
        echo "<p>{$record['name']} ({$record['role']}): {$status}";
        if ($record['check_in']) {
            echo " - In: {$record['check_in']}";
            if ($record['check_out']) {
                echo ", Out: {$record['check_out']}";
            }
        }
        echo "</p>";
    }
    
    echo "<p><strong>Setup completed successfully!</strong></p>";
    echo "<p><a href='/ergon/attendance'>Go to Attendance Page</a></p>";
    echo "<p><a href='/ergon/test_admin_attendance.php'>Run Admin Attendance Test</a></p>";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?>