<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Debug Change Password</h1>";

// Test 1: Check if route exists
echo "<h2>1. Route Test</h2>";
$routesFile = __DIR__ . '/app/config/routes.php';
if (file_exists($routesFile)) {
    $routesContent = file_get_contents($routesFile);
    if (strpos($routesContent, 'system-admin/change-password') !== false) {
        echo "✅ Route exists in routes.php<br>";
    } else {
        echo "❌ Route missing in routes.php<br>";
    }
} else {
    echo "❌ Routes file not found<br>";
}

// Test 2: Check if controller method exists
echo "<h2>2. Controller Method Test</h2>";
require_once __DIR__ . '/app/core/Controller.php';
require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/controllers/SystemAdminController.php';

if (method_exists('SystemAdminController', 'changePassword')) {
    echo "✅ changePassword method exists<br>";
} else {
    echo "❌ changePassword method missing<br>";
}

// Test 3: Test direct method call
echo "<h2>3. Direct Method Test</h2>";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Mock session for testing
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'owner';

// Mock POST data
$_POST['admin_id'] = '1';
$_POST['password'] = 'test123';
$_POST['confirm_password'] = 'test123';
$_SERVER['REQUEST_METHOD'] = 'POST';

try {
    $controller = new SystemAdminController();
    
    // Capture output
    ob_start();
    $controller->changePassword();
    $output = ob_get_clean();
    
    echo "✅ Method executed successfully<br>";
    echo "Output: " . htmlspecialchars($output) . "<br>";
    
} catch (Exception $e) {
    echo "❌ Method execution failed: " . $e->getMessage() . "<br>";
}

// Test 4: Test database connection
echo "<h2>4. Database Test</h2>";
try {
    $db = Database::connect();
    echo "✅ Database connection successful<br>";
    
    // Check if users table exists
    $stmt = $db->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Users table exists<br>";
        
        // Check if there are admin users
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
        $stmt->execute();
        $result = $stmt->fetch();
        echo "Admin users count: " . $result['count'] . "<br>";
        
    } else {
        echo "❌ Users table missing<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

echo "<h2>Debug Complete</h2>";
echo "Access this script at: https://athenas.co.in/ergon/debug_change_password.php";
?>