<?php
require_once __DIR__ . '/app/helpers/TimezoneHelper.php';

echo "<h2>Hostinger Timezone Test</h2>";

// Test cases
$testTimes = [
    '2024-01-15 10:30:00',
    '2024-01-15 18:45:00',
    '2024-01-15 06:15:00'
];

echo "<h3>Server Info:</h3>";
echo "PHP Timezone: " . date_default_timezone_get() . "<br>";
echo "Server Time: " . date('Y-m-d H:i:s') . "<br>";
echo "UTC Time: " . gmdate('Y-m-d H:i:s') . "<br>";

echo "<h3>Conversion Tests:</h3>";
foreach ($testTimes as $testTime) {
    echo "<strong>Input:</strong> $testTime<br>";
    
    // Method 1: Direct strtotime
    $ts1 = strtotime($testTime);
    $result1 = date('H:i', $ts1 + 19800);
    echo "Method 1 (direct): $result1<br>";
    
    // Method 2: Force UTC
    $ts2 = strtotime($testTime . ' UTC');
    $result2 = date('H:i', $ts2 + 19800);
    echo "Method 2 (UTC): $result2<br>";
    
    // Method 3: TimezoneHelper
    $result3 = TimezoneHelper::displayTime($testTime);
    echo "TimezoneHelper: $result3<br>";
    
    echo "---<br>";
}

// Test with actual database data
try {
    require_once __DIR__ . '/app/config/database.php';
    $db = Database::connect();
    
    $stmt = $db->query("SELECT check_in FROM attendance WHERE check_in IS NOT NULL LIMIT 1");
    $sample = $stmt->fetch();
    
    if ($sample) {
        echo "<h3>Database Sample:</h3>";
        echo "Raw DB time: " . $sample['check_in'] . "<br>";
        echo "Converted: " . TimezoneHelper::displayTime($sample['check_in']) . "<br>";
    }
} catch (Exception $e) {
    echo "DB Error: " . $e->getMessage();
}
?>