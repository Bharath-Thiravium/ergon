<?php
require_once __DIR__ . '/app/config/database.php';

echo "<h2>Hostinger Timezone Fix Script</h2>";

try {
    $db = Database::connect();
    
    // Force UTC timezone for consistency
    $db->exec("SET time_zone = '+00:00'");
    echo "✓ Set MySQL timezone to UTC<br>";
    
    // Check if we need to update existing data
    $stmt = $db->query("SELECT COUNT(*) as count FROM attendance WHERE check_in IS NOT NULL");
    $result = $stmt->fetch();
    echo "Found " . $result['count'] . " attendance records<br>";
    
    // Test current timezone handling
    echo "<h3>Testing Current Setup</h3>";
    
    // Insert a test record with current UTC time
    $utcNow = gmdate('Y-m-d H:i:s');
    echo "Current UTC: " . $utcNow . "<br>";
    
    // Calculate IST manually
    $istNow = date('Y-m-d H:i:s', strtotime($utcNow) + (5.5 * 3600));
    echo "Expected IST: " . $istNow . "<br>";
    
    // Test the conversion function
    require_once __DIR__ . '/app/helpers/TimezoneHelper.php';
    $convertedTime = TimezoneHelper::utcToIst($utcNow);
    echo "TimezoneHelper conversion: " . $convertedTime . "<br>";
    
    $displayTime = TimezoneHelper::displayTime($utcNow);
    echo "Display time: " . $displayTime . "<br>";
    
    if ($convertedTime === $istNow) {
        echo "✓ Timezone conversion is working correctly<br>";
    } else {
        echo "✗ Timezone conversion has issues<br>";
        
        // Fix the TimezoneHelper
        echo "<h3>Fixing TimezoneHelper</h3>";
        
        $fixedHelper = '<?php
class TimezoneHelper {
    
    public static function displayTime($dbTime) {
        if (!$dbTime) return null;
        
        // Convert UTC to IST and display time only
        $utc = new DateTime($dbTime, new DateTimeZone("UTC"));
        $ist = $utc->setTimezone(new DateTimeZone("Asia/Kolkata"));
        return $ist->format("H:i");
    }
    
    public static function utcToIst($utcTime) {
        if (!$utcTime) return null;
        
        // Convert UTC datetime to IST datetime
        $utc = new DateTime($utcTime, new DateTimeZone("UTC"));
        $ist = $utc->setTimezone(new DateTimeZone("Asia/Kolkata"));
        return $ist->format("Y-m-d H:i:s");
    }
    
    public static function nowUtc() {
        return gmdate("Y-m-d H:i:s");
    }
    
    public static function getCurrentDate() {
        // Get current IST date
        $utc = new DateTime("now", new DateTimeZone("UTC"));
        $ist = $utc->setTimezone(new DateTimeZone("Asia/Kolkata"));
        return $ist->format("Y-m-d");
    }
}
?>';
        
        file_put_contents(__DIR__ . '/app/helpers/TimezoneHelper.php', $fixedHelper);
        echo "✓ Updated TimezoneHelper with proper DateTime handling<br>";
        
        // Test again
        require_once __DIR__ . '/app/helpers/TimezoneHelper.php';
        $newConvertedTime = TimezoneHelper::utcToIst($utcNow);
        $newDisplayTime = TimezoneHelper::displayTime($utcNow);
        echo "New conversion: " . $newConvertedTime . "<br>";
        echo "New display time: " . $newDisplayTime . "<br>";
    }
    
    echo "<h3>Database Query Test</h3>";
    
    // Test a sample query with timezone conversion
    $stmt = $db->query("SELECT check_in FROM attendance WHERE check_in IS NOT NULL LIMIT 1");
    $sample = $stmt->fetch();
    
    if ($sample) {
        echo "Sample DB time: " . $sample['check_in'] . "<br>";
        $converted = TimezoneHelper::utcToIst($sample['check_in']);
        echo "Converted to IST: " . $converted . "<br>";
        $display = TimezoneHelper::displayTime($sample['check_in']);
        echo "Display format: " . $display . "<br>";
    }
    
    echo "<h3>✓ Timezone fix completed!</h3>";
    echo "The system should now properly convert UTC database times to IST display times.<br>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
    echo "Stack trace: " . $e->getTraceAsString();
}
?>