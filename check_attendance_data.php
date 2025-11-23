<?php
require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/helpers/TimezoneHelper.php';

echo "<h2>Attendance Data Check</h2>";

try {
    $db = Database::connect();
    
    // Check all attendance records
    echo "<h3>All Attendance Records:</h3>";
    $stmt = $db->query("SELECT id, user_id, check_in, check_out, DATE(check_in) as date FROM attendance ORDER BY check_in DESC LIMIT 10");
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($records)) {
        echo "No attendance records found in database.<br>";
    } else {
        foreach ($records as $record) {
            echo "ID: {$record['id']}, User: {$record['user_id']}, Date: {$record['date']}<br>";
            echo "Raw check_in: {$record['check_in']}<br>";
            echo "Converted: " . TimezoneHelper::displayTime($record['check_in']) . "<br>";
            echo "---<br>";
        }
    }
    
    // Check what date we're filtering for
    echo "<h3>Date Filter Check:</h3>";
    $filterDate = $_GET['date'] ?? date('Y-m-d');
    echo "Filter date: $filterDate<br>";
    echo "Today's date: " . date('Y-m-d') . "<br>";
    echo "IST date: " . TimezoneHelper::getCurrentDate() . "<br>";
    
    // Check records for today
    echo "<h3>Today's Records:</h3>";
    $stmt = $db->prepare("SELECT * FROM attendance WHERE DATE(check_in) = ?");
    $stmt->execute([$filterDate]);
    $todayRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($todayRecords)) {
        echo "No records for $filterDate<br>";
        
        // Check recent dates
        echo "<h4>Recent dates with records:</h4>";
        $stmt = $db->query("SELECT DATE(check_in) as date, COUNT(*) as count FROM attendance GROUP BY DATE(check_in) ORDER BY date DESC LIMIT 5");
        $dates = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($dates as $dateInfo) {
            echo "Date: {$dateInfo['date']}, Records: {$dateInfo['count']}<br>";
        }
    } else {
        echo "Found " . count($todayRecords) . " records for $filterDate<br>";
        foreach ($todayRecords as $record) {
            echo "User {$record['user_id']}: {$record['check_in']} -> " . TimezoneHelper::displayTime($record['check_in']) . "<br>";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>