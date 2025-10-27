<?php
// Debug system admin creation
session_start();

echo "<h2>Debug System Admin Creation</h2>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h3>POST Data Received:</h3>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    
    try {
        require_once __DIR__ . '/app/config/database.php';
        
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        echo "<h3>Processed Data:</h3>";
        echo "Name: " . htmlspecialchars($name) . "<br>";
        echo "Email: " . htmlspecialchars($email) . "<br>";
        echo "Password: " . (empty($password) ? 'EMPTY' : 'PROVIDED') . "<br>";
        
        if (empty($name) || empty($email) || empty($password)) {
            throw new Exception('Name, email and password are required');
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email format');
        }
        
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        echo "Hashed Password: " . substr($hashedPassword, 0, 20) . "...<br>";
        
        $db = Database::connect();
        echo "Database connected successfully<br>";
        
        // Check if email already exists
        $checkStmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $checkStmt->execute([$email]);
        if ($checkStmt->fetch()) {
            throw new Exception('Email already exists');
        }
        echo "Email check passed<br>";
        
        $stmt = $db->prepare("INSERT INTO users (name, email, password, role, status, created_at) VALUES (?, ?, ?, 'admin', 'active', NOW())");
        $result = $stmt->execute([$name, $email, $hashedPassword]);
        
        if ($result) {
            echo "<div style='color: green; font-weight: bold;'>SUCCESS: Admin created successfully!</div>";
            echo "Inserted ID: " . $db->lastInsertId() . "<br>";
        } else {
            throw new Exception('Failed to execute insert statement');
        }
        
    } catch (Exception $e) {
        echo "<div style='color: red; font-weight: bold;'>ERROR: " . $e->getMessage() . "</div>";
        echo "<pre>";
        print_r($e->getTrace());
        echo "</pre>";
    }
} else {
    echo "<p>No POST data received. Use the form below to test:</p>";
}
?>

<form method="POST" style="border: 1px solid #ccc; padding: 20px; margin: 20px 0;">
    <h3>Test Form</h3>
    <div style="margin: 10px 0;">
        <label>Name:</label><br>
        <input type="text" name="name" value="Test Admin" required style="width: 300px; padding: 5px;">
    </div>
    <div style="margin: 10px 0;">
        <label>Email:</label><br>
        <input type="email" name="email" value="test@admin.com" required style="width: 300px; padding: 5px;">
    </div>
    <div style="margin: 10px 0;">
        <label>Password:</label><br>
        <input type="password" name="password" value="password123" required style="width: 300px; padding: 5px;">
    </div>
    <button type="submit" style="padding: 10px 20px; background: #007cba; color: white; border: none;">Create Admin</button>
</form>

<p><a href="/ergon/system-admin">← Back to System Admin</a></p>