<?php
/**
 * Fix user access issues
 */

require_once 'config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "<h2>Fixing User Access Issues</h2>";
    
    // Check current user data
    $stmt = $conn->prepare("SELECT id, name, email, role, status, department FROM users WHERE email = 'ilayaraja@athenas.co.in'");
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "<h3>Current User Data:</h3>";
        echo "<pre>" . print_r($user, true) . "</pre>";
        
        // Fix user data
        $stmt = $conn->prepare("UPDATE users SET 
            name = 'Ilayaraja',
            role = 'user',
            status = 'active',
            department = 'IT',
            designation = 'Developer',
            phone = '9876543210',
            joining_date = '2024-01-01'
            WHERE email = 'ilayaraja@athenas.co.in'");
        
        if ($stmt->execute()) {
            echo "<p>✅ User data updated successfully!</p>";
            
            // Verify update
            $stmt = $conn->prepare("SELECT id, name, email, role, status, department, designation FROM users WHERE email = 'ilayaraja@athenas.co.in'");
            $stmt->execute();
            $updatedUser = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo "<h3>Updated User Data:</h3>";
            echo "<pre>" . print_r($updatedUser, true) . "</pre>";
        } else {
            echo "<p>❌ Failed to update user data</p>";
        }
    } else {
        echo "<p>❌ User not found</p>";
    }
    
    echo "<h3>✅ User Access Fix Complete!</h3>";
    echo "<p><a href='/ergon/login'>Login Again</a></p>";
    
} catch (Exception $e) {
    echo "<h3>❌ Error:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>