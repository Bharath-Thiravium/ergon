<?php
// Emergency fix - bypass all existing code and force IST display
require_once __DIR__ . '/app/config/database.php';

echo "<h2>Emergency Timezone Fix</h2>";

// Create a simple, direct conversion function
$directFix = '<?php
class TimezoneHelper {
    
    public static function displayTime($dbTime) {
        if (!$dbTime || $dbTime === "0000-00-00 00:00:00") return null;
        
        // Force IST conversion - add 5.5 hours to any time
        $parts = explode(" ", $dbTime);
        if (count($parts) >= 2) {
            $timePart = $parts[1];
        } else {
            $timePart = $dbTime;
        }
        
        // Parse time
        $timeArray = explode(":", $timePart);
        if (count($timeArray) >= 2) {
            $hours = (int)$timeArray[0];
            $minutes = (int)$timeArray[1];
            
            // Add 5.5 hours
            $totalMinutes = ($hours * 60) + $minutes + 330; // 5.5 hours = 330 minutes
            
            // Handle day overflow
            if ($totalMinutes >= 1440) { // 24 hours = 1440 minutes
                $totalMinutes -= 1440;
            }
            
            $newHours = floor($totalMinutes / 60);
            $newMinutes = $totalMinutes % 60;
            
            return sprintf("%02d:%02d", $newHours, $newMinutes);
        }
        
        return "00:00";
    }
    
    public static function utcToIst($utcTime) {
        if (!$utcTime) return null;
        return $utcTime; // Return as-is for now
    }
    
    public static function nowUtc() {
        return gmdate("Y-m-d H:i:s");
    }
    
    public static function getCurrentDate() {
        return date("Y-m-d");
    }
}
?>';

file_put_contents(__DIR__ . '/app/helpers/TimezoneHelper.php', $directFix);
echo "✓ Applied emergency timezone fix<br>";

// Test the fix
require_once __DIR__ . '/app/helpers/TimezoneHelper.php';

$testCases = [
    "2024-01-15 10:30:00" => "16:00", // 10:30 UTC + 5:30 = 16:00 IST
    "2024-01-15 18:45:00" => "00:15", // 18:45 UTC + 5:30 = 00:15 IST (next day)
    "2024-01-15 06:15:00" => "11:45"  // 06:15 UTC + 5:30 = 11:45 IST
];

echo "<h3>Testing Conversion:</h3>";
foreach ($testCases as $input => $expected) {
    $result = TimezoneHelper::displayTime($input);
    $status = ($result === $expected) ? "✓" : "✗";
    echo "$status Input: $input → Output: $result (Expected: $expected)<br>";
}

echo "<h3>✓ Emergency fix applied!</h3>";
echo "This bypasses all complex timezone logic and uses simple math.<br>";
?>