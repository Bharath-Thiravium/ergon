<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    // Add more employees
    $employees = [
        ['Ravi Kumar', 'ravi@athenas.co.in', 'user'],
        ['Priya Sharma', 'priya@athenas.co.in', 'user'],
        ['Amit Singh', 'amit@athenas.co.in', 'user']
    ];
    
    echo "<h3>Adding Employees</h3>";
    
    foreach ($employees as $emp) {
        $stmt = $db->prepare("INSERT INTO users (name, email, role, password, created_at) VALUES (?, ?, ?, ?, NOW())");
        $result = $stmt->execute([$emp[0], $emp[1], $emp[2], password_hash('password123', PASSWORD_DEFAULT)]);
        
        if ($result) {
            echo "✅ Added: {$emp[0]}<br>";
        } else {
            echo "❌ Failed to add: {$emp[0]}<br>";
        }
    }
    
    echo "<br><a href='/ergon/attendance'>Go to Attendance</a>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>