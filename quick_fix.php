<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    // Check users table
    $stmt = $db->query("SELECT * FROM users ORDER BY role, name");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Current Users (" . count($users) . "):</h3>";
    
    if (empty($users)) {
        echo "No users found. Creating sample users...<br>";
        
        $sampleUsers = [
            ['John Doe', 'john@ergon.com', 'user'],
            ['Jane Smith', 'jane@ergon.com', 'user'],
            ['Admin User', 'admin@ergon.com', 'admin']
        ];
        
        foreach ($sampleUsers as $user) {
            $stmt = $db->prepare("INSERT INTO users (name, email, role, password, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$user[0], $user[1], $user[2], password_hash('password123', PASSWORD_DEFAULT)]);
            echo "Created: {$user[0]} ({$user[2]})<br>";
        }
        
        echo "<br>✅ Users created! <a href='/ergon/attendance'>Go to Attendance</a>";
    } else {
        echo "<table border='1'><tr><th>Name</th><th>Email</th><th>Role</th></tr>";
        foreach ($users as $user) {
            echo "<tr><td>{$user['name']}</td><td>{$user['email']}</td><td>{$user['role']}</td></tr>";
        }
        echo "</table>";
        echo "<br><a href='/ergon/attendance'>Go to Attendance</a>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>