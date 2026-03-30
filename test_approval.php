<?php
// Test script to verify advance approval functionality
session_start();

// Set up test session (remove this in production)
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'owner';

require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/controllers/AdvanceController.php';

echo "<h1>Advance Approval Test</h1>";

try {
    $db = Database::connect();
    
    // Check if there are any pending advances
    $stmt = $db->query("SELECT id, user_id, amount, reason, status FROM advances WHERE status = 'pending' LIMIT 1");
    $advance = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($advance) {
        echo "<h2>Found pending advance:</h2>";
        echo "<pre>" . print_r($advance, true) . "</pre>";
        
        // Test GET request (should return JSON)
        echo "<h3>Testing GET request:</h3>";
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
        
        ob_start();
        $controller = new AdvanceController();
        $controller->approve($advance['id']);
        $output = ob_get_clean();
        
        echo "<pre>GET Response: " . htmlspecialchars($output) . "</pre>";
        
        // Test if it's valid JSON
        $json = json_decode($output, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            echo "<p style='color: green;'>✓ GET request returns valid JSON</p>";
        } else {
            echo "<p style='color: red;'>✗ GET request does not return valid JSON</p>";
            echo "<p>JSON Error: " . json_last_error_msg() . "</p>";
        }
        
    } else {
        echo "<p>No pending advances found. Creating a test advance...</p>";
        
        // Create a test advance
        $stmt = $db->prepare("INSERT INTO advances (user_id, amount, reason, status, created_at) VALUES (?, ?, ?, 'pending', NOW())");
        $stmt->execute([1, 1000.00, 'Test advance for approval testing']);
        $testId = $db->lastInsertId();
        
        echo "<p>Created test advance with ID: $testId</p>";
        echo "<p><a href='?test_id=$testId'>Test with this advance</a></p>";
    }
    
    // If test_id is provided, test with that specific advance
    if (isset($_GET['test_id'])) {
        $testId = $_GET['test_id'];
        echo "<h3>Testing with advance ID: $testId</h3>";
        
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
        
        ob_start();
        $controller = new AdvanceController();
        $controller->approve($testId);
        $output = ob_get_clean();
        
        echo "<pre>Response: " . htmlspecialchars($output) . "</pre>";
        
        $json = json_decode($output, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            echo "<p style='color: green;'>✓ Response is valid JSON</p>";
            if (isset($json['success']) && $json['success']) {
                echo "<p style='color: green;'>✓ Success response received</p>";
            } else {
                echo "<p style='color: orange;'>⚠ Response indicates failure: " . ($json['error'] ?? 'Unknown error') . "</p>";
            }
        } else {
            echo "<p style='color: red;'>✗ Response is not valid JSON</p>";
            echo "<p>JSON Error: " . json_last_error_msg() . "</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='/ergon/advances'>Go to Advances Page</a></p>";
echo "<p><strong>Note:</strong> Delete this test file after testing is complete.</p>";
?>
