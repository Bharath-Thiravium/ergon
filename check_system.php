<?php
// System diagnostic tool
session_start();

echo "<h1>System Diagnostic</h1>";

// Check PHP extensions
echo "<h2>PHP Extensions</h2>";
$required_extensions = ['pdo', 'pdo_mysql', 'json', 'session'];
foreach ($required_extensions as $ext) {
    $status = extension_loaded($ext) ? '✅ Loaded' : '❌ Missing';
    echo "<p>$ext: $status</p>";
}

// Check database connection
echo "<h2>Database Connection</h2>";
try {
    require_once 'app/config/database.php';
    $db = Database::connect();
    echo "<p>✅ Database connection successful</p>";
    
    // Test query
    $stmt = $db->query("SELECT COUNT(*) as count FROM daily_tasks WHERE user_id = 16");
    $result = $stmt->fetch();
    echo "<p>✅ Found {$result['count']} tasks for user 16</p>";
    
} catch (Exception $e) {
    echo "<p>❌ Database error: " . $e->getMessage() . "</p>";
}

// Check session
echo "<h2>Session Status</h2>";
$_SESSION['user_id'] = 16;
$_SESSION['csrf_token'] = bin2hex(random_bytes(16));
echo "<p>✅ Session user_id: " . $_SESSION['user_id'] . "</p>";
echo "<p>✅ CSRF token: " . $_SESSION['csrf_token'] . "</p>";

// Test API endpoint
echo "<h2>API Test</h2>";
echo "<p>Testing API with task 212 (in_progress status)...</p>";

// Simulate API call
$_SERVER['REQUEST_METHOD'] = 'POST';
$_GET['action'] = 'pause';

// Create proper input
$input = json_encode([
    'task_id' => 212,
    'csrf_token' => $_SESSION['csrf_token']
]);

// Temporarily override php://input
$temp_file = tempnam(sys_get_temp_dir(), 'api_test');
file_put_contents($temp_file, $input);

echo "<p>Request data: $input</p>";

// Test the API response (capture output)
ob_start();
try {
    // Mock the input stream
    $_POST = [];
    
    // Include API but capture any errors
    include 'api/daily_planner_workflow.php';
    $api_output = ob_get_clean();
    echo "<p>✅ API Response: <pre>$api_output</pre></p>";
} catch (Exception $e) {
    ob_end_clean();
    echo "<p>❌ API Error: " . $e->getMessage() . "</p>";
}

unlink($temp_file);
?>