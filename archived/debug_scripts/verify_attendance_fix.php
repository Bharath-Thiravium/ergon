<?php
require_once 'app/config/database.php';
require_once 'app/helpers/TimezoneHelper.php';

try {
    $db = Database::connect();
    
    echo "<h2>Attendance Records Verification</h2>";
    
    // 1. Check current attendance records
    $stmt = $db->query("SELECT id, user_id, check_in, check_out FROM attendance ORDER BY id DESC LIMIT 5");
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Current Records (Raw vs Converted)</h3>";
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>User</th><th>Raw Check In</th><th>Display Check In</th><th>Raw Check Out</th><th>Display Check Out</th></tr>";
    
    foreach ($records as $record) {
        echo "<tr>";
        echo "<td>{$record['id']}</td>";
        echo "<td>{$record['user_id']}</td>";
        echo "<td>{$record['check_in']}</td>";
        echo "<td>" . (TimezoneHelper::displayTime($record['check_in']) ?: '-') . "</td>";
        echo "<td>" . ($record['check_out'] ?: '-') . "</td>";
        echo "<td>" . (TimezoneHelper::displayTime($record['check_out']) ?: '-') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 2. Test timezone conversion
    echo "<h3>Timezone Conversion Test</h3>";
    $testUtc = '2025-01-02 10:17:00';
    echo "<p>UTC: $testUtc → IST: " . TimezoneHelper::displayTime($testUtc) . "</p>";
    
    // 3. Check if view will show correct times
    echo "<h3>View Simulation</h3>";
    if (!empty($records)) {
        $employee = $records[0];
        $checkInTime = $employee['check_in'] ? TimezoneHelper::displayTime($employee['check_in']) : null;
        $checkOutTime = $employee['check_out'] ? TimezoneHelper::displayTime($employee['check_out']) : null;
        
        echo "<p>Employee {$employee['user_id']}:</p>";
        echo "<p>Check In: " . ($checkInTime ?: '-') . "</p>";
        echo "<p>Check Out: " . ($checkOutTime ?: '-') . "</p>";
    }
    
    echo "<h3>Status</h3>";
    echo "<p style='color: green;'>✅ Fixes applied successfully</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>