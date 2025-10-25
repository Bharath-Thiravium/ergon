<?php
require_once __DIR__ . '/config/database.php';

$email = 'info@athenas.co.in';
$newPassword = 'admin123';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
    $result = $stmt->execute([$hashedPassword, $email]);
    
    if ($result) {
        echo "<h2>✅ Password Reset Successful</h2>";
        echo "<p><strong>Email:</strong> $email</p>";
        echo "<p><strong>New Password:</strong> $newPassword</p>";
        echo "<p><a href='/ergon/login'>Go to Login</a></p>";
    } else {
        echo "<h2>❌ Password Reset Failed</h2>";
    }
    
} catch (Exception $e) {
    echo "<h2>Error:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>