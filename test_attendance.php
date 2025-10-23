<?php
session_start();

// Mock session for testing
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['role'] = 'user';
    $_SESSION['user_name'] = 'Test User';
}

require_once 'app/controllers/AttendanceController.php';

echo "<h1>Attendance Test</h1>";

try {
    $controller = new AttendanceController();
    echo "<p>✅ AttendanceController loaded successfully</p>";
    
    // Test POST request simulation
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $controller->clock();
    } else {
        echo "<form method='POST'>";
        echo "<button type='submit' name='test' value='clock_in'>Test Clock In</button>";
        echo "</form>";
        
        echo "<script>";
        echo "function testClockIn() {";
        echo "  fetch('/ergon/attendance/clock', {";
        echo "    method: 'POST',";
        echo "    headers: {'Content-Type': 'application/json'},";
        echo "    body: JSON.stringify({action: 'clock_in', latitude: 0, longitude: 0, location_name: 'Test'})";
        echo "  })";
        echo "  .then(r => r.text())";
        echo "  .then(data => console.log('Response:', data))";
        echo "  .catch(e => console.error('Error:', e));";
        echo "}";
        echo "</script>";
        echo "<button onclick='testClockIn()'>Test AJAX Clock In</button>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?>