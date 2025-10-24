<?php
/**
 * Test user access to daily planner
 */

session_start();

echo "<h2>User Access Test</h2>";

if (!isset($_SESSION['user_id'])) {
    echo "<p>❌ Not logged in</p>";
    echo "<p><a href='/ergon/login'>Login</a></p>";
    exit;
}

echo "<h3>Session Data:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Test database connection
require_once 'config/database.php';
try {
    $database = new Database();
    $conn = $database->getConnection();
    
    $stmt = $conn->prepare("SELECT id, name, email, role, status, department FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h3>Database User Data:</h3>";
    echo "<pre>";
    print_r($user);
    echo "</pre>";
    
    if ($user && $user['status'] === 'active') {
        echo "<p>✅ User is active and should have access</p>";
        echo "<p><a href='/ergon/daily-planner'>Test Daily Planner Access</a></p>";
    } else {
        echo "<p>❌ User is inactive or not found</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Database error: " . $e->getMessage() . "</p>";
}
?>