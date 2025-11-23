<?php
require_once __DIR__ . '/app/config/database.php';

echo "<h2>Final Timezone Fix for Hostinger</h2>";

try {
    $db = Database::connect();
    
    // Check sample data
    echo "<h3>Current Data Check</h3>";
    $stmt = $db->query("SELECT check_in FROM attendance WHERE check_in IS NOT NULL LIMIT 1");
    $sample = $stmt->fetch();
    
    if ($sample) {
        echo "Sample DB time: " . $sample['check_in'] . "<br>";
    }
    
    // Create the fixed TimezoneHelper
    $fixedHelper = '<?php
class TimezoneHelper {
    
    public static function displayTime($dbTime) {
        if (!$dbTime) return null;
        
        try {
            // Create DateTime object from database time (assuming UTC)
            $utc = new DateTime($dbTime, new DateTimeZone("UTC"));
            // Convert to IST
            $ist = $utc->setTimezone(new DateTimeZone("Asia/Kolkata"));
            return $ist->format("H:i");
        } catch (Exception $e) {
            // Fallback to manual calculation
            $timestamp = strtotime($dbTime);
            $istTimestamp = $timestamp + (5.5 * 3600);
            return date("H:i", $istTimestamp);
        }
    }
    
    public static function utcToIst($utcTime) {
        if (!$utcTime) return null;
        
        try {
            // Create DateTime object from UTC time
            $utc = new DateTime($utcTime, new DateTimeZone("UTC"));
            // Convert to IST
            $ist = $utc->setTimezone(new DateTimeZone("Asia/Kolkata"));
            return $ist->format("Y-m-d H:i:s");
        } catch (Exception $e) {
            // Fallback to manual calculation
            $timestamp = strtotime($utcTime);
            $istTimestamp = $timestamp + (5.5 * 3600);
            return date("Y-m-d H:i:s", $istTimestamp);
        }
    }
    
    public static function nowUtc() {
        return gmdate("Y-m-d H:i:s");
    }
    
    public static function getCurrentDate() {
        try {
            // Get current IST date
            $utc = new DateTime("now", new DateTimeZone("UTC"));
            $ist = $utc->setTimezone(new DateTimeZone("Asia/Kolkata"));
            return $ist->format("Y-m-d");
        } catch (Exception $e) {
            // Fallback
            $istTimestamp = time() + (5.5 * 3600);
            return gmdate("Y-m-d", $istTimestamp);
        }
    }
}
?>';
    
    // Write the fixed helper
    file_put_contents(__DIR__ . '/app/helpers/TimezoneHelper.php', $fixedHelper);
    echo "✓ Updated TimezoneHelper with DateTime objects and fallback<br>";
    
    // Test the new helper
    require_once __DIR__ . '/app/helpers/TimezoneHelper.php';
    
    if ($sample) {
        $converted = TimezoneHelper::utcToIst($sample['check_in']);
        $display = TimezoneHelper::displayTime($sample['check_in']);
        echo "Converted to IST: " . $converted . "<br>";
        echo "Display format: " . $display . "<br>";
    }
    
    // Test with current time
    $utcNow = gmdate('Y-m-d H:i:s');
    echo "<br>Current UTC: " . $utcNow . "<br>";
    echo "Converted IST: " . TimezoneHelper::utcToIst($utcNow) . "<br>";
    echo "Display time: " . TimezoneHelper::displayTime($utcNow) . "<br>";
    
    echo "<h3>✓ Fix Complete!</h3>";
    echo "Your attendance system should now show correct IST times.<br>";
    echo "The TimezoneHelper now uses PHP DateTime objects with fallback to manual calculation.<br>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>