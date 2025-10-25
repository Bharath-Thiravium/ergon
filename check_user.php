<?php
require_once __DIR__ . '/config/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare("SELECT id, email, password, role FROM users WHERE email = ?");
    $stmt->execute(['info@athenas.co.in']);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "<h2>User Found:</h2>";
        echo "<p><strong>ID:</strong> " . $user['id'] . "</p>";
        echo "<p><strong>Email:</strong> " . $user['email'] . "</p>";
        echo "<p><strong>Role:</strong> " . $user['role'] . "</p>";
        echo "<p><strong>Password Hash:</strong> " . substr($user['password'], 0, 20) . "...</p>";
        
        // Check if it's a bcrypt hash
        if (password_get_info($user['password'])['algo']) {
            echo "<p><strong>Hash Type:</strong> bcrypt (secure)</p>";
            echo "<p><strong>Suggested Password:</strong> Try 'admin123' or 'password'</p>";
        } else {
            echo "<p><strong>Hash Type:</strong> Plain text or MD5</p>";
            echo "<p><strong>Password:</strong> " . $user['password'] . "</p>";
        }
    } else {
        echo "<h2>User Not Found</h2>";
        echo "<p>No user with email 'info@athenas.co.in' exists in the database.</p>";
        
        // Show all users
        $stmt = $conn->prepare("SELECT id, email, role FROM users LIMIT 5");
        $stmt->execute();
        $users = $stmt->fetchAll();
        
        echo "<h3>Available Users:</h3>";
        foreach ($users as $u) {
            echo "<p>ID: {$u['id']}, Email: {$u['email']}, Role: {$u['role']}</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<h2>Database Error:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>