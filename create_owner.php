<?php
/**
 * Create Fresh Owner Account Script
 * Run this after database reset
 */

require_once 'config/database.php';

echo "<h2>🧭 ERGON - Create Owner Account</h2>";

if ($_POST) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($name) || empty($email) || empty($password)) {
        echo "<p style='color:red'>❌ All fields are required!</p>";
    } else {
        try {
            $database = new Database();
            $conn = $database->getConnection();
            
            // Check if any users exist
            $stmt = $conn->query("SELECT COUNT(*) FROM users");
            $userCount = $stmt->fetchColumn();
            
            if ($userCount > 0) {
                echo "<p style='color:red'>❌ Users already exist! Reset database first.</p>";
            } else {
                // Create owner account
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $employeeId = 'EMP001';
                
                $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, status, employee_id, created_at) VALUES (?, ?, ?, 'owner', 'active', ?, NOW())");
                $stmt->execute([$name, $email, $hashedPassword, $employeeId]);
                
                echo "<div style='background:#d4edda;padding:15px;border-radius:5px;margin:10px 0;'>";
                echo "<h3>✅ Owner Account Created Successfully!</h3>";
                echo "<p><strong>Name:</strong> $name</p>";
                echo "<p><strong>Email:</strong> $email</p>";
                echo "<p><strong>Employee ID:</strong> $employeeId</p>";
                echo "<p><strong>Role:</strong> Owner</p>";
                echo "</div>";
                
                echo "<p><a href='/ergon/login' style='background:#007bff;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>🚀 Go to Login</a></p>";
            }
        } catch (Exception $e) {
            echo "<p style='color:red'>❌ Error: " . $e->getMessage() . "</p>";
        }
    }
} else {
?>

<form method="POST" style="max-width:400px;margin:20px 0;">
    <div style="margin-bottom:15px;">
        <label style="display:block;margin-bottom:5px;font-weight:bold;">Owner Name:</label>
        <input type="text" name="name" required style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;">
    </div>
    
    <div style="margin-bottom:15px;">
        <label style="display:block;margin-bottom:5px;font-weight:bold;">Email:</label>
        <input type="email" name="email" required style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;">
    </div>
    
    <div style="margin-bottom:15px;">
        <label style="display:block;margin-bottom:5px;font-weight:bold;">Password:</label>
        <input type="password" name="password" required minlength="6" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;">
        <small style="color:#666;">Minimum 6 characters</small>
    </div>
    
    <button type="submit" style="background:#28a745;color:white;padding:10px 20px;border:none;border-radius:4px;cursor:pointer;">
        ✅ Create Owner Account
    </button>
</form>

<div style="background:#fff3cd;padding:15px;border-radius:5px;margin:20px 0;">
    <h4>⚠️ Instructions:</h4>
    <ol>
        <li>First run the database reset script in phpMyAdmin</li>
        <li>Then use this form to create a fresh owner account</li>
        <li>Delete this file after creating the owner for security</li>
    </ol>
</div>

<?php } ?>