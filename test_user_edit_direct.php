<?php
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'owner';

// Simulate the edit request
$userId = 2;

require_once __DIR__ . '/app/controllers/UsersController.php';

echo "<h1>Direct User Edit Test</h1>";

try {
    $controller = new UsersController();
    
    // Capture output
    ob_start();
    $controller->edit($userId);
    $output = ob_get_clean();
    
    if (strpos($output, 'User not found') !== false) {
        echo "<h2>❌ User not found error detected</h2>";
    } else {
        echo "<h2>✅ Edit form loaded successfully</h2>";
    }
    
    // Show first 500 characters of output
    echo "<h3>Output Preview:</h3>";
    echo "<pre>" . htmlspecialchars(substr($output, 0, 500)) . "...</pre>";
    
} catch (Exception $e) {
    echo "<h2>❌ Exception: " . $e->getMessage() . "</h2>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>