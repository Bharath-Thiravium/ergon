<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/app/helpers/Security.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Find ilayaraja user
    $stmt = $conn->prepare("SELECT id FROM users WHERE name LIKE '%ilayaraja%' LIMIT 1");
    $stmt->execute();
    $user = $stmt->fetch();
    
    if (!$user) {
        // Create ilayaraja user
        $tempPassword = 'IT' . rand(1000, 9999);
        $hashedPassword = Security::hashPassword($tempPassword);
        
        $stmt = $conn->prepare("
            INSERT INTO users (name, email, password, role, department, temp_password, is_first_login, password_reset_required) 
            VALUES (?, ?, ?, ?, ?, ?, TRUE, TRUE)
        ");
        
        $stmt->execute([
            'ilayaraja',
            'ilayaraja@company.com',
            $hashedPassword,
            'user',
            'IT Department',
            $tempPassword
        ]);
        
        $userId = $conn->lastInsertId();
        echo "Created user 'ilayaraja' (ID: $userId) with temp password: $tempPassword<br>";
    } else {
        $userId = $user['id'];
        echo "Found existing user 'ilayaraja' (ID: $userId)<br>";
    }
    
    // Create sample activity logs for the past 3 days
    for ($i = 0; $i < 3; $i++) {
        $date = date('Y-m-d H:i:s', strtotime("-$i days"));
        
        // System pings
        for ($j = 0; $j < 10; $j++) {
            $stmt = $conn->prepare("
                INSERT INTO activity_logs (user_id, activity_type, description, is_active, created_at) 
                VALUES (?, 'system_ping', 'System activity ping', 1, ?)
            ");
            $stmt->execute([$userId, $date]);
        }
        
        // Break sessions
        $stmt = $conn->prepare("
            INSERT INTO activity_logs (user_id, activity_type, description, is_active, created_at) 
            VALUES (?, 'break_start', 'Break session started', 1, ?)
        ");
        $stmt->execute([$userId, $date]);
    }
    
    echo "Sample activity logs created for ilayaraja<br>";
    echo "<a href='/ergon/reports/activity'>View Activity Reports</a>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>