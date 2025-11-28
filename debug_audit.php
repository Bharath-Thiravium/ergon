<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

$_SESSION['user_id'] = 16;
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

echo "<h1>Complete API Audit</h1>";

// Test 1: Check if API file exists and is accessible
echo "<h2>1. API File Check</h2>";
$apiFile = __DIR__ . '/api/daily_planner_workflow.php';
echo "<p>API file exists: " . (file_exists($apiFile) ? 'YES' : 'NO') . "</p>";
echo "<p>API file readable: " . (is_readable($apiFile) ? 'YES' : 'NO') . "</p>";

// Test 2: Check database connection
echo "<h2>2. Database Connection</h2>";
try {
    require_once 'app/config/database.php';
    $db = Database::connect();
    echo "<p>Database connection: SUCCESS</p>";
    
    // Check if tasks exist
    $stmt = $db->prepare("SELECT id, status FROM daily_tasks WHERE user_id = 16 AND id IN (212, 209, 210)");
    $stmt->execute();
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>Tasks found: " . count($tasks) . "</p>";
    foreach ($tasks as $task) {
        echo "<p>Task {$task['id']}: {$task['status']}</p>";
    }
} catch (Exception $e) {
    echo "<p>Database error: " . $e->getMessage() . "</p>";
}

// Test 3: Direct API call simulation
echo "<h2>3. Direct API Call Test</h2>";

// Simulate POST request
$_SERVER['REQUEST_METHOD'] = 'POST';
$_GET['action'] = 'pause';

// Create JSON input
$jsonInput = json_encode([
    'task_id' => 209,
    'csrf_token' => $_SESSION['csrf_token']
]);

// Mock php://input
$tempFile = tempnam(sys_get_temp_dir(), 'api_test');
file_put_contents($tempFile, $jsonInput);

echo "<p>Request method: " . $_SERVER['REQUEST_METHOD'] . "</p>";
echo "<p>Action: " . $_GET['action'] . "</p>";
echo "<p>JSON input: " . $jsonInput . "</p>";
echo "<p>Session user_id: " . $_SESSION['user_id'] . "</p>";
echo "<p>CSRF token: " . substr($_SESSION['csrf_token'], 0, 16) . "...</p>";

// Test JSON parsing
$decoded = json_decode($jsonInput, true);
echo "<p>JSON decode success: " . (json_last_error() === JSON_ERROR_NONE ? 'YES' : 'NO') . "</p>";

// Test 4: Include API and capture output
echo "<h2>4. API Response Test</h2>";
ob_start();
try {
    // Temporarily override file_get_contents for php://input
    $originalInput = file_get_contents('php://input');
    
    // Include the API
    include 'api/daily_planner_workflow.php';
    
    $output = ob_get_clean();
    echo "<p>API output length: " . strlen($output) . " characters</p>";
    echo "<pre>" . htmlspecialchars($output) . "</pre>";
    
} catch (Exception $e) {
    ob_end_clean();
    echo "<p>API exception: " . $e->getMessage() . "</p>";
} catch (Error $e) {
    ob_end_clean();
    echo "<p>API error: " . $e->getMessage() . "</p>";
}

unlink($tempFile);

// Test 5: Check PHP configuration
echo "<h2>5. PHP Configuration</h2>";
echo "<p>PHP version: " . PHP_VERSION . "</p>";
echo "<p>PDO available: " . (extension_loaded('pdo') ? 'YES' : 'NO') . "</p>";
echo "<p>PDO MySQL available: " . (extension_loaded('pdo_mysql') ? 'YES' : 'NO') . "</p>";
echo "<p>JSON extension: " . (extension_loaded('json') ? 'YES' : 'NO') . "</p>";
echo "<p>Session started: " . (session_status() === PHP_SESSION_ACTIVE ? 'YES' : 'NO') . "</p>";

// Test 6: Check error logs
echo "<h2>6. Recent Error Logs</h2>";
$errorLog = ini_get('error_log');
if ($errorLog && file_exists($errorLog)) {
    $errors = file_get_contents($errorLog);
    $recentErrors = array_slice(explode("\n", $errors), -10);
    echo "<pre>" . htmlspecialchars(implode("\n", $recentErrors)) . "</pre>";
} else {
    echo "<p>No error log found or accessible</p>";
}
?>