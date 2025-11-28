<?php
session_start();

// Test the API directly with the exact same data
$_SESSION['user_id'] = 16; // From your task data
$_SESSION['csrf_token'] = 'test_token_123';

// Test pause task 212 (which is in_progress)
$taskId = 212;
$action = 'pause';

echo "Testing API call for task $taskId with action $action\n";
echo "Session user_id: " . $_SESSION['user_id'] . "\n";
echo "CSRF token: " . $_SESSION['csrf_token'] . "\n\n";

// Simulate the API call
$_GET['action'] = $action;
$_POST['csrf_token'] = $_SESSION['csrf_token'];

// Create JSON input like the JavaScript sends
$jsonInput = json_encode([
    'task_id' => intval($taskId),
    'csrf_token' => $_SESSION['csrf_token']
]);

echo "JSON input: $jsonInput\n\n";

// Test JSON parsing
$decoded = json_decode($jsonInput, true);
echo "Decoded JSON:\n";
print_r($decoded);

// Test the actual API
echo "\n--- Testing API Response ---\n";
ob_start();
include 'api/daily_planner_workflow.php';
$output = ob_get_clean();
echo $output;
?>