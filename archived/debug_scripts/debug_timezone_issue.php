<?php
require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/helpers/TimezoneHelper.php';

echo "<h2>Timezone Debug Script</h2>";

try {
    $db = Database::connect();
    
    // Check server timezone
    echo "<h3>1. Server Information</h3>";
    echo "PHP Timezone: " . date_default_timezone_get() . "<br>";
    echo "Server Time: " . date('Y-m-d H:i:s') . "<br>";
    echo "UTC Time: " . gmdate('Y-m-d H:i:s') . "<br>";
    
    // Check MySQL timezone
    echo "<h3>2. MySQL Information</h3>";
    $stmt = $db->query("SELECT NOW() as mysql_time, UTC_TIMESTAMP() as utc_time, @@session.time_zone as session_tz, @@global.time_zone as global_tz");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "MySQL NOW(): " . $result['mysql_time'] . "<br>";
    echo "MySQL UTC_TIMESTAMP(): " . $result['utc_time'] . "<br>";
    echo "MySQL Session Timezone: " . $result['session_tz'] . "<br>";
    echo "MySQL Global Timezone: " . $result['global_tz'] . "<br>";
    
    // Test TimezoneHelper
    echo "<h3>3. TimezoneHelper Test</h3>";
    $testUtc = '2024-01-15 10:30:00';
    echo "Test UTC Time: " . $testUtc . "<br>";
    echo "TimezoneHelper::displayTime(): " . TimezoneHelper::displayTime($testUtc) . "<br>";
    echo "TimezoneHelper::utcToIst(): " . TimezoneHelper::utcToIst($testUtc) . "<br>";
    
    // Check actual attendance data
    echo "<h3>4. Sample Attendance Data</h3>";
    $stmt = $db->query("SELECT id, user_id, check_in, check_out FROM attendance ORDER BY id DESC LIMIT 3");
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($records as $record) {
        echo "Record ID: " . $record['id'] . "<br>";
        echo "Raw check_in: " . $record['check_in'] . "<br>";
        echo "Converted check_in: " . TimezoneHelper::utcToIst($record['check_in']) . "<br>";
        echo "Display time: " . TimezoneHelper::displayTime($record['check_in']) . "<br>";
        echo "---<br>";
    }
    
    // Test manual conversion
    echo "<h3>5. Manual Conversion Test</h3>";
    if (!empty($records)) {
        $testTime = $records[0]['check_in'];
        echo "Original: " . $testTime . "<br>";
        
        $timestamp = strtotime($testTime);
        echo "Timestamp: " . $timestamp . "<br>";
        
        $istTimestamp = $timestamp + (5.5 * 3600);
        echo "IST Timestamp: " . $istTimestamp . "<br>";
        
        $istTime = date('Y-m-d H:i:s', $istTimestamp);
        echo "IST Time: " . $istTime . "<br>";
        
        $displayTime = date('H:i', $istTimestamp);
        echo "Display Time: " . $displayTime . "<br>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>