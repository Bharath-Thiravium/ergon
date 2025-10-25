<?php
session_start();

// Simulate admin login
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'admin';
$_SESSION['name'] = 'Test Admin';
$_SESSION['login_time'] = time();
$_SESSION['last_activity'] = time();

echo "Session data:<br>";
print_r($_SESSION);

echo "<br><br>Testing admin dashboard access...<br>";

// Test direct access to admin dashboard
try {
    require_once __DIR__ . '/app/controllers/AdminController.php';
    $controller = new AdminController();
    echo "AdminController loaded successfully<br>";
    
    // Test the dashboard method
    ob_start();
    $controller->dashboard();
    $output = ob_get_clean();
    
    if (empty($output)) {
        echo "Dashboard method executed but no output<br>";
    } else {
        echo "Dashboard output length: " . strlen($output) . " characters<br>";
        echo "First 200 chars: " . substr($output, 0, 200) . "...<br>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
}
?>