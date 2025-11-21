<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    echo "<h2>Users Table Structure:</h2>";
    $stmt = $db->query("SHOW COLUMNS FROM users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($columns);
    echo "</pre>";
    
    echo "<h2>Users Data:</h2>";
    $stmt = $db->query("SELECT * FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($users);
    echo "</pre>";
    
    echo "<h2>Attendance Table Structure:</h2>";
    $stmt = $db->query("SHOW COLUMNS FROM attendance");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($columns);
    echo "</pre>";
    
    echo "<h2>Attendance Data:</h2>";
    $stmt = $db->query("SELECT * FROM attendance ORDER BY created_at DESC LIMIT 10");
    $attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($attendance);
    echo "</pre>";
    
    echo "<h2>Query Test - What UnifiedAttendanceController Returns:</h2>";
    $selectedDate = date('Y-m-d');
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
            END as status
        FROM users u
        LEFT JOIN attendance a ON u.id = a.user_id AND DATE(a.check_in) = ?
        WHERE u.role IN ('user', 'admin')
        ORDER BY u.role DESC, u.name
    ");
    $stmt->execute([$selectedDate]);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($result);
    echo "</pre>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>