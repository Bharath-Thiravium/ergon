<?php
session_start();

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

echo "Session ID: " . session_id() . "<br>";
echo "User ID: " . ($_SESSION['user_id'] ?? 'Not set') . "<br>";
echo "CSRF Token: " . $_SESSION['csrf_token'] . "<br>";

// Test API call
if ($_POST['test_api'] ?? false) {
    $taskId = $_POST['task_id'] ?? 1;
    $action = $_POST['action'] ?? 'pause';
    
    $data = [
        'task_id' => (int)$taskId,
        'csrf_token' => $_SESSION['csrf_token']
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://localhost/ergon/api/daily_planner_workflow.php?action=" . $action);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-CSRF-Token: ' . $_SESSION['csrf_token']
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "<h3>API Test Result:</h3>";
    echo "HTTP Code: " . $httpCode . "<br>";
    echo "Response: " . htmlspecialchars($response) . "<br>";
}
?>

<form method="POST">
    <input type="hidden" name="test_api" value="1">
    Task ID: <input type="number" name="task_id" value="1"><br>
    Action: <select name="action">
        <option value="pause">Pause</option>
        <option value="resume">Resume</option>
        <option value="start">Start</option>
    </select><br>
    <button type="submit">Test API</button>
</form>