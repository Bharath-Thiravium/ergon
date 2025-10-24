<?php
/**
 * Fix session data for logged in users
 */

session_start();

if (!isset($_SESSION['user_id'])) {
    echo "<p>Not logged in. <a href='/ergon/login'>Login</a></p>";
    exit;
}

require_once 'config/database.php';
require_once 'app/models/User.php';

try {
    $userModel = new User();
    $userData = $userModel->getById($_SESSION['user_id']);
    
    if ($userData) {
        $_SESSION['user'] = $userData;
        $_SESSION['user_name'] = $userData['name'];
        $_SESSION['role'] = $userData['role'];
        
        echo "<h2>✅ Session Data Fixed</h2>";
        echo "<h3>Updated Session:</h3>";
        echo "<pre>";
        print_r($_SESSION);
        echo "</pre>";
        
        echo "<p><a href='/ergon/daily-planner'>Test Daily Planner</a></p>";
    } else {
        echo "<p>❌ User not found in database</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?>