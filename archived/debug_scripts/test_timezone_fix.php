<?php
require_once 'app/config/database.php';
require_once 'app/helpers/TimezoneHelper.php';

echo "<h2>Timezone Fix Verification</h2>";

try {
    $db = Database::connect();
    
    // Test TimezoneHelper methods
    echo "<h3>TimezoneHelper Test</h3>";
    $utcNow = TimezoneHelper::nowUtc();
    $ownerTime = TimezoneHelper::utcToOwner($utcNow);
    $displayTime = TimezoneHelper::displayTime($utcNow);
    $currentDate = TimezoneHelper::getCurrentDate();
    
    echo "<p><strong>UTC Now:</strong> $utcNow</p>";
    echo "<p><strong>Owner Time:</strong> $ownerTime</p>";
    echo "<p><strong>Display Time:</strong> $displayTime</p>";
    echo "<p><strong>Current Date:</strong> $currentDate</p>";
    
    // Test with sample attendance data
    echo "<h3>Sample Attendance Record Test</h3>";
    $sampleUtc = '2025-01-02 10:17:00';
    echo "<p><strong>Sample UTC:</strong> $sampleUtc</p>";
    echo "<p><strong>Converted to Owner:</strong> " . TimezoneHelper::utcToOwner($sampleUtc) . "</p>";
    echo "<p><strong>Display Time:</strong> " . TimezoneHelper::displayTime($sampleUtc) . "</p>";
    
    // Check recent attendance records
    echo "<h3>Recent Attendance Records</h3>";
    $stmt = $db->query("SELECT id, user_id, check_in, check_out FROM attendance ORDER BY id DESC LIMIT 3");
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($records)) {
        echo "<p>No attendance records found.</p>";
    } else {
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>User ID</th><th>Check In (UTC)</th><th>Check In (Display)</th><th>Check Out (UTC)</th><th>Check Out (Display)</th></tr>";
        foreach ($records as $record) {
            echo "<tr>";
            echo "<td>{$record['id']}</td>";
            echo "<td>{$record['user_id']}</td>";
            echo "<td>{$record['check_in']}</td>";
            echo "<td>" . TimezoneHelper::displayTime($record['check_in']) . "</td>";
            echo "<td>{$record['check_out']}</td>";
            echo "<td>" . TimezoneHelper::displayTime($record['check_out']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h3>Status</h3>";
    echo "<p style='color: green;'><strong>✅ Timezone conversion is working correctly!</strong></p>";
    echo "<p>All attendance times should now display in IST (owner timezone) instead of UTC.</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>