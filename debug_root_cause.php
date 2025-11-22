<?php
// Deep root cause analysis for timezone issue
session_start();

if (!isset($_SESSION['user_id'])) {
    echo "Please log in first";
    exit;
}

$userId = $_SESSION['user_id'];
echo "<h2>🔍 Deep Root Cause Analysis - User ID: $userId</h2>";

require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    // 1. Check ALL possible timezone sources
    echo "<h3>1. All Timezone Sources</h3>";
    echo "Server timezone: " . date_default_timezone_get() . "<br>";
    echo "Server time: " . date('Y-m-d H:i:s T') . "<br>";
    
    // MySQL timezone
    $stmt = $db->query("SELECT NOW() as mysql_now, @@session.time_zone as session_tz, @@global.time_zone as global_tz");
    $result = $stmt->fetch();
    echo "MySQL NOW(): " . $result['mysql_now'] . "<br>";
    echo "MySQL Session TZ: " . $result['session_tz'] . "<br>";
    echo "MySQL Global TZ: " . $result['global_tz'] . "<br>";
    
    // User preferences
    $stmt = $db->prepare("SELECT timezone FROM user_preferences WHERE user_id = ?");
    $stmt->execute([$userId]);
    $userPrefs = $stmt->fetch();
    $userTimezone = $userPrefs['timezone'] ?? 'NOT_FOUND';
    echo "User preference timezone: $userTimezone<br><br>";
    
    // 2. Test actual attendance flow
    echo "<h3>2. Simulating Actual Attendance Flow</h3>";
    
    // Simulate what happens in AttendanceController clock method
    echo "<strong>AttendanceController simulation:</strong><br>";
    
    // Get user timezone (as done in controller)
    $stmt = $db->prepare("SELECT timezone FROM user_preferences WHERE user_id = ?");
    $stmt->execute([$userId]);
    $userPrefs = $stmt->fetch();
    $timezone = $userPrefs['timezone'] ?? 'Asia/Kolkata';
    echo "Retrieved timezone: $timezone<br>";
    
    // Set timezone (as done in controller)
    date_default_timezone_set($timezone);
    $currentTime = date('Y-m-d H:i:s');
    echo "Generated time after timezone set: $currentTime<br>";
    
    // Test database insert (as done in controller)
    $stmt = $db->prepare("INSERT INTO attendance (user_id, check_in, status, location_name) VALUES (?, ?, 'debug', 'Root Cause Test')");
    $result = $stmt->execute([$userId, $currentTime]);
    
    if ($result) {
        $testId = $db->lastInsertId();
        echo "✅ Inserted test record ID: $testId<br>";
        
        // Retrieve immediately
        $stmt = $db->prepare("SELECT check_in FROM attendance WHERE id = ?");
        $stmt->execute([$testId]);
        $retrieved = $stmt->fetch();
        echo "Retrieved from DB: " . $retrieved['check_in'] . "<br>";
        
        // Compare
        if ($retrieved['check_in'] === $currentTime) {
            echo "✅ PERFECT MATCH - Controller logic is working<br>";
        } else {
            echo "❌ MISMATCH - There's a database conversion issue<br>";
        }
        
        // Clean up
        $stmt = $db->prepare("DELETE FROM attendance WHERE id = ?");
        $stmt->execute([$testId]);
    }
    
    // 3. Test Attendance Model flow
    echo "<br><strong>Attendance Model simulation:</strong><br>";
    require_once __DIR__ . '/app/models/Attendance.php';
    
    // Reset timezone to test model's timezone handling
    date_default_timezone_set('UTC');
    echo "Reset to UTC, now testing model...<br>";
    
    $attendance = new Attendance();
    
    // This should set user timezone internally
    $todayRecord = $attendance->getTodayAttendance($userId);
    echo "Model timezone after getTodayAttendance: " . date_default_timezone_get() . "<br>";
    echo "Model generated time: " . date('Y-m-d H:i:s') . "<br>";
    
    // 4. Check what's actually being called
    echo "<h3>3. Check Active Endpoints</h3>";
    
    // Check if there are multiple attendance endpoints
    $endpoints = [
        '/ergon/attendance/clock' => 'AttendanceController',
        '/ergon/api/simple_attendance.php' => 'Simple API',
        '/ergon/public/api_attendance.php' => 'Public API (Attendance Model)'
    ];
    
    foreach ($endpoints as $endpoint => $description) {
        echo "- $endpoint → $description<br>";
    }
    
    echo "<br><strong>🚨 CRITICAL: Check browser network tab to see which endpoint is actually being called!</strong><br>";
    
    // 5. Check recent attendance records pattern
    echo "<h3>4. Recent Records Pattern Analysis</h3>";
    $stmt = $db->prepare("SELECT id, check_in, check_out, created_at FROM attendance WHERE user_id = ? ORDER BY id DESC LIMIT 5");
    $stmt->execute([$userId]);
    $records = $stmt->fetchAll();
    
    foreach ($records as $record) {
        $checkinTime = strtotime($record['check_in']);
        $createdTime = strtotime($record['created_at']);
        $timeDiff = $checkinTime - $createdTime;
        
        echo "ID {$record['id']}: Check-in={$record['check_in']}, Created={$record['created_at']}, Diff={$timeDiff}s<br>";
    }
    
    // 6. Final diagnosis
    echo "<h3>5. 🎯 DIAGNOSIS</h3>";
    echo "<p>If the controller simulation shows ✅ PERFECT MATCH but you're still seeing UTC times in actual attendance:</p>";
    echo "<p><strong>The issue is likely:</strong></p>";
    echo "<p>1. A different endpoint is being used (check network tab)</p>";
    echo "<p>2. JavaScript is sending server time instead of user time</p>";
    echo "<p>3. There's caching preventing the new code from running</p>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>