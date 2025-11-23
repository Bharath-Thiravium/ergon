<?php
// Deep investigation of actual data flow
session_start();
require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/helpers/TimezoneHelper.php';

if (!isset($_SESSION['user_id'])) {
    echo "Please log in first";
    exit;
}

$userId = $_SESSION['user_id'];

try {
    $db = Database::connect();
    
    echo "<h2>🔍 Deep Data Flow Investigation</h2>";
    
    // 1. Check what's actually in the database
    echo "<h3>1. Raw Database Data</h3>";
    $stmt = $db->prepare("SELECT id, check_in, check_out, created_at FROM attendance WHERE user_id = ? ORDER BY id DESC LIMIT 3");
    $stmt->execute([$userId]);
    $records = $stmt->fetchAll();
    
    foreach ($records as $record) {
        echo "ID {$record['id']}: check_in='{$record['check_in']}', created_at='{$record['created_at']}'<br>";
    }
    
    // 2. Test what happens when we display these times
    echo "<h3>2. Display Test</h3>";
    TimezoneHelper::setSystemTimezone();
    echo "System timezone set to: " . date_default_timezone_get() . "<br>";
    
    foreach ($records as $record) {
        $displayTime = date('H:i', strtotime($record['check_in']));
        echo "ID {$record['id']}: Raw='{$record['check_in']}' → Display='$displayTime'<br>";
    }
    
    // 3. Test new record creation
    echo "<h3>3. New Record Creation Test</h3>";
    $testTime = TimezoneHelper::getCurrentTime();
    echo "TimezoneHelper generated time: $testTime<br>";
    
    // Insert test record
    $stmt = $db->prepare("INSERT INTO attendance (user_id, check_in, status, location_name) VALUES (?, ?, 'deep_test', 'Deep Investigation')");
    $result = $stmt->execute([$userId, $testTime]);
    
    if ($result) {
        $testId = $db->lastInsertId();
        
        // Immediately retrieve
        $stmt = $db->prepare("SELECT check_in FROM attendance WHERE id = ?");
        $stmt->execute([$testId]);
        $retrieved = $stmt->fetch();
        
        echo "Inserted: '$testTime'<br>";
        echo "Retrieved: '{$retrieved['check_in']}'<br>";
        
        // Test display
        TimezoneHelper::setSystemTimezone();
        $displayTime = date('H:i', strtotime($retrieved['check_in']));
        echo "Display: '$displayTime'<br>";
        
        // Check if it's actually IST
        $expectedHour = date('H', strtotime($testTime));
        $actualHour = date('H', strtotime($retrieved['check_in']));
        
        if ($expectedHour === $actualHour) {
            echo "✅ NEW RECORDS: Working correctly<br>";
        } else {
            echo "❌ NEW RECORDS: Still broken (Expected: $expectedHour, Got: $actualHour)<br>";
        }
        
        // Clean up
        $stmt = $db->prepare("DELETE FROM attendance WHERE id = ?");
        $stmt->execute([$testId]);
    }
    
    // 4. Check if the issue is with existing data
    echo "<h3>4. Existing Data Analysis</h3>";
    if (!empty($records)) {
        $latestRecord = $records[0];
        $checkInTime = $latestRecord['check_in'];
        
        // Parse the time
        $timestamp = strtotime($checkInTime);
        $utcTime = gmdate('H:i', $timestamp);
        $istTime = date('H:i', $timestamp + (5.5 * 3600)); // Add IST offset
        
        echo "Latest record check_in: '$checkInTime'<br>";
        echo "If interpreted as UTC: $utcTime UTC<br>";
        echo "If converted to IST: $istTime IST<br>";
        
        // Check current IST time
        TimezoneHelper::setSystemTimezone();
        $currentIST = date('H:i');
        echo "Current IST time: $currentIST<br>";
        
        // Diagnosis
        if (abs(strtotime($currentIST) - strtotime($istTime)) < 3600) {
            echo "🎯 DIAGNOSIS: Existing records are stored in UTC, need conversion<br>";
        } else {
            echo "🎯 DIAGNOSIS: Records might be in different timezone<br>";
        }
    }
    
    // 5. Check what the attendance page is actually showing
    echo "<h3>5. Live Page Data Check</h3>";
    echo "<p>Go to <a href='/ergon/attendance' target='_blank'>attendance page</a> and compare times shown there with times here:</p>";
    
    foreach ($records as $record) {
        TimezoneHelper::setSystemTimezone();
        $pageDisplayTime = date('H:i', strtotime($record['check_in']));
        echo "Record ID {$record['id']} should show: $pageDisplayTime<br>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>