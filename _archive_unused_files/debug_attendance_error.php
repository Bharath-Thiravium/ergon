<?php
// Debug script to identify attendance controller error
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Debugging Attendance Controller</h1>";

try {
    echo "<h2>1. Testing Database Connection</h2>";
    require_once __DIR__ . '/app/config/database.php';
    $db = Database::connect();
    echo "✅ Database connection successful<br>";
    
    echo "<h2>2. Testing Session</h2>";
    session_start();
    if (!isset($_SESSION['user_id'])) {
        // Set a test session for debugging
        $_SESSION['user_id'] = 1;
        $_SESSION['role'] = 'user';
        $_SESSION['name'] = 'Test User';
        echo "⚠️ No session found, created test session<br>";
    } else {
        echo "✅ Session exists: User ID " . $_SESSION['user_id'] . "<br>";
    }
    
    echo "<h2>3. Testing Controller Loading</h2>";
    require_once __DIR__ . '/app/controllers/UnifiedAttendanceController.php';
    echo "✅ Controller class loaded<br>";
    
    echo "<h2>4. Testing Controller Instantiation</h2>";
    $controller = new UnifiedAttendanceController();
    echo "✅ Controller instantiated<br>";
    
    echo "<h2>5. Testing Database Tables</h2>";
    
    // Check if users table exists
    $stmt = $db->query("SHOW TABLES LIKE 'users'");
    if ($stmt->fetch()) {
        echo "✅ Users table exists<br>";
        
        // Check if we have any users
        $stmt = $db->query("SELECT COUNT(*) as count FROM users");
        $result = $stmt->fetch();
        echo "Users count: " . $result['count'] . "<br>";
    } else {
        echo "❌ Users table missing<br>";
    }
    
    // Check if attendance table exists
    $stmt = $db->query("SHOW TABLES LIKE 'attendance'");
    if ($stmt->fetch()) {
        echo "✅ Attendance table exists<br>";
    } else {
        echo "⚠️ Attendance table missing (will be created)<br>";
    }
    
    echo "<h2>6. Testing Controller Method</h2>";
    ob_start();
    $controller->index();
    $output = ob_get_clean();
    
    if (strlen($output) > 0) {
        echo "✅ Controller method executed successfully<br>";
        echo "<h3>Output Preview:</h3>";
        echo "<div style='border: 1px solid #ccc; padding: 10px; max-height: 300px; overflow: auto;'>";
        echo htmlspecialchars(substr($output, 0, 500)) . "...";
        echo "</div>";
    } else {
        echo "⚠️ Controller method executed but no output<br>";
    }
    
} catch (Exception $e) {
    echo "<h2>❌ Error Found:</h2>";
    echo "<div style='background: #ffebee; border: 1px solid #f44336; padding: 10px; border-radius: 4px;'>";
    echo "<strong>Error:</strong> " . $e->getMessage() . "<br>";
    echo "<strong>File:</strong> " . $e->getFile() . "<br>";
    echo "<strong>Line:</strong> " . $e->getLine() . "<br>";
    echo "<strong>Stack Trace:</strong><br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    echo "</div>";
}

echo "<h2>7. Environment Info</h2>";
echo "PHP Version: " . PHP_VERSION . "<br>";
echo "Server: " . $_SERVER['SERVER_SOFTWARE'] . "<br>";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
?>