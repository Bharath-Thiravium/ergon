<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/app/helpers/Security.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "<h1>🔧 Reset User Passwords</h1>";
    
    // Define test passwords
    $testPasswords = [
        'owner' => 'owner123',
        'admin' => 'admin123', 
        'user' => 'user123'
    ];
    
    $stmt = $conn->prepare("SELECT id, name, email, role FROM users ORDER BY role DESC");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Name</th><th>Email</th><th>Role</th><th>New Password</th><th>Status</th></tr>";
    
    foreach ($users as $user) {
        $newPassword = $testPasswords[$user['role']] ?? 'password123';
        $hashedPassword = Security::hashPassword($newPassword);
        
        $updateStmt = $conn->prepare("
            UPDATE users 
            SET password = ?, temp_password = ?, is_first_login = FALSE, password_reset_required = FALSE 
            WHERE id = ?
        ");
        
        $success = $updateStmt->execute([$hashedPassword, $newPassword, $user['id']]);
        
        echo "<tr>";
        echo "<td>{$user['name']}</td>";
        echo "<td>{$user['email']}</td>";
        echo "<td>" . ucfirst($user['role']) . "</td>";
        echo "<td style='background: #fff9c4; font-weight: bold;'>$newPassword</td>";
        echo "<td>" . ($success ? '✅ Updated' : '❌ Failed') . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    echo "<div style='margin: 20px 0; padding: 20px; background: #d4edda; border-radius: 8px;'>";
    echo "<h3>✅ Password Reset Complete!</h3>";
    echo "<p><strong>Test Credentials:</strong></p>";
    echo "<ul>";
    echo "<li><strong>Owner:</strong> owner@company.com / owner123</li>";
    echo "<li><strong>Admin:</strong> admin@company.com / admin123</li>";
    echo "<li><strong>User:</strong> user@company.com / user123</li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<br><a href='/ergon/'>🔐 Go to Login Page</a>";
?>