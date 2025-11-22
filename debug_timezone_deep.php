<?php
// Deep timezone investigation for Hostinger
session_start();

echo "<h2>Deep Timezone Debug - Hostinger Investigation</h2>";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<p style='color:red;'>❌ Please log in first</p>";
    exit;
}

$userId = $_SESSION['user_id'];
echo "<p><strong>User ID:</strong> $userId</p>";

require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::connect();
    
    // 1. Check server timezone settings
    echo "<h3>1. Server Timezone Settings</h3>";
    echo "PHP default timezone: " . date_default_timezone_get() . "<br>";
    echo "Server time (before any changes): " . date('Y-m-d H:i:s T') . "<br>";
    
    // Check MySQL timezone
    $stmt = $db->query("SELECT NOW() as mysql_now, @@session.time_zone as session_tz, @@global.time_zone as global_tz");
    $result = $stmt->fetch();
    echo "MySQL NOW(): " . $result['mysql_now'] . "<br>";
    echo "MySQL Session TZ: " . $result['session_tz'] . "<br>";
    echo "MySQL Global TZ: " . $result['global_tz'] . "<br>";
    
    // 2. Check user preferences table
    echo "<h3>2. User Preferences Check</h3>";
    $stmt = $db->prepare("SELECT * FROM user_preferences WHERE user_id = ?");
    $stmt->execute([$userId]);
    $prefs = $stmt->fetch();
    
    if ($prefs) {
        echo "✅ User preferences found<br>";
        echo "Saved timezone: " . ($prefs['timezone'] ?? 'NULL') . "<br>";
        echo "All preferences: " . json_encode($prefs) . "<br>";
    } else {
        echo "❌ No user preferences found<br>";
    }
    
    // 3. Test timezone setting
    echo "<h3>3. Timezone Setting Test</h3>";
    $savedTimezone = $prefs['timezone'] ?? 'Asia/Kolkata';
    echo "Attempting to set timezone to: $savedTimezone<br>";
    
    $oldTz = date_default_timezone_get();
    date_default_timezone_set($savedTimezone);
    $newTz = date_default_timezone_get();
    
    echo "Old timezone: $oldTz<br>";
    echo "New timezone: $newTz<br>";
    echo "Time after setting timezone: " . date('Y-m-d H:i:s T') . "<br>";
    
    // 4. Check attendance table structure
    echo "<h3>4. Attendance Table Structure</h3>";
    $stmt = $db->query("DESCRIBE attendance");
    $columns = $stmt->fetchAll();
    foreach ($columns as $col) {
        echo "{$col['Field']} - {$col['Type']} - Default: {$col['Default']}<br>";
    }
    
    // 5. Test actual attendance operations
    echo "<h3>5. Attendance Operations Test</h3>";
    
    // Test the Attendance model
    require_once __DIR__ . '/app/models/Attendance.php';
    $attendance = new Attendance();
    
    echo "Testing getTodayAttendance...<br>";
    $todayRecord = $attendance->getTodayAttendance($userId);
    if ($todayRecord) {
        echo "Today's record found: " . json_encode($todayRecord) . "<br>";
        echo "Check-in time: " . $todayRecord['check_in'] . "<br>";
        if ($todayRecord['check_out']) {
            echo "Check-out time: " . $todayRecord['check_out'] . "<br>";
        }
    } else {
        echo "No attendance record for today<br>";
    }
    
    // 6. Test direct database operations
    echo "<h3>6. Direct Database Test</h3>";
    
    // Reset timezone to user preference
    date_default_timezone_set($savedTimezone);
    $testTime = date('Y-m-d H:i:s');
    echo "PHP generated time: $testTime<br>";
    
    // Test insert with PHP time
    $stmt = $db->prepare("INSERT INTO attendance (user_id, check_in, status, location_name) VALUES (?, ?, 'test', 'Debug Test')");
    $result = $stmt->execute([$userId, $testTime]);
    
    if ($result) {
        $testId = $db->lastInsertId();
        echo "✅ Test record inserted with ID: $testId<br>";
        
        // Retrieve and check
        $stmt = $db->prepare("SELECT check_in FROM attendance WHERE id = ?");
        $stmt->execute([$testId]);
        $retrieved = $stmt->fetch();
        echo "Retrieved time: " . $retrieved['check_in'] . "<br>";
        
        // Clean up
        $stmt = $db->prepare("DELETE FROM attendance WHERE id = ?");
        $stmt->execute([$testId]);
        echo "Test record cleaned up<br>";
    } else {
        echo "❌ Failed to insert test record<br>";
    }
    
    // 7. Check which attendance endpoint is being called
    echo "<h3>7. Attendance Endpoint Investigation</h3>";
    echo "Check browser network tab to see which endpoint is being called:<br>";
    echo "- /ergon/attendance/clock (AttendanceController)<br>";
    echo "- /ergon/api/simple_attendance.php<br>";
    echo "- /ergon/public/api_attendance.php<br>";
    
    // 8. Environment check
    echo "<h3>8. Environment Check</h3>";
    require_once __DIR__ . '/app/config/environment.php';
    echo "Environment: " . (Environment::isDevelopment() ? 'Development' : 'Production') . "<br>";
    echo "Is Hostinger: " . (Environment::isHostinger() ? 'YES' : 'NO') . "<br>";
    echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
    
} catch (Exception $e) {
    echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
}
?>