<?php
// Direct fix - replace TimezoneHelper with hardcoded IST conversion
$newHelper = '<?php
class TimezoneHelper {
    
    public static function displayTime($dbTime) {
        if (!$dbTime) return null;
        
        // Add exactly 5 hours 30 minutes to UTC time
        $timestamp = strtotime($dbTime . " UTC");
        $istTime = $timestamp + 19800; // 5.5 hours in seconds
        return date("H:i", $istTime);
    }
    
    public static function utcToIst($utcTime) {
        if (!$utcTime) return null;
        
        // Add exactly 5 hours 30 minutes to UTC time
        $timestamp = strtotime($utcTime . " UTC");
        $istTime = $timestamp + 19800; // 5.5 hours in seconds
        return date("Y-m-d H:i:s", $istTime);
    }
    
    public static function nowUtc() {
        return gmdate("Y-m-d H:i:s");
    }
    
    public static function getCurrentDate() {
        // Get IST date
        $istTime = time() + 19800; // Add 5.5 hours
        return date("Y-m-d", $istTime);
    }
}
?>';

file_put_contents(__DIR__ . '/app/helpers/TimezoneHelper.php', $newHelper);

echo "✓ TimezoneHelper updated with hardcoded IST conversion (+5:30)<br>";
echo "This should now work on Hostinger.<br>";

// Test it
require_once __DIR__ . '/app/helpers/TimezoneHelper.php';

$testUtc = "2024-01-15 10:30:00";
echo "Test UTC: " . $testUtc . "<br>";
echo "IST Result: " . TimezoneHelper::displayTime($testUtc) . "<br>";
echo "Expected: 16:00<br>";

if (TimezoneHelper::displayTime($testUtc) === "16:00") {
    echo "✓ Conversion working correctly!<br>";
} else {
    echo "✗ Still not working<br>";
}
?>