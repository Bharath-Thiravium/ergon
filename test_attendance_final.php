<?php
// Final attendance timezone test
session_start();

if (!isset($_SESSION['user_id'])) {
    echo "Please log in first";
    exit;
}

$userId = $_SESSION['user_id'];
echo "<h2>Final Attendance Timezone Test - User ID: $userId</h2>";

require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/models/Attendance.php';

try {
    $db = Database::connect();
    
    // 1. Check user preferences
    echo "<h3>1. User Preferences</h3>";
    $stmt = $db->prepare("SELECT timezone FROM user_preferences WHERE user_id = ?");
    $stmt->execute([$userId]);
    $userPrefs = $stmt->fetch();
    $savedTimezone = $userPrefs['timezone'] ?? 'Asia/Kolkata';
    echo "Saved timezone: $savedTimezone<br>";
    
    // 2. Test timezone setting
    echo "<h3>2. Timezone Test</h3>";
    echo "Before setting: " . date_default_timezone_get() . " - " . date('Y-m-d H:i:s T') . "<br>";
    date_default_timezone_set($savedTimezone);
    echo "After setting: " . date_default_timezone_get() . " - " . date('Y-m-d H:i:s T') . "<br>";
    
    // 3. Test Attendance model directly
    echo "<h3>3. Attendance Model Test</h3>";
    $attendance = new Attendance();
    
    // Test the setUserTimezone method
    echo "Testing Attendance model timezone handling...<br>";
    
    // Simulate check-in
    $testTime = date('Y-m-d H:i:s');
    echo "PHP generated time (should be IST): $testTime<br>";
    
    // Test direct database insert with user timezone
    $stmt = $db->prepare("INSERT INTO attendance (user_id, check_in, status, location_name) VALUES (?, ?, 'test', 'Final Test')");
    $result = $stmt->execute([$userId, $testTime]);
    
    if ($result) {
        $testId = $db->lastInsertId();
        echo "✅ Test record inserted with ID: $testId<br>";
        
        // Retrieve and check
        $stmt = $db->prepare("SELECT check_in FROM attendance WHERE id = ?");
        $stmt->execute([$testId]);
        $retrieved = $stmt->fetch();
        echo "Retrieved time from DB: " . $retrieved['check_in'] . "<br>";
        
        // Check if times match
        if ($retrieved['check_in'] === $testTime) {
            echo "✅ Times match - timezone handling is working<br>";
        } else {
            echo "❌ Times don't match - there's still an issue<br>";
        }
        
        // Clean up
        $stmt = $db->prepare("DELETE FROM attendance WHERE id = ?");
        $stmt->execute([$testId]);
        echo "Test record cleaned up<br>";
    }
    
    // 4. Test the actual attendance endpoints
    echo "<h3>4. Check Current Attendance Records</h3>";
    $stmt = $db->prepare("SELECT * FROM attendance WHERE user_id = ? ORDER BY id DESC LIMIT 3");
    $stmt->execute([$userId]);
    $records = $stmt->fetchAll();
    
    foreach ($records as $record) {
        echo "ID: {$record['id']} - Check-in: {$record['check_in']} - Check-out: " . ($record['check_out'] ?? 'NULL') . "<br>";
    }
    
    // 5. Test which endpoint is being used
    echo "<h3>5. Endpoint Detection</h3>";
    echo "Check browser network tab when you clock in/out to see which URL is called:<br>";
    echo "- If it's /ergon/attendance/clock → AttendanceController<br>";
    echo "- If it's /ergon/api/simple_attendance.php → Simple API<br>";
    echo "- If it's /ergon/public/api_attendance.php → Public API (uses Attendance model)<br>";
    
    // 6. Force a test with current time
    echo "<h3>6. Live Test</h3>";
    echo "<p>Current IST time should be: " . date('Y-m-d H:i:s') . "</p>";
    echo "<p>If you clock in now, it should record this time exactly.</p>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>