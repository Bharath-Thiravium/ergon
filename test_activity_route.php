<?php
session_start();

// Test the activity reports route directly
echo "<h1>Testing Activity Reports Route</h1>";

// Check session
echo "<h2>Session Info:</h2>";
echo "User ID: " . ($_SESSION['user_id'] ?? 'Not set') . "<br>";
echo "Role: " . ($_SESSION['role'] ?? 'Not set') . "<br>";
echo "User Name: " . ($_SESSION['user_name'] ?? 'Not set') . "<br>";

// Test database connection
echo "<h2>Database Test:</h2>";
try {
    require_once __DIR__ . '/config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    echo "Database connection: SUCCESS<br>";
    
    // Check if activity_logs table exists
    $stmt = $db->prepare("SHOW TABLES LIKE 'activity_logs'");
    $stmt->execute();
    $result = $stmt->fetch();
    echo "Activity logs table exists: " . ($result ? 'YES' : 'NO') . "<br>";
    
} catch (Exception $e) {
    echo "Database error: " . $e->getMessage() . "<br>";
}

// Test controller
echo "<h2>Controller Test:</h2>";
try {
    require_once __DIR__ . '/app/controllers/ReportsController.php';
    $controller = new ReportsController();
    echo "ReportsController loaded: SUCCESS<br>";
    
    // Test method exists
    if (method_exists($controller, 'activityReport')) {
        echo "activityReport method exists: YES<br>";
    } else {
        echo "activityReport method exists: NO<br>";
    }
    
} catch (Exception $e) {
    echo "Controller error: " . $e->getMessage() . "<br>";
}

echo "<br><a href='/ergon/reports/activity'>Try Activity Reports Link</a>";
?>