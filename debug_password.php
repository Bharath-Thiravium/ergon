<?php
require_once __DIR__ . '/app/config/database.php';

// Test password reset functionality
$userId = 2; // Change this to the user ID you're testing
$testPassword = 'TEST123';

try {
    $db = Database::connect();
    
    // Get current user data
    $stmt = $db->prepare("SELECT id, email, password FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "User found: " . $user['email'] . "\n";
        echo "Current password hash: " . substr($user['password'], 0, 20) . "...\n";
        
        // Update password
        $hashedPassword = password_hash($testPassword, PASSWORD_BCRYPT);
        $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
        $result = $stmt->execute([$hashedPassword, $userId]);
        
        if ($result) {
            echo "Password updated successfully\n";
            echo "New password: " . $testPassword . "\n";
            echo "New hash: " . substr($hashedPassword, 0, 20) . "...\n";
            
            // Test verification
            if (password_verify($testPassword, $hashedPassword)) {
                echo "✅ Password verification works\n";
            } else {
                echo "❌ Password verification failed\n";
            }
        } else {
            echo "❌ Failed to update password\n";
        }
    } else {
        echo "❌ User not found\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>